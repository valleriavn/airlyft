<?php
// booking/check_availability.php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['available' => false, 'error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$lift_id = $input['lift_id'] ?? 0;
$departure_date = $input['departure_date'] ?? '';
$departure_time = $input['departure_time'] ?? '';

if (!$lift_id || !$departure_date) {
    echo json_encode(['available' => false, 'error' => 'Missing parameters']);
    exit;
}

// Check if date is in the past
$current_date = date('Y-m-d');
$current_time = date('H:i:s');
if ($departure_date < $current_date) {
    echo json_encode([
        'available' => false, 
        'message' => 'Cannot book in the past',
        'is_past' => true
    ]);
    exit;
}

// Check if aircraft is already booked on this date
$stmt = $conn->prepare("
    SELECT COUNT(*) as booked_count
    FROM Schedule s
    JOIN Booking b ON s.schedule_id = b.sched_id
    WHERE s.lift_id = ? 
    AND DATE(s.departure_time) = ?
    AND b.booking_status IN ('Confirmed', 'Pending')
");

$stmt->bind_param("is", $lift_id, $departure_date);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();
$stmt->close();

$available = ($row['booked_count'] == 0);

echo json_encode([
    'available' => $available,
    'message' => $available ? 'Aircraft available' : 'Aircraft already booked on this date',
    'is_past' => false
]);
?>