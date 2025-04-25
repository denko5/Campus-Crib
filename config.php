<?php
// database configuration
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "housefy";

// create a connection
$conn = new mysqli($localhost, $root, $password, $housefy);

// check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Connected successfully";
?>


