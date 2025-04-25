<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>
    <h2>L.Login</h2>

    <?php

    session_start();
    include "housefydb.php"; // Connects to `housefy` database

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $email = $_POST["email"];
        $password = $_POST["password"];



        // Initialize failed login attempts if not set
        if (!isset($_SESSION['failed_attempts'])) {
            $_SESSION['failed_attempts'] = 0;
        }

        // Retrive user from database
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
                // Check Password
                if (password_verify($password, $user["password"])) {
                    // Successful Login
                    $_SESSION['failed_attempts'] = 0; // Reset failed attempts on successful login
                

                // Update failed_attempts to 0 in the databse
                $sql_update = "UPDATE landlord SET failed_attempts = 0 WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("s", $email);
                $stmt_update->execute();

                $_SESSION['email'] = $email; // Store the user's email in the session
                header("Location: welcome.php?msg=Login successful!");
                exit();
            } else {
                // Increment failed attempts in session and in database
                $_SESSION['failed_attempts']++;

                // Increment failed attempts in database
                $failed_attempts =$user['failed_attempts'] + 1; // Get the current failed attempts from the database
                $sql_update = "UPDATE landlord SET failed_attempts = ? WHERE email = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("is", $failed_attempts, $email);
                $stmt_update->execute();

                // Calculate remaining attempts
                $remaining_attempts = 3 - $failed_attempts;
                echo "<p style='color:red;'>Invalid password. You have $remaining_attempts attempt(s) left.</p>";
            }

            }
         
        } else {
            echo "<p style='color:red;'>No user found with this email.</p>";
        }

        $stmt->close();
        $conn->close();
    }

            

    ?>

    <form method="post" action="">
        Email: <input type="email" name="email" required><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Login</button>
    </form>


    <p>Don't have an account? <a href="landlord_registration.php">Sign Up</a></p>
    <p>Forgot your password? <a href="landlord_forgot_password.php">Reset Password</a></p>

</body>
</html>