<?php
session_start();
include_once 'db_config.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
// Accept schedule_id from GET or POST (forms or redirects)
$schedule_id = 0;
if (isset($_GET['schedule_id'])) {
    $schedule_id = (int)$_GET['schedule_id'];
} elseif (isset($_POST['schedule_id'])) {
    $schedule_id = (int)$_POST['schedule_id'];
}

if ($schedule_id <= 0) {
    // Friendly error page instead of fatal die; log GET/POST for debugging
    error_log('booking.php: missing or invalid schedule_id. GET=' . json_encode($_GET) . ' POST=' . json_encode($_POST) . ' REQUEST_URI=' . ($_SERVER['REQUEST_URI'] ?? '') . ' REF=' . ($_SERVER['HTTP_REFERER'] ?? ''));
    ?>
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Schedule Invalid - KAI Tiket Kereta</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    </head>
    <body class="p-4">
      <div class="container" style="max-width:720px;">
        <div class="alert alert-warning">Jadwal tidak valid atau tidak ditemukan. Silakan pilih jadwal dari <a href="search.php">halaman pencarian</a>.</div>
        <div class="card"><div class="card-body"><pre style="font-size:0.85rem;color:#333;">Debug:
      REQUEST_URI=<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>
      QUERY_STRING=<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>
      HTTP_REFERER=<?= htmlspecialchars($_SERVER['HTTP_REFERER'] ?? '') ?>
      GET=<?= htmlspecialchars(json_encode($_GET)) ?>
      POST=<?= htmlspecialchars(json_encode($_POST)) ?>
      </pre></div></div>
      </div>
    </body>
    </html>
    <?php
    exit;
}

$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
if (!$conn) die('DB connection error.');

// Fetch schedule + train
$stmt = $conn->prepare("SELECT s.schedule_id, s.train_id, s.departure_time, s.arrival_time, s.price, t.train_name FROM schedules s JOIN trains t ON s.train_id = t.train_id WHERE s.schedule_id = ?");
$stmt->bind_param('i', $schedule_id);
$stmt->execute();
$res = $stmt->get_result();
$schedule = $res->fetch_assoc();
$stmt->close();
if (!$schedule) die('Schedule not found.');

// Load carriages for train
$cstmt = $conn->prepare("SELECT carriage_id, name, capacity FROM carriages WHERE train_id = ?");
$cstmt->bind_param('i', $schedule['train_id']);
$cstmt->execute();
$cres = $cstmt->get_result();
$carriages = $cres->fetch_all(MYSQLI_ASSOC);
$cstmt->close();

// Detect if bookings table has advanced columns (carriage_id, seats, passenger_name, status, created_at)
$has_carriage_col = false;
$has_seats_col = false;
$has_passenger_col = false;
$has_status_col = false;
$booking_carriage_nullable = true; // assume nullable by default

$colRes = $conn->query("SHOW COLUMNS FROM `bookings`");
if ($colRes) {
    while ($col = $colRes->fetch_assoc()) {
      $field = $col['Field'];
      if ($field === 'carriage_id') {
        $has_carriage_col = true;
        // check if column allows NULL
        $booking_carriage_nullable = strtoupper($col['Null'] ?? '') === 'YES';
      }
      if ($field === 'seats') $has_seats_col = true;
      if ($field === 'passenger_name') $has_passenger_col = true;
      if ($field === 'status') $has_status_col = true;
    }
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($has_carriage_col && $has_seats_col && $has_passenger_col && $has_status_col) {
        // Full booking flow with availability check
        $passenger_name = trim($_POST['passenger_name'] ?? '');
        $carriage_id = isset($_POST['carriage_id']) ? (int)$_POST['carriage_id'] : 0;
        $seats = max(1, (int)($_POST['seats'] ?? 1));

        if ($passenger_name === '') $errors[] = 'Nama penumpang harus diisi.';
        if ($carriage_id <= 0) $errors[] = 'Pilih kelas/carriage.';
        if ($seats <= 0) $errors[] = 'Jumlah kursi minimal 1.';

        $passenger_nik = trim($_POST['passenger_nik'] ?? '');

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
                // Lock sum of used seats for this schedule+carriage
                $q = $conn->prepare("SELECT COALESCE(SUM(seats),0) AS used FROM bookings WHERE schedule_id = ? AND carriage_id = ? AND status IN ('PENDING','CONFIRMED','PAID') FOR UPDATE");
                $q->bind_param('ii', $schedule_id, $carriage_id);
                $q->execute();
                $usedRow = $q->get_result()->fetch_assoc();
                $used = (int)$usedRow['used'];
                $q->close();

                // Lock carriage capacity
                $q2 = $conn->prepare("SELECT capacity FROM carriages WHERE carriage_id = ? FOR UPDATE");
                $q2->bind_param('i', $carriage_id);
                $q2->execute();
                $capRow = $q2->get_result()->fetch_assoc();
                $q2->close();
                if (!$capRow) throw new Exception('Carriage not found.');
                $capacity = (int)$capRow['capacity'];

                if ($used + $seats > $capacity) {
                    throw new Exception('Kursi tidak cukup. Tersisa: ' . max(0, $capacity - $used));
                }

                $total_price = $seats * (float)$schedule['price'];

                if ($user_id === null) {
                  $ins = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, carriage_id, passenger_name, seats, total_amount, status, created_at) VALUES (NULL, ?, ?, ?, ?, ?, 'PENDING', NOW())");
                  // schedule_id, carriage_id, passenger_name, seats, total_price
                  $ins->bind_param('iisid', $schedule_id, $carriage_id, $passenger_name, $seats, $total_price);
                } else {
                  $ins = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, carriage_id, passenger_name, seats, total_amount, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'PENDING', NOW())");
                  $ins->bind_param('iiisid', $user_id, $schedule_id, $carriage_id, $passenger_name, $seats, $total_price);
                }
                $ins->execute();
                $booking_id = $ins->insert_id;
                $ins->close();

                // If a tickets table exists, create ticket rows (one row per seat)
                $hasTickets = false;
                $check = $conn->query("SHOW TABLES LIKE 'tickets'");
                if ($check && $check->num_rows > 0) {
                  $hasTickets = true;
                }

                if ($hasTickets) {
                  // prepare ticket insert (booking_id, carriage_id, seat_number, passenger_name, passenger_nik)
                  $tstmt = $conn->prepare("INSERT INTO tickets (booking_id, carriage_id, seat_number, passenger_name, passenger_nik) VALUES (?, ?, ?, ?, ?)");
                  if ($tstmt) {
                    $seat_number = null;
                    // use provided NIK if available
                    //$passenger_nik is already set above
                    // Insert one ticket row per seat requested
                    for ($i = 0; $i < max(1, $seats); $i++) {
                      // For now we don't assign seat numbers; leave NULL
                      $pname = $passenger_name ?: 'Tamu';
                      $tstmt->bind_param('iisss', $booking_id, $carriage_id, $seat_number, $pname, $passenger_nik);
                      $tstmt->execute();
                    }
                    $tstmt->close();
                  }
                }

                $conn->commit();

                header('Location: booking_confirm.php?booking_id=' . $booking_id);
                exit;
            } catch (Exception $ex) {
                $conn->rollback();
                $errors[] = $ex->getMessage();
            }
        }
    } else {
        // Minimal fallback: insert basic booking into existing columns
        $total_amount = (float)$schedule['price'];
        $now = date('Y-m-d H:i:s');
        // Determine a sensible default carriage if bookings.carriage_id exists
        $default_carriage = null;
        if ($has_carriage_col && !empty($carriages)) {
          $default_carriage = (int)$carriages[0]['carriage_id'];
        }

        // If we have a default carriage, include it in the fallback insert to avoid NULL errors
        if ($default_carriage !== null) {
          if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, carriage_id, booking_date, total_amount, payment_status) VALUES (NULL, ?, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iisd', $schedule_id, $default_carriage, $now, $total_amount);
          } else {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, carriage_id, booking_date, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iiisd', $user_id, $schedule_id, $default_carriage, $now, $total_amount);
          }
        } else {
          // No carriage column or no carriages available — insert without carriage_id
          if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, booking_date, total_amount, payment_status) VALUES (NULL, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iss', $schedule_id, $now, $total_amount);
          } else {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, booking_date, total_amount, payment_status) VALUES (?, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iisd', $user_id, $schedule_id, $now, $total_amount);
          }
        }

        // Wrap execute in try/catch-like check to give a friendly error instead of fatal
        if (!$stmt) {
          $errors[] = 'Gagal menyiapkan query booking: ' . $conn->error;
        }
        try {
          $ok = $stmt ? $stmt->execute() : false;
        } catch (mysqli_sql_exception $e) {
          $ok = false;
          $errors[] = 'Gagal membuat booking: ' . $e->getMessage();
        }

        if ($ok) {
          $booking_id = $stmt->insert_id;
          $stmt->close();

          // If tickets table exists, create ticket rows for minimal fallback as well
          $hasTickets = false;
          $check = $conn->query("SHOW TABLES LIKE 'tickets'");
          if ($check && $check->num_rows > 0) {
            $hasTickets = true;
          }

          if ($hasTickets) {
            $tstmt = $conn->prepare("INSERT INTO tickets (booking_id, carriage_id, seat_number, passenger_name, passenger_nik) VALUES (?, ?, ?, ?, ?)");
            if ($tstmt) {
              $seat_number = null;
              $passenger_nik = trim($_POST['passenger_nik'] ?? '');
              // fallback assumed 1 seat
              $seats_to_create = isset($seats) ? max(1, (int)$seats) : 1;
                  $car = $default_carriage ?? (isset($carriage_id) ? $carriage_id : null);
                  for ($i = 0; $i < $seats_to_create; $i++) {
                    $pname = $passenger_name ?? 'Tamu';
                    // If carriage_id is null, bind as null via PHP variable
                    $cid = $car;
                    $tstmt->bind_param('iisss', $booking_id, $cid, $seat_number, $pname, $passenger_nik);
                    try {
                      $tok = $tstmt->execute();
                      if (!$tok) {
                        $errors[] = 'Gagal membuat tiket: ' . $tstmt->error;
                        break;
                      }
                    } catch (mysqli_sql_exception $te) {
                      $errors[] = 'Gagal membuat tiket: ' . $te->getMessage();
                      break;
                    }
                  }
              $tstmt->close();
            }
          }

          header('Location: booking_confirm.php?booking_id=' . $booking_id);
          exit;
        } else {
          if (empty($errors)) $errors[] = 'Gagal membuat booking: ' . $conn->error;
        }
    }
}
?>
    <?php
    // If advanced booking schema present, compute availability per carriage
    $carriage_availability = [];
    if ($has_carriage_col) {
      foreach ($carriages as $c) {
        $cid = (int)$c['carriage_id'];
        $used = 0;
        $q = $conn->prepare("SELECT COALESCE(SUM(seats),0) AS used FROM bookings WHERE schedule_id = ? AND carriage_id = ? AND status IN ('PENDING','CONFIRMED','PAID')");
        $q->bind_param('ii', $schedule_id, $cid);
        $q->execute();
        $r = $q->get_result()->fetch_assoc();
        if ($r) $used = (int)$r['used'];
        $q->close();
        $carriage_availability[$cid] = max(0, $c['capacity'] - $used);
      }
    }
    ?>
    <!doctype html>
    <html lang="id">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width,initial-scale=1">
      <title>Booking - KAI Tiket Kereta</title>
      <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
      <style> .meta-row {font-size:0.95rem;color:#555} </style>
    </head>
    <body class="p-4">
      <div class="container" style="max-width:920px;">

        <div class="card">
          <div class="card-body">
            <h4 class="card-title mb-1"><?= htmlspecialchars($schedule['train_name']) ?></h4>
            <?php if (isset($_GET['from']) && $_GET['from'] === 'search'): ?>
              <div class="alert alert-info">
                Anda <strong>memilih</strong> jadwal ini dari hasil pencarian. Lanjutkan untuk mengisi data penumpang dan menyelesaikan pemesanan.
              </div>
            <?php endif; ?>
            <div class="meta-row mb-2">
              <strong>Keberangkatan:</strong> <?= htmlspecialchars($schedule['departure_time']) ?> &nbsp; • &nbsp;
              <strong>Datang:</strong> <?= htmlspecialchars($schedule['arrival_time']) ?> &nbsp; • &nbsp;
              <strong>Harga per kursi:</strong> Rp <?= number_format($schedule['price'],0,',','.') ?>
            </div>

            <?php if (!empty($errors)): ?>
              <div class="alert alert-danger">
                <ul class="mb-0">
                  <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>

            <form method="post" id="bookingForm">
              <div class="row g-3">
                <?php if ($has_carriage_col && $has_seats_col && $has_passenger_col && $has_status_col): ?>
                  <div class="col-md-6">
                    <label class="form-label">Nama Penumpang</label>
                    <input name="passenger_name" class="form-control" required value="<?= isset($_POST['passenger_name']) ? htmlspecialchars($_POST['passenger_name']) : '' ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">No. Identitas / NIK (opsional)</label>
                    <input name="passenger_nik" class="form-control" value="<?= isset($_POST['passenger_nik']) ? htmlspecialchars($_POST['passenger_nik']) : '' ?>" placeholder="0812xxxx atau NIK">
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Pilih Kelas / Carriage</label>
                    <select name="carriage_id" id="carriageSelect" class="form-select" required>
                      <option value="">Pilih...</option>
                      <?php foreach ($carriages as $c): $avail = $carriage_availability[(int)$c['carriage_id']] ?? $c['capacity']; ?>
                        <option value="<?= $c['carriage_id'] ?>" data-capacity="<?= $c['capacity'] ?>" data-available="<?= $avail ?>" <?= (isset($_POST['carriage_id']) && (int)$_POST['carriage_id'] === (int)$c['carriage_id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?> — kapasitas <?= $c['capacity'] ?>, tersisa <?= $avail ?></option>
                      <?php endforeach; ?>
                    </select>
                    <div id="availNotice" class="form-text text-muted mt-1">Pilih kelas untuk melihat ketersediaan.</div>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Jumlah</label>
                    <input type="number" name="seats" id="seatsInput" min="1" class="form-control" value="<?= isset($_POST['seats']) ? (int)$_POST['seats'] : 1 ?>">
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Total Bayar</label>
                    <div class="border rounded p-2" id="totalPrice">Rp <?= number_format(((isset($_POST['seats']) ? (int)$_POST['seats'] : 1) * $schedule['price']),0,',','.') ?></div>
                  </div>

                <?php else: ?>
                  <div class="col-12">
                    <p class="text-muted">Mode sederhana: sistem akan membuat booking dasar. Untuk fitur lengkap (pilih kelas & jumlah kursi) jalankan migration SQL yang tersedia.</p>
                  </div>
                <?php endif; ?>

                <div class="col-12 d-flex gap-2">
                  <button class="btn btn-primary" id="submitBtn">Pesan</button>
                  <a href="search.php" class="btn btn-outline-secondary">Batal</a>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>

      <script>
        (function(){
          const price = <?= json_encode((float)$schedule['price']) ?>;
          const carriageSelect = document.getElementById('carriageSelect');
          const seatsInput = document.getElementById('seatsInput');
          const totalPrice = document.getElementById('totalPrice');
          const availNotice = document.getElementById('availNotice');

          function fmtIDR(n){ return 'Rp ' + n.toLocaleString('id-ID'); }

          function updateTotal(){
            const seats = Math.max(1, parseInt(seatsInput ? seatsInput.value : '1'));
            totalPrice.textContent = fmtIDR(seats * price);
          }

          function updateAvail(){
            if (!carriageSelect) return;
            const opt = carriageSelect.options[carriageSelect.selectedIndex];
            if (!opt || !opt.value) {
              availNotice.textContent = 'Pilih kelas untuk melihat ketersediaan.';
              return;
            }
            const avail = parseInt(opt.dataset.available || '0');
            availNotice.textContent = avail > 0 ? ('Tersisa ' + avail + ' kursi di kelas ini.') : 'Kelas ini sudah penuh.';
          }

          if (seatsInput) seatsInput.addEventListener('input', updateTotal);
          if (carriageSelect) carriageSelect.addEventListener('change', function(){ updateAvail(); updateTotal(); });
          // initial
          updateAvail(); updateTotal();
        })();
      </script>
    </body>
    </html>
