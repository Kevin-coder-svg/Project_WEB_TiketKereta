<?php
// login.php - Handle user login

// Include database configuration
require 'db_config.php';

// Get database connection
$conn = get_db_connection();

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Method not allowed');
}

// Get form data
$nip = isset($_POST['nip']) ? trim($_POST['nip']) : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';
$role = isset($_POST['role']) ? $_POST['role'] : '';

// Server-side validation
$errors = [];

if (empty($nip)) {
    $errors[] = 'NIP harus diisi.';
}

if (empty($password)) {
    $errors[] = 'Password harus diisi.';
}

if (empty($role)) {
    $errors[] = 'Role harus dipilih.';
}

// If there are validation errors, return them
if (!empty($errors)) {
    http_response_code(400);
    echo implode('<br>', $errors);
    exit;
}

// Prepared statement to get user by NIP and role
$sql = "SELECT user_id, full_name, password, role FROM users WHERE nik = ? AND role = ?";
$stmt = $conn->prepare($sql);

if (!$stmt) {
    http_response_code(500);
    die('Prepare failed: ' . $conn->error);
}

// Bind parameters
$stmt->bind_param('ss', $nip, $role);

// Execute statement
if (!$stmt->execute()) {
    http_response_code(500);
    die('Execute failed: ' . $stmt->error);
}

// Get result
$result = $stmt->get_result();

// Check if user exists
if ($result->num_rows === 0) {
    http_response_code(401);
    echo 'NIP atau role tidak ditemukan.';
    exit;
}

// Fetch user data
$user = $result->fetch_assoc();

// Verify password using password_verify (matches bcrypt hash)
if (!password_verify($password, $user['password'])) {
    http_response_code(401);
    echo 'Password salah.';
    exit;
}

// Login successful - start session and store user info
session_start();
$_SESSION['user_id'] = $user['user_id'];
$_SESSION['full_name'] = $user['full_name'];
$_SESSION['role'] = $user['role'];
$_SESSION['nip'] = $nip;

// Return HTML with JavaScript to store user info and redirect
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login Berhasil</title>
</head>
<body>
    <p>Redirecting...</p>
    <script>
        localStorage.setItem('userName', '<?php echo htmlspecialchars($user['full_name']); ?>');
        localStorage.setItem('userRole', '<?php echo htmlspecialchars($user['role']); ?>');
        window.location.href = 'home.html';
    </script>
</body>
</html>
<?php
exit;
