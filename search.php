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
  <a href="home.html" class="back-arrow" title="Kembali ke Home" aria-label="Kembali ke Home">
    <!-- simple left arrow SVG -->
    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
      <path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"/>
    </svg>
  </a>
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
                  <th>Durasi</th>
                  <th>Kelas</th>
                  <th>Harga</th>
                  <th>Kursi</th>
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
                    <td><?= htmlspecialchars($duration) ?></td>
                    <td><?= htmlspecialchars(implode(', ', $classes)) ?></td>
                    <td>Rp <?= number_format($price,0,',','.') ?></td>
                    <td><?= htmlspecialchars($seats !== null ? $seats : 'N/A') ?></td>
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
                        <form method="get" action="booking.php" style="display:inline; margin:0;">
                          <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($scheduleId) ?>">
                          <input type="hidden" name="from" value="search">
                          <button type="submit" class="btn btn-sm btn-primary">Pilih</button>
                        </form>
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>