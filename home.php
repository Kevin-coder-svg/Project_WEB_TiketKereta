<?php
session_start();

// Cek apakah user sudah login
if (!isset($_SESSION['loggedin']) || !$_SESSION['loggedin']) {
    header('Location: login.php');
    exit();
}

$userName = $_SESSION['full_name'] ?? $_SESSION['email'] ?? 'Pengguna';
$userEmail = $_SESSION['email'] ?? '';
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Home - KAI Tiket Kereta</title>
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

    .nav-link {
      margin: 0 5px;
      transition: all 0.2s;
      font-weight: 500;
    }

    .nav-link:hover {
      color: #0d6efd !important;
      transform: translateY(-2px);
    }

    .nav-link.active {
      color: #0d6efd !important;
      border-bottom: 2px solid #0d6efd;
    }

    .dropdown-menu {
      background-color: rgba(0, 0, 0, 0.95);
      border: 1px solid #0d6efd;
      border-radius: 6px;
    }

    .dropdown-item {
      color: white;
      transition: all 0.2s;
    }

    .dropdown-item:hover {
      background-color: #0d6efd;
      color: white;
    }

    .content-section {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 8px;
      padding: 30px;
      margin-top: 30px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }

    .welcome-card {
      background: linear-gradient(135deg, #0b5ed7 0%, #0d6efd 100%);
      color: white;
      border-radius: 8px;
      padding: 30px;
      margin-bottom: 20px;
    }

    .feature-card {
      border: 1px solid #e9ecef;
      border-radius: 8px;
      padding: 20px;
      margin-bottom: 15px;
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .feature-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
    }
    .feature-link {
    text-decoration: none;
    color: inherit;
    display: block;
    }

    .feature-link:hover {
    color: inherit;
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
      <li><a href="home.php" class="active">🏠 Home</a></li>
      <li><a href="search.php">🔍 Cari Jadwal</a></li>
      <li><a href="booking.php">🎫 Pesan Tiket</a></li>
      <li><a href="history.php">📋 Riwayat Pemesanan</a></li>
      <li><a href="profile.php">👤 Profil Saya</a></li>
      <li><a href="QnA.php">💭 QnA</a></li>
      <li><a href="logout.php" style="color: #ff6b6b;">🚪 Logout</a></li>
    </ul>
  </div>

  <!-- Navigation -->
  <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
      <button class="sidebar-toggle" id="sidebarToggle" type="button">
        ☰
      </button>
      <a class="navbar-brand fw-bold" href="home.php">
        🚂 KAI - Tiket Kereta
      </a>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container">
    <div class="content-section">
      <!-- Welcome Card -->
      <div class="welcome-card">
        <h1>Selamat Datang, <?php echo htmlspecialchars($userName); ?>!</h1>
        <p class="mb-0">Nikmati kemudahan dalam memesan tiket kereta online.</p>
        <?php if ($userEmail): ?>
          <small class="text-light">Email: <?php echo htmlspecialchars($userEmail); ?></small>
        <?php endif; ?>
      </div>

      <!-- Features -->
      <h3 class="mb-4">Fitur Tersedia</h3>
      <div class="row g-3">
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="search.php" class="feature-link">
                <div class="feature-card h-100">
                    <h5>📅 Cari Jadwal Kereta</h5>
                    <p class="text-muted mb-0">
                    Lihat jadwal kereta dari berbagai rute dan pilih yang sesuai dengan kebutuhan Anda.
                    </p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="booking.php" class="feature-link">
                <div class="feature-card h-100">
                    <h5>🎫 Pesan Tiket</h5>
                    <p class="text-muted mb-0">
                    Pesan tiket dengan mudah dan dapatkan konfirmasi langsung ke email Anda.
                    </p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="history.php" class="feature-link">
                <div class="feature-card h-100">
                    <h5>📋 Riwayat Pemesanan</h5>
                    <p class="text-muted mb-0">
                    Kelola dan lihat semua riwayat pemesanan tiket Anda.
                    </p>
                </div>
            </a>
        </div>
        <div class="col-lg-3 col-md-6 col-sm-12">
            <a href="QnA.php" class="feature-link">
                <div class="feature-card h-100">
                    <h5>💭 QnA</h5>
                    <p class="text-muted mb-0">
                    Ajukan pertanyaan dan lihat jawaban seputar layanan kereta.
                    </p>
                </div>
            </a>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="mt-5 pt-4 border-top">
        <h4 class="mb-3">Aksi Cepat</h4>
        <a href="search.php" class="btn btn-primary me-2">Cari Jadwal</a>
        <a href="history.php" class="btn btn-outline-primary">Lihat Pemesanan</a>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="text-center text-white mt-5 py-4" style="background-color: rgba(0, 0, 0, 0.8);">
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