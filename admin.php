<?php
session_start();
require_once 'db_config.php';

// Set Timezone
date_default_timezone_set('Asia/Jakarta'); 

// Check Login Admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: HTML_login.html');
    exit;
}

// Init Dashboard Variables
$totalRevenue = 0;
$totalUsers = 0;
$totalBookings = 0;

// 1. Hitung Revenue (Status PAID)
$query = "SELECT SUM(total_amount) AS revenue FROM bookings WHERE status = 'PAID'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $totalRevenue = $row['revenue'] ?? 0;
}

// 2. Hitung Total User
$query = "SELECT COUNT(*) AS user_count FROM users WHERE role = 'user'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $totalUsers = $row['user_count'];
}

// 3. Hitung Total Booking
$query = "SELECT COUNT(*) AS booking_count FROM bookings";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $totalBookings = $row['booking_count'];
}

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        // Ambil data POST
        $scheduleId = $_POST['schedule_id'] ?? null;
        $trainId = $_POST['train_id'] ?? null;
        $originStationId = $_POST['origin_station_id'] ?? null;
        $destinationStationId = $_POST['destination_station_id'] ?? null;
        $departureTime = $_POST['departure_time'] ?? null;
        $arrivalTime = $_POST['arrival_time'] ?? null;
        $price = $_POST['price'] ?? null;

        // Validasi Tanggal (Server Side)
        if ($departureTime && $arrivalTime && ($action == 'add_schedule' || $action == 'edit_schedule')) {
            $depTimestamp = strtotime($departureTime);
            $arrTimestamp = strtotime($arrivalTime);
            $nowTimestamp = time();

            // Cek masa lalu hanya untuk Add Schedule
            if ($action == 'add_schedule' && $depTimestamp < $nowTimestamp) {
                echo "<script>alert('Gagal! Waktu keberangkatan tidak boleh di masa lalu.'); window.location.href='admin.php';</script>";
                exit;
            }

            // Cek logika Tiba vs Berangkat
            if ($arrTimestamp <= $depTimestamp) {
                echo "<script>alert('Gagal! Waktu tiba harus setelah waktu berangkat.'); window.location.href='admin.php';</script>";
                exit;
            }
        }

        switch ($action) {
            case 'add_schedule':
                if ($trainId && $originStationId && $destinationStationId && $departureTime && $arrivalTime && $price) {
                    $query = "INSERT INTO schedules (train_id, origin_station_id, destination_station_id, departure_time, arrival_time, price) VALUES ('$trainId', '$originStationId', '$destinationStationId', '$departureTime', '$arrivalTime', '$price')";
                    mysqli_query($conn, $query);
                }
                header('Location: admin.php');
                exit;

            case 'delete_schedule':
                if ($scheduleId) {
                    $query = "DELETE FROM schedules WHERE schedule_id = $scheduleId";
                    mysqli_query($conn, $query);
                }
                break;

            case 'edit_schedule':
                if ($scheduleId && $trainId && $originStationId && $destinationStationId && $departureTime && $arrivalTime && $price) {
                    $query = "UPDATE schedules SET train_id = '$trainId', origin_station_id = '$originStationId', destination_station_id = '$destinationStationId', departure_time = '$departureTime', arrival_time = '$arrivalTime', price = '$price' WHERE schedule_id = $scheduleId";
                    if (!mysqli_query($conn, $query)) {
                        echo "Error: " . mysqli_error($conn); exit;
                    }
                }
                break;
        }
    }
}

// --- FETCH DATA UNTUK TAMPILAN ---

// 1. Schedules (Urut ID ASC)
$schedules = [];
$query = "SELECT schedules.*, trains.train_name, origin.station_name AS origin_station, destination.station_name AS destination_station 
          FROM schedules
          JOIN trains ON schedules.train_id = trains.train_id
          JOIN stations AS origin ON schedules.origin_station_id = origin.station_id
          JOIN stations AS destination ON schedules.destination_station_id = destination.station_id
          ORDER BY schedules.schedule_id ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) { $schedules[] = $row; }

// 2. Users (Urut ID ASC)
$users = [];
$query = "SELECT user_id, full_name, email, phone, role FROM users ORDER BY user_id ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) { $users[] = $row; }

// 3. Bookings (Urut ID ASC)
$bookings = [];
$query = "SELECT bookings.booking_id, users.full_name, schedules.departure_time, schedules.arrival_time, bookings.total_amount, bookings.status 
          FROM bookings
          JOIN users ON bookings.user_id = users.user_id
          JOIN schedules ON bookings.schedule_id = schedules.schedule_id
          ORDER BY bookings.booking_id ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) { $bookings[] = $row; }

// 4. Revenue Details (Urut ID ASC)
$revenueDetails = [];
$query = "SELECT bookings.booking_id, bookings.total_amount, bookings.status, bookings.booking_date 
          FROM bookings 
          WHERE status = 'PAID'
          ORDER BY bookings.booking_id ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) { $revenueDetails[] = $row; }

// Dropdowns
$stations = [];
$query = "SELECT station_id, station_name FROM stations ORDER BY station_id ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) { $stations[] = $row; }

$trains = [];
$query = "SELECT train_id, train_name FROM trains ORDER BY train_id ASC";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) { $trains[] = $row; }

$minDate = date('Y-m-d\TH:i');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const cards = document.querySelectorAll('.dashboard-card');
            cards.forEach(card => {
                card.addEventListener('click', function () {
                    const target = this.getAttribute('data-target');
                    const modal = new bootstrap.Modal(document.getElementById(target));
                    modal.show();
                });
            });
        });
    </script>
</head>
<body>

    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top mb-4">
        <div class="container">
            <a class="navbar-brand" href="admin.php"><i class="bi bi-speedometer2"></i> Admin Panel</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item"><a class="nav-link active" href="admin.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="QnA_Admin.php">Manajemen Q&A</a></li>
                </ul>
                <div class="d-flex">
                    <span class="navbar-text me-3 text-light">Hello, Admin</span>
                    <a href="logout.php" class="btn btn-danger btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container">
        <h1 class="text-center mb-4">Admin Dashboard</h1>

        <section class="my-4">
            <h2>Overview</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-bg-primary mb-3 dashboard-card h-100" data-target="revenueModal" style="cursor: pointer;">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-cash-coin"></i> Total Revenue (PAID)</h5>
                            <p class="card-text fs-4">Rp<?= number_format($totalRevenue, 2, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-success mb-3 dashboard-card h-100" data-target="usersModal" style="cursor: pointer;">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-people"></i> Total Users</h5>
                            <p class="card-text fs-4"><?= $totalUsers ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-warning mb-3 dashboard-card h-100" data-target="bookingsModal" style="cursor: pointer;">
                        <div class="card-body">
                            <h5 class="card-title"><i class="bi bi-ticket-perforated"></i> Total Bookings</h5>
                            <p class="card-text fs-4"><?= $totalBookings ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <div class="modal fade" id="revenueModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Revenue Details</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>ID</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                <?php foreach ($revenueDetails as $r): ?>
                                <tr>
                                    <td><?= $r['booking_id'] ?></td>
                                    <td>Rp<?= number_format($r['total_amount'], 2, ',', '.') ?></td>
                                    <td><span class="badge bg-success"><?= $r['status'] ?></span></td>
                                    <td><?= $r['booking_date'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="usersModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">Users</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th></tr></thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td><?= $u['user_id'] ?></td>
                                    <td><?= $u['full_name'] ?></td>
                                    <td><?= $u['email'] ?></td>
                                    <td><?= $u['role'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="bookingsModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title">All Bookings</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                    <div class="modal-body">
                        <table class="table table-bordered table-striped">
                            <thead><tr><th>ID</th><th>User</th><th>Dep</th><th>Arr</th><th>Amount</th><th>Status</th></tr></thead>
                            <tbody>
                                <?php foreach ($bookings as $b): ?>
                                <tr>
                                    <td><?= $b['booking_id'] ?></td>
                                    <td><?= $b['full_name'] ?></td>
                                    <td><?= date('d/m H:i', strtotime($b['departure_time'])) ?></td>
                                    <td><?= date('d/m H:i', strtotime($b['arrival_time'])) ?></td>
                                    <td>Rp<?= number_format($b['total_amount'], 2, ',', '.') ?></td>
                                    <td>
                                        <?php 
                                            $st = $b['status'];
                                            $badgeClass = 'bg-secondary'; // Default
                                            if($st == 'PAID') $badgeClass = 'bg-success';
                                            elseif($st == 'PENDING') $badgeClass = 'bg-warning text-dark';
                                            elseif($st == 'CANCELLED') $badgeClass = 'bg-danger';
                                            elseif($st == 'CONFIRMED') $badgeClass = 'bg-primary';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= $st ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <section class="my-4">
            <h2>Manage Schedules</h2>
            
            <div class="card p-3 shadow-sm mb-4">
                <form method="POST" class="mb-3">
                    <input type="hidden" name="action" value="add_schedule">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <select name="train_id" class="form-select" required>
                                <option value="" disabled selected>Select Train</option>
                                <?php foreach ($trains as $t): ?><option value="<?= $t['train_id'] ?>"><?= $t['train_name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="origin_station_id" class="form-select" required>
                                <option value="" disabled selected>Origin</option>
                                <?php foreach ($stations as $s): ?><option value="<?= $s['station_id'] ?>"><?= $s['station_name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select name="destination_station_id" class="form-select" required>
                                <option value="" disabled selected>Destination</option>
                                <?php foreach ($stations as $s): ?><option value="<?= $s['station_id'] ?>"><?= $s['station_name'] ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3"><input type="datetime-local" name="departure_time" class="form-control" min="<?= $minDate ?>" required></div>
                        <div class="col-md-3"><input type="datetime-local" name="arrival_time" class="form-control" min="<?= $minDate ?>" required></div>
                        <div class="col-md-3"><input type="number" step="0.01" name="price" class="form-control" placeholder="Price" required></div>
                        <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Add Schedule</button></div>
                    </div>
                </form>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-dark">
                        <tr><th>ID</th><th>Train</th><th>Origin</th><th>Dest</th><th>Dep</th><th>Arr</th><th>Price</th><th>Actions</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($schedules as $sch): ?>
                            <tr>
                                <td><?= $sch['schedule_id'] ?></td>
                                <td><?= $sch['train_name'] ?></td>
                                <td><?= $sch['origin_station'] ?></td>
                                <td><?= $sch['destination_station'] ?></td>
                                <td><?= $sch['departure_time'] ?></td>
                                <td><?= $sch['arrival_time'] ?></td>
                                <td>Rp<?= number_format($sch['price'], 2, ',', '.') ?></td>
                                <td>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="schedule_id" value="<?= $sch['schedule_id'] ?>">
                                        <input type="hidden" name="action" value="delete_schedule">
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this schedule?')">Delete</button>
                                    </form>
                                    
                                    <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editModal<?= $sch['schedule_id'] ?>">Edit</button>

                                    <div class="modal fade" id="editModal<?= $sch['schedule_id'] ?>" tabindex="-1" aria-hidden="true">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header"><h5 class="modal-title">Edit Schedule #<?= $sch['schedule_id'] ?></h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                <div class="modal-body">
                                                    <form method="POST">
                                                        <input type="hidden" name="action" value="edit_schedule">
                                                        <input type="hidden" name="schedule_id" value="<?= $sch['schedule_id'] ?>">
                                                        
                                                        <div class="mb-3">
                                                            <label>Train</label>
                                                            <select name="train_id" class="form-select" required>
                                                                <?php foreach ($trains as $t): ?>
                                                                    <option value="<?= $t['train_id'] ?>" <?= $sch['train_id'] == $t['train_id'] ? 'selected' : '' ?>><?= $t['train_name'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label>Origin</label>
                                                                <select name="origin_station_id" class="form-select" required>
                                                                    <?php foreach ($stations as $s): ?>
                                                                        <option value="<?= $s['station_id'] ?>" <?= $sch['origin_station_id'] == $s['station_id'] ? 'selected' : '' ?>><?= $s['station_name'] ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label>Destination</label>
                                                                <select name="destination_station_id" class="form-select" required>
                                                                    <?php foreach ($stations as $s): ?>
                                                                        <option value="<?= $s['station_id'] ?>" <?= $sch['destination_station_id'] == $s['station_id'] ? 'selected' : '' ?>><?= $s['station_name'] ?></option>
                                                                    <?php endforeach; ?>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-6 mb-3">
                                                                <label>Departure</label>
                                                                <input type="datetime-local" name="departure_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($sch['departure_time'])) ?>" required>
                                                            </div>
                                                            <div class="col-md-6 mb-3">
                                                                <label>Arrival</label>
                                                                <input type="datetime-local" name="arrival_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($sch['arrival_time'])) ?>" required>
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label>Price</label>
                                                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $sch['price'] ?>" required>
                                                        </div>
                                                        <div class="text-end">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>