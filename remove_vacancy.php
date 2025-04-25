<?php

$conn = new mysqli("localhost", "root", "password", "student_housing");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check if form is submitted and vacancy_id is set
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vacancy_id'])) {
    $vacancy_id = $_POST['vacancy_id'];

    // Check if the vacancy exists
    $sql_check = "SELECT * FROM vacancies WHERE vacancy_id = ? AND is_booked = 0";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("i", $vacancy_id);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows > 0) {
        
        // Delete the vacancy
        $sql_delete = "DELETE FROM vacancies WHERE vacancy_id = ?";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bind_param("i", $vacancy_id);

        if ($stmt_delete->execute()) {
            echo "<p>Vacancy removed successfully!</p>";
        } else {
            echo "<p>Error: " . $stmt_delete->error . "</p>";
        }

        $stmt_delete->close();
    } //else {
        // echo "<p style='color:red;'><b>Vacancy not found or already booked.</b></p>";
    //} 

    $stmt_check->close();
    
}

$conn->close();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message</title>
    <link rel="stylesheet" href="remove_vacancy.css">
</head>
<style>
    /* General Body Style */
:root {
    --primary-color: #EB5B38;
    --secondary-color: #272A2D;
    --background-color: #E6E4DC;
    --highlight-color: #347CCB;
    --link-color: #007bff;
    --card-color: white;
}
.header {
    background-color: var(--secondary-color);
    border-radius: 8px 8px 0 0;
    color: red;
    padding:10px 0;
}
    .header h1 {
        text-align:center;

    }
</style>

<body>
    
</body>
</html>
<header class="header">
     
    <h1>Vacancy not found or already booked !!</h1>
</header>
<p><a href="Homepage.php">Back to Homepage</a></p>
</body>
</html>