<?php
// register.php - Handle user registration (standalone version)

// Database credentials
$db_host = 'localhost';
$db_user = 'root';
$db_password = '';
$db_name = 'tiket kereta';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_password, $db_name);

// Check connection
if ($conn->connect_error) {
    http_response_code(500);
    die('Database connection error: ' . $conn->connect_error);
}

// Set charset
$conn->set_charset('utf8mb4');

// Handle GET request (for debugging)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Debug - Register</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-4">
        <div class="container">
            <h2>Debug Information</h2>
            <div class="alert alert-info">
                <p><strong>Request Method:</strong> <?php echo $_SERVER['REQUEST_METHOD']; ?></p>
                <p><strong>Database Connection:</strong> ✅ Connected</p>
                <p><strong>This page processes POST requests only.</strong></p>
            </div>
            <a href="register.html" class="btn btn-primary">Kembali ke Form</a>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get form data
$full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
$nip = isset($_POST['nip']) ? trim($_POST['nip']) : '';
$password_input = isset($_POST['password']) ? $_POST['password'] : '';

// Server-side validation
$errors = [];

if (strlen($full_name) < 2) {
    $errors[] = 'Nama harus diisi minimal 2 karakter.';
}

if (!ctype_digit($nip) || strlen($nip) < 6 || strlen($nip) > 16) {
    $errors[] = 'NIP harus berupa angka dan minimal 6 sampai 16 digit.';
}

if (strlen($password_input) < 6) {
    $errors[] = 'Password minimal 6 karakter.';
}

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Validation Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f8f9fa; }
            .card { max-width: 400px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="card-body">
                <h3 class="text-danger mb-3">⚠️ Error Validasi</h3>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p class="mb-2">• <?php echo htmlspecialchars($error); ?></p>
                    <?php endforeach; ?>
                </div>
                <a href="register.html" class="btn btn-primary w-100">Kembali ke Form</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Hash password using PHP's built-in password_hash (bcrypt)
$hashed_password = password_hash($password_input, PASSWORD_BCRYPT);

// Prepare SQL statement to prevent SQL injection
$sql = "INSERT INTO users (full_name, password, nik, role, created_at) VALUES (?, ?, ?, 'user', NOW())";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Database Error</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f8f9fa; }
            .card { max-width: 400px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="card-body">
                <h3 class="text-danger mb-3">❌ Database Error</h3>
                <p class="text-muted">Prepare failed: <?php echo htmlspecialchars($conn->error); ?></p>
                <a href="register.html" class="btn btn-primary w-100">Kembali ke Form</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Bind parameters
$stmt->bind_param('sss', $full_name, $hashed_password, $nip);

// Execute statement
if ($stmt->execute()) {
    // Registration successful
    $stmt->close();
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Registrasi Berhasil</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f8f9fa; }
            .card { max-width: 400px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="card-body text-center">
                <h3 class="text-success mb-3">✅ Registrasi Berhasil!</h3>
                <p class="text-muted mb-4">Nama: <strong><?php echo htmlspecialchars($full_name); ?></strong></p>
                <p class="text-muted mb-4">Akun Anda telah dibuat. Anda akan dialihkan ke halaman login dalam 3 detik...</p>
                <a href="HTML_login.html" class="btn btn-primary w-100">Masuk Sekarang</a>
            </div>
        </div>
        <script>
            setTimeout(() => {
                window.location.href = 'HTML_login.html';
            }, 3000);
        </script>
    </body>
    </html>
    <?php
    exit;
} else {
    // Error in execution
    http_response_code(500);
    $error_msg = $stmt->error;
    $stmt->close();
    $conn->close();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="utf-8">
        <title>Registrasi Gagal</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { display: flex; align-items: center; justify-content: center; min-height: 100vh; background: #f8f9fa; }
            .card { max-width: 400px; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="card-body">
                <h3 class="text-danger mb-3">❌ Registrasi Gagal</h3>
                <p class="text-muted">Error: <?php echo htmlspecialchars($error_msg); ?></p>
                <a href="register.html" class="btn btn-primary w-100">Kembali ke Form</a>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
