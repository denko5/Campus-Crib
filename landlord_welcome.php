<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome</title>
    <link rel="stylesheet" href="styless.css">
</head>
<body>

    <?php

    if (!isset($_GET['msg'])) {
        header("Location: landlord_login_copy.php");
        exit();
    }
    $msg = $_GET['msg'];

    ?>

    <h2><?php echo htmlspecialchars($msg); ?></h2>
    <p>Welcome to the Student Housing Finder Platform!</p>
    <a href="landlord_login_copy.php">Go to Homepage</a>
    
</body>
</html>