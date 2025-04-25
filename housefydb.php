<?php
// database configuration
$servername = "localhost";
$username = "root";
$password = "password";
$dbname = "housefy";

// create a connection
$conn = new mysqli($servername, $username, $password, $dbname);

// check connection
if ($conn->connect_error) {
    die("Database Connection failed: " . $conn->connect_error);
}
//echo "Connected successfully";
?>
