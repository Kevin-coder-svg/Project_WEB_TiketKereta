<?php
// profile.php - User Profile Page

session_start();
require 'db_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: HTML_login.html');
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'tiket kereta');

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('User tidak ditemukan');
}

$user = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!doctype html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Profil - KAI Tiket Kereta</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <style>
        body {
            background-image: url('background.jpg');
            background-size: cover;
            background-attachment: fixed;
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        .navbar {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.9) 0%, rgba(13, 110, 253, 0.2) 100%) !important;
            border-bottom: 3px solid #0d6efd;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.2);
            padding: 15px 20px;
        }

        .navbar-brand {
            font-size: 1.5rem;
            transition: opacity 0.2s;
            margin-left: 0 !important;
        }

        .navbar-brand:hover {
            opacity: 0.8;
        }

        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: -300px;
            top: 0;
            width: 300px;
            height: 100vh;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(13, 110, 253, 0.1) 100%);
            transition: left 0.3s ease;
            z-index: 1000;
            overflow-y: auto;
            border-right: 2px solid #0d6efd;
            padding-top: 20px;
        }

        .sidebar.active {
            left: 0;
        }

        .sidebar-header {
            color: white;
            font-size: 1.3rem;
            padding: 20px;
            border-bottom: 1px solid #0d6efd;
            margin-bottom: 20px;
            font-weight: bold;
        }

        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .sidebar-menu li {
            border-bottom: 1px solid rgba(13, 110, 253, 0.2);
        }

        .sidebar-menu a {
            display: block;
            padding: 15px 25px;
            color: white;
            text-decoration: none;
            transition: all 0.2s;
            font-size: 1.1rem;
        }

        .sidebar-menu a:hover {
            background-color: #0d6efd;
            padding-left: 30px;
        }

        .sidebar-menu a.active {
            background-color: #0d6efd;
            border-left: 4px solid white;
        }

        /* Toggle Button */
        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            margin-right: 15px;
        }

        .sidebar-toggle:hover {
            color: #0d6efd;
        }

        /* Overlay */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 999;
        }

        .sidebar-overlay.active {
            display: block;
        }

        .profile-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 12px;
            padding: 40px;
            margin-top: 30px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 40px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 20px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 48px;
            color: white;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
        }

        .profile-name {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .profile-role {
            font-size: 1.1rem;
            color: #0d6efd;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }

        .info-card {
            background: linear-gradient(135deg, rgba(13, 110, 253, 0.05) 0%, rgba(13, 110, 253, 0.1) 100%);
            border: 1px solid rgba(13, 110, 253, 0.2);
            border-radius: 8px;
            padding: 20px;
            transition: all 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.15);
            border-color: #0d6efd;
        }

        .info-label {
            font-size: 0.9rem;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            font-weight: 600;
        }

        .info-value {
            font-size: 1.2rem;
            color: #333;
            font-weight: 500;
            word-break: break-all;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-custom {
            padding: 12px 30px;
            font-size: 1rem;
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn-edit {
            background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
            color: white;
            border: none;
        }

        .btn-edit:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(13, 110, 253, 0.4);
            color: white;
        }

        .btn-back {
            background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
            color: white;
            border: none;
        }

        .btn-back:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
            color: white;
        }

        .member-since {
            text-align: center;
            color: #999;
            font-size: 0.95rem;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #e9ecef;
        }

        footer {
            background-color: rgba(0, 0, 0, 0.8);
            color: white;
            padding: 20px;
            text-align: center;
            margin-top: 40px;
        }
    </style>
</head>

<body>
    <!-- Sidebar Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            ☰ Menu
        </div>
        <ul class="sidebar-menu">
            <li><a href="home.html">🏠 Home</a></li>
            <li><a href="search.php">🔍 Cari Jadwal</a></li>
            <li><a href="booking.php">🎫 Pesan Tiket</a></li>
            <li><a href="history.php">📋 Riwayat Pemesanan</a></li>
            <li><a href="profile.php" class="active">👤 Profil Saya</a></li>
            <li><a href="settings.php">⚙️ Pengaturan</a></li>
            <li><a href="logout.php" style="color: #ff6b6b;">🚪 Logout</a></li>
        </ul>
    </div>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container-fluid">
            <button class="sidebar-toggle" id="sidebarToggle" type="button">
                ☰
            </button>
            <a class="navbar-brand fw-bold" href="home.html">
                🚂 KAI - Tiket Kereta
            </a>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="profile-avatar">
                    👤
                </div>
                <div class="profile-name"><?php echo htmlspecialchars($user['full_name']); ?></div>
                <div class="profile-role"><?php echo htmlspecialchars($user['role']); ?></div>
            </div>

            <!-- Profile Information -->
            <div class="profile-info">
                <div class="info-card">
                    <div class="info-label">🆔 User ID</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['user_id']); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">🏷️ NIK</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['nik']); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">📧 Email</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['email'] ?? 'Tidak tersedia'); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">📱 Nomor Telepon</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['phone'] ?? 'Tidak tersedia'); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">📍 Alamat</div>
                    <div class="info-value"><?php echo htmlspecialchars($user['address'] ?? 'Tidak tersedia'); ?></div>
                </div>

                <div class="info-card">
                    <div class="info-label">📅 Bergabung Sejak</div>
                    <div class="info-value"><?php echo isset($user['created_at']) && !empty($user['created_at']) ? date('d F Y', strtotime($user['created_at'])) : 'Tidak tersedia'; ?></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="edit_profile.php" class="btn-custom btn-edit">✏️ Edit Profil</a>
                <a href="home.html" class="btn-custom btn-back">← Kembali ke Home</a>
            </div>

            <!-- Member Since -->
            <div class="member-since">
                Anda telah menjadi member sejak <?php echo isset($user['created_at']) && !empty($user['created_at']) ? date('d F Y', strtotime($user['created_at'])) : 'Tidak tersedia'; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 PT. KAI (Persero). All rights reserved.</p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
        crossorigin="anonymous"></script>
    <script>
        // Sidebar Toggle Functionality
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');

        // Toggle sidebar
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
        });

        // Close sidebar when clicking overlay
        sidebarOverlay.addEventListener('click', function () {
            sidebar.classList.remove('active');
            sidebarOverlay.classList.remove('active');
        });

        // Close sidebar when clicking a menu item
        const sidebarMenuItems = document.querySelectorAll('.sidebar-menu a');
        sidebarMenuItems.forEach(item => {
            item.addEventListener('click', function () {
                sidebar.classList.remove('active');
                sidebarOverlay.classList.remove('active');
            });
        });
    </script>
</body>

</html>
