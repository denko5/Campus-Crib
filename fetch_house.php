<?php
header('Content-Type: application/json');


// Connect to database
$conn = new mysqli("localhost", "root", "", "student_housing");

if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed"]));
}

// Get house ID from URL
$houseId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($houseId > 0) {
    $stmt = $conn->prepare("SELECT type, location, price, distance_km, landlord_contact, image_path, description FROM vacancies WHERE vacancy_id = ?");
    $stmt->bind_param("i", $houseId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        echo json_encode([
            "success" => true,
            "type" => $row["type"],
            "location" => $row["location"],
            "price" => $row["price"],
            "distance_km" => $row["distance_km"],
            "landlord_contact" => $row["landlord_contact"],
            "image" => $row["image_path"],

            "description" => $row["description"]
        ]);
    } else {
        echo json_encode(["success" => false, "message" => "House not found"]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid house ID"]);
}

$conn->close();
?>
