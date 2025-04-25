<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>
    <h2>Create an Account</h2>

    <?php

    // Database connection
    $conn = new mysqli("localhost", "root", "password", "student_housing");
    if ($conn->connect_error) {
        die("connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $first_name = $_POST["first_name"];
        $last_name = $_POST["last_name"];
        $email = $_POST["email"];
        $phone_number = $_POST["phone_number"];
        $campus = $_POST["campus"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);

        // Check if email exists
        $sql = "SELECT * FROM credentials WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<p style='color:red;'>Email already exists. Try logging in instead!</p>";
        } else {
            
            // Insert into database
            $insert_sql = "INSERT INTO credentials (first_name, last_name, email, phone_number, campus, password) VALUES (?, ?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone_number, $campus, $password);


            if ($insert_stmt->execute()) {
                header("Location: welcome.php?msg=Registration successful!");
                exit();
            } else {
                echo "<p style='color:red;'>Error: " . $insert_stmt->error . "</p>";
            }
        }

        $stmt->close();
        $conn->close();
    }

    ?>

    <form method="post" action="">
        First Name: <input type="text" name="first_name" required><br>
        Last Name: <input type="text" name="last_name" required><br>
        Email: <input type="email" name="email" required><br>
        Phone Number: <input type="text" name="phone_number" required><br>
        Campus: <input type="text" name="campus" require><br>
        Password: <input type="password" name="password" required><br>
        <button type="submit">Sign Up</button>
    </form>
    <p>Already have an account? <a href="login.php">Login</a></p>
</body>
</html>