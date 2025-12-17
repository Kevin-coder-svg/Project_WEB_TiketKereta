<?php

$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'tiket kereta';
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error . '. Pastikan MySQL running dan database "tiket kereta" ada.');
}
$conn->set_charset('utf8mb4');
function get_db_connection() {
    global $conn;
    return $conn;
}
?>
