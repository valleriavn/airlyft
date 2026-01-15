<?php
// confirm_payment.php
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!isset($input['transaction_id']) || !isset($input['booking_id']) || !isset($input['payment_id'])) {
    echo json_encode(['error' => 'Missing transaction data']);
    exit;
}

$transaction_id = $input['transaction_id'];
$booking_id = $input['booking_id'];
$payment_id = $input['payment_id'];

try {
    $conn->begin_transaction();
    
    // 1. Update Payment record
    $update_payment = $conn->prepare("
        UPDATE Payment 
        SET payment_status = 'Paid', 
            paid_at = NOW(),
            transaction_id = ?
        WHERE payment_id = ?
    ");
    $update_payment->bind_param("si", $transaction_id, $payment_id);
    $update_payment->execute();
    
    // 2. Update Booking status
    $update_booking = $conn->prepare("
        UPDATE Booking 
        SET booking_status = 'Confirmed'
        WHERE booking_id = ?
    ");
    $update_booking->bind_param("i", $booking_id);
    $update_booking->execute();
    
    $conn->commit();
    
    // Get booking details for session
    $stmt = $conn->prepare("
        SELECT b.booking_id, b.total_amount,
               l.aircraft_name,
               p.place_name,
               s.airport,
               s.departure_time,
               pass.passenger_f_name, pass.passenger_l_name
        FROM Booking b
        JOIN Schedule s ON b.sched_id = s.schedule_id
        JOIN Lift l ON s.lift_id = l.lift_id
        JOIN Place p ON s.place_id = p.place_id
        JOIN Passenger pass ON b.booking_id = pass.booking_id
        WHERE b.booking_id = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking = $result->fetch_assoc();
    
    if ($booking) {
        // Store for success page
        $_SESSION['booking'] = [
            'booking_id' => $booking['booking_id'],
            'transaction_id' => $transaction_id,
            'amount' => $booking['total_amount'],
            'aircraft' => $booking['aircraft_name'],
            'destination' => $booking['place_name'],
            'departure' => $booking['airport'],
            'departure_date_time' => $booking['departure_time'],
            'passenger_name' => $booking['passenger_f_name'] . ' ' . $booking['passenger_l_name']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Payment confirmed successfully'
    ]);
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Confirm payment error: " . $e->getMessage());
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}