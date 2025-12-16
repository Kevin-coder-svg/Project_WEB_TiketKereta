<?php
session_start();
// require_once 'db_config.php'; // Aktifkan jika file ini ada
// Koneksi Manual (Fallback)
$conn = new mysqli('localhost', 'root', '', 'tiket kereta');

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

// Cek Login
if (!isset($_SESSION['user_id'])) {
  header('Location: HTML_login.html'); exit;
}

$user_id = (int)$_SESSION['user_id'];

// Query Booking
$sql = "SELECT b.booking_id, b.schedule_id, b.total_amount, b.status, b.created_at, s.departure_time, s.arrival_time, t.train_name
  FROM bookings b
  LEFT JOIN schedules s ON b.schedule_id = s.schedule_id
  LEFT JOIN trains t ON s.train_id = t.train_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
  LIMIT 200";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo '<h3>Database prepare error:</h3><pre>' . htmlspecialchars($conn->error) . '</pre>'; exit;
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$bookings = [];
while ($r = $res->fetch_assoc()) $bookings[] = $r;
$stmt->close();

// Cek Tiket / Kursi
$hasTickets = false;
$check = $conn->query("SHOW TABLES LIKE 'tickets'");
if ($check && $check->num_rows > 0) $hasTickets = true;

$seatsMap = [];
if ($hasTickets && !empty($bookings)) {
  $ids = array_map(function($b){return (int)$b['booking_id'];}, $bookings);
  
  if (!empty($ids)) {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));
      $types = str_repeat('i', count($ids));
      $sql2 = "SELECT booking_id, seat_number FROM tickets WHERE booking_id IN (".$placeholders.")";
      $stmt2 = $conn->prepare($sql2);
      
      if (!$stmt2) {
        echo '<h3>Database prepare error:</h3><pre>' . htmlspecialchars($conn->error) . '</pre>'; exit;
      }
      
      if ($stmt2) {
        $refs = [];
        $refs[] = &$types;
        for ($i=0;$i<count($ids);$i++) $refs[] = &$ids[$i];
        call_user_func_array([$stmt2, 'bind_param'], $refs);
        $stmt2->execute();
        $r2 = $stmt2->get_result();
        while ($row = $r2->fetch_assoc()) {
          $bid = $row['booking_id'];
          $seatsMap[$bid][] = $row['seat_number'];
        }
        $stmt2->close();
      }
  }
}
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Riwayat Pemesanan - KAI Tiket</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <style>
    body {
      background-image: url('background.jpg'); /* Pastikan ada file background */
      background-size: cover;
      background-attachment: fixed;
      background-color: #f4f6f8; /* Fallback color */
      min-height: 100vh;
      margin: 0;
      padding: 0;
    }

    /* Navbar Styles */
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

    /* Sidebar Styles */
    .sidebar {
      position: fixed;
      left: -300px;
      top: 0;
      width: 300px;
      height: 100vh;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(13, 110, 253, 0.1) 100%);
      transition: left 0.3s ease;
      /* PERBAIKAN: z-index ditingkatkan agar di atas navbar (1020) */
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
      /* PERBAIKAN: z-index di bawah sidebar tapi di atas konten lain */
      z-index: 1999;
    }

    .sidebar-overlay.active {
      display: block;
    }

    /* Content Styling */
    .content-section {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 30px;
      margin-top: 40px;
      margin-bottom: 40px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
    }

    .list-group-item {
        border-left: 4px solid #0d6efd;
        margin-bottom: 10px;
        transition: transform 0.2s;
    }
    
    .list-group-item:hover {
        transform: translateX(5px);
        background-color: #f8f9fa;
    }

    footer {
        text-align: center;
        color: white;
        margin-top: 20px;
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
      <li><a href="home.php">🏠 Home</a></li>
      <li><a href="search.php">📅 Cari Jadwal</a></li>
      <li><a href="history.php" class="active">📋 Riwayat Pemesanan</a></li> <li><a href="profile.php">👤 Profil Saya</a></li>
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

  <div class="container" style="max-width:1000px">
    <div class="content-section">
      <h3 class="mb-4 border-bottom pb-2">📋 Riwayat Pemesanan</h3>
      
      <?php if (empty($bookings)): ?>
        <div class="alert alert-info text-center py-4">
            <h5>Belum ada riwayat pemesanan.</h5>
            <p>Yuk, pesan tiket kereta untuk perjalananmu sekarang!</p>
            <a href="search.php" class="btn btn-primary mt-2">Cari Jadwal Kereta</a>
        </div>
      <?php else: ?>
        <div class="list-group">
          <?php foreach ($bookings as $b): ?>
            <div class="list-group-item shadow-sm">
              <div class="d-flex w-100 justify-content-between align-items-center">
                <div>
                  <h5 class="mb-1 text-primary">
                    <?= htmlspecialchars($b['train_name'] ?? 'Kereta Tidak Dikenal') ?>
                  </h5>
                  <p class="mb-1">
                    <strong>Berangkat:</strong> <?= date('d M Y H:i', strtotime($b['departure_time'])) ?><br>
                    <strong>Tiba:</strong> <?= date('d M Y H:i', strtotime($b['arrival_time'])) ?>
                  </p>
                  <small class="text-muted">ID Booking: #<?= htmlspecialchars($b['booking_id']) ?></small>
                </div>
                <div class="text-end">
                  <?php 
                    $statusColor = match($b['status']) {
                        'CONFIRMED', 'PAID' => 'success',
                        'PENDING' => 'warning',
                        'CANCELLED' => 'danger',
                        default => 'secondary'
                    };
                  ?>
                  <span class="badge bg-<?= $statusColor ?> mb-2"><?= htmlspecialchars($b['status'] ?? '') ?></span>
                  <h6 class="mb-2">Rp <?= number_format((float)$b['total_amount'], 0, ',', '.') ?></h6>
                  <a class="btn btn-sm btn-outline-primary" href="booking_confirm.php?booking_id=<?= (int)$b['booking_id'] ?>">Detail Tiket</a>
                </div>
              </div>
              
              <div class="mt-2 pt-2 border-top">
                <small class="text-muted">
                  Jumlah Penumpang: 
                  <?php
                    $num = 1;
                    if (isset($b['seats']) && is_numeric($b['seats'])) {
                      $num = (int)$b['seats'];
                    } else if (!empty($seatsMap[$b['booking_id']])) {
                      $num = count($seatsMap[$b['booking_id']]);
                    }
                    echo htmlspecialchars($num);
                  ?>
                  <?php if (!empty($seatsMap[$b['booking_id']])): ?>
                    &nbsp;|&nbsp; Kursi: <strong><?= htmlspecialchars(implode(', ', $seatsMap[$b['booking_id']])) ?></strong>
                  <?php endif; ?>
                </small>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <footer>
    <p class="mb-0">&copy; 2025 PT. KAI (Persero). All rights reserved.</p>
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