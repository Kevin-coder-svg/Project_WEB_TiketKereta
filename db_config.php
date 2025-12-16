<?php
// db_config.php - Database configuration for PHP

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'tiket kereta';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error . '. Pastikan MySQL running dan database "tiket kereta" ada.');
}

// Set charset to utf8mb4
$conn->set_charset('utf8mb4');

// Optional: You can create a function to get connection if needed elsewhere
function get_db_connection() {
    global $conn;
    return $conn;
}
?>
