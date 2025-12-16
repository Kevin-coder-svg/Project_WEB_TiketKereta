<?php
session_start();
include_once 'db_config.php';

// Get booking_id from GET
$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : 0;
if ($booking_id <= 0) {
    header('Location: search.php');
    exit;
}

// Fetch booking details
$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
$booking = null;
$tickets = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT b.booking_id, b.seats, b.total_amount, b.status, s.train_name, sch.departure_time, sch.arrival_time FROM bookings b JOIN schedules sch ON b.schedule_id = sch.schedule_id JOIN trains s ON sch.train_id = s.train_id WHERE b.booking_id = ?");
    $stmt->bind_param('i', $booking_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $booking = $res->fetch_assoc();
    $stmt->close();

    // Fetch tickets
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

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $payment_method = $_POST['payment_method'] ?? '';
    if ($payment_method) {
        // Simulate payment success
        if ($conn) {
            $conn->begin_transaction();
            try {
                // Get necessary data from booking and session
                $num_passengers = $booking['seats'] ?? count($_SESSION['selected_seats'] ?? []);
                $selected_seats = $_SESSION['selected_seats'] ?? [];

                // Re-fetch schedule_id from booking if not in session
                $schedule_id = null;
                $b_stmt = $conn->prepare("SELECT schedule_id, seats FROM bookings WHERE booking_id = ?");
                $b_stmt->bind_param('i', $booking_id);
                $b_stmt->execute();
                $b_res = $b_stmt->get_result();
                if ($b_row = $b_res->fetch_assoc()) {
                    $schedule_id = $b_row['schedule_id'];
                    $num_passengers = (int)$b_row['seats'];
                }
                $b_stmt->close();

                if ($schedule_id && !empty($selected_seats) && count($selected_seats) === $num_passengers) {
                    // Use real passenger data from session
                    $passengers_data = $_SESSION['passengers'] ?? [];

                    if (!empty($passengers_data) && count($passengers_data) === $num_passengers) {
                        $tstmt = $conn->prepare("INSERT INTO tickets (booking_id, seat_number, passenger_name, passenger_nik, phone_number) VALUES (?, ?, ?, ?, ?)");
                        foreach ($passengers_data as $index => $p_data) {
                            $seat = $selected_seats[$index];
                            // Note: 'nik' and 'phone' keys come from the form in booking_detail.php
                            $tstmt->bind_param('issss', $booking_id, $seat, $p_data['name'], $p_data['nik'], $p_data['phone']);
                            $tstmt->execute();
                        }
                        $tstmt->close();
                    } else {
                        // This would indicate a logic error, as session data should be consistent.
                        throw new Exception("Data penumpang sesi tidak cocok atau hilang.");
                    }
                }

                // Update booking status
                $ustmt = $conn->prepare("UPDATE bookings SET status = 'PAID' WHERE booking_id = ?");
                $ustmt->bind_param('i', $booking_id);
                $ustmt->execute();
                $ustmt->close();

                $conn->commit();

                // Clear session data after successful booking and payment
                unset($_SESSION['num_passengers'], $_SESSION['selected_seats'], $_SESSION['booking_schedule_id'], $_SESSION['passengers']);

            } catch (Exception $ex) {
                $conn->rollback();
                // Optional: Show an error message to the user
                // For now, we just won't redirect, and the form will be shown again.
            }
        }
        // Redirect to ticket detail
        header('Location: ticket_detail.php?booking_id=' . $booking_id);
        exit;
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pembayaran - KAI Tiket Kereta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f8; }
    .container { max-width: 600px; margin-top: 30px; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Pembayaran Tiket</h4>
        <p><strong>Jadwal:</strong> <?= htmlspecialchars($booking['train_name']) ?><br>
        <strong>Berangkat:</strong> <?= date('d/m/Y H:i', strtotime($booking['departure_time'])) ?><br>
        <strong>Total Bayar:</strong> Rp <?= number_format($booking['total_amount'], 0, ',', '.') ?><br>
        <strong>Jumlah Penumpang:</strong> <?= htmlspecialchars($booking['seats']) ?></p>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">Pilih Metode Pembayaran</label>
            <select name="payment_method" class="form-select" required>
              <option value="">Pilih...</option>
              <option value="bank_transfer">Transfer Bank</option>
              <option value="gopay">GoPay</option>
              <option value="ovo">OVO</option>
              <option value="dana">DANA</option>
            </select>
          </div>

          <button type="submit" class="btn btn-success">Konfirmasi Pembayaran</button>
          <a href="search.php" class="btn btn-secondary">Batal</a>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>