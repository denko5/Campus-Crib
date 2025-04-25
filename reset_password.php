<?php

session_start();

// Prevent page caching and force a fresh page load
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
header("Content-Type: text/html; charset=UTF-8");

$conn = new mysqli("localhost", "root", "password", "housefy");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$message = "";

// Check if reset_email is set in the session
if (!isset($_SESSION['reset_email'])) {
    header("Location: forgot_password.php");
    exit();
}

$email = $_SESSION['reset_email'];

// Handle form submission for resetting the password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = $_POST["new_password"];
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

    // Update the password in the database and reset failed attempts
    $sql = "UPDATE student SET password = ?, failed_attempts = 0 WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $hashed_password, $email);

    if ($stmt->execute()) {
        // clear reset email session
        unset($_SESSION['reset_email']); 

        // Clear cookies to prevent stale data
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Regenerate session to ensure no stale data
        session_regenerate_id(true);
        session_destroy(); // Destroy the old session completely
        session_start(); // Start a fresh new session for future use 

        $message = "Password reset successful! <a href='login.php'>Login here</a>";
    } else {
        $message = "Failed to reset password. Please try again.";
    }

    $stmt->close();
    $conn->close();

}
?>

<!DOCTYPE html>
<html lang="en">
    
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="styless.css">
    <style>
        .password-container {
            position: relative;
            width: fit-content;
        }
        .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
        } 
    </style>
</head>

<body>

    <h2>Reset Password</h2>

    <form method="POST" action="">
    <div class="password-container">
            Enter your new password: <input type="password" id="password" name="password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <button type="submit">Reset Password</button>
    </form>

    <?php if ($message): ?>
        <p style="color: green;"><?php echo $message; ?></p>
    <?php endif; ?>
    
    <script>
        
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const toggleButton = document.querySelector('.toggle-password');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                toggleButton.textContent = '🙈'; // Change icon to "hide"
            } else {
                passwordField.type = 'password';
                toggleButton.textContent = '👁️'; // Change icon to "show"
            }
        }
    </script>
    
</body>
</html>