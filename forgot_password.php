<?php

session_start();

$conn = new mysqli("localhost", "root", "password", "housefy");


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Handle form submission for password reset request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST["email"];

    // Check if the email exists in the database
    $sql = "SELECT * FROM student WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
         // Email exists, reset failed attempts to 0
         $sql = "UPDATE student SET failed_attempts = 0 WHERE email = ?";
         $stmt = $conn->prepare($sql);
         $stmt->bind_param("s", $email);
         $stmt->execute();

        // Store the email in session for resetting password
        $_SESSION['reset_email'] = $email;
        header("Location: reset_password.php");
        exit();
    } else {
        // Email does not exist, increment failed attempts
        $sql = "UPDATE student SET failed_attempts = failed_attempts + 1 WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();

        $message = "No user found with that email.";
    }

    $conn->close();
   
}

$conn->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="styless.css">

</head>

<body>

    <h2>Forgot Password</h2>

    <form method="POST" action="">
    <input type="email" name="email" placeholder="Email" required><br>
        <button type="submit">Continue</button><br><br>
        <p>Remember your password? <a href="login.php">Login</a></p>
    </form>

    <?php if ($message): ?>
        <p style="color: red;"><?php echo $message; ?></p>
    <?php endif; ?>

    


</body>
</html>