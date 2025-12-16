<?php
session_start();
include_once 'db_config.php';

// Get schedule_id from GET
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
if ($schedule_id <= 0 || !isset($_SESSION['num_passengers']) || !isset($_SESSION['booking_schedule_id']) || $_SESSION['booking_schedule_id'] != $schedule_id) {
    header('Location: search.php');
    exit;
}

$num_passengers = $_SESSION['num_passengers'];

// Fetch schedule details
$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
$schedule = null;
$carriages = [];
if ($conn) {
    $stmt = $conn->prepare("SELECT s.schedule_id, s.train_id, t.train_name, t.total_carriages, o.station_name AS origin_name, d.station_name AS destination_name, s.departure_time, s.arrival_time, s.price FROM schedules s JOIN trains t ON s.train_id = t.train_id LEFT JOIN stations o ON s.origin_station_id = o.station_id LEFT JOIN stations d ON s.destination_station_id = d.station_id WHERE s.schedule_id = ?");
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $schedule = $res->fetch_assoc();
    $stmt->close();

    // Get carriages
    $cstmt = $conn->prepare("SELECT carriage_id, name, capacity FROM carriages WHERE train_id = ?");
    $cstmt->bind_param('i', $schedule['train_id']);
    $cstmt->execute();
    $cres = $cstmt->get_result();
    $carriages = $cres->fetch_all(MYSQLI_ASSOC);
    $cstmt->close();

    // Get booked seats
    $booked_seats = [];
    if ($conn) {
        $bstmt = $conn->prepare("SELECT t.seat_number FROM tickets t JOIN bookings b ON t.booking_id = b.booking_id WHERE b.schedule_id = ? AND b.status IN ('PENDING','CONFIRMED','PAID')");
        if ($bstmt) {
            $bstmt->bind_param('i', $schedule_id);
            $bstmt->execute();
            $bres = $bstmt->get_result();
            while ($row = $bres->fetch_assoc()) {
                $booked_seats[] = (int)$row['seat_number'];
            }
            $bstmt->close();
        }
    }
}
if (!$schedule) {
    header('Location: search.php');
    exit;
}

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_seats = isset($_POST['selected_seats']) ? $_POST['selected_seats'] : [];
    if (count($selected_seats) != $num_passengers) {
        $error = 'Pilih kursi sebanyak jumlah penumpang (' . $num_passengers . ').';
    } else {
        // Store selected seats in session and redirect to booking.php
        $_SESSION['selected_seats'] = $selected_seats;
        header('Location: booking.php?schedule_id=' . $schedule_id);
        exit;
    }
}

// Get total seats
$total_seats = (int)$schedule['total_carriages'];
$available_seats = $total_seats; // Simplified, assume all available for now
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Pilih Kursi - KAI Tiket Kereta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f6f8; }
    .container { max-width: 1000px; margin-top: 30px; }
    .seat-btn { width: 56px; height: 56px; display: inline-flex; align-items: center; justify-content: center; padding: 6px; font-size: 11px; }
    .seat-btn.selected { background-color: #0d6efd; color: white; }
    .seat-btn.booked { background-color: #6c757d; color: white; opacity: 0.6; cursor: not-allowed; }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <div class="card-body">
        <h4 class="card-title">Pilih Kursi</h4>
        <p><strong>Jadwal:</strong> <?= htmlspecialchars($schedule['train_name']) ?> - <?= htmlspecialchars($schedule['origin_name']) ?> → <?= htmlspecialchars($schedule['destination_name']) ?><br>
        <strong>Berangkat:</strong> <?= date('d/m/Y H:i', strtotime($schedule['departure_time'])) ?> • <strong>Tiba:</strong> <?= date('d/m/Y H:i', strtotime($schedule['arrival_time'])) ?><br>
        <strong>Jumlah Penumpang:</strong> <?= $num_passengers ?></p>

        <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" id="seatForm">
          <div class="mb-3">
            <h6>Kursi Tersedia</h6>
            <div id="seatMap" class="d-flex flex-wrap gap-2" style="min-height:160px;">
              <!-- Seat buttons inserted here -->
            </div>
            <p class="mt-3 text-muted small">Klik kursi untuk memilih. Pilih <?= $num_passengers ?> kursi.</p>
          </div>

          <div id="selectedSeats" class="mb-3">
            <strong>Kursi Terpilih:</strong> <span id="selectedList">Belum ada</span>
          </div>

          <button type="submit" class="btn btn-primary" id="confirmBtn" disabled>Konfirmasi & Lanjut</button>
          <a href="booking_detail.php?schedule_id=<?= $schedule_id ?>" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    (function(){
      let selectedSeats = new Set();
      const numPassengers = <?= $num_passengers ?>;
      const availableSeats = <?= $available_seats ?>;
      const seatMap = document.getElementById('seatMap');
      const selectedList = document.getElementById('selectedList');
      const confirmBtn = document.getElementById('confirmBtn');

      // Render seat map: 3 left, aisle, 3 right per row
      const map = document.getElementById('seatMap');
      map.innerHTML = '';
      map.style.display = 'flex';
      map.style.flexDirection = 'column';
      map.style.alignItems = 'center';
      const totalSeats = <?= $total_seats ?>; // From train capacity
      const bookedSeats = <?= json_encode($booked_seats) ?>;
      if (totalSeats === 0) {
        map.innerHTML = '<p class="text-muted">Tidak ada kursi tersedia untuk jadwal ini.</p>';
        return;
      }
      const seatsPerSide = 3;

      function createSeatButton(num){
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'seat-btn';
        btn.style.width = '56px';
        btn.style.height = '56px';
        btn.style.display = 'inline-flex';
        btn.style.alignItems = 'center';
        btn.style.justifyContent = 'center';
        btn.style.padding = '6px';
        btn.dataset.seat = num;
        btn.innerHTML = '<div style="display:flex;align-items:center;gap:6px"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="7" width="18" height="10" rx="2"></rect><path d="M7 7V5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2"></path></svg><div style="font-size:11px">'+num+'</div></div>';
        if (bookedSeats.includes(num)) {
          btn.classList.add('btn', 'btn-danger');
          btn.disabled = true;
          btn.title = 'Kursi sudah dipesan';
        } else {
          btn.classList.add('btn', 'btn-outline-success');
        }
        btn.addEventListener('click', function(){
          if (btn.disabled) return;
          const n = btn.dataset.seat;
          if (selectedSeats.has(n)) {
            selectedSeats.delete(n);
            btn.classList.remove('selected', 'btn-primary');
            btn.classList.add('btn-outline-success');
          } else {
            if (selectedSeats.size < numPassengers) {
              selectedSeats.add(n);
              btn.classList.add('selected', 'btn-primary');
              btn.classList.remove('btn-outline-success');
            }
          }
          updateSelected();
        });
        return btn;
      }

      let seatNum = 1;
      const rows = Math.ceil(totalSeats / (seatsPerSide * 2));
      for (let row = 0; row < rows; row++) {
        const rowDiv = document.createElement('div');
        rowDiv.style.display = 'grid';
        rowDiv.style.gridTemplateColumns = '56px 56px 56px 60px 56px 56px 56px';
        rowDiv.style.gap = '10px';
        rowDiv.style.justifyContent = 'center';
        rowDiv.style.marginBottom = '10px';

        // Left side
        for (let s = 0; s < seatsPerSide; s++) {
          if (seatNum <= totalSeats) {
            rowDiv.appendChild(createSeatButton(seatNum++));
          } else {
            const empty = document.createElement('div');
            rowDiv.appendChild(empty);
          }
        }
        // Aisle
        const aisle = document.createElement('div');
        rowDiv.appendChild(aisle);
        // Right side
        for (let s = 0; s < seatsPerSide; s++) {
          if (seatNum <= totalSeats) {
            rowDiv.appendChild(createSeatButton(seatNum++));
          } else {
            const empty = document.createElement('div');
            rowDiv.appendChild(empty);
          }
        }

        map.appendChild(rowDiv);
      }

      function updateSelected() {
        const list = Array.from(selectedSeats).sort((a,b)=>a-b);
        selectedList.textContent = list.length > 0 ? list.join(', ') : 'Belum ada';
        confirmBtn.disabled = list.length !== numPassengers;

        // Add hidden inputs
        const form = document.getElementById('seatForm');
        // Remove existing hidden inputs
        form.querySelectorAll('input[name="selected_seats[]"]').forEach(el => el.remove());
        // Add new ones
        list.forEach(seat => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'selected_seats[]';
          input.value = seat;
          form.appendChild(input);
        });
      }
    })();
  </script>
</body>
</html>