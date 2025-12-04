<?php
// Simple server-side search page for train schedules
// Data sample - in production this should come from a database
$schedules = [
    [
        'id' => 1,
        'name' => 'Argo Bromo Anggrek',
        'route' => 'Jakarta - Surabaya',
        'origin' => 'Jakarta',
        'destination' => 'Surabaya',
        'date' => '2025-12-05',
        'departure' => '18:00',
        'arrival' => '06:30',
        'duration' => '12h 30m',
        'class' => 'Eksekutif',
        'price' => 450000,
        'seats' => 25,
        'status' => 'Aktif'
    ],
    [
        'id' => 2,
        'name' => 'Bima',
        'route' => 'Jakarta - Surabaya',
        'origin' => 'Jakarta',
        'destination' => 'Surabaya',
        'date' => '2025-12-05',
        'departure' => '20:00',
        'arrival' => '08:00',
        'duration' => '12h',
        'class' => 'Bisnis',
        'price' => 350000,
        'seats' => 15,
        'status' => 'Aktif'
    ],
    [
        'id' => 3,
        'name' => 'Gajayana',
        'route' => 'Jakarta - Malang',
        'origin' => 'Jakarta',
        'destination' => 'Malang',
        'date' => '2025-12-05',
        'departure' => '08:15',
        'arrival' => '14:45',
        'duration' => '6h 30m',
        'class' => 'Ekonomi',
        'price' => 150000,
        'seats' => 40,
        'status' => 'Aktif'
    ],
    [
        'id' => 4,
        'name' => 'Eksekutif Brantas',
        'route' => 'Bandung - Yogyakarta',
        'origin' => 'Bandung',
        'destination' => 'Yogyakarta',
        'date' => '2025-12-05',
        'departure' => '10:00',
        'arrival' => '15:30',
        'duration' => '5h 30m',
        'class' => 'Eksekutif',
        'price' => 280000,
        'seats' => 10,
        'status' => 'Tunda'
    ],
    [
        'id' => 5,
        'name' => 'Mutiara Timur',
        'route' => 'Semarang - Jakarta',
        'origin' => 'Semarang',
        'destination' => 'Jakarta',
        'date' => '2025-12-05',
        'departure' => '22:00',
        'arrival' => '04:00',
        'duration' => '6h',
        'class' => 'Premium',
        'price' => 520000,
        'seats' => 8,
        'status' => 'Aktif'
    ]
];

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
    // filter schedules
    foreach ($schedules as $s) {
        $matchOrigin = $origin === '' || stripos($s['origin'], $origin) !== false || stripos($s['route'], $origin) !== false;
        $matchDestination = $destination === '' || stripos($s['destination'], $destination) !== false || stripos($s['route'], $destination) !== false;
        $matchDate = $date === '' || $s['date'] === $date;
        $matchClass = $class === '' || strcasecmp($s['class'], $class) === 0;

        if ($matchOrigin && $matchDestination && $matchDate && $matchClass) {
            $results[] = $s;
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

      <!-- Tombol kembali ke Home -->
      <div class="mb-3">
        <a href="home.html" class="btn btn-outline-secondary">&larr; Kembali ke Home</a>
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
                <?php foreach ($results as $r): ?>
                  <tr>
                    <td>
                      <strong><?= htmlspecialchars($r['name']) ?></strong><br>
                      <small>No: <?= $r['id'] ?></small>
                    </td>
                    <td><?= htmlspecialchars($r['route']) ?><br><small><?= htmlspecialchars($r['date']) ?></small></td>
                    <td><strong><?= htmlspecialchars($r['departure']) ?></strong></td>
                    <td><strong><?= htmlspecialchars($r['arrival']) ?></strong></td>
                    <td><?= htmlspecialchars($r['duration']) ?></td>
                    <td><?= htmlspecialchars($r['class']) ?></td>
                    <td>Rp <?= number_format($r['price'],0,',','.') ?></td>
                    <td><?= htmlspecialchars($r['seats']) ?></td>
                    <td>
                      <?php if (strtolower($r['status']) === 'aktif'): ?>
                        <span class="status-active"><?= htmlspecialchars($r['status']) ?></span>
                      <?php else: ?>
                        <span class="status-delayed"><?= htmlspecialchars($r['status']) ?></span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <a href="booking.php?train_id=<?= $r['id'] ?>" class="btn btn-sm btn-success">Pesan</a>
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
