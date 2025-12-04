<?php
// logout.php - Handle user logout

// Start session
session_start();

// Clear all session data
session_destroy();

// Redirect to login page
header('Location: HTML_login.html');
exit;
?>
