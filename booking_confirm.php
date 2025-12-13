<?php
// Booking confirmation page
include_once 'db_config.php';

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    http_response_code(404);
    echo "<h2>Booking tidak ditemukan (ID tidak valid)</h2>";
    exit;
}

$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
if (!$conn) {
    http_response_code(500);
    echo "<h2>Database tidak tersedia</h2>";
    exit;
}

// Fetch booking
$stmt = $conn->prepare("SELECT * FROM bookings WHERE booking_id = ? LIMIT 1");
$stmt->bind_param('i', $booking_id);
$stmt->execute();
$bres = $stmt->get_result();
$booking = $bres ? $bres->fetch_assoc() : null;
$stmt->close();

if (!$booking) {
    http_response_code(404);
    echo "<h2>Booking tidak ditemukan</h2>";
    exit;
}

// Normalize some fields (support older/newer schema)
$user_id = isset($booking['user_id']) ? $booking['user_id'] : null;
$schedule_id = isset($booking['schedule_id']) ? $booking['schedule_id'] : null;
$carriage_id = isset($booking['carriage_id']) ? $booking['carriage_id'] : null;
$seats = isset($booking['seats']) ? (int)$booking['seats'] : 1;
$passenger_name = $booking['passenger_name'] ?? null;
$total_amount = $booking['total_amount'] ?? ($booking['total_amount'] ?? 0);
$status = $booking['status'] ?? ($booking['payment_status'] ?? 'PENDING');
$booking_date = $booking['created_at'] ?? ($booking['booking_date'] ?? null);

// Fetch schedule + train
$schedule = null;
if ($schedule_id) {
    $q = $conn->prepare("SELECT s.*, t.train_name FROM schedules s LEFT JOIN trains t ON s.train_id = t.train_id WHERE s.schedule_id = ? LIMIT 1");
    $q->bind_param('i', $schedule_id);
    $q->execute();
    $sr = $q->get_result();
    $schedule = $sr ? $sr->fetch_assoc() : null;
    $q->close();
}

// Fetch carriage
$carriage = null;
if ($carriage_id) {
    $q2 = $conn->prepare("SELECT * FROM carriages WHERE carriage_id = ? LIMIT 1");
    $q2->bind_param('i', $carriage_id);
    $q2->execute();
    $cr = $q2->get_result();
    $carriage = $cr ? $cr->fetch_assoc() : null;
    $q2->close();
}

// Fetch user info (optional)
$user = null;
if ($user_id) {
    $q3 = $conn->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ? LIMIT 1");
    $q3->bind_param('i', $user_id);
    $q3->execute();
    $ur = $q3->get_result();
    $user = $ur ? $ur->fetch_assoc() : null;
    $q3->close();
}

// Render
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Konfirmasi Booking #<?= htmlspecialchars($booking_id) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>body{background:#f7f9fb;padding:24px}</style>
</head>
<body>
  <div class="container" style="max-width:900px">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Konfirmasi Pemesanan</h4>
        <p class="text-muted">Booking ID: <strong>#<?= htmlspecialchars($booking_id) ?></strong></p>

        <dl class="row">
          <dt class="col-sm-3">Status</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($status) ?></dd>

          <dt class="col-sm-3">Nama Pemesan</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($user['full_name'] ?? ($passenger_name ?? 'Tamu')) ?> <?= $user ? '<small class="text-muted">(' . htmlspecialchars($user['email']) . ')</small>' : '' ?></dd>

          <dt class="col-sm-3">Jumlah Kursi</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($seats) ?></dd>

          <dt class="col-sm-3">Total Bayar</dt>
          <dd class="col-sm-9">Rp <?= number_format((float)$total_amount,0,',','.') ?></dd>

          <?php if ($schedule): ?>
            <dt class="col-sm-3">Kereta</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($schedule['train_name'] ?? 'N/A') ?></dd>

            <dt class="col-sm-3">Berangkat / Tiba</dt>
            <dd class="col-sm-9"><?= htmlspecialchars(($schedule['departure_time'] ?? '') . ' → ' . ($schedule['arrival_time'] ?? '')) ?></dd>
          <?php endif; ?>

          <?php if ($carriage): ?>
            <dt class="col-sm-3">Carriage / Kelas</dt>
            <dd class="col-sm-9"><?= htmlspecialchars($carriage['name']) ?> (kapasitas <?= htmlspecialchars($carriage['capacity']) ?>)</dd>
          <?php endif; ?>

          <dt class="col-sm-3">Tanggal Pemesanan</dt>
          <dd class="col-sm-9"><?= htmlspecialchars($booking_date ?? '') ?></dd>
        </dl>

        <div class="mt-3">
          <a href="home.html" class="btn btn-secondary">Kembali ke Home</a>
          <a href="search.php" class="btn btn-outline-primary">Cari Jadwal Lain</a>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
