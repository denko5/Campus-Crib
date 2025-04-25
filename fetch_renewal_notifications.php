<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "password";
$database = "housefydb";

$conn = new mysqli($host, $user, $password, $database);

// Check database connection
if ($conn->connect_error) {
    die(json_encode(['error' => 'Connection failed. Please try again later.']));
}

// Ensure student ID is provided
if (isset($_GET['student_id'])) {
    $student_id = intval($_GET['student_id']);

    // Fetch renewal notifications for the student
    $query_renewal_notifications = "SELECT p.transaction_code, l.listing_name, p.amount, p.phone_number 
                                    FROM payment p
                                    JOIN listing l ON p.listing_id = l.listing_id
                                    WHERE p.student_id = ? AND p.status = 'Pending' AND p.notification_status = 'Unread'
                                    ORDER BY p.paid_at DESC";
    $stmt = $conn->prepare($query_renewal_notifications);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = [
            'transaction_code' => $row['transaction_code'],
            'listing_name' => $row['listing_name'],
            'amount' => number_format($row['amount']),
            'phone_number' => $row['phone_number']
        ];
    }

    echo json_encode(['notifications' => $notifications]);
} else {
    echo json_encode(['error' => 'Invalid student ID.']);
}
?>
