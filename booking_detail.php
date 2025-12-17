<?php
session_start();
include_once 'db_config.php';


$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
if ($schedule_id <= 0) {
    header('Location: search.php');
    exit;
}

$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
$schedule = null;
if ($conn) {
    $stmt = $conn->prepare("SELECT s.schedule_id, s.train_id, t.train_name, o.station_name AS origin_name, d.station_name AS destination_name, s.departure_time, s.arrival_time, s.price FROM schedules s JOIN trains t ON s.train_id = t.train_id LEFT JOIN stations o ON s.origin_station_id = o.station_id LEFT JOIN stations d ON s.destination_station_id = d.station_id WHERE s.schedule_id = ?");
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $schedule = $res->fetch_assoc();
    $stmt->close();
}
if (!$schedule) {
    header('Location: search.php');
    exit;
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $num_passengers = (int)($_POST['num_passengers'] ?? 0);
    $passenger_names = $_POST['passenger_name'] ?? [];
    $passenger_niks = $_POST['passenger_nik'] ?? [];
    $passenger_phones = $_POST['passenger_phone'] ?? [];

    if ($num_passengers < 1 || $num_passengers > 10) {
        $error = 'Jumlah penumpang harus antara 1-10.';
    } elseif (count($passenger_names) !== $num_passengers || count($passenger_niks) !== $num_passengers || count($passenger_phones) !== $num_passengers) {
        $error = 'Harap isi semua data untuk setiap penumpang.';
    } else {
        $passengers_data = [];
        for ($i = 0; $i < $num_passengers; $i++) {
            if (empty(trim($passenger_names[$i])) || empty(trim($passenger_niks[$i])) || empty(trim($passenger_phones[$i]))) {
                $error = 'Data penumpang tidak boleh kosong.';
                break;
            }
            $passengers_data[] = [
                'name' => trim($passenger_names[$i]),
                'nik' => trim($passenger_niks[$i]),
                'phone' => trim($passenger_phones[$i])
            ];
        }

        if (!isset($error)) {
          
            $_SESSION['num_passengers'] = $num_passengers;
            $_SESSION['passengers'] = $passengers_data;
            $_SESSION['booking_schedule_id'] = $schedule_id;
            header('Location: seat_selection.php?schedule_id=' . $schedule_id);
            exit;
        }
    }
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Detail Penumpang - KAI Tiket Kereta</title>
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
        <h4 class="card-title">Detail Penumpang</h4>
        <p><strong>Jadwal:</strong> <?= htmlspecialchars($schedule['train_name']) ?> - <?= htmlspecialchars($schedule['origin_name']) ?> → <?= htmlspecialchars($schedule['destination_name']) ?><br>
        <strong>Berangkat:</strong> <?= date('d/m/Y H:i', strtotime($schedule['departure_time'])) ?> • <strong>Tiba:</strong> <?= date('d/m/Y H:i', strtotime($schedule['arrival_time'])) ?><br>
        <strong>Harga:</strong> Rp <?= number_format($schedule['price'], 0, ',', '.') ?></p>

        <?php if (isset($error)): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="mb-3">
            <label class="form-label">Jumlah Penumpang</label>
            <select name="num_passengers" id="num_passengers" class="form-select" required>
              <option value="">Pilih jumlah</option>
              <?php for ($i=1; $i<=10; $i++): ?>
                <option value="<?= $i ?>" <?= (isset($_POST['num_passengers']) && $_POST['num_passengers'] == $i) ? 'selected' : '' ?>><?= $i ?></option>
              <?php endfor; ?>
            </select>
          </div>

          <div id="passenger_fields_container"></div>

          <button type="submit" class="btn btn-primary">Lanjut ke Pilih Kursi</button>
          <a href="search.php" class="btn btn-secondary">Kembali</a>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.getElementById('num_passengers').addEventListener('change', function() {
      const num = parseInt(this.value);
      const container = document.getElementById('passenger_fields_container');
      
      if (isNaN(num) || num <= 0) {
        container.innerHTML = '';
        return;
      }
      
      let html = '';
      for (let i = 1; i <= num; i++) {
        html += `
          <div class="passenger-fields mb-4 p-3 border rounded">
            <h6>Penumpang ${i}</h6>
            <div class="row">
              <div class="col-md-4">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="passenger_name[]" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">No. Identitas / NIK</label>
                <input type="text" name="passenger_nik[]" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">No. HP</label>
                <input type="tel" name="passenger_phone[]" class="form-control" required>
              </div>
            </div>
          </div>
        `;
      }
      container.innerHTML = html;
    });

  
    if (document.getElementById('num_passengers').value) {
        document.getElementById('num_passengers').dispatchEvent(new Event('change'));
    }
  </script>
</body>
</html>