<?php
/**
 * SMS Webhook Receiver
 * Uses SIMPLE direct logic for receiving SMS
 */

require_once __DIR__ . '/../../auth/config.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

/**
 * Normalize phone number
 */
function normalize_phone($phone) {
    return preg_replace('/[^0-9]/', '', (string)$phone);
}

// Read incoming data
$rawData  = file_get_contents('php://input');
$postData = $_POST;

// Try JSON first, fallback to POST
$data = json_decode($rawData, true);
if (!is_array($data)) {
    $data = $postData;
}

// Extract SMS fields (SIMPLE LOGIC — as requested)
$from     = $data['from'] 
         ?? $data['sender'] 
         ?? $data['phone'] 
         ?? $data['msisdn'] 
         ?? 'Unknown';

$message  = $data['message'] 
         ?? $data['text'] 
         ?? $data['content'] 
         ?? $data['body'] 
         ?? 'No Message';

$received = $data['timestamp'] 
         ?? $data['time'] 
         ?? date("Y-m-d H:i:s");

// Normalize phone
$phone_clean = normalize_phone($from);

// Find latest booking linked to phone
$booking_id = null;
$user_id    = null;

$bookingQuery = $conn->prepare("
    SELECT b.booking_id, b.user_id
    FROM Booking b
    JOIN Users u ON b.user_id = u.user_id
    WHERE REPLACE(REPLACE(REPLACE(u.phone,'+',''),'-',''),' ','') = ?
    ORDER BY b.booking_id DESC
    LIMIT 1
");
$bookingQuery->bind_param("s", $phone_clean);
$bookingQuery->execute();
$bookingQuery->bind_result($foundBookingId, $foundUserId);

if ($bookingQuery->fetch()) {
    $booking_id = $foundBookingId;
    $user_id    = $foundUserId;
}
$bookingQuery->close();

// Detect snack selection
$snack_choice = null;
switch (strtoupper(trim($message))) {
    case 'A':
        $snack_choice = 'A - Chips & Nuts';
        break;
    case 'B':
        $snack_choice = 'B - Sandwich & Juice';
        break;
    case 'C':
        $snack_choice = 'C - Pasta & Champagne';
        break;
}

// Format SMS content
$formatted_message = "RECEIVED from {$from} at {$received}:\n{$message}";
$sms_status = 'Received';

// Insert into smsnotification
$insert = $conn->prepare("
    INSERT INTO smsnotification (booking_id, message, sms_status)
    VALUES (?, ?, ?)
");
$insert->bind_param(
    "iss",
    $booking_id,
    $formatted_message,
    $sms_status
);

if (!$insert->execute()) {
    file_put_contents(
        __DIR__ . "/sms_log.txt",
        "[" . date("Y-m-d H:i:s") . "] DB ERROR: " . $insert->error . PHP_EOL,
        FILE_APPEND
    );
}
$insert->close();

// Log raw SMS to file
file_put_contents(
    __DIR__ . "/sms_log.txt",
    "[" . date("Y-m-d H:i:s") . "] FROM:$from MESSAGE:$message BOOKING_ID:$booking_id USER_ID:$user_id RAW:$rawData" . PHP_EOL,
    FILE_APPEND
);

// Optional: log snack choice
if ($snack_choice && $booking_id) {
    error_log("SMS Snack selected: {$snack_choice} for booking #{$booking_id}");
}

// Return response
http_response_code(200);
echo json_encode([
    "status"       => "ok",
    "booking_id"   => $booking_id,
    "user_id"      => $user_id,
    "snack_choice" => $snack_choice
]);

$conn->close();
