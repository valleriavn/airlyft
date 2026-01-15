<?php
// capturePayment.php - Capture PayPal payment and update booking status
session_start();
require_once '../db/connect.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['orderID'])) {
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

$user_id = $_SESSION['user_id'];
$order_id = $input['orderID'];

// Extract booking_id from order_id (format: ORDER-{timestamp}-{booking_id})
$parts = explode('-', $order_id);
$booking_id = end($parts);

// Verify order exists in session
if (!isset($_SESSION['paypal_order_' . $booking_id])) {
    echo json_encode(['error' => 'Invalid order']);
    exit;
}

$order_data = $_SESSION['paypal_order_' . $booking_id];

// Begin transaction
$conn->begin_transaction();

try {
    // 1. Verify booking belongs to user
    $stmt = $conn->prepare("
        SELECT booking_id, total_amount 
        FROM bookings 
        WHERE booking_id = ? AND user_id = ? AND payment_status = 'pending'
    ");
    $stmt->bind_param("ii", $booking_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Booking not found or already processed');
    }

    $booking = $result->fetch_assoc();
    $stmt->close();

    // 2. Generate transaction ID
    $transaction_id = 'TXN-' . time() . '-' . $booking_id;

    // 3. Update booking status to completed
    $stmt = $conn->prepare("
        UPDATE bookings 
        SET payment_status = 'completed', 
            transaction_id = ?
        WHERE booking_id = ?
    ");
    $stmt->bind_param("si", $transaction_id, $booking_id);

    if (!$stmt->execute()) {
        throw new Exception('Failed to update booking: ' . $stmt->error);
    }
    $stmt->close();

    // 4. Create payment record
    $stmt = $conn->prepare("
        INSERT INTO payments (
            booking_id, transaction_id, amount, 
            payment_method, payment_status, payment_date
        ) VALUES (?, ?, ?, 'PayPal', 'completed', NOW())
    ");
    $stmt->bind_param("isd", $booking_id, $transaction_id, $order_data['amount']);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create payment record: ' . $stmt->error);
    }
    $payment_id = $conn->insert_id;
    $stmt->close();

    // 5. Clear session data
    unset($_SESSION['paypal_order_' . $booking_id]);

    // 6. Get booking details for success page
    $stmt = $conn->prepare("
        SELECT 
            b.booking_id,
            b.destination,
            b.aircraft,
            b.trip_type,
            b.departure_location,
            b.departure_datetime,
            b.return_datetime,
            b.passengers_count,
            b.notes,
            b.total_amount,
            b.transaction_id
        FROM bookings b
        WHERE b.booking_id = ?
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $booking_details = $result->fetch_assoc();
    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Return success response
    echo json_encode([
        'success' => true,
        'transaction_id' => $transaction_id,
        'amount' => $booking_details['total_amount'],
        'booking_details' => $booking_details,
        'redirect_url' => '../booking/bookingSuccess.php?tx=' . $transaction_id .
            '&booking_id=' . $booking_id
    ]);
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();

    error_log('CapturePayment Error: ' . $e->getMessage());
    echo json_encode([
        'error' => 'Payment failed: ' . $e->getMessage(),
        'redirect_url' => '../booking/booking.php?error=payment_failed'
    ]);
}
