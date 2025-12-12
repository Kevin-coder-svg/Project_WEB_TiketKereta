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
            // TAMBAHKAN INI:
            $_SESSION['user_id'] = $user['user_id']; // atau 'id' tergantung nama kolom di DB
            $_SESSION['full_name'] = $user['full_name']; // tambahkan jika ada
            
            // Return success response
            echo json_encode([
                "success" => true, 
                "role" => $user['role'],
                "user_id" => $user['user_id'], // kirim juga ke frontend jika diperlukan
                "full_name" => $user['full_name']
            ]);
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
