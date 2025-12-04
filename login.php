<?php
session_start();
require 'db_config.php'; // Include database configuration

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Prepare and execute the query
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify the password
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            // Return success response
            echo json_encode(["success" => true, "role" => $user['role']]);
        } else {
            // Return error response for incorrect password
            echo json_encode(["success" => false, "message" => "Password salah!"]);
        }
    } else {
        // Return error response for incorrect email or role
        echo json_encode(["success" => false, "message" => "Email atau role tidak ditemukan!"]);
    }

    $stmt->close();
}

$conn->close();
?>
