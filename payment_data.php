<?php
session_start();
include("housefydb.php");

if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$landlord_email = $_SESSION['email'];

$query = "SELECT 
            COUNT(CASE WHEN status = 'Approved' THEN 1 END) AS approved_payments,
            COUNT(CASE WHEN status = 'Pending' THEN 1 END) AS pending_payments,
            COUNT(CASE WHEN status = 'Cancelled' THEN 1 END) AS cancelled_payments,
            SUM(CASE WHEN status = 'Approved' THEN 1 ELSE 0 END) AS booked_rooms,
            SUM(vacant_rooms) AS unbooked_rooms
          FROM listing 
          LEFT JOIN payment ON listing.listing_id = payment.listing_id
          WHERE listing.email = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $landlord_email);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode($result);
exit;
