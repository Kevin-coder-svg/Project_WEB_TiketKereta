<?php
header('Content-Type: application/json');
include_once 'db_config.php';
$schedule_id = isset($_GET['schedule_id']) ? (int)$_GET['schedule_id'] : 0;
if ($schedule_id <= 0) {
  echo json_encode([]);
  exit;
}
$conn = function_exists('get_db_connection') ? get_db_connection() : (isset($conn) ? $conn : null);
if (!$conn) {
  echo json_encode([]);
  exit;
}

$booked = [];
$checkTickets = $conn->query("SHOW TABLES LIKE 'tickets'");
if ($checkTickets && $checkTickets->num_rows > 0) {
  $sql = "SELECT t.seat_number FROM tickets t JOIN bookings b ON t.booking_id = b.booking_id WHERE b.schedule_id = ? AND t.seat_number IS NOT NULL AND b.status IN ('PENDING','CONFIRMED','PAID')";
  $stmt = $conn->prepare($sql);
  if ($stmt) {
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
      $sn = $r['seat_number'];
      if ($sn !== null && $sn !== '') $booked[] = (string)$sn;
    }
    $stmt->close();
  }
}



echo json_encode(array_values(array_unique($booked)));
