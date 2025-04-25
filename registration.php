<?php
    // Define registration page URLs
    $student_reg = "student_registration_copy.php";
    $landlord_reg = "landlord_registration.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="r.css">
    <style>
       /* General Styles */
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    margin: 0;
    padding: 0;
    background: url('welcome-image.jpg') no-repeat center center fixed;
    background-size: cover;
    backdrop-filter: blur(8px);
    background-color: rgba(0, 0, 0, 0.85); /* Added for deeper contrast */
    color: #fff;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}

/* Header */
.header {
    text-align: center;
    margin-bottom: 2rem;
    border-radius: 10px;
}

.header-title {
    font-size: 3rem;
    font-weight: bold;
    color: #fff;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
}

/* Registration Container */
.registration-container {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(8px);
    border-radius: 15px;
    padding: 2rem;
    width: 90%;
    max-width: 400px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    text-align: center;
}

.registration-container h2 {
    font-size: 1.8rem;
    margin-bottom: 1.5rem;
    color: #ffeb3b;
}

/* Registration Options */
.registration-options {
    display: flex;
    justify-content: space-around;
    margin-top: 1rem;
    gap: 1rem; /* Added gap for better spacing */
}

.register-link {
    text-decoration: none;
    color: white;
    background: linear-gradient(to right, #ff512f, #dd2476);
    padding: 0.8rem 1.5rem;
    font-size: 1.2rem;
    border-radius: 8px;
    font-weight: bold;
    transition: transform 0.3s, background 0.3s;
}

.register-link:hover {
    background: linear-gradient(to right, #dd2476, #ff512f);
    transform: scale(1.1);
}

/* Responsive Design */
@media (max-width: 768px) {
    .registration-container {
        padding: 1.5rem;
        width: 95%; /* Expand to utilize space on small devices */
    }

    .registration-options {
        flex-direction: column;
        gap: 1rem; /* Uniform spacing for stacked buttons */
    }

    .header-title {
        font-size: 2.5rem; /* Smaller header for medium screens */
    }
}

@media (max-width: 480px) {
    .header-title {
        font-size: 2rem; /* Smaller header for extra-small screens */
    }

    .registration-container {
        padding: 1rem;
    }

    .register-link {
        font-size: 1rem; /* Adjust button size for extra-small screens */
        padding: 0.8rem 1rem; /* Compact padding */
    }

    .registration-options {
        gap: 0.8rem; /* Compact spacing for tighter screens */
    }
}

    </style>
</head>
<body>
    <div class="registration-container">
        <header class="header">
            <h1 class="header-title">Register</h1>
        </header>
        <h2>Create an Account</h2>
        <div class="registration-options">
            <a href="<?php echo $student_reg; ?>" class="register-link">Student</a>
            <a href="<?php echo $landlord_reg; ?>" class="register-link">Landlord</a>
        </div>
    </div>
</body>
</html>
