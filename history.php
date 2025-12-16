<?php
session_start();
require_once 'db_config.php';
if (!isset($_SESSION['user_id'])) {
  header('Location: HTML_login.html'); exit;
}
$user_id = (int)$_SESSION['user_id'];
$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
if (!$conn) die('DB connection error.');

$sql = "SELECT b.booking_id, b.schedule_id, b.total_amount, b.status, b.created_at, s.departure_time, s.arrival_time, t.train_name
  FROM bookings b
  LEFT JOIN schedules s ON b.schedule_id = s.schedule_id
  LEFT JOIN trains t ON s.train_id = t.train_id
  WHERE b.user_id = ?
  ORDER BY b.created_at DESC
  LIMIT 200";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  // show DB error to help debugging
  echo '<h3>Database prepare error:</h3><pre>' . htmlspecialchars($conn->error) . '</pre>'; exit;
}
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
$bookings = [];
while ($r = $res->fetch_assoc()) $bookings[] = $r;
$stmt->close();

// For each booking, fetch seat numbers from tickets if present
$hasTickets = false;
$check = $conn->query("SHOW TABLES LIKE 'tickets'");
if ($check && $check->num_rows > 0) $hasTickets = true;

$seatsMap = [];
if ($hasTickets && !empty($bookings)) {
  $ids = array_map(function($b){return (int)$b['booking_id'];}, $bookings);
  $placeholders = implode(',', array_fill(0, count($ids), '?'));
  $types = str_repeat('i', count($ids));
  $sql2 = "SELECT booking_id, seat_number FROM tickets WHERE booking_id IN (".$placeholders.")";
  $stmt2 = $conn->prepare($sql2);
  if (!$stmt2) {
    echo '<h3>Database prepare error:</h3><pre>' . htmlspecialchars($conn->error) . '</pre>'; exit;
  }
  if ($stmt2) {
    // bind dynamic
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

?><!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Riwayat Pemesanan</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container" style="max-width:1000px">
    <h3>Riwayat Pemesanan</h3>
    <?php if (empty($bookings)): ?>
      <div class="alert alert-info">Belum ada pemesanan.</div>
    <?php else: ?>
      <div class="list-group">
        <?php foreach ($bookings as $b): ?>
          <div class="list-group-item">
            <div class="d-flex justify-content-between">
              <div>
                <strong>#<?= htmlspecialchars($b['booking_id']) ?></strong> — <?= htmlspecialchars($b['train_name'] ?? 'N/A') ?>
                <div class="small text-muted"><?= htmlspecialchars($b['departure_time'] ?? '') ?> → <?= htmlspecialchars($b['arrival_time'] ?? '') ?></div>
              </div>
              <div class="text-end">
                <div>Status: <span class="badge bg-secondary"><?= htmlspecialchars($b['status'] ?? '') ?></span></div>
                <div>Total: Rp <?= number_format((float)$b['total_amount'],0,',','.') ?></div>
                <div class="mt-2"><a class="btn btn-sm btn-outline-primary" href="booking_confirm.php?booking_id=<?= (int)$b['booking_id'] ?>">Lihat</a></div>
              </div>
            </div>
            <div class="mt-2">Jumlah kursi: <?php
                $num = 1;
                if (isset($b['seats']) && is_numeric($b['seats'])) {
                  $num = (int)$b['seats'];
                } else if (!empty($seatsMap[$b['booking_id']])) {
                  $num = count($seatsMap[$b['booking_id']]);
                }
                echo htmlspecialchars($num);
              ?>
              <?php if (!empty($seatsMap[$b['booking_id']])): ?>
                — Kursi: <?= htmlspecialchars(implode(', ', $seatsMap[$b['booking_id']])) ?>
              <?php endif; ?>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</body>
</html>