<?php
session_start(); // Start session
include("housefydb.php"); // Include database connection

// Validate session
if (!isset($_SESSION['email'])) {
    die("Error: Landlord email is not set in session.");
}

$landlord_email = $_SESSION['email']; // Get landlord's email

// Clear any prior output
if (ob_get_level()) {
    ob_end_clean();
}

// Set headers for CSV download
header("Content-Type: text/csv");
header("Content-Disposition: attachment; filename=payments.csv");

// Open output stream
$output = fopen("php://output", "w");

// Write column headers
fputcsv($output, ["Transaction Code", "Student Name", "Phone Number", "Amount (KES)", "Paid At", "Status", "Listing Name"]);

// Fetch payment data
$query = "SELECT p.transaction_code, 
                 CONCAT(s.first_name, ' ', s.last_name) AS student_name, 
                 p.phone_number, 
                 p.amount, 
                 p.paid_at, 
                 p.status, 
                 l.listing_name
          FROM payment p
          JOIN listing l ON p.listing_id = l.listing_id
          JOIN student s ON p.student_id = s.student_id
          WHERE l.email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // Write each row to the CSV
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, $row);
    }
} else {
    // If no data is found, add a placeholder row
    fputcsv($output, ["No data available"]);
}

// Close output stream and database connection
fclose($output);
$stmt->close();
$conn->close();
?>
