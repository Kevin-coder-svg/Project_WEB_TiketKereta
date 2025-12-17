<?php
session_start();
include_once 'db_config.php';

$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

$schedule_id = 0;
if (isset($_GET['schedule_id'])) {
    $schedule_id = (int)$_GET['schedule_id'];
} elseif (isset($_POST['schedule_id'])) {
    $schedule_id = (int)$_POST['schedule_id'];
}

if ($schedule_id <= 0) {
   
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


$stmt = $conn->prepare("SELECT s.schedule_id, s.train_id, s.departure_time, s.arrival_time, s.price, t.train_name, t.total_carriages FROM schedules s JOIN trains t ON s.train_id = t.train_id WHERE s.schedule_id = ?");
if (!$stmt) {
    die('Query gagal: ' . $conn->error . '. Pastikan tabel schedules dan trains ada.');
}
$stmt->bind_param('i', $schedule_id);
$stmt->execute();
$res = $stmt->get_result();
$schedule = $res->fetch_assoc();
$stmt->close();
if (!$schedule) {
    die('Jadwal tidak ditemukan. Pastikan data ada di database.');
}

$hasCarriagesTable = true;
try {
  $cstmt = $conn->prepare("SELECT carriage_id, name, capacity FROM carriages WHERE train_id = ?");
} catch (mysqli_sql_exception $e) {
  $cstmt = false;
  $hasCarriagesTable = false;
  error_log('Carriages table not available (booking load): ' . $e->getMessage());
}
if ($cstmt) {
  $cstmt->bind_param('i', $schedule['train_id']);
  $cstmt->execute();
  $cres = $cstmt->get_result();
  $carriages = $cres->fetch_all(MYSQLI_ASSOC);
  $cstmt->close();
} else {
  $carriages = [];
}

$has_carriage_col = false;
$has_seats_col = false;
$has_passenger_col = false;
$has_status_col = false;
$booking_carriage_nullable = true;

$colRes = $conn->query("SHOW COLUMNS FROM `bookings`");
if ($colRes) {
    while ($col = $colRes->fetch_assoc()) {
      $field = $col['Field'];
      if ($field === 'carriage_id') {
        $has_carriage_col = true;
      
        $booking_carriage_nullable = strtoupper($col['Null'] ?? '') === 'YES';
      }
      if ($field === 'seats') $has_seats_col = true;
      if ($field === 'passenger_name') $has_passenger_col = true;
      if ($field === 'status') $has_status_col = true;
    }
}

if (!$hasCarriagesTable) {
  $has_carriage_col = false;
}

$errors = [];

$preselected_seats = [];
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['selected_seats'])) {
  if (is_array($_GET['selected_seats'])) {
    foreach ($_GET['selected_seats'] as $ss) {
      $s = trim((string)$ss);
      if ($s !== '') $preselected_seats[] = $s;
    }
  } else {

    $s = trim((string)$_GET['selected_seats']);
    if ($s !== '') $preselected_seats[] = $s;
  }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 
  if (isset($_SESSION['num_passengers']) && isset($_SESSION['selected_seats'])) {
    $num_passengers = $_SESSION['num_passengers'];
    $selected_seats = $_SESSION['selected_seats'];
    if (count($selected_seats) !== $num_passengers) {
      $errors[] = 'Jumlah kursi dan penumpang tidak cocok.';
    } else {
    
      $passengers = [];
      for ($i = 0; $i < $num_passengers; $i++) {
        $passengers[] = ['name' => 'Penumpang ' . ($i + 1), 'nip' => '', 'phone' => ''];
      }

      $conn->begin_transaction();
      try {
        $total_amount = $num_passengers * $schedule['price'];
        $now = date('Y-m-d H:i:s');
        $user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

        $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, seats, total_amount, status, created_at) VALUES (?, ?, ?, ?, 'PENDING', ?)");
        $stmt->bind_param('iiids', $user_id, $schedule_id, $num_passengers, $total_amount, $now);
        $stmt->execute();
        $booking_id = $conn->insert_id;
        $stmt->close();

        $conn->commit();

        header('Location: payment.php?booking_id=' . $booking_id);
        exit;
      } catch (Exception $ex) {
        $conn->rollback();
        $errors[] = $ex->getMessage();
      }
    }
  } elseif ($has_carriage_col && $has_seats_col && $has_passenger_col && $has_status_col) {

        $passenger_name = trim($_POST['passenger_name'] ?? '');
        $carriage_id = isset($_POST['carriage_id']) ? (int)$_POST['carriage_id'] : 0;
      
        $selected_seats = [];
        if (isset($_POST['selected_seats']) && is_array($_POST['selected_seats'])) {
          foreach ($_POST['selected_seats'] as $ss) {
            $s = trim((string)$ss);
            if ($s !== '') $selected_seats[] = $s;
          }
        }
        $seats = max(1, (int)($_POST['seats'] ?? count($selected_seats) ?: 1));

        if ($passenger_name === '') $errors[] = 'Nama penumpang harus diisi.';
       
        if (empty($selected_seats) && $carriage_id <= 0) $errors[] = 'Pilih kelas/carriage.';
        if ($seats <= 0) $errors[] = 'Jumlah kursi minimal 1.';

        $passenger_nik = trim($_POST['passenger_nik'] ?? '');

        if (empty($errors)) {
            $conn->begin_transaction();
            try {
           
                $hasTickets = false;
                $check = $conn->query("SHOW TABLES LIKE 'tickets'");
                if ($check && $check->num_rows > 0) {
                  $hasTickets = true;
                }

                if ($hasTickets && !empty($selected_seats)) {
                
                  $placeholders = implode(',', array_fill(0, count($selected_seats), '?'));
                  $types = str_repeat('s', count($selected_seats));
                  $sql = "SELECT t.seat_number FROM tickets t JOIN bookings b ON t.booking_id = b.booking_id WHERE b.schedule_id = ? AND t.seat_number IN (".$placeholders.") AND b.status IN ('PENDING','CONFIRMED','PAID') FOR UPDATE";
                  $stmt = $conn->prepare($sql);
                  if ($stmt) {
                    
                    $bindParams = [];
                    $bindTypes = 'i' . $types;
                    $bindParams[] = $schedule_id;
                    foreach ($selected_seats as $ss) $bindParams[] = $ss;
                  
                    $refs = [];
                    $refs[] = &$bindTypes;
                    for ($i=0;$i<count($bindParams);$i++) { $refs[] = &$bindParams[$i]; }
                    call_user_func_array([$stmt, 'bind_param'], $refs);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    $conflicts = [];
                    while ($r = $res->fetch_assoc()) {
                      $conflicts[] = (string)$r['seat_number'];
                    }
                    $stmt->close();
                    if (!empty($conflicts)) {
                      throw new Exception('Beberapa kursi sudah dibooking oleh orang lain: ' . implode(', ', $conflicts));
                    }
                  }
                }

         
                if (empty($selected_seats)) {
                  
                  $q = $conn->prepare("SELECT COALESCE(SUM(seats),0) AS used FROM bookings WHERE schedule_id = ? AND status IN ('PENDING','CONFIRMED','PAID') FOR UPDATE");
                  $q->bind_param('i', $schedule_id);
                  $q->execute();
                  $usedRow = $q->get_result()->fetch_assoc();
                  $used = (int)$usedRow['used'];
                  $q->close();


                  try {
                    $q2 = $conn->prepare("SELECT capacity FROM carriages WHERE carriage_id = ? FOR UPDATE");
                  } catch (mysqli_sql_exception $e) {
                    $q2 = false;
                    error_log('Carriages table not available (booking cap): ' . $e->getMessage());
                  }
                  if (!$q2) {
                    throw new Exception('Carriage data not available.');
                  }
                  $q2->bind_param('i', $carriage_id);
                  $q2->execute();
                  $capRow = $q2->get_result()->fetch_assoc();
                  $q2->close();
                  if (!$capRow) throw new Exception('Carriage not found.');
                  $capacity = (int)$capRow['capacity'];
                  if ($used + $seats > $capacity) {
                      throw new Exception('Kursi tidak cukup. Tersisa: ' . max(0, $capacity - $used));
                  }
                }

                $total_price = $seats * (float)$schedule['price'];

                if ($user_id === null) {
                  $ins = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, seats, total_amount, status, created_at) VALUES (NULL, ?, ?, ?, 'PENDING', NOW())");
                  $ins->bind_param('iids', $schedule_id, $seats, $total_price);
                } else {
                  $ins = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, seats, total_amount, status, created_at) VALUES (?, ?, ?, ?, 'PENDING', NOW())");
                  $ins->bind_param('iiids', $user_id, $schedule_id, $seats, $total_price);
                }
                $ins->execute();
                $booking_id = $ins->insert_id;
                $ins->close();

          
                if ($hasTickets) {
                  $tstmt = $conn->prepare("INSERT INTO tickets (booking_id, seat_number, passenger_name, passenger_nik) VALUES (?, ?, ?, ?)");
                  if ($tstmt) {
                
                    if (!empty($selected_seats)) {
                      foreach ($selected_seats as $seat_number) {
                        $pname = $passenger_name ?: 'Tamu';
                        $tstmt->bind_param('isss', $booking_id, $seat_number, $pname, $passenger_nik);
                        $tstmt->execute();
                      }
                    } else {
                   
                      $seat_number = null;
                      for ($i = 0; $i < max(1, $seats); $i++) {
                        $pname = $passenger_name ?: 'Tamu';
                        $tstmt->bind_param('isss', $booking_id, $seat_number, $pname, $passenger_nik);
                        $tstmt->execute();
                      }
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

        $selected_from_post = [];
        if (isset($_POST['selected_seats']) && is_array($_POST['selected_seats'])) {
          foreach ($_POST['selected_seats'] as $ss) {
            $s = trim((string)$ss);
            if ($s !== '') $selected_from_post[] = $s;
          }
        }
        $seats_to_create = !empty($selected_from_post) ? count($selected_from_post) : (isset($_POST['seats']) ? max(1,(int)$_POST['seats']) : 1);
        $total_amount = $schedule['price'] * $seats_to_create;
        $now = date('Y-m-d H:i:s');

        $default_carriage = null;
        if ($has_carriage_col && !empty($carriages)) {
          $default_carriage = (int)$carriages[0]['carriage_id'];
        }

        if ($default_carriage !== null) {
          if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, carriage_id, booking_date, total_amount, payment_status) VALUES (NULL, ?, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iisd', $schedule_id, $default_carriage, $now, $total_amount);
          } else {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, carriage_id, booking_date, total_amount, payment_status) VALUES (?, ?, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iiisd', $user_id, $schedule_id, $default_carriage, $now, $total_amount);
          }
        } else {

          if ($user_id === null) {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, booking_date, total_amount, payment_status) VALUES (NULL, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iss', $schedule_id, $now, $total_amount);
          } else {
            $stmt = $conn->prepare("INSERT INTO bookings (user_id, schedule_id, booking_date, total_amount, payment_status) VALUES (?, ?, ?, ?, 'PENDING')");
            $stmt->bind_param('iisd', $user_id, $schedule_id, $now, $total_amount);
          }
        }

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

          $hasTickets = false;
          $check = $conn->query("SHOW TABLES LIKE 'tickets'");
          if ($check && $check->num_rows > 0) {
            $hasTickets = true;
          }

          if ($hasTickets) {
            $tstmt = $conn->prepare("INSERT INTO tickets (booking_id, seat_number, passenger_name, passenger_nik) VALUES (?, ?, ?, ?)");
            if ($tstmt) {
              $passenger_nik = trim($_POST['passenger_nik'] ?? '');
              if (!empty($selected_from_post)) {
                foreach ($selected_from_post as $seat_number) {
                  $pname = $passenger_name ?? 'Tamu';
                  $tstmt->bind_param('isss', $booking_id, $seat_number, $pname, $passenger_nik);
                  try { $tstmt->execute(); } catch (mysqli_sql_exception $te) { $errors[] = 'Gagal membuat tiket: ' . $te->getMessage(); break; }
                }
              } else {
                for ($i = 0; $i < $seats_to_create; $i++) {
                  $seat_number = null;
                  $pname = $passenger_name ?? 'Tamu';
                  $tstmt->bind_param('isss', $booking_id, $seat_number, $pname, $passenger_nik);
                  try { $tstmt->execute(); } catch (mysqli_sql_exception $te) { $errors[] = 'Gagal membuat tiket: ' . $te->getMessage(); break; }
                }
              }
              $tstmt->close();
            }
          }

          if ($has_seats_col) {
            $u = $conn->prepare("UPDATE bookings SET seats = ? WHERE booking_id = ?");
            if ($u) { $u->bind_param('ii', $seats_to_create, $booking_id); $u->execute(); $u->close(); }
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
              <input type="hidden" name="schedule_id" value="<?= $schedule_id ?>">
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
                    <?php $prefCount = !empty($preselected_seats) ? count($preselected_seats) : (isset($_POST['seats']) ? (int)$_POST['seats'] : 1); ?>
                    <input type="number" name="seats" id="seatsInput" min="1" class="form-control" value="<?= $prefCount ?>" <?= !empty($preselected_seats) ? 'readonly' : '' ?> >
                    <?php if (!empty($preselected_seats)): ?>
                      <?php foreach ($preselected_seats as $ps): ?><input type="hidden" name="selected_seats[]" value="<?= htmlspecialchars($ps) ?>"><?php endforeach; ?>
                      <div class="form-text">Kursi terpilih: <?= htmlspecialchars(implode(', ', $preselected_seats)) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Total Bayar</label>
                    <div class="border rounded p-2" id="totalPrice">Rp <?= number_format(((isset($_POST['seats']) ? (int)$_POST['seats'] : 1) * $schedule['price']),0,',','.') ?></div>
                  </div>

                <?php else: ?>

                <?php endif; ?>

                <div class="col-12 d-flex gap-2">
                  <button type="submit" class="btn btn-primary" id="submitBtn">Pesan</button>
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
          updateAvail(); updateTotal();
        })();
      </script>
    </body>
    </html>
