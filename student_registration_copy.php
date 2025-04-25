<?php
session_start();
$conn = new mysqli("localhost", "root", "password", "housefy");
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

    $sql = "SELECT * FROM student WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        echo "<p style='color:red;'><b>Email already exists. Try logging in instead!</b></p>";
    } else {
        $insert_sql = "INSERT INTO student (first_name, last_name, email, phone_number, campus, password) VALUES (?, ?, ?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone_number, $campus, $password);
        if ($insert_stmt->execute()) {
            header("Location: login.php?msg=Registration successful!");
            exit();
        } else {
            echo "<p style='color:red;'>Error: " . $insert_stmt->error . "</p>";
        }
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
    <title>Create Account</title>
    <link rel="stylesheet" href="styless.css">
    <style>
        #strengthBar {
            width: 100%;
            height: 10px;
            background-color:#E6E4DC;
            margin-top: 5px;
            border-radius: 5px;
        }
        #strengthBar span {
            height: 100%;
            display: block;
        }
        #message {
            font-size: 12px;
            font-weight: bold;
            margin-top: 5px;
        }
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
    <h2>Create an Account</h2>
    <form method="post" action="" id="registrationForm">
        <input type="text" name="first_name" placeholder="First Name" required><br>
        <input type="text" name="last_name" placeholder="Last Name" required><br>
        <input type="email" name="email" placeholder="Email" required><br>
        <input type="text" name="phone_number" placeholder="Phone Number" required><br>
        <input type="text" name="campus" placeholder="Campus" required><br>
        <div class="password-container">
            Password: <input type="password" id="password" name="password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <div id="strengthBar"></div>
        <p id="message"></p>
        <button type="submit" id="submitButton">Sign Up</button>
        <p>Already have an account? <a href="login.php">Login</a></p>
    </form>
    

    <script>
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('strengthBar');
        const message = document.getElementById('message');
        const submitButton = document.getElementById('submitButton');

        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            const strength = calculateStrength(password);

            // Clear previous segments
            strengthBar.innerHTML = '';
            const barSegment = document.createElement('span');
            strengthBar.appendChild(barSegment);

            if (strength === 'weak') {
                barSegment.style.width = '33%';
                barSegment.style.backgroundColor = 'red';
                message.textContent = 'Weak password. Please make it stronger.';
                message.style.color = 'red'; // Matches bar color
                message.style.fontWeight = 'bold'; // Makes the text bold
                submitButton.disabled = true;
            } else if (strength === 'medium') {
                barSegment.style.width = '66%';
                barSegment.style.backgroundColor = 'yellow';
                message.textContent = 'Medium password. You can proceed.';
                message.style.color = 'yellow'; // Matches bar color
                message.style.fontWeight = 'bold'; // Makes the text bold
                submitButton.disabled = false;
            } else if (strength === 'strong') {
                barSegment.style.width = '100%';
                barSegment.style.backgroundColor = 'green';
                message.textContent = 'Strong password. Good to go!';
                message.style.color = 'green'; // Matches bar color
                message.style.fontWeight = 'bold'; // Makes the text bold
                submitButton.disabled = false;
            }
        });

        function calculateStrength(password) {
            let score = 0;

            if (password.length >= 8) score++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) score++;
            if (/\d/.test(password)) score++;
            if (/[\W_]/.test(password)) score++;

            if (score <= 1) return 'weak';
            if (score === 2) return 'medium';
            return 'strong';
        }

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
