<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'tiket kereta');

if ($conn->connect_error) {
    die('Koneksi database gagal: ' . $conn->connect_error);
}

$origin = isset($_GET['origin']) ? trim($_GET['origin']) : '';
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';


$today = date('Y-m-d'); 
$dateInput = isset($_GET['date']) ? trim($_GET['date']) : '';

if ($dateInput !== '' && $dateInput < $today) {
    $date = $today;
} else {
    $date = $dateInput;
}

$results = [];

$where = [];
$types = '';
$values = [];
if ($origin !== '') {
    $where[] = "(o.station_name LIKE ? OR o.code LIKE ? OR o.city LIKE ? )";
    $types .= 'sss';
    $values[] = "%$origin%";
    $values[] = "%$origin%";
    $values[] = "%$origin%";
}

if ($destination !== '') {
    $where[] = "(d.station_name LIKE ? OR d.code LIKE ? OR d.city LIKE ? )";
    $types .= 'sss';
    $values[] = "%$destination%";
    $values[] = "%$destination%";
    $values[] = "%$destination%";
}

if ($date !== '') {
    $where[] = "DATE(s.departure_time) = ?";
    $types .= 's';
    $values[] = $date;
} else {
    $where[] = "DATE(s.departure_time) >= ?";
    $types .= 's';
    $values[] = $today;
}

$sql = "SELECT s.schedule_id, s.train_id, t.train_name, o.station_name AS origin_name, d.station_name AS destination_name, s.departure_time, s.arrival_time, s.price 
        FROM `schedules` s 
        JOIN `trains` t ON s.train_id = t.train_id 
        LEFT JOIN `stations` o ON s.origin_station_id = o.station_id 
        LEFT JOIN `stations` d ON s.destination_station_id = d.station_id";

if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}
$sql .= " ORDER BY s.departure_time ASC LIMIT 500";

if ($conn) {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        if (!empty($values)) {
            $bind_names = [];
            $bind_names[] = $types;
            for ($i = 0; $i < count($values); $i++) {
                $bindVar = 'bind' . $i;
                $$bindVar = $values[$i];
                $bind_names[] = &$$bindVar;
            }
            call_user_func_array([$stmt, 'bind_param'], $bind_names);
        }

        $stmt->execute();
        $res = $stmt->get_result();
        if ($res) {
            while ($row = $res->fetch_assoc()) {
                $results[] = $row;
            }
        }
        $stmt->close();
    }
}
?>

<!doctype html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cari Jadwal - KAI Tiket Kereta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet"
    integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <style>
    body {
      background-image: url('background.jpg');
      background-size: cover;
      background-attachment: fixed;
      background-color: #f4f6f8;
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
      position: fixed; left: -300px; top: 0; width: 300px; height: 100vh;
      background: linear-gradient(180deg, rgba(0, 0, 0, 0.95) 0%, rgba(13, 110, 253, 0.1) 100%);
      transition: left 0.3s ease; z-index: 2000; overflow-y: auto; border-right: 2px solid #0d6efd; padding-top: 20px;
    }
    .sidebar.active { left: 0; }
    .sidebar-header { color: white; font-size: 1.3rem; padding: 20px; border-bottom: 1px solid #0d6efd; margin-bottom: 20px; font-weight: bold; }
    .sidebar-menu { list-style: none; padding: 0; margin: 0; }
    .sidebar-menu li { border-bottom: 1px solid rgba(13, 110, 253, 0.2); }
    .sidebar-menu a { display: block; padding: 15px 25px; color: white; text-decoration: none; transition: all 0.2s; font-size: 1.1rem; }
    .sidebar-menu a:hover { background-color: #0d6efd; padding-left: 30px; }
    .sidebar-menu a.active { background-color: #0d6efd; border-left: 4px solid white; }
    .sidebar-toggle { background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer; padding: 0; margin-right: 15px; }
    .sidebar-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); display: none; z-index: 1999; }
    .sidebar-overlay.active { display: block; }

    .content-section {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      padding: 30px;
      margin-top: 40px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
      margin-bottom: 40px;
    }

    .status-active { background: #d4edda; color: #155724; padding: 4px 10px; border-radius: 14px; font-size: 0.9rem;}
    .status-delayed { background: #fff3cd; color: #856404; padding: 4px 10px; border-radius: 14px; font-size: 0.9rem;}

    footer { text-align: center; color: white; margin-top: 20px; padding: 20px; background-color: rgba(0, 0, 0, 0.8); }
  </style>
</head>

<body>
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <div class="sidebar" id="sidebar">
    <div class="sidebar-header">☰ Menu</div>
    <ul class="sidebar-menu">
      <li><a href="home.php">🏠 Home</a></li>
      <li><a href="search.php" class="active">📅 Cari Jadwal</a></li> 
      <li><a href="history.php">📋 Riwayat Pemesanan</a></li>
      <li><a href="profile.php">👤 Profil Saya</a></li>
      <li><a href="QnA.php">💭 QnA</a></li>
      <li><a href="logout.php" style="color: #ff6b6b;">🚪 Logout</a></li>
    </ul>
  </div>

  <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
    <div class="container-fluid">
      <button class="sidebar-toggle" id="sidebarToggle" type="button">☰</button>
      <a class="navbar-brand fw-bold" href="home.php">🚂 KAI - Tiket Kereta</a>
    </div>
  </nav>

  <div class="container" style="max-width: 1100px;">
    
    <div class="content-section">
        <h4 class="mb-4 text-primary">🔍 Cari Jadwal Kereta</h4>
        <form method="get" class="row g-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Stasiun Asal</label>
            <input class="form-control" name="origin" value="<?= htmlspecialchars($origin) ?>" placeholder="Cth: Gambir">
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Stasiun Tujuan</label>
            <input class="form-control" name="destination" value="<?= htmlspecialchars($destination) ?>" placeholder="Cth: Bandung">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-bold">Tanggal</label>
            <input type="date" class="form-control" name="date" 
                   value="<?= htmlspecialchars($date) ?>" 
                   min="<?= date('Y-m-d') ?>">
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100 fw-bold">Cari</button>
          </div>
        </form>
    </div>

    <div class="content-section">
        <h4 class="mb-3">Hasil Pencarian</h4>
        
        <?php if (empty($results)): ?>
          <div class="alert alert-danger">
            ❌ Tidak ada jadwal yang ditemukan. Coba ubah tanggal atau rute pencarian.
          </div>
        <?php else: ?>
          <?php if($origin === '' && $destination === '' && $dateInput === ''): ?>
             <div class="alert alert-info border-0 bg-info-subtle">
                <strong>✨ Menampilkan semua jadwal perjalanan yang tersedia mulai hari ini.</strong>
             </div>
          <?php else: ?>
             <p class="text-muted mb-3">Ditemukan <strong><?= count($results) ?></strong> jadwal sesuai pencarian.</p>
          <?php endif; ?>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Kereta</th>
                  <th>Rute</th>
                  <th>Tanggal</th>
                  <th>Jam</th>
                  <th>Harga</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($results as $r):
                  $trainName = $r['train_name'] ?? 'N/A';
                  $scheduleId = (int)($r['schedule_id'] ?? 0);
                  $originName = $r['origin_name'] ?? '';
                  $destName = $r['destination_name'] ?? '';
                  $dep = isset($r['departure_time']) ? strtotime($r['departure_time']) : null;
                  $arr = isset($r['arrival_time']) ? strtotime($r['arrival_time']) : null;
                  
    
                  $dateStr = $dep ? date('d M Y', $dep) : ''; 
                  $depTime = $dep ? date('H:i', $dep) : '';   
                  $arrTime = $arr ? date('H:i', $arr) : '';   
                  $price = $r['price'] ?? 0;
                ?>
                  <tr>
                    <td>
                      <span class="fw-bold text-primary"><?= htmlspecialchars($trainName) ?></span><br>
                      <small class="text-muted">ID: <?= htmlspecialchars($scheduleId) ?></small>
                    </td>
                    <td>
                        <?= htmlspecialchars($originName) ?> ➝ <?= htmlspecialchars($destName) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($dateStr) ?>
                    </td>
                    <td>
                        <?= htmlspecialchars($depTime) ?> <span class="text-muted">-</span> <?= htmlspecialchars($arrTime) ?>
                    </td>
                    <td class="fw-bold text-success">Rp <?= number_format($price, 0, ',', '.') ?></td>
                    <td>
                      <?php 
                        $status = ($dep && $dep > time()) ? 'Tersedia' : 'Selesai';
                        $statusClass = ($status == 'Tersedia') ? 'status-active' : 'status-delayed';
                      ?>
                      <span class="<?= $statusClass ?>"><?= $status ?></span>
                    </td>
                    <td>
                      <?php if ($scheduleId > 0 && $status == 'Tersedia'): ?>
                        <a href="booking_detail.php?schedule_id=<?= $scheduleId ?>" class="btn btn-sm btn-primary">Pilih</a>
                      <?php else: ?>
                        <button class="btn btn-sm btn-secondary" disabled>—</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
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