
<?php
session_start();
include "housefydb.php"; // Connects to `housefy` database

// Ensure landlord is logged in
if (!isset($_SESSION['email'])) {
    header("Location: landlord_login.php"); // Redirect to login page
    exit();
}

$landlord_email = $_SESSION['email']; // Get landlord email from session

// Fetch landlord's houses from `listing` table
$stmt = $conn->prepare("SELECT * FROM listing WHERE email = ?");
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$houses = $stmt->get_result();

// Fetch messages sent by students
$msg_stmt = $conn->prepare("SELECT message, email FROM message WHERE email = ?");
$msg_stmt->bind_param("s", $landlord_email);
$msg_stmt->execute();
$messages = $msg_stmt->get_result();
?>

<!DOCTYPE html>

<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Landlord Dashboard</title>
    <style>
        /* Global Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background-color: #f4f4f4;
            color: #333;
        }

        /* Header */
        header {
            background-color: #005b96;
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .menu {
            display: flex;
            gap: 15px;
        }

        .menu a {
            color: white;
            text-decoration: none;
            font-size: 16px;
        }

        /* Mobile Menu */
        .hamburger {
            display: none;
            cursor: pointer;
        }

        .hamburger div {
            width: 25px;
            height: 3px;
            background: white;
            margin: 5px;
        }

        .mobile-menu {
            display: none;
            flex-direction: column;
            position: absolute;
            background: #005b96;
            width: 100%;

            top: 50px;
            left: 0;
            padding: 10px;
        }

        .mobile-menu a {
            color: white;
            text-decoration: none;
            padding: 10px;
            display: block;
        }

        /* Main Content */
        .container {
            max-width: 1000px;
            margin: 20px auto;
            padding: 20px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .house-list, .message-list {
            margin-top: 20px;
        }

        .house, .message {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 8px;

            margin-bottom: 10px;
        }

        .house img {
            width: 100%;
            max-width: 400px;
            border-radius: 8px;
        }

        .edit-button {
            display: inline-block;
            padding: 8px 12px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 10px;
        }

        .reply-box {
            margin-top: 10px;
        }

        .reply-box textarea {
            width: 100%;
            padding: 8px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        .reply-box button {
            margin-top: 5px;
            padding: 8px 12px;
            border: none;
            background: #005b96;
            color: white;
            cursor: pointer;
            border-radius: 5px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .menu {
                display: none;
            }

            .hamburger {
                display: block;
            }

            .mobile-menu {
                display: none;
                position: absolute;
                top: 60px;
                left: 0;
                width: 100%;
                background: #005b96;
                flex-direction: column;
                align-items: center;
                padding: 10px 0;

            }

            .mobile-menu.active {
                display: flex;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>Landlord Dashboard</h1>
    <nav class="menu">
        <a href="post_vacancy.php">Post Vacancy</a>
        <a href="delete_vacancy.php">Delete Vacancy</a>
        <a href="logout.php">Logout</a>
    </nav>
    <div class="hamburger" onclick="toggleMenu()">
        <div></div>
        <div></div>
        <div></div>
    </div>
</header>

<nav class="mobile-menu" id="mobileMenu">
    <a href="post_vacancy.php">Post Vacancy</a>
    <a href="delete_vacancy.php">Delete Vacancy</a>
    <a href="logout.php">Logout</a>
</nav>

<div class="container">
    <h2>Your Posted Houses</h2>
    <div class="house-list">
        <?php while ($house = $houses->fetch_assoc()) { ?>
            <div class="house">
                <img src="<?php echo $house['image_path']; ?>" alt="House Photo">
                <p><strong>Listing Name:</strong> <?php echo $house['listing_name']; ?></p>
                <p><strong>Location:</strong> <?php echo $house['location']; ?></p>
                <p><strong>Price:</strong> KES <?php echo number_format($house['price']); ?></p>
                <a href="edit_listing.php?id=<?php echo $house['id']; ?>" class="edit-button">Edit</a>
            </div>
        <?php } ?>
    </div>

    <h2>Messages from Students</h2>
    <div class="message-list">
        <?php while ($msg = $messages->fetch_assoc()) { ?>
            <div class="message">
                <p><strong>Student Email:</strong> <?php echo $msg['email']; ?></p>
                <p><strong>Message:</strong> <?php echo $msg['message']; ?></p>
                
                <!-- Reply Box -->
                <div class="reply-box">

                    <form method="POST" action="reply_message.php">
                        <textarea name="reply" placeholder="Type your reply..."></textarea>
                        <input type="hidden" name="student_email" value="<?php echo $msg['email']; ?>">
                        <button type="submit">Send Reply</button>
                    </form>
                </div>
            </div>
        <?php } ?>
    </div>
</div>

<script>
    function toggleMenu() {
        const menu = document.getElementById("mobileMenu");
        menu.classList.toggle("active");
    }
</script>
<!--  

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
            // Approve payment and reduce vacant rooms
            $update_payment = "UPDATE payment SET status = 'Approved', notification_status = 'Unread' WHERE payment_id = ?";
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
                          p.phone_number, p.amount, p.paid_at, p.status, l.listing_name
                   FROM payment p 
                   JOIN listing l ON p.listing_id = l.listing_id
                   JOIN student s ON p.student_id = s.student_id
                   WHERE l.email = ?
                   ORDER BY FIELD(p.status, 'Pending') DESC, p.paid_at DESC"; // Pending payments at the top, ordered by latest
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

// Handle Add or Reduce Rooms
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['listing_id'], $_POST['modify_rooms'], $_POST['action'])) {
    $listing_id = intval($_POST['listing_id']);
    $modify_rooms = intval($_POST['modify_rooms']);
    $action = $_POST['action'];

    if ($modify_rooms > 0) {
        if ($action === 'add') {
            // Add rooms to the listing
            $query_add_rooms = "UPDATE listing SET vacant_rooms = vacant_rooms + ? WHERE listing_id = ?";
            $stmt = $conn->prepare($query_add_rooms);
            $stmt->bind_param("ii", $modify_rooms, $listing_id);
            if ($stmt->execute()) {
                echo "<script>alert('Successfully added $modify_rooms room(s).');</script>";
            } else {
                echo "<script>alert('Error adding rooms.');</script>";
            }
        } elseif ($action === 'reduce') {
            // Reduce rooms, ensuring the number of vacant rooms doesn't drop below zero
            $query_reduce_rooms = "UPDATE listing SET vacant_rooms = GREATEST(vacant_rooms - ?, 0) WHERE listing_id = ?";
            $stmt = $conn->prepare($query_reduce_rooms);
            $stmt->bind_param("ii", $modify_rooms, $listing_id);
            if ($stmt->execute()) {
                echo "<script>alert('Successfully reduced $modify_rooms room(s).');</script>";
            } else {
                echo "<script>alert('Error reducing rooms.');</script>";
            }
        }
        $stmt->close();

        // Redirect to avoid form resubmission on refresh
        header("Location: dashboard2.php");
        exit();
    } else {
        echo "<script>alert('Enter a valid number of rooms to modify.');</script>";
    }
}

?>



-->
</body>
</html>


