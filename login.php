<?php
session_start();
require 'db_config.php'; 

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

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
    $conn->close();
    exit; 
}
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>KAI-Kaki Anak Itam</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
      body {
        background-image: url('background.jpg');
        background-size: cover;
        height: 100vh;
      }
    </style>
  </head>
  <body class="d-flex justify-content-center align-items-center">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <div id="page" class="container-fluid">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header text-center bg-primary text-white">
                        <h3>KAI - Kaki Anak Itam</h3>
                        </div>
                        <div class="card-body">
                        <div id="msg" class="mb-3"></div>
                        <form id="loginForm" method="post" action="" novalidate>
                          <div class="mb-3">
                          <label for="email" class="form-label">Email</label>
                          <input type="email" class="form-control" id="email" name="email" placeholder="Masukkan email Anda" required>
                          </div>
                            <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password Anda" required>
                            </div>
                            <div class="mb-3">
                            <label for="role" class="form-label">Role</label>
                            <select class="form-select" id="role" name="role" required>
                              <option value="user">User</option>
                              <option value="admin">Admin</option>
                            </select>
                            </div>
                            <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        </div>
                        <div class="text-center mb-4">
                          <a href="register.php" class="link-primary">Register/Daftar</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script>
      document.getElementById('loginForm').addEventListener('submit', function(event) {
        event.preventDefault(); 
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const role = document.getElementById('role').value;

        fetch('', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: new URLSearchParams({ email, password, role }),
        })
          .then(response => {
              if (!response.ok) { throw new Error('Network response was not ok'); }
              return response.json();
          })
          .then(data => {
            if (data.success) {
              if (data.role === 'admin') {
                window.location.href = 'admin.php';
              } else {
                window.location.href = 'home.php';
              }
            } else {
              const msgDiv = document.getElementById('msg');
              msgDiv.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Terjadi kesalahan sistem atau koneksi.');
          });
      });
    </script>
  </body>
</html>