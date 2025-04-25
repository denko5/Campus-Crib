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
            background-color: #ddd;
            margin-top: 5px;
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

    <?php

    // Database connection
    $conn = new mysqli("localhost", "root", "password", "housefy");
    if ($conn->connect_error) {
        die("connection failed: " . $conn->connect_error);
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $first_name = $_POST["first_name"];
        $last_name = $_POST["last_name"];
        $email = $_POST["email"];
        $phone_number = $_POST["phone_number"];
        $password = password_hash($_POST["password"], PASSWORD_DEFAULT);
    
        // Check if email or phone number exists
        $sql = "SELECT email, phone_number FROM landlord WHERE email = ? OR phone_number = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $email, $phone_number);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $existing = $result->fetch_assoc();
            if ($existing['email'] === $email && $existing['phone_number'] === $phone_number) {
                echo "<p style='color:red;'>Email and phone number already exist. Please try different ones or log in!</p>";
            } elseif ($existing['email'] === $email) {
                echo "<p style='color:red;'>Email already exists. Please try a different one or log in!</p>";
            } elseif ($existing['phone_number'] === $phone_number) {
                echo "<p style='color:red;'>Phone number already exists. Please try a different one or log in!</p>";
            }
        } else {
            // Insert into database
            $insert_sql = "INSERT INTO landlord (first_name, last_name, email, phone_number, password) VALUES (?, ?, ?, ?, ?)";
            $insert_stmt = $conn->prepare($insert_sql);
            $insert_stmt->bind_param("sssss", $first_name, $last_name, $email, $phone_number, $password);
    
            if ($insert_stmt->execute()) {
                header("Location: landlord_welcome.php?msg=Registration successful!");
                exit();
            } else {
                echo "<p style='color:red;'>An error occurred during registration. Please try again.</p>";
            }
        }
    
        $stmt->close();
        $conn->close();
    }
    
    

    ?>

    <form method="post" action="">
    <input type="text" name="first_name" placeholder="First Name" required><br>
    <input type="text" name="last_name" placeholder="Last Name" required><br>
    <input type="email" name="email" placeholder="Email" required><br>
    <input type="text" name="phone_number" placeholder="Phone Number" required><br>
        <div class="password-container">
            Password: <input type="password" id="password" name="password" required>
            <span class="toggle-password" onclick="togglePassword()">👁️</span>
        </div>
        <div id="strengthBar"></div>
        <p id="message"></p>
        <button type="submit">L.Sign Up</button><br><br>
        <p>Already have an account? <a href="landlord_login_copy.php">Login</a></p>
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
                message.textContent = 'Medium password. Can proceed.';
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