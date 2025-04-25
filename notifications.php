<?php
include("housefydb.php");

// Parse the POST request
$input = json_decode(file_get_contents("php://input"), true);
$student_id = intval($input['student_id'] ?? 0);

if ($student_id > 0) {
    // Update notifications to "Read"
    $sql = "UPDATE payment SET Notification_status = 'Read' WHERE student_id = ? AND Notification_status = 'Unread'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $stmt->close();
}

$conn->close();
?>
