<?php
session_start();
require_once 'db_config.php';

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: HTML_login.html');
    exit;
}

// Fetch data for the dashboard
$totalRevenue = 0;
$totalUsers = 0;
$totalBookings = 0;

// Get total revenue
$query = "SELECT SUM(total_amount) AS revenue FROM bookings WHERE payment_status = 'paid'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $totalRevenue = $row['revenue'] ?? 0;
}

// Get total users
$query = "SELECT COUNT(*) AS user_count FROM users WHERE role = 'user'";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $totalUsers = $row['user_count'];
}

// Get total bookings
$query = "SELECT COUNT(*) AS booking_count FROM bookings";
$result = mysqli_query($conn, $query);
if ($row = mysqli_fetch_assoc($result)) {
    $totalBookings = $row['booking_count'];
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];

        switch ($action) {
            case 'add_schedule':
                $trainId = $_POST['train_id'];
                $originStationId = $_POST['origin_station_id'];
                $destinationStationId = $_POST['destination_station_id'];
                $departureTime = $_POST['departure_time'];
                $arrivalTime = $_POST['arrival_time'];
                $price = $_POST['price'];
                $query = "INSERT INTO schedules (train_id, origin_station_id, destination_station_id, departure_time, arrival_time, price) VALUES ('$trainId', '$originStationId', '$destinationStationId', '$departureTime', '$arrivalTime', '$price')";
                mysqli_query($conn, $query);

                // Redirect to prevent form resubmission
                header('Location: admin.php');
                exit;

            case 'delete_schedule':
                $scheduleId = $_POST['schedule_id'];
                $query = "DELETE FROM schedules WHERE schedule_id = $scheduleId";
                mysqli_query($conn, $query);
                break;

            case 'edit_schedule':
                $scheduleId = $_POST['schedule_id'];
                $trainId = $_POST['train_id'];
                $originStationId = $_POST['origin_station_id'];
                $destinationStationId = $_POST['destination_station_id'];
                $departureTime = $_POST['departure_time'];
                $arrivalTime = $_POST['arrival_time'];
                $price = $_POST['price'];
                $query = "UPDATE schedules SET train_id = '$trainId', origin_station_id = '$originStationId', destination_station_id = '$destinationStationId', departure_time = '$departureTime', arrival_time = '$arrivalTime', price = '$price' WHERE schedule_id = $scheduleId";
                mysqli_query($conn, $query);
                break;
        }
    }
}

// Fetch schedules for management
$schedules = [];
$query = "SELECT schedules.schedule_id, trains.train_name, origin.station_name AS origin_station, destination.station_name AS destination_station, schedules.departure_time, schedules.arrival_time, schedules.price FROM schedules
          JOIN trains ON schedules.train_id = trains.train_id
          JOIN stations AS origin ON schedules.origin_station_id = origin.station_id
          JOIN stations AS destination ON schedules.destination_station_id = destination.station_id";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $schedules[] = $row;
}

// Fetch users for the dashboard
$users = [];
$query = "SELECT user_id, full_name, email, phone, role FROM users";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

// Fetch bookings for the dashboard
$bookings = [];
$query = "SELECT bookings.booking_id, users.full_name, schedules.departure_time, schedules.arrival_time, bookings.total_amount, bookings.payment_status FROM bookings
          JOIN users ON bookings.user_id = users.user_id
          JOIN schedules ON bookings.schedule_id = schedules.schedule_id";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $bookings[] = $row;
}

// Fetch revenue details for the dashboard
$revenueDetails = [];
$query = "SELECT bookings.booking_id, bookings.total_amount, bookings.payment_status, bookings.booking_date FROM bookings WHERE payment_status = 'paid'";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $revenueDetails[] = $row;
}

// Fetch stations for schedule input
$stations = [];
$query = "SELECT station_id, station_name FROM stations";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $stations[] = $row;
}

// Fetch trains for schedule input
$trains = [];
$query = "SELECT train_id, train_name FROM trains";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
    $trains[] = $row;
}

// Ensure edit form is displayed for schedules
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_schedule') {
    $scheduleId = $_POST['schedule_id'];
    $query = "SELECT * FROM schedules WHERE schedule_id = $scheduleId";
    $result = mysqli_query($conn, $query);
    $scheduleToEdit = mysqli_fetch_assoc($result);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <div class="container mt-5">
        <h1 class="text-center">Admin Dashboard</h1>

        <section class="my-4">
            <h2>Dashboard</h2>
            <div class="row">
                <div class="col-md-4">
                    <div class="card text-bg-primary mb-3 dashboard-card" data-target="revenueModal">
                        <div class="card-body">
                            <h5 class="card-title">Total Revenue</h5>
                            <p class="card-text">Rp<?= number_format($totalRevenue, 2, ',', '.') ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-success mb-3 dashboard-card" data-target="usersModal">
                        <div class="card-body">
                            <h5 class="card-title">Total Users</h5>
                            <p class="card-text"><?= $totalUsers ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-bg-warning mb-3 dashboard-card" data-target="bookingsModal">
                        <div class="card-body">
                            <h5 class="card-title">Total Bookings</h5>
                            <p class="card-text"><?= $totalBookings ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Modals -->
        <div class="modal fade" id="revenueModal" tabindex="-1" aria-labelledby="revenueModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="revenueModalLabel">Revenue Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Booking ID</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                    <th>Booking Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($revenueDetails as $revenue): ?>
                                    <tr>
                                        <td><?= $revenue['booking_id'] ?></td>
                                        <td>Rp<?= number_format($revenue['total_amount'], 2, ',', '.') ?></td>
                                        <td><?= $revenue['payment_status'] ?></td>
                                        <td><?= $revenue['booking_date'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="usersModal" tabindex="-1" aria-labelledby="usersModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="usersModalLabel">Users</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Full Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['user_id'] ?></td>
                                        <td><?= $user['full_name'] ?></td>
                                        <td><?= $user['email'] ?></td>
                                        <td><?= $user['phone'] ?></td>
                                        <td><?= $user['role'] ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal fade" id="bookingsModal" tabindex="-1" aria-labelledby="bookingsModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="bookingsModalLabel">Bookings</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Departure</th>
                                    <th>Arrival</th>
                                    <th>Total Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking): ?>
                                    <tr>
                                        <td><?= $booking['booking_id'] ?></td>
                                        <td><?= $booking['full_name'] ?></td>
                                        <td><?= $booking['departure_time'] ?></td>
                                        <td><?= $booking['arrival_time'] ?></td>
                                        <td>Rp<?= number_format($booking['total_amount'], 2, ',', '.') ?></td>
                                        <td><?= $booking['payment_status'] ?></td>
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
            <form method="POST" class="mb-3">
                <input type="hidden" name="action" value="<?= isset($scheduleToEdit) ? 'edit_schedule' : 'add_schedule' ?>">
                <?php if (isset($scheduleToEdit)): ?>
                    <input type="hidden" name="schedule_id" value="<?= $scheduleToEdit['schedule_id'] ?>">
                <?php endif; ?>
                <div class="row g-3">
                    <div class="col-md-3">
                        <select name="train_id" class="form-select" required>
                            <option value="" disabled <?= !isset($scheduleToEdit) ? 'selected' : '' ?>>Select Train</option>
                            <?php foreach ($trains as $train): ?>
                                <option value="<?= $train['train_id'] ?>" <?= isset($scheduleToEdit) && $scheduleToEdit['train_id'] == $train['train_id'] ? 'selected' : '' ?>>Train ID <?= $train['train_id'] ?> - <?= $train['train_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="origin_station_id" class="form-select" required>
                            <option value="" disabled <?= !isset($scheduleToEdit) ? 'selected' : '' ?>>Select Origin Station</option>
                            <?php foreach ($stations as $station): ?>
                                <option value="<?= $station['station_id'] ?>" <?= isset($scheduleToEdit) && $scheduleToEdit['origin_station_id'] == $station['station_id'] ? 'selected' : '' ?>>Station ID <?= $station['station_id'] ?> - <?= $station['station_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="destination_station_id" class="form-select" required>
                            <option value="" disabled <?= !isset($scheduleToEdit) ? 'selected' : '' ?>>Select Destination Station</option>
                            <?php foreach ($stations as $station): ?>
                                <option value="<?= $station['station_id'] ?>" <?= isset($scheduleToEdit) && $scheduleToEdit['destination_station_id'] == $station['station_id'] ? 'selected' : '' ?>>Station ID <?= $station['station_id'] ?> - <?= $station['station_name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="datetime-local" name="departure_time" class="form-control" placeholder="Departure Time" value="<?= isset($scheduleToEdit) ? date('Y-m-d\TH:i', strtotime($scheduleToEdit['departure_time'])) : '' ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="datetime-local" name="arrival_time" class="form-control" placeholder="Arrival Time" value="<?= isset($scheduleToEdit) ? date('Y-m-d\TH:i', strtotime($scheduleToEdit['arrival_time'])) : '' ?>" required>
                    </div>
                    <div class="col-md-3">
                        <input type="number" step="0.01" name="price" class="form-control" placeholder="Price" value="<?= isset($scheduleToEdit) ? $scheduleToEdit['price'] : '' ?>" required>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">Submit</button>
                    </div>
                </div>
            </form>

            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Schedule ID</th>
                        <th>Train</th>
                        <th>Origin Station</th>
                        <th>Destination Station</th>
                        <th>Departure Time</th>
                        <th>Arrival Time</th>
                        <th>Price</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= $schedule['schedule_id'] ?></td>
                            <td><?= $schedule['train_name'] ?></td>
                            <td><?= $schedule['origin_station'] ?></td>
                            <td><?= $schedule['destination_station'] ?></td>
                            <td><?= $schedule['departure_time'] ?></td>
                            <td><?= $schedule['arrival_time'] ?></td>
                            <td>Rp<?= number_format($schedule['price'], 2, ',', '.') ?></td>
                            <td>
                                <form method="POST" class="d-inline">
                                    <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
                                    <input type="hidden" name="action" value="delete_schedule">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this schedule?')">Delete</button>
                                </form>
                                <button class="btn btn-warning btn-sm" data-bs-toggle="modal" data-bs-target="#editScheduleModal<?= $schedule['schedule_id'] ?>">Edit</button>

                                <!-- Edit Schedule Modal -->
                                <div class="modal fade" id="editScheduleModal<?= $schedule['schedule_id'] ?>" tabindex="-1" aria-labelledby="editScheduleModalLabel<?= $schedule['schedule_id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="editScheduleModalLabel<?= $schedule['schedule_id'] ?>">Edit Schedule - ID <?= $schedule['schedule_id'] ?></h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                <form method="POST">
                                                    <input type="hidden" name="action" value="edit_schedule">
                                                    <input type="hidden" name="schedule_id" value="<?= $schedule['schedule_id'] ?>">
                                                    <div class="row g-3">
                                                        <div class="col-md-4">
                                                            <label>Train</label>
                                                            <select name="train_id" class="form-select" required>
                                                                <option value="" disabled>Select Train</option>
                                                                <?php foreach ($trains as $train): ?>
                                                                    <option value="<?= $train['train_id'] ?>" <?= $schedule['train_id'] == $train['train_id'] ? 'selected' : '' ?>>Train ID <?= $train['train_id'] ?> - <?= $train['train_name'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label>Origin Station</label>
                                                            <select name="origin_station_id" class="form-select" required>
                                                                <option value="" disabled>Select Origin Station</option>
                                                                <?php foreach ($stations as $station): ?>
                                                                    <option value="<?= $station['station_id'] ?>" <?= $schedule['origin_station_id'] == $station['station_id'] ? 'selected' : '' ?>>Station ID <?= $station['station_id'] ?> - <?= $station['station_name'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label>Destination Station</label>
                                                            <select name="destination_station_id" class="form-select" required>
                                                                <option value="" disabled>Select Destination Station</option>
                                                                <?php foreach ($stations as $station): ?>
                                                                    <option value="<?= $station['station_id'] ?>" <?= $schedule['destination_station_id'] == $station['station_id'] ? 'selected' : '' ?>>Station ID <?= $station['station_id'] ?> - <?= $station['station_name'] ?></option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label>Departure Time</label>
                                                            <input type="datetime-local" name="departure_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($schedule['departure_time'])) ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label>Arrival Time</label>
                                                            <input type="datetime-local" name="arrival_time" class="form-control" value="<?= date('Y-m-d\TH:i', strtotime($schedule['arrival_time'])) ?>" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label>Price</label>
                                                            <input type="number" step="0.01" name="price" class="form-control" value="<?= $schedule['price'] ?>" required>
                                                        </div>
                                                        <div class="col-md-4 d-flex align-items-end">
                                                            <button type="submit" class="btn btn-primary w-100">Update Schedule</button>
                                                        </div>
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
        </section>

        <a href="logout.php" class="btn btn-secondary">Logout</a>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>