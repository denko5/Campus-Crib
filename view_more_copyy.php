<?php
session_start(); // Start the session

// Database connection
$host = "localhost";
$user = "root";
$password = "password";
$database = "housefy";

$conn = new mysqli($host, $user, $password, $database);

// Check database connection
if ($conn->connect_error) {
    die("Connection failed. Please try again later."); // User-friendly message
}

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    die("Error: User is not logged in.");
}

$student_email = $_SESSION['email']; // Get student's email from session

// Get student ID and details
$sql = "SELECT student_id, first_name, last_name FROM student WHERE email = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $student_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    die("Error: Student not found. Please try again.");
}

$student = $result->fetch_assoc();
$student_id = $student['student_id'];

// Close statement
$stmt->close();

// Get the listing ID from the URL
if (isset($_GET['id'])) {
    $listing_id = intval($_GET['id']);

    // Fetch the listing details
    $sql_listing = "SELECT * FROM listing WHERE listing_id = ?";
    $stmt = $conn->prepare($sql_listing);
    $stmt->bind_param("i", $listing_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
    } else {
        die("<h2>Error: Listing details not found.</h2>");
    }

    $stmt->close();
} else {
    die("<h2>Error: No house selected.</h2>");
}

// Handle Payment Confirmation with PRG Pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number']) && isset($_POST['form_action']) && $_POST['form_action'] === 'confirm_payment') {
    $phone_number = trim($_POST['phone_number']); // Retrieve phone number

    if (!empty($phone_number)) {
        // Check if there are vacant rooms before proceeding
        $sql_check_rooms = "SELECT vacant_rooms FROM listing WHERE listing_id = ?";
        $stmt = $conn->prepare($sql_check_rooms);
        $stmt->bind_param("i", $listing_id);
        $stmt->execute();
        $result_rooms = $stmt->get_result();
        $room_data = $result_rooms->fetch_assoc();

        if ($room_data['vacant_rooms'] <= 0) {
            echo "<script>alert('No vacant rooms available for this listing.');</script>";
        } else {
            // Fetch listing price
            $sql_price = "SELECT price FROM listing WHERE listing_id = ?";
            $stmt = $conn->prepare($sql_price);
            $stmt->bind_param("i", $listing_id);
            $stmt->execute();
            $result_price = $stmt->get_result();

            if ($result_price->num_rows > 0) {
                $row_price = $result_price->fetch_assoc();
                $amount = $row_price['price'];

                $transaction_code = strtoupper(bin2hex(random_bytes(5))); // Generate random transaction code

                // Insert payment with status 'Pending'
                $sql_payment = "INSERT INTO payment (student_id, listing_id, transaction_code, phone_number, amount, status, paid_at) 
                                VALUES (?, ?, ?, ?, ?, 'Pending', NOW())";
                $stmt = $conn->prepare($sql_payment);
                $stmt->bind_param("iissd", $student_id, $listing_id, $transaction_code, $phone_number, $amount);

                if ($stmt->execute()) {
                    // Redirect to prevent resubmission
                    header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $listing_id);
                    exit(); // Stop further script execution
                } else {
                    echo "<script>alert('Error saving payment: " . htmlspecialchars($stmt->error) . "');</script>";
                }
            } else {
                echo "<script>alert('Error: Listing price not found.');</script>";
            }
        }

        $stmt->close();
    } else {
        echo "<script>alert('Phone number cannot be empty.');</script>";
    }
}

// Fetch Messages and Replies for the Student
$sql_messages = "SELECT m.message, m.sent_at, m.reply, m.reply_at 
                 FROM message m 
                 WHERE m.student_id = ? AND m.receiver_email = ?";
$stmt = $conn->prepare($sql_messages);
$stmt->bind_param("is", $student_id, $row['email']);
$stmt->execute();
$result_messages = $stmt->get_result();

// Handle Message Submission with PRG Pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'send_message') {
    $message = trim($_POST['message']); // Sanitize the message input
    $receiver_email = $row['email']; // Landlord's email from the listing

    if (!empty($message)) {
        $sql_message = "INSERT INTO message (student_id, email, receiver_email, message, sent_at) 
                        VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql_message);
        $stmt->bind_param("isss", $student_id, $student_email, $receiver_email, $message);

        if ($stmt->execute()) {
            // Redirect to avoid duplicate submissions
            header("Location: " . $_SERVER['PHP_SELF'] . "?id=" . $listing_id);
            exit(); // Stop further script execution
        } else {
            echo "<script>alert('Error sending message: " . htmlspecialchars($stmt->error) . "');</script>";
        }

        $stmt->close();
    } else {
        echo "<script>alert('Error: Message cannot be empty.');</script>";
    }
}

// Fetch Notifications for the Student
$sql_notifications = "SELECT p.transaction_code, p.status, p.notification_status, p.paid_at, l.listing_name
                      FROM payment p 
                      JOIN listing l ON p.listing_id = l.listing_id
                      WHERE p.student_id = ? AND p.notification_status = 'Unread'
                      ORDER BY p.paid_at DESC";
$stmt = $conn->prepare($sql_notifications);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_notifications = $stmt->get_result();

// Fetch Payments for the Timer
$query_timers = "SELECT p.payment_id, p.transaction_code, p.approved_at, 
                        TIMESTAMPDIFF(DAY, p.approved_at, NOW()) AS days_passed,
                        TIMESTAMPDIFF(HOUR, p.approved_at, NOW()) % 24 AS hours_passed,
                        TIMESTAMPDIFF(MINUTE, p.approved_at, NOW()) % 60 AS minutes_passed,
                        TIMESTAMPDIFF(SECOND, p.approved_at, NOW()) % 60 AS seconds_passed,
                        TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(p.approved_at, INTERVAL 30 DAY)) AS time_remaining
                 FROM payment p
                 WHERE p.status = 'Approved'";
$stmt = $conn->prepare($query_timers);
$stmt->execute();
$result_timers = $stmt->get_result();

while ($row = $result_timers->fetch_assoc()) {
    $days_left = floor($row['time_remaining'] / (24 * 60 * 60));
    $hours_left = floor(($row['time_remaining'] % (24 * 60 * 60)) / (60 * 60));
    $minutes_left = floor(($row['time_remaining'] % (60 * 60)) / 60);
    $seconds_left = $row['time_remaining'] % 60;

    echo "Transaction: " . htmlspecialchars($row['transaction_code']) . "<br>";
    echo "Approved At: " . htmlspecialchars($row['approved_at']) . "<br>";
    echo "Time Remaining: {$days_left} Days, {$hours_left} Hours, {$minutes_left} Minutes, {$seconds_left} Seconds<br>";
    echo "<hr>";
}

?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campus Crib</title>
    <link rel="stylesheet" href="view-more.css">
</head>
<body>

<header>
    <h1>Campus Crib</h1>
    <nav class="menu">
        <a href="logout.php">Logout</a>
        <a href="print_receipt.php" class="print-receipt">Print Receipt</a>
    </nav>
</header>

<div class="container">

    <div class="house-container">
        <div class="house-details">
            <img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="House Photo" class="house-image">
            <p><strong>Hostel Name:</strong> <?php echo htmlspecialchars($row['listing_name']); ?></p>
            <p><strong>Rent Price (KES):</strong> <?php echo number_format($row['price']); ?></p>
            <p><strong>Distance to campus:</strong> <?php echo htmlspecialchars($row['distance']); ?> km</p>
            <p><strong>Landlord Contact:</strong> <?php echo htmlspecialchars($row['landlord_contact']); ?></p>
            <p><strong>Vacant Rooms:</strong> <span id="vacant-rooms-count"><?php echo htmlspecialchars($row['vacant_rooms']); ?></span></p>
            <p><strong>Amenities:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
        </div>

        <div class="contact-landlord">
            <h3>Contact Landlord</h3>
            <form method="post">
                <input type="hidden" name="form_action" value="send_message">
                <textarea name="message" placeholder="Enter your message..." required></textarea>
                <button type="submit">Send Message</button>
            </form>
        </div>
    </div>

    <!-- Messages Section -->
    <div class="messages-container">
        <h3>Your Messages</h3>
        <ul id="messages-list">
            <?php if ($result_messages->num_rows > 0) { ?>
                <?php while ($message_row = $result_messages->fetch_assoc()) { ?>
                    <li>
                        <p><?php echo htmlspecialchars($message_row['message']); ?></p>
                        <p><small>Sent: <?php echo $message_row['sent_at']; ?></small></p>
                        <p><strong>Reply:</strong> <?php echo htmlspecialchars($message_row['reply']) ?: "No reply yet."; ?></p>
                        <p><small>Reply At: <?php echo $message_row['reply_at'] ?: "N/A"; ?></small></p>
                    </li>
                <?php } ?>
            <?php } else { ?>
                <li>No messages sent yet.</li>
            <?php } ?>
        </ul>
    </div>

    <!-- Payment Confirmation Section -->
    <div class="payment">
        <h3>Payment Confirmation</h3>
        <form method="post">
            <input type="hidden" name="form_action" value="confirm_payment">
            <input type="text" name="phone_number" placeholder="Enter phone number" required>
            <button type="submit" id="confirm-payment-btn">Confirm Payment</button>
        </form>
    </div>

    <!-- Notifications Section -->
    <div class="notifications">
        <h3>Notifications</h3>
        <ul id="notifications-list">
            <?php if ($result_notifications->num_rows > 0) { ?>
                <?php while ($notification = $result_notifications->fetch_assoc()) { ?>
                    <li>
                        <?php if ($notification['status'] === 'Approved') { ?>
                            ✅ Payment for <strong><?php echo htmlspecialchars($notification['listing_name']); ?></strong> 
                            was <strong>Approved</strong> on <?php echo $notification['paid_at']; ?>.
                        <?php } elseif ($notification['status'] === 'Cancelled') { ?>
                            ❌ Payment for <strong><?php echo htmlspecialchars($notification['listing_name']); ?></strong> 
                            was <strong>Cancelled</strong> on <?php echo $notification['paid_at']; ?>.
                        <?php } else { ?>
                            Payment for <strong><?php echo htmlspecialchars($notification['listing_name']); ?></strong> 
                            is <strong><?php echo htmlspecialchars($notification['status']); ?></strong> 
                            on <?php echo $notification['paid_at']; ?>.
                        <?php } ?>
                    </li>
                <?php } ?>
            <?php } else { ?>
                <li>No new notifications.</li>
            <?php } ?>
        </ul>
    </div>
</div>

<!-- JavaScript for Automatic Updates -->
<script>
    // Update Notifications
    function fetchNotifications() {
        fetch('fetch_notifications.php?student_id=<?php echo $student_id; ?>')
            .then(response => response.json())
            .then(data => {
                const notificationsList = document.getElementById('notifications-list');
                notificationsList.innerHTML = '';
                data.notifications.forEach(notification => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        ${notification.status === 'Approved' ? '✅' : '❌'} 
                        Payment for <strong>${notification.listing_name}</strong> 
                        was <strong>${notification.status}</strong> on ${notification.paid_at}.
                    `;
                    notificationsList.appendChild(li);
                });
            })
            .catch(error => console.error('Error fetching notifications:', error));
    }

    // Update Messages
    function fetchMessages() {
        fetch('fetch_messages.php?student_id=<?php echo $student_id; ?>&receiver_email=<?php echo $row['email']; ?>')
            .then(response => response.json())
            .then(data => {
                const messagesList = document.getElementById('messages-list');
                messagesList.innerHTML = '';
                data.messages.forEach(message => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <p>${message.message}</p>
                        <small>Sent: ${message.sent_at}</small>
                        ${message.reply ? `<p><strong>Reply:</strong> ${message.reply}</p>` : '<p>No reply yet.</p>'}
                        <small>Reply At: ${message.reply_at || 'N/A'}</small>
                    `;
                    messagesList.appendChild(li);
                });
            })
            .catch(error => console.error('Error fetching messages:', error));
    }

    // Update Vacant Rooms
    function updateVacantRooms() {
        fetch('fetch_vacant_rooms.php?listing_id=<?php echo $listing_id; ?>')
            .then(response => response.json())
            .then(data => {
                document.getElementById('vacant-rooms-count').innerText = data.vacant_rooms;
                if (data.vacant_rooms === 0) {
                    document.getElementById('confirm-payment-btn').disabled = true;
                }
            })
            .catch(error => console.error('Error fetching vacant rooms:', error));
    }

    // Periodically Fetch Updates
    setInterval(fetchNotifications, 5000); // Fetch notifications every 5 seconds
    setInterval(fetchMessages, 5000); // Fetch messages every 5 seconds
    setInterval(updateVacantRooms, 5000); // Update vacant rooms every 5 seconds
</script>

<footer>
    <p>&copy; 2025 Student Housing Finder | <a href="#">View More</a></p>
</footer>
</body>
</html>
