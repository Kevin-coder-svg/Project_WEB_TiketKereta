<?php
session_start();
include_once 'db_config.php';


$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    header('Location: search.php');
    exit;
}


$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
$booking = null;
$tickets = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT b.booking_id, b.total_amount, b.status, s.train_name, sch.departure_time, sch.arrival_time FROM bookings b JOIN schedules sch ON b.schedule_id = sch.schedule_id JOIN trains s ON sch.train_id = s.train_id WHERE b.booking_id = ?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $booking = $res->fetch_assoc();
    $stmt->close();

    $tstmt = $conn->prepare("SELECT passenger_name, seat_number FROM tickets WHERE booking_id = ?");
    $tstmt->bind_param('i', $booking_id);
    $tstmt->execute();
    $tres = $tstmt->get_result();
    while ($row = $tres->fetch_assoc()) {
        $tickets[] = $row;
    }
    $tstmt->close();
}
if (!$booking) {
    header('Location: search.php');
    exit;
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail Tiket - KAI Tiket Kereta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f8; }
    .container { max-width: 800px; margin-top: 30px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Detail Tiket</h4>
        <p><strong>Booking ID:</strong> <?= htmlspecialchars($booking['booking_id']) ?><br>
        <strong>Jadwal:</strong> <?= htmlspecialchars($booking['train_name']) ?><br>
        <strong>Berangkat:</strong> <?= date('d/m/Y H:i', strtotime($booking['departure_time'])) ?><br>
        <strong>Tiba:</strong> <?= date('d/m/Y H:i', strtotime($booking['arrival_time'])) ?><br>
        <strong>Status:</strong> <?= htmlspecialchars($booking['status']) ?></p>

        <h5>Detail Penumpang</h5>
        <div class="row">
          <?php foreach ($tickets as $index => $ticket): ?>
            <div class="col-md-6 mb-3">
              <div class="card">
                <div class="card-body">
                  <h6>Penumpang <?= $index + 1 ?></h6>
                  <p><strong>Nama:</strong> <?= htmlspecialchars($ticket['passenger_name']) ?><br>
                  <strong>No. Kursi:</strong> <?= htmlspecialchars($ticket['seat_number']) ?></p>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <p class="mt-3"><strong>Total Bayar:</strong> Rp <?= number_format($booking['total_amount'], 0, ',', '.') ?></p>

        <a href="home.php" class="btn btn-primary">Kembali ke Home</a>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>