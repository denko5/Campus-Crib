<?php
session_start();
include("housefydb.php"); // Ensure database connection is included

// Check if landlord is logged in
if (!isset($_SESSION['email'])) {
    die("Error: Landlord email is not set in session.");
}

$landlord_email = $_SESSION['email'];

// Fetch landlord details
$query_landlord = "SELECT * FROM landlord WHERE email = ?";
$stmt = $conn->prepare($query_landlord);
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$result_landlord = $stmt->get_result();
$landlord = $result_landlord->fetch_assoc();
$stmt->close();

if (!$landlord) {
    die("Error: No landlord found with this email.");
}

// Handle Approve or Cancel Payment using the Post-Redirect-Get (PRG) pattern
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['payment_id'])) {
    $payment_id = intval($_POST['payment_id']);
    $action = $_POST['action'];

    // Fetch payment details
    $query_payment = "SELECT * FROM payment WHERE payment_id = ?";
    $stmt = $conn->prepare($query_payment);
    $stmt->bind_param("i", $payment_id);
    $stmt->execute();
    $result_payment = $stmt->get_result();
    $payment = $result_payment->fetch_assoc();
    $stmt->close();

    if ($payment) {
        $listing_id = $payment['listing_id'];

        // Validate payment status to avoid duplicate processing
        if ($payment['status'] !== 'Pending' && $action === 'approve') {
            echo "<script>alert('This payment request has already been processed.');</script>";
            header("Location: dashboard2.php");
            exit();
        }

        if ($action === 'approve') {
            // Approve payment and start timer
            $update_payment = "UPDATE payment SET status = 'Approved', notification_status = 'Unread', paid_at = NOW() WHERE payment_id = ?";
            $stmt = $conn->prepare($update_payment);
            $stmt->bind_param("i", $payment_id);
            if ($stmt->execute()) {
                $reduce_vacancy = "UPDATE listing SET vacant_rooms = GREATEST(vacant_rooms - 1, 0) WHERE listing_id = ?";
                $stmt = $conn->prepare($reduce_vacancy);
                $stmt->bind_param("i", $listing_id);
                $stmt->execute();
                $stmt->close();
            }
        } elseif ($action === 'cancel') {
            // Cancel payment and increment vacant rooms if the status is 'Approved'
            if ($payment['status'] === 'Approved') {
                $increase_vacancy = "UPDATE listing SET vacant_rooms = vacant_rooms + 1 WHERE listing_id = ?";
                $stmt = $conn->prepare($increase_vacancy);
                $stmt->bind_param("i", $listing_id);
                $stmt->execute();
                $stmt->close();
            }

            // Update payment status to 'Cancelled'
            $update_payment = "UPDATE payment SET status = 'Cancelled', notification_status = 'Unread' WHERE payment_id = ?";
            $stmt = $conn->prepare($update_payment);
            $stmt->bind_param("i", $payment_id);
            $stmt->execute();
            $stmt->close();
        }

        // Redirect to dashboard2.php to prevent resubmission on refresh
        header("Location: dashboard2.php");
        exit();
    }
}

// Fetch Total Listings for the Landlord
$query_listings = "SELECT * FROM listing WHERE email = ?";
$stmt = $conn->prepare($query_listings);
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$result_listings = $stmt->get_result();
$total_listings = $result_listings->num_rows;
$stmt->close();


// Fetch Payments for the Landlord
$query_payments = "SELECT p.payment_id, p.transaction_code, s.first_name AS student_first_name, s.last_name AS student_last_name, 
                          p.phone_number, p.amount, p.paid_at, p.status, l.listing_name,
                          CASE 
                              WHEN p.status = 'Approved' THEN 
                                  TIMESTAMPDIFF(DAY, p.paid_at, NOW()) AS days_passed,
                                  TIMESTAMPDIFF(HOUR, p.paid_at, NOW()) % 24 AS hours_passed,
                                  TIMESTAMPDIFF(MINUTE, p.paid_at, NOW()) % 60 AS minutes_passed,
                                  TIMESTAMPDIFF(SECOND, p.paid_at, NOW()) % 60 AS seconds_passed,
                                  TIMESTAMPDIFF(SECOND, NOW(), DATE_ADD(p.paid_at, INTERVAL 30 DAY)) AS time_remaining
                              ELSE NULL
                          END AS timer
                   FROM payment p 
                   JOIN listing l ON p.listing_id = l.listing_id
                   JOIN student s ON p.student_id = s.student_id
                   WHERE l.email = ?
                   ORDER BY FIELD(p.status, 'Pending') DESC, p.paid_at DESC";
$stmt = $conn->prepare($query_payments);
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$result_payments = $stmt->get_result();

// Handle Reply Submission using Post-Redirect-Get (PRG)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message_id'], $_POST['reply'])) {
    $message_id = intval($_POST['message_id']);
    $reply = trim($_POST['reply']);

    if (!empty($reply)) {
        $update_reply = "UPDATE message SET reply = ?, reply_at = NOW() WHERE message_id = ?";
        $stmt = $conn->prepare($update_reply);
        $stmt->bind_param("si", $reply, $message_id);
        if (!$stmt->execute()) {
            echo "<script>alert('Error saving reply: " . htmlspecialchars($stmt->error) . "');</script>";
        }
        $stmt->close();
    } else {
        echo "<script>alert('Reply cannot be empty.');</script>";
    }

    // Redirect to dashboard2.php to prevent resubmission
    header("Location: dashboard2.php");
    exit();
}

// Fetch Messages and Replies for the Landlord
$query_messages = "SELECT m.message_id, m.message, m.sent_at, m.reply, m.reply_at, s.first_name, s.last_name, s.student_id 
                   FROM message m 
                   JOIN student s ON m.student_id = s.student_id  
                   WHERE m.receiver_email = ?";
$stmt = $conn->prepare($query_messages);
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$result_messages = $stmt->get_result();
$stmt->close();

// Fetch currently vacant rooms for all listings of the landlord
$query_vacant_rooms = "SELECT SUM(vacant_rooms) AS total_vacant_rooms FROM listing WHERE email = ?";
$stmt_vacant = $conn->prepare($query_vacant_rooms);
$stmt_vacant->bind_param("s", $landlord_email);
$stmt_vacant->execute();
$result_vacant_rooms = $stmt_vacant->get_result();
$vacant_data = $result_vacant_rooms->fetch_assoc();
$total_vacant_rooms = $vacant_data['total_vacant_rooms'] ?: 0; // Total vacant rooms (rooms currently available)

// Calculate booked rooms and total rooms dynamically
$total_rooms = $total_vacant_rooms + ($booked_rooms = $result_payments->num_rows); // Total rooms = vacant + booked

// Calculate the percentage of booked rooms
$percentage_booked = ($total_rooms > 0) ? round(($booked_rooms / $total_rooms) * 100, 2) : 0;

// Close statements
$stmt_vacant->close();
?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard</title>
    <link rel="stylesheet" href="dashboard.css">
</head>
<body>
<div class="navbar">
    <h2>Welcome, <?php echo htmlspecialchars($landlord['first_name'] . " " . $landlord['last_name']); ?></h2>
    <div class="hamburger-menu" onclick="toggleMenu()">☰</div>
    <ul class="nav-links">
        <li><a href="post_vacancy.html">Post Vacancy</a></li>
        <li><a href="remove_vacancy.html">Remove Vacancy</a></li>
        <li><a href="landlord_logout.php">Logout</a></li>
        <li><button id="printButton" onclick="window.print()">Print</button></li>
        <li><a href="export_payments.php">Export Payments</a></li>
    </ul>
</div>

<div class="dashboard-container">
    <h1>Dashboard Overview</h1>
    <div class="card">
        <h3>Total Listings</h3>
        <p><?php echo $total_listings; ?></p>
    </div>
    
    <div class="dashboard-analysis">
        <h2>Room Booking Analysis</h2>
        <table>
            <tr>
                <th>Metric</th>
                <th>Value</th>
            </tr>
            <tr>
                <td>Total Available Rooms</td>
                <td><?php echo $total_rooms; ?></td>
            </tr>
            <tr>
                <td>Currently Vacant Rooms</td>
                <td><?php echo $total_vacant_rooms; ?></td>
            </tr>
            <tr>
                <td>Booked Rooms</td>
                <td><?php echo $booked_rooms; ?></td>
            </tr>
            <tr>
                <td>Percentage of Rooms Booked</td>
                <td><?php echo $percentage_booked; ?>%</td>
            </tr>
        </table>
    </div>

    <h2>Your Listings</h2>
    <table>
        <tr>
            <th>Listing Name</th>
            <th>Price</th>
            <th>Vacant Rooms</th>
            <th>Distance</th>
            <th>Image</th>
        </tr>
        <?php while ($row = $result_listings->fetch_assoc()) { ?>
            <tr>
                <td><?php echo htmlspecialchars($row['listing_name']); ?></td>
                <td>Ksh <?php echo number_format($row['price'], 2); ?></td>
                <td><?php echo $row['vacant_rooms']; ?></td>
                <td><?php echo $row['distance']; ?> km</td>
                <td><img src="<?php echo htmlspecialchars($row['image_path']); ?>" alt="Listing Image" width="100"></td>
            </tr>
        <?php } ?>
    </table>

    <h2>Payment Confirmations</h2>
    <table>
        <tr>
            <th>Transaction Code</th>
            <th>Listing Name</th>
            <th>Student</th>
            <th>Phone Number</th>
            <th>Amount (KES)</th>
            <th>Paid At</th>
            <th>Timer</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
        <?php if ($result_payments->num_rows > 0) { ?>
            <?php while ($row = $result_payments->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['transaction_code']); ?></td>
                    <td><?php echo htmlspecialchars($row['listing_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['student_first_name'] . " " . $row['student_last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['phone_number']); ?></td>
                    <td>Ksh <?php echo number_format($row['amount'], 2); ?></td>
                    <td><?php echo $row['paid_at']; ?></td>
                    <td>
                        <?php
                            $days_left = floor($row['time_remaining'] / (24 * 60 * 60));
                            $hours_left = floor(($row['time_remaining'] % (24 * 60 * 60)) / (60 * 60));
                            $minutes_left = floor(($row['time_remaining'] % (60 * 60)) / 60);
                            $seconds_left = $row['time_remaining'] % 60;
                            echo "{$days_left} Days, {$hours_left} Hours, {$minutes_left} Minutes, {$seconds_left} Seconds";
                        ?>
                    </td>
                    <td><?php echo ucfirst($row['status']); ?></td>
                    <td>
                        <?php if ($row['status'] === 'Pending') { ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                <button type="submit" name="action" value="approve">Approve</button>
                            </form>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                <button type="submit" name="action" value="cancel">Cancel</button>
                            </form>
                        <?php } elseif ($row['status'] === 'Approved') { ?>
                            ✅ Approved
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="payment_id" value="<?php echo $row['payment_id']; ?>">
                                <button type="submit" name="action" value="cancel">Cancel</button>
                            </form>
                        <?php } elseif ($row['status'] === 'Cancelled') { ?>
                            ❌ Canceled
                        <?php } ?>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="9">No payments received yet.</td>
            </tr>
        <?php } ?>
    </table>

    <h2>Messages</h2>
    <table>
        <tr>
            <th>Student</th>
            <th>Message</th>
            <th>Sent At</th>
            <th>Reply</th>
            <th>Action</th>
        </tr>
        <?php if ($result_messages->num_rows > 0) { ?>
            <?php while ($row = $result_messages->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['first_name'] . " " . $row['last_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['message']); ?></td>
                    <td><?php echo $row['sent_at']; ?></td>
                    <td><?php echo !empty($row['reply']) ? htmlspecialchars($row['reply']) : "No reply yet"; ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="message_id" value="<?php echo $row['message_id']; ?>">
                            <input type="text" name="reply" placeholder="Type reply..." required>
                            <button type="submit">Send</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
        <?php } else { ?>
            <tr>
                <td colspan="5">No messages yet.</td>
            </tr>
        <?php } ?>
    </table>
</div>

<script>
    function toggleMenu() {
        const navLinks = document.querySelector('.nav-links');
        navLinks.classList.toggle('active');
    }
</script>
</body>
</html>
