<?php
session_start();
require 'db_config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
    $stmt->bind_param("ss", $email, $role);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        
        if (password_verify($password, $user['password'])) {
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
        
            $_SESSION['user_id'] = $user['user_id']; 
            $_SESSION['full_name'] = $user['full_name']; 
            
            
            echo json_encode([
                "success" => true, 
                "role" => $user['role'],
                "user_id" => $user['user_id'], 
                "full_name" => $user['full_name']
            ]);
        } else {
           
            echo json_encode(["success" => false, "message" => "Password salah!"]);
        }
    } else {
  
        echo json_encode(["success" => false, "message" => "Email atau role tidak ditemukan!"]);
    }

    $stmt->close();
}

$conn->close();
?>