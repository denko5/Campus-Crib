<?php
session_start();

// Destroy all session data
session_unset();
session_destroy();

// Redirect to registration page
header("Location: registration.php"); 
exit();
?>
