<?php
// bookingProcess.php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$required_fields = [
    'lift_id', 'place_id', 'departure_date', 'departure_time',
    'airport', 'passengers', 'total_amount', 'trip_type'
];

foreach ($required_fields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['error' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $conn->begin_transaction();
    
    // 1. Create Schedule
    $departure_time = $data['departure_date'] . ' ' . $data['departure_time'];
    $arrival_time = date('Y-m-d H:i:s', strtotime($departure_time . ' + 2 hours'));
    
    $schedule_stmt = $conn->prepare("
        INSERT INTO Schedule (lift_id, place_id, departure_time, arrival_time, airport) 
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $schedule_stmt->bind_param(
        "iisss",
        $data['lift_id'],
        $data['place_id'],
        $departure_time,
        $arrival_time,
        $data['airport']
    );
    
    if (!$schedule_stmt->execute()) {
        throw new Exception('Failed to create schedule: ' . $schedule_stmt->error);
    }
    
    $schedule_id = $conn->insert_id;
    
    // 2. Create Payment
    $payment_stmt = $conn->prepare("
        INSERT INTO Payment (amount, method, payment_status) 
        VALUES (?, 'PayPal', 'Pending')
    ");
    $payment_stmt->bind_param("d", $data['total_amount']);
    
    if (!$payment_stmt->execute()) {
        throw new Exception('Failed to create payment: ' . $payment_stmt->error);
    }
    
    $payment_id = $conn->insert_id;
    
    // 3. Create Booking
    $booking_stmt = $conn->prepare("
        INSERT INTO Booking (user_id, sched_id, payment_id, total_amount, booking_status) 
        VALUES (?, ?, ?, ?, 'Pending')
    ");
    $booking_stmt->bind_param("iiid", $user_id, $schedule_id, $payment_id, $data['total_amount']);
    
    if (!$booking_stmt->execute()) {
        throw new Exception('Failed to create booking: ' . $booking_stmt->error);
    }
    
    $booking_id = $conn->insert_id;
    
    // 4. Create Passengers
    $passenger_count = min(
        count($data['passenger_f_names'] ?? []),
        count($data['passenger_l_names'] ?? []),
        count($data['passenger_emails'] ?? []),
        count($data['passenger_phones'] ?? []),
        count($data['passenger_insurances'] ?? [])
    );
    
    if ($passenger_count > 0) {
        $passenger_stmt = $conn->prepare("
            INSERT INTO Passenger (user_id, booking_id, passenger_f_name, passenger_l_name, 
                                  passenger_phone_number, insurance) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        for ($i = 0; $i < $passenger_count; $i++) {
            $phone = preg_replace('/\D/', '', $data['passenger_phones'][$i] ?? '');
            $phone_value = !empty($phone) ? $phone : null;
            $insurance = ($data['passenger_insurances'][$i] ?? 'no') === 'yes' ? 'yes' : 'no';
            
            $passenger_stmt->bind_param(
                "iissss",
                $user_id,
                $booking_id,
                $data['passenger_f_names'][$i],
                $data['passenger_l_names'][$i],
                $phone_value,
                $insurance
            );
            
            if (!$passenger_stmt->execute()) {
                throw new Exception('Failed to add passenger: ' . $passenger_stmt->error);
            }
        }
        
        $first_passenger = [
            'email' => $data['passenger_emails'][0] ?? '',
            'phone' => $data['passenger_phones'][0] ?? '',
            'name' => ($data['passenger_f_names'][0] ?? '') . ' ' . ($data['passenger_l_names'][0] ?? '')
        ];
    }
    
    $update_payment = $conn->prepare("
        UPDATE Payment SET booking_id = ? WHERE payment_id = ?
    ");
    $update_payment->bind_param("ii", $booking_id, $payment_id);
    $update_payment->execute();
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'booking_id' => $booking_id,
        'payment_id' => $payment_id,
        'schedule_id' => $schedule_id,
        'total_amount' => $data['total_amount'],
        'passenger_email' => $first_passenger['email'] ?? '',
        'passenger_phone' => $first_passenger['phone'] ?? '',
        'passenger_name' => $first_passenger['name'] ?? '',
        'message' => 'Booking created successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error: ' . $e->getMessage(),
        'debug' => $conn->error ?? 'No connection error'
    ]);
}