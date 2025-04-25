<?php
session_start();
include "housefydb.php"; // Database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    // Initialize failed login attempts if not set
    if (!isset($_SESSION['failed_attempts'])) {
        $_SESSION['failed_attempts'] = 0;
    }

    // Retrieve user from the database
    $sql = "SELECT * FROM landlord WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Check if account is locked due to too many failed attempts
        if ($user['failed_attempts'] >= 3) {
            echo "<p style='color:red;'>Your account is locked due to too many failed attempts. <a href='landlord_forgot_password.php'>Reset Password?</a></p>";

        } else {
            // Verify password
            if (password_verify($password, $user["password"])) {
                // Successful login
                $_SESSION['email'] = $email;
                $_SESSION['failed_attempts'] = 0; // Reset failed attempts
                
                // Update failed_attempts to 0 in the database
                $sql_update = "UPDATE landlord SET failed_attempts = 0 WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("s", $email);
                $stmt_update->execute();

                // Redirect to dashboard
                header("Location: dashboard2.php?msg=Login successful!");
                exit();
            } else {
                // Increment failed attempts
                $failed_attempts = $user['failed_attempts'] + 1;
                $sql_update = "UPDATE landlord SET failed_attempts = ? WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("is", $failed_attempts, $email);
                $stmt_update->execute();

                $remaining_attempts = 3 - $failed_attempts;

                if ($remaining_attempts > 0) {
                    echo "<p style='color:red;'>Invalid credentials. You have $remaining_attempts attempt(s) left.</p>";
                } else {
                    echo "<p style='color:red;'>Your account is now locked. <a href='landlord_forgot_password.php'>Reset Password?</a></p>";
                }
            }
        }
    } else {
        echo "<p style='color:red;'>Invalid credentials. Please try again.</p>";
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
    <title>Login</title>
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

    <h2>Login</h2>

    <form method="post" action="">
        <input type="email" name="email" placeholder="Email" required><br>
        <div class="password-container">
            Password: <input type="password" id="password" name="password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <button type="submit">Login</button><br>
        <p>Don't have an account? <a href="landlord_registration.php">Sign Up</a></p>
    <p>Forgot your password? <a href="landlord_forgot_password.php">Reset Password</a></p>
    </form>

    

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
