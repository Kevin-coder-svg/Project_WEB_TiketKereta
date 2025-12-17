<?php
session_start();


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
      margin-left: 0 !important;
    }

    
    .sidebar {
      position: fixed;
      left: -300px;
      top: 0;
      width: 300px;
      height: 100vh;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(13, 110, 253, 0.1) 100%);
      transition: left 0.3s ease;
      z-index: 2000;
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


    .sidebar-toggle {
      background: none;
      border: none;
      color: white;
      font-size: 1.5rem;
      cursor: pointer;
      padding: 0;
      margin-right: 15px;
    }


    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      display: none;
      
      z-index: 1999;
    }

    .sidebar-overlay.active {
      display: block;
    }

    /* Content Styling */
    .content-section {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 40px;
      margin-top: 40px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    }

    .welcome-card {
      background: linear-gradient(135deg, #0b5ed7 0%, #0d6efd 100%);
      color: white;
      border-radius: 12px;
      padding: 40px;
      margin-bottom: 30px;
      box-shadow: 0 4px 15px rgba(13, 110, 253, 0.3);
      text-align: center;
    }

    .feature-card {
      background: #fff;
      border: 1px solid #e9ecef;
      border-radius: 12px;
      padding: 25px;
      transition: all 0.3s ease;
      height: 100%;
      text-align: center;
    }

    .feature-card:hover {
      transform: translateY(-8px);
      border-color: #0d6efd;
      box-shadow: 0 10px 25px rgba(13, 110, 253, 0.15);
    }

    .feature-card h5 {
        margin-top: 10px;
        margin-bottom: 15px;
        font-weight: 600;
        color: #333;
    }

    .feature-link {
        text-decoration: none;
        color: inherit;
        display: block;
        height: 100%;
    }

    .footer {
        text-align: center;
        color: white;
        margin-top: 50px;
        padding: 20px;
        background-color: rgba(0, 0, 0, 0.8);
    }
  </style>
</head>

<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">
      ☰ Menu
    </div>
    <ul class="sidebar-menu">
      <li><a href="home.php" class="active">🏠 Home</a></li>
      <li><a href="search.php">📅 Cari Jadwal</a></li>
      <li><a href="history.php">📋 Riwayat Pemesanan</a></li>
      <li><a href="profile.php">👤 Profil Saya</a></li>
      <li><a href="QnA.php">💭 QnA</a></li>
      <li><a href="logout.php" style="color: #ff6b6b;">🚪 Logout</a></li>
    </ul>
  </div>

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

  <div class="container">
    <div class="content-section">
      <div class="welcome-card">
        <h1 class="fw-bold">Selamat Datang, <?php echo htmlspecialchars($userName); ?>!</h1>
        <p class="lead mb-2">Mau pergi ke mana hari ini?</p>
        <?php if ($userEmail): ?>
          <small style="opacity: 0.8;">Masuk sebagai: <?php echo htmlspecialchars($userEmail); ?></small>
        <?php endif; ?>
      </div>

      <h4 class="mb-4 text-center fw-bold text-secondary">Layanan Kami</h4>
      <div class="row g-4 justify-content-center">
        
        <div class="col-lg-4 col-md-6 col-sm-12">
            <a href="search.php" class="feature-link">
                <div class="feature-card">
                    <div class="display-4 mb-3">📅</div>
                    <h5>Cari Jadwal Kereta</h5>
                    <p class="text-muted">
                    Cek ketersediaan kursi dan jadwal keberangkatan ke berbagai tujuan.
                    </p>
                </div>
            </a>
        </div>

        <div class="col-lg-4 col-md-6 col-sm-12">
            <a href="history.php" class="feature-link">
                <div class="feature-card">
                    <div class="display-4 mb-3">📋</div>
                    <h5>Riwayat Pemesanan</h5>
                    <p class="text-muted">
                    Lihat status pembayaran dan tiket elektronik perjalanan Anda sebelumnya.
                    </p>
                </div>
            </a>
        </div>

        <div class="col-lg-4 col-md-6 col-sm-12">
            <a href="QnA.php" class="feature-link">
                <div class="feature-card">
                    <div class="display-4 mb-3">💭</div>
                    <h5>Pusat Bantuan (QnA)</h5>
                    <p class="text-muted">
                    Temukan jawaban atas pertanyaan umum seputar layanan kereta api.
                    </p>
                </div>
            </a>
        </div>
      </div>

      <div class="mt-5 pt-4 border-top text-center">
        <h5 class="mb-3 text-muted">Aksi Cepat</h5>
        <div class="d-flex justify-content-center gap-3">
            <a href="search.php" class="btn btn-primary btn-lg px-4">Mulai Pencarian</a>
            <a href="profile.php" class="btn btn-outline-secondary btn-lg px-4">Profil Saya</a>
        </div>
      </div>
    </div>
  </div>

  <footer class="footer">
    <p class="mb-0">&copy; 2025 PT. KAI (Persero). All rights reserved.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"
    integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI"
    crossorigin="anonymous"></script>
  <script>
   
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebarOverlay = document.getElementById('sidebarOverlay');

    
    sidebarToggle.addEventListener('click', function () {
      sidebar.classList.toggle('active');
      sidebarOverlay.classList.toggle('active');
    });

   
    sidebarOverlay.addEventListener('click', function () {
      sidebar.classList.remove('active');
      sidebarOverlay.classList.remove('active');
    });

    
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