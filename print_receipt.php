<?php
session_start();
include("housefydb.php"); // Include database connection

// Ensure user is logged in
if (!isset($_SESSION['email'])) {
    die("Error: User not logged in.");
}

$student_email = $_SESSION['email']; // Get student's email

// Fetch student's details
$sql_student = "SELECT student_id, first_name, last_name FROM student WHERE email = ?";
$stmt = $conn->prepare($sql_student);
$stmt->bind_param("s", $student_email);
$stmt->execute();
$result_student = $stmt->get_result();

if ($result_student->num_rows === 0) {
    die("Error: Student not found.");
}

$student = $result_student->fetch_assoc();
$student_id = $student['student_id'];

// Fetch payment details for the approved request
$sql_payment = "SELECT p.transaction_code, p.amount, p.paid_at, 
                       l.first_name AS landlord_first_name, l.last_name AS landlord_last_name, 
                       lst.listing_name
                FROM payment p
                JOIN listing lst ON p.listing_id = lst.listing_id
                JOIN landlord l ON lst.email = l.email
                WHERE p.student_id = ? AND p.status = 'Approved'
                ORDER BY p.paid_at DESC LIMIT 1"; // Get the latest approved payment
$stmt = $conn->prepare($sql_payment);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$result_payment = $stmt->get_result();

if ($result_payment->num_rows === 0) {
    die("<h2>No approved payment found. You can only print a receipt for approved payments.</h2>");
}

$payment = $result_payment->fetch_assoc();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RECEIPT</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        .receipt {
            border: 5px solid #ddd;
            padding: 20px;
            max-width: 600px;
            margin: 0 auto;
            background-color:rgb(185, 197, 203); /* Subtle background color for readability */
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Light shadow effect */
        }
        .receipt h1 {
            text-decoration: underline;
            text-align: center;
            color:rgb(9, 49, 100);
        }
        .receipt p {
            margin: 20px 0;
            font-size: 20px;
        }
        .signature-section {
            margin-top: 30px;
            text-align: left;
        }
        .signature-line {
            margin-top: 10px;
            margin-bottom: 30px;
            border-top: 1px solid black;
            width: 200px;
        }
        /* Button Styling */
button {
    background-color: #FF5733; /* Vibrant orange background */
    color: white; /* White text color */
    font-size: 16px; /* Comfortable font size */
    padding: 10px 20px; /* Add padding for a larger clickable area */
    border: none; /* Remove default border */
    border-radius: 5px; /* Rounded corners */
    cursor: pointer; /* Pointer cursor on hover */
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); /* Subtle shadow for depth */
    transition: all 0.3s ease; /* Smooth hover effects */
}

button:hover {
    background-color: #C70039; /* Darker red color for hover effect */
    transform: scale(1.05); /* Slight zoom-in on hover */
}

/* Link Styling */
a {
    text-decoration: none; /* Remove underline */
    color: #FF5733; /* Vibrant orange text color */
    font-size: 16px; /* Comfortable font size */
    font-weight: bold; /* Make it stand out */
    padding: 5px 10px; /* Add padding for better readability */
    border: 2px solid #FF5733; /* Add a border matching the color */
    border-radius: 5px; /* Rounded corners */
    transition: all 0.3s ease; /* Smooth hover effects */
}

a:hover {
    background-color: #FF5733; /* Orange background on hover */
    color: white; /* White text for contrast */
    transform: scale(1.05); /* Slight zoom-in on hover */
}

        @media print {
    #printButton, #backToHomepageButton {
        display: none; /* Hide the buttons when printing */
    }
}

    </style>
</head>
<body>
    <div class="receipt">
        <h1>Payment Receipt</h1>
        <p><strong>Transaction Code:</strong> <?php echo htmlspecialchars($payment['transaction_code']); ?></p>
        <p><strong>Hostel Name:</strong> <?php echo htmlspecialchars($payment['listing_name']); ?></p> <!-- Listing Name -->
        <p><strong>Amount Paid:</strong> KES <?php echo number_format($payment['amount'], 2); ?></p>
        <p><strong>Date & Time of Payment:</strong> <?php echo $payment['paid_at']; ?></p>
        <p><strong>Student Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
        <p><strong>Landlord Name:</strong> <?php echo htmlspecialchars($payment['landlord_first_name'] . ' ' . $payment['landlord_last_name']); ?></p>
        
        <!-- Sign Here Section -->
        <div class="signature-section">
            <p><strong>Landlord Signature:</strong></p><br>
            <div class="signature-line"></div> <!-- Blank line for physical signature -->
        </div>
        
        <!-- Print Button -->
        <!-- <a href="#" class="print-button" onclick="window.print()">Print Receipt</a> -->
        <button id="printButton" onclick="window.print()">Print Receipt</button>


    </div>

    <!-- Back to Student Page Button -->
    <a id="backToHomepageButton" href="homepage.html">Back to your page</a>
    <!-- <a href="view-more.php" class="back-button">Back to Student Page</a> -->
</body>
</html>


<?php
$stmt->close();
$conn->close();
?>
