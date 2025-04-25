<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Debugging: Print received data
    echo "<pre>";
    print_r($_POST);
    echo "</pre>";

    // Trim inputs to remove spaces
    $listing_name = isset($_POST['listing_name']) ? trim($_POST['listing_name']) : "";
    $price = isset($_POST['price']) ? trim($_POST['price']) : "";
    $distance = isset($_POST['distance']) ? trim($_POST['distance']) : "";
    $landlord_contact = isset($_POST['landlord_contact']) ? trim($_POST['landlord_contact']) : "";
    $email = isset($_POST['email']) ? trim($_POST['email']) : "";
    $vacant_rooms = isset($_POST['vacant_rooms']) ? trim($_POST['vacant_rooms']) : "";
    $description = isset($_POST['description']) ? trim($_POST['description']) : "";
    $image_path = "";

    // If any field is empty, stop execution
    if (empty($listing_name) || empty($price) || empty($distance) || empty($landlord_contact) || empty($email) || empty($vacant_rooms) || empty($description)) {
        die("Error: All fields are required!");
    }

    // Handle image upload
    if (!isset($_FILES['image_path']) || $_FILES['image_path']['error'] !== 0) {
        die("Error: Image upload failed!");
    } else {
        $target_dir = "uploads/";
        $target_file = $target_dir . basename($_FILES["image_path"]["name"]);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        $allowed_types = ["jpg", "jpeg", "png", "gif"];
        if (!in_array($imageFileType, $allowed_types)) {
            die("Error: Only JPG, JPEG, PNG, and GIF files are allowed.");
        }

        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!move_uploaded_file($_FILES["image_path"]["tmp_name"], $target_file)) {
            die("Error: Could not move uploaded file.");
        }
        
        $image_path = $target_file;
    }

    // Connect to database
    $conn = new mysqli("localhost", "root", "password", "housefy");

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert data into database
    $stmt = $conn->prepare("INSERT INTO listing (listing_name, price, description, vacant_rooms, distance, image_path, landlord_contact, email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $listing_name, $price, $description, $vacant_rooms, $distance, $image_path, $landlord_contact, $email);

    if ($stmt->execute()) {
        header("Location: dashboard2.php");
        exit();
    } else {
        die("Database Error: " . $stmt->error);
    }

    $stmt->close();
    $conn->close();
}
?>
