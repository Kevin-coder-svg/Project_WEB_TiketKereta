<?php
// Search page for train schedules backed by database
// Include DB config (expects `db_config.php` with $conn or get_db_connection())
include_once 'db_config.php';

// Get search params (use GET so clicking link or form submit works)
$origin = isset($_GET['origin']) ? trim($_GET['origin']) : '';
$destination = isset($_GET['destination']) ? trim($_GET['destination']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$class = isset($_GET['class']) ? trim($_GET['class']) : '';

$results = [];
if ($origin === '' && $destination === '' && $date === '' && $class === '') {
  // no search yet
  $results = null; // null means not searched
} else {
  // Build dynamic WHERE clause using station joins
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
  }

  // Base query: join schedules -> trains -> stations (origin/destination)
  $sql = "SELECT s.schedule_id, s.train_id, t.train_name, o.station_name AS origin_name, d.station_name AS destination_name, s.departure_time, s.arrival_time, s.price FROM `schedules` s JOIN `trains` t ON s.train_id = t.train_id LEFT JOIN `stations` o ON s.origin_station_id = o.station_id LEFT JOIN `stations` d ON s.destination_station_id = d.station_id";

  if (!empty($where)) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
  }
  $sql .= " ORDER BY s.departure_time ASC LIMIT 500";

  $conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
  if (!$conn) {
    $results = [];
    error_log('Database connection not available in search.php');
  } else {
    $stmt = $conn->prepare($sql);
    if ($stmt) {
      if (!empty($values)) {
        // bind_param requires references and first arg is types string
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

      // If class filter requested, remove schedules whose trains don't have that class in carriages
      if ($class !== '' && !empty($results)) {
        $filtered = [];
        $chkStmt = $conn->prepare("SELECT 1 FROM `carriages` WHERE train_id = ? AND name LIKE ? LIMIT 1");
        foreach ($results as $r) {
          $has = false;
          if ($chkStmt) {
            $like = "%$class%";
            $tid = (int)$r['train_id'];
            $chkStmt->bind_param('is', $tid, $like);
            $chkStmt->execute();
            $g = $chkStmt->get_result();
            if ($g && $g->fetch_row()) {
              $has = true;
            }
          }
          if ($has) $filtered[] = $r;
        }
        if ($chkStmt) $chkStmt->close();
        $results = $filtered;
      }
    } else {
      error_log('Prepare failed in search.php: ' . $conn->error);
      $results = [];
    }
  }
}
?>
<!doctype html>
<html lang="id">
<head>
  
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Cari Jadwal - KAI Tiket Kereta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f8; }
    .container { max-width: 1100px; margin-top: 30px; }
    .status-active { background: #d4edda; color: #155724; padding: 6px 10px; border-radius: 14px; }
    .status-delayed { background: #fff3cd; color: #856404; padding: 6px 10px; border-radius: 14px; }

    /* Modern fixed back arrow in top-left */
    .back-arrow {
      position: fixed;
      left: 16px;
      top: 16px;
      width: 52px;
      height: 52px;
      background: linear-gradient(135deg,#0d6efd 0%, #3b82f6 100%);
      color: #fff;
      border-radius: 12px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 6px 18px rgba(13,110,253,0.18);
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
      z-index: 9999;
      text-decoration: none;
      transition: transform 0.18s ease, box-shadow 0.18s ease;
    }

    .back-arrow:hover {
      transform: translateX(-6px) scale(1.03);
      box-shadow: 0 10px 26px rgba(13,110,253,0.26);
    }

    .back-arrow svg {
      width: 20px;
      height: 20px;
      fill: white;
    }

    @media (max-width: 576px) {
      .back-arrow { left: 10px; top: 10px; width:44px; height:44px; border-radius:10px; }
    }
  </style>
</head>
<body>
  <!-- Modern back arrow (top-left) -->
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
  <div class="container">
    <div class="card mb-4">
      <div class="card-body">
        <h4 class="card-title">Cari Jadwal Kereta</h4>
        <form method="get" class="row g-3">
          <div class="col-md-3">
            <label class="form-label">Stasiun Asal</label>
            <input class="form-control" name="origin" value="<?= htmlspecialchars($origin) ?>" placeholder="Cth: Jakarta">
          </div>
          <div class="col-md-3">
            <label class="form-label">Stasiun Tujuan</label>
            <input class="form-control" name="destination" value="<?= htmlspecialchars($destination) ?>" placeholder="Cth: Surabaya">
          </div>
          <div class="col-md-3">
            <label class="form-label">Tanggal</label>
            <input type="date" class="form-control" name="date" value="<?= htmlspecialchars($date) ?>">
          </div>
          <div class="col-md-2">
            <label class="form-label">Kelas</label>
            <select name="class" class="form-select">
              <option value="">Semua Kelas</option>
              <option value="Ekonomi" <?= $class === 'Ekonomi' ? 'selected' : '' ?>>Ekonomi</option>
              <option value="Bisnis" <?= $class === 'Bisnis' ? 'selected' : '' ?>>Bisnis</option>
              <option value="Eksekutif" <?= $class === 'Eksekutif' ? 'selected' : '' ?>>Eksekutif</option>
              <option value="Premium" <?= $class === 'Premium' ? 'selected' : '' ?>>Premium</option>
            </select>
          </div>
          <div class="col-md-1 d-flex align-items-end">
            <button class="btn btn-primary w-100">Cari</button>
          </div>
        </form>
      </div>
      </div>

      <div class="card">
      <div class="card-body">
        <h5 class="card-title">Hasil Pencarian</h5>
        <?php if ($results === null): ?>
          <p class="text-muted">Silakan isi kriteria pencarian dan klik "Cari" untuk melihat jadwal.</p>
        <?php elseif (empty($results)): ?>
          <p class="text-danger">Tidak ada jadwal yang sesuai dengan kriteria Anda.</p>
        <?php else: ?>
          <p class="mb-2">Menampilkan <strong><?= count($results) ?></strong> hasil.</p>
          <div class="table-responsive">
            <table class="table table-striped align-middle">
              <thead>
                <tr>
                  <th>Kereta</th>
                  <th>Rute</th>
                  <th>Berangkat</th>
                  <th>Tiba</th>
                  <th>Kelas</th>
                  <th>Harga</th>
                  <th>Status</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($results as $r):
                  // compute display fields
                  $trainName = isset($r['train_name']) ? $r['train_name'] : 'N/A';
                  // Ensure schedule_id is an integer
                  $scheduleId = isset($r['schedule_id']) ? (int)$r['schedule_id'] : 0;
                  $originName = isset($r['origin_name']) ? $r['origin_name'] : '';
                  $destName = isset($r['destination_name']) ? $r['destination_name'] : '';
                  $dep = isset($r['departure_time']) ? strtotime($r['departure_time']) : null;
                  $arr = isset($r['arrival_time']) ? strtotime($r['arrival_time']) : null;
                  $dateStr = $dep ? date('Y-m-d', $dep) : '';
                  $depTime = $dep ? date('H:i', $dep) : '';
                  $arrTime = $arr ? date('H:i', $arr) : '';
                  $duration = '';
                  if ($dep && $arr) {
                    $i1 = new DateTime($r['departure_time']);
                    $i2 = new DateTime($r['arrival_time']);
                    $iv = $i1->diff($i2);
                    $duration = ($iv->d ? $iv->d . 'd ' : '') . ($iv->h ? $iv->h . 'h ' : '') . ($iv->i ? $iv->i . 'm' : '');
                  }
                  $price = isset($r['price']) ? $r['price'] : 0;

                  // get classes and seats for this train
                  $classes = [];
                  $seats = null;
                  if (isset($conn) && $conn) {
                    $cstmt = $conn->prepare("SELECT name, capacity FROM carriages WHERE train_id = ?");
                    if ($cstmt) {
                      $tid = (int)$r['train_id'];
                      $cstmt->bind_param('i', $tid);
                      $cstmt->execute();
                      $cres = $cstmt->get_result();
                      $totalSeats = 0;
                      while ($crow = $cres->fetch_assoc()) {
                        $name = $crow['name'];
                        // derive class from carriage name (first word)
                        $parts = preg_split('/\s+/', trim($name));
                        if (!empty($parts)) $classes[] = $parts[0];
                        $totalSeats += (int)$crow['capacity'];
                      }
                      $seats = $totalSeats;
                      $classes = array_values(array_unique($classes));
                      $cstmt->close();
                    }
                  }
                ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($trainName) ?></strong><br>
                      <small>No: <?= htmlspecialchars($scheduleId) ?></small>
                    </td>
                    <td><?= htmlspecialchars($originName . ' - ' . $destName) ?><br><small><?= htmlspecialchars($dateStr) ?></small></td>
                    <td><strong><?= htmlspecialchars($depTime) ?></strong></td>
                    <td><strong><?= htmlspecialchars($arrTime) ?></strong></td>
                    <td><?= htmlspecialchars(implode(', ', $classes)) ?></td>
                    <td>Rp <?= number_format($price,0,',','.') ?></td>
                    <td>
                      <?php $status = ($dep && $dep > time()) ? 'Aktif' : 'Selesai';
                      if (strtolower($status) === 'aktif'): ?>
                        <span class="status-active"><?= htmlspecialchars($status) ?></span>
                      <?php else: ?>
                        <span class="status-delayed"><?= htmlspecialchars($status) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($scheduleId > 0): ?>
                        <a href="booking_detail.php?schedule_id=<?= htmlspecialchars($scheduleId) ?>" class="btn btn-sm btn-primary">Pilih</a>
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
  </div>

  <!-- Seat selection modal -->
  <div class="modal fade" id="seatModal" tabindex="-1" aria-labelledby="seatModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="seatModalLabel">Pilih Kursi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="seatDetails" class="mb-3">
            <!-- populated by JS -->
          </div>
          <div id="seatMap" class="d-flex flex-wrap gap-2" style="min-height:160px;">
            <!-- seat buttons inserted here -->
          </div>
          <p class="mt-3 text-muted small">Klik kursi untuk memilih. Anda dapat memilih lebih dari 1 kursi.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="button" id="confirmSeat" class="btn btn-primary" disabled>Konfirmasi & Lanjut</button>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      const seatModal = document.getElementById('seatModal');
      let selectedSeats = new Set();

      seatModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const scheduleId = button.getAttribute('data-schedule-id');
        const trainName = button.getAttribute('data-train-name');
        const origin = button.getAttribute('data-origin');
        const destination = button.getAttribute('data-destination');
        const departure = button.getAttribute('data-departure');
        const arrival = button.getAttribute('data-arrival');
        const price = button.getAttribute('data-price');
        const classes = button.getAttribute('data-classes');
        const seats = parseInt(button.getAttribute('data-seats') || '0', 10) || 40;

        // Populate details
        const details = document.getElementById('seatDetails');
        details.innerHTML = '<strong>'+escapeHtml(trainName)+'</strong> — '+escapeHtml(origin)+' → '+escapeHtml(destination)+'<br>'+
          'Berangkat: ' + new Date(departure).toLocaleString() + ' • Tiba: ' + new Date(arrival).toLocaleTimeString() + '<br>'+
          'Kelas: ' + escapeHtml(classes) + ' • Harga: Rp ' + Number(price).toLocaleString();

        // Render seat map as 4 rows (2 left, aisle, 2 right) with 20 seats per row (total 80)
        const map = document.getElementById('seatMap');
        map.innerHTML = '';
        const availableSeats = parseInt(seats, 10) || 0;
        const rowsPerSide = 2;
        const seatsPerRow = 20;
        const totalSeats = rowsPerSide * 2 * seatsPerRow; // 80

        function createSeatButton(num){
          const btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'btn btn-outline-secondary seat-btn';
          btn.style.width = '56px';
          btn.style.height = '56px';
          btn.style.display = 'inline-flex';
          btn.style.alignItems = 'center';
          btn.style.justifyContent = 'center';
          btn.style.padding = '6px';
          btn.dataset.seat = num;
          btn.innerHTML = '<div style="display:flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="10" rx="2"></rect><path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"></path></svg><div style="font-size:11px">'+num+'</div></div>';
          if (num > availableSeats) {
            btn.classList.add('disabled');
            btn.disabled = true;
            btn.title = 'Tidak tersedia';
            btn.style.opacity = '0.45';
          }
          btn.addEventListener('click', function(){
            if (btn.disabled || btn.classList.contains('booked')) return;
            const n = btn.dataset.seat;
            if (selectedSeats.has(n)) {
              selectedSeats.delete(n);
              btn.classList.remove('selected');
              btn.classList.remove('btn-primary');
              btn.classList.add('btn-outline-secondary');
            } else {
              selectedSeats.add(n);
              btn.classList.add('selected');
              btn.classList.remove('btn-outline-secondary');
              btn.classList.add('btn-primary');
            }
            document.getElementById('confirmSeat').disabled = selectedSeats.size === 0;
          });
          return btn;
        }

        // Build seat map as 20 vertical blocks (each block = 2 seats side-by-side)
        // Display blocks 1..10 in the left column and blocks 11..20 in the right column
        const totalBlocks = 20;
        const blocksPerColumn = 10;
        const seatsPerBlock = 2;
        let seatCounter = 1;

        const container = document.createElement('div');
        container.className = 'd-flex gap-4';

        const leftCol = document.createElement('div');
        leftCol.className = 'd-flex flex-column gap-3';
        for (let b = 0; b < blocksPerColumn; b++){
          const block = document.createElement('div');
          block.className = 'd-flex gap-2 align-items-center';
          for (let s = 0; s < seatsPerBlock; s++){
            block.appendChild(createSeatButton(seatCounter));
            seatCounter++;
          }
          leftCol.appendChild(block);
        }

        const rightCol = document.createElement('div');
        rightCol.className = 'd-flex flex-column gap-3';
        for (let b = 0; b < blocksPerColumn; b++){
          const block = document.createElement('div');
          block.className = 'd-flex gap-2 align-items-center';
          for (let s = 0; s < seatsPerBlock; s++){
            block.appendChild(createSeatButton(seatCounter));
            seatCounter++;
          }
          rightCol.appendChild(block);
        }

        // assemble two columns and enable vertical scrolling
        container.appendChild(leftCol);
        const spacer = document.createElement('div'); spacer.style.width = '20px'; container.appendChild(spacer);
        container.appendChild(rightCol);
        map.style.maxHeight = '520px';
        map.style.overflowY = 'auto';
        map.appendChild(container);

        // fetch already booked seats for this schedule and mark them
        (function markBooked(){
          const bookedUrl = 'get_booked_seats.php?schedule_id=' + encodeURIComponent(scheduleId);
          fetch(bookedUrl).then(r=>{
            if (!r.ok) return [];
            return r.json();
          }).then(data=>{
            if (!Array.isArray(data)) return;
            data.forEach(function(s){
              const btn = map.querySelector('.seat-btn[data-seat="'+s+'"]');
              if (btn) {
                btn.classList.add('booked');
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-danger');
                btn.disabled = true;
                btn.title = 'Sudah dibooking';
              }
            });
          }).catch(()=>{});
        })();

        // attach confirm handler (store scheduleId in dataset)
        const confirm = document.getElementById('confirmSeat');
        confirm.dataset.scheduleId = scheduleId;
        confirm.onclick = function(){
          if (selectedSeats.size === 0) return;
          // create form and submit GET to booking.php with selected_seats[] so user can fill passenger data
          const f = document.createElement('form');
          f.method = 'get';
          f.action = 'booking.php';
          const si = document.createElement('input'); si.type='hidden'; si.name='schedule_id'; si.value = scheduleId; f.appendChild(si);
          const from = document.createElement('input'); from.type='hidden'; from.name='from'; from.value='search'; f.appendChild(from);
          selectedSeats.forEach(function(seat){
            const inp = document.createElement('input'); inp.type='hidden'; inp.name='selected_seats[]'; inp.value = seat; f.appendChild(inp);
          });
          document.body.appendChild(f);
          f.submit();
        };

        // reset selection state when modal is closed
        selectedSeats.clear();
        document.getElementById('confirmSeat').disabled = true;
      });

      function escapeHtml(text){
        return String(text)
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      }
    })();
      
  
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