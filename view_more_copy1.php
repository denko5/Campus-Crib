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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone_number'])) {
    $phone_number = trim($_POST['phone_number']); // Retrieve phone number

    if (!empty($phone_number)) {
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

        $stmt->close();
    } else {
        echo "<script>alert('Phone number cannot be empty.');</script>";
    }
}

// Fetch messages and replies for the student
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

// Fetch unread notifications for the student
$sql_notifications = "SELECT p.transaction_code, p.status, p.notification_status, p.paid_at, l.listing_name
                      FROM payment p 
                      JOIN listing l ON p.listing_id = l.listing_id
                      WHERE p.student_id = ? AND p.notification_status = 'Unread'
                      ORDER BY p.paid_at DESC";
$stmt = $conn->prepare($sql_notifications);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_notifications = $stmt->get_result();
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
            <p><strong>Vacant Rooms:</strong> <?php echo htmlspecialchars($row['vacant_rooms']); ?></p>
            <p><strong>Amenities:</strong> <?php echo htmlspecialchars($row['description']); ?></p>
        </div>

        <div class="contact-landlord">
    <h3>Contact Landlord</h3>
    <form method="post">
        <!-- Hidden input to identify message submission -->
        <input type="hidden" name="form_action" value="send_message">
        <textarea name="message" placeholder="Enter your message..." required></textarea>
        <button type="submit">Send Message</button>
    </form>
</div>


    </div>

    <!-- View Replies -->
    <div class="messages-container">
            <h3>Your Messages</h3>
            <table>
                <tr>
                    <th>Your Message</th>
                    <th>Sent At</th>
                    <th>Landlord's Reply</th>
                    <th>Reply At</th>
                </tr>
                <?php if ($result_messages->num_rows > 0) { ?>
                    <?php while ($message_row = $result_messages->fetch_assoc()) { ?>
                        <tr>
                            <td><?php echo htmlspecialchars($message_row['message']); ?></td>
                            <td><?php echo $message_row['sent_at']; ?></td>
                            <td><?php echo htmlspecialchars($message_row['reply']) ?: "No reply yet."; ?></td>
                            <td><?php echo $message_row['reply_at'] ?: "N/A"; ?></td>
                        </tr>
                    <?php } ?>
                <?php } else { ?>
                    <tr>
                        <td colspan="4">No messages sent yet.</td>
                    </tr>
                <?php } ?>
            </table>
        </div>

        <!-- Payment Confirmation -->
        <div class="payment">
            <h3>Payment Confirmation</h3>
            <form method="post">
                <input type="text" name="phone_number" placeholder="Enter phone number" required>
                <button type="submit">Confirm Payment</button>
            </form>
        </div>

        <div class="notifications">
            <h3>Notifications</h3>
            <?php if ($result_notifications->num_rows > 0) { ?>
                <ul id="notifications-list">
                    <?php 
                    $counter = 0; // Counter to track visible notifications
                    while ($notification = $result_notifications->fetch_assoc()) { 
                        $counter++;
                        ?>
                        <li class="notification-item <?php echo $counter > 5 ? 'hidden' : ''; ?>">
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
                </ul>
                <?php if ($result_notifications->num_rows > 5) { ?>
                    <button id="view-more" onclick="toggleNotifications(true)">View More ▼</button>
                    <button id="show-less" onclick="toggleNotifications(false)" style="display: none;">Show Less ▲</button>
                <?php } ?>
            <?php } else { ?>
                <p>No new notifications.</p>
            <?php } ?>
        </div>



</div>

<script>
    function toggleNotifications(showMore) {
        const items = document.querySelectorAll('.notification-item');
        const viewMoreBtn = document.getElementById('view-more');
        const showLessBtn = document.getElementById('show-less');
        
        if (showMore) {
            items.forEach(item => item.style.display = 'flex'); // Show all notifications
            viewMoreBtn.style.display = 'none'; // Hide "View More" button
            showLessBtn.style.display = 'block'; // Show "Show Less" button
        } else {
            items.forEach((item, index) => {
                item.style.display = index < 5 ? 'flex' : 'none'; // Show only first 5 notifications
            });
            viewMoreBtn.style.display = 'block'; // Show "View More" button
            showLessBtn.style.display = 'none'; // Hide "Show Less" button
        }
    }
</script>



<footer>
    <p>&copy; 2025 Student Housing Finder | <a href="#">View More</a></p>
</footer>

</body>
</html>

<?php
$conn->close();
?>
