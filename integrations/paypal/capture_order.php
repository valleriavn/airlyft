<?php
// integrations/paypal/capture_order.php
session_start();
ob_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 0);
error_reporting(E_ALL); // Enable for debugging

// Load .env file from project root
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#') continue;
        
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes
            if ((str_starts_with($value, '"') && str_ends_with($value, '"')) || 
                (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                $value = substr($value, 1, -1);
            }
            
            if (!defined($key)) {
                define($key, $value);
            }
            $_ENV[$key] = $value;
        }
    }
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/../../db/connect.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';

/* =========================
   METHOD CHECK
========================= */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_end_clean();
    http_response_code(405);
    exit(json_encode([
        'success' => false,
        'error' => 'Method not allowed'
    ]));
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    ob_end_clean();
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'error' => 'Invalid JSON input'
    ]));
}

$order_id = trim($input['order_id'] ?? '');
$booking_id = trim($input['booking_id'] ?? '');

if (!$order_id || !$booking_id) {
    ob_end_clean();
    http_response_code(400);
    exit(json_encode([
        'success' => false,
        'error' => 'Missing order_id or booking_id'
    ]));
}

try {
    $stmt = $conn->prepare("
        SELECT b.booking_id, b.user_id, b.total_amount, b.payment_id,
               p.place_name, l.aircraft_name, u.email as user_email, u.phone as user_phone,
               s.departure_time, s.airport
        FROM Booking b
        JOIN Schedule s ON b.sched_id = s.schedule_id
        JOIN Lift l ON s.lift_id = l.lift_id
        JOIN Place p ON s.place_id = p.place_id
        JOIN Users u ON b.user_id = u.user_id
        WHERE b.booking_id = ? AND b.booking_status = 'Pending'
        LIMIT 1
    ");
    $stmt->bind_param("i", $booking_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Booking not found or already processed');
    }
    
    $booking = $result->fetch_assoc();
    $stmt->close();
    
    // Retrieve passenger phone number - handle INT type by converting to string
    $passenger_stmt = $conn->prepare("
        SELECT CAST(passenger_phone_number AS CHAR) as passenger_phone_number, 
               passenger_f_name, 
               passenger_l_name
        FROM Passenger 
        WHERE booking_id = ? 
        AND passenger_phone_number IS NOT NULL 
        AND passenger_phone_number != 0
        ORDER BY passenger_id ASC
        LIMIT 1
    ");
    $passenger_stmt->bind_param("i", $booking_id);
    $passenger_stmt->execute();
    $passenger_result = $passenger_stmt->get_result();
    $passenger = $passenger_result->fetch_assoc();
    $passenger_stmt->close();
    
    $booking['passenger_phone_number'] = null;
    if ($passenger && !empty($passenger['passenger_phone_number'])) {
        // Convert to string and clean the phone number
        $phone = trim((string)$passenger['passenger_phone_number']);
        
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format Philippine phone numbers
        if (strlen($phone) >= 10) {
            if (strlen($phone) === 10 && substr($phone, 0, 2) === '09') {
                // Format: 09123456789 -> +639123456789
                $phone = '+63' . substr($phone, 1);
            } elseif (strlen($phone) === 11 && substr($phone, 0, 3) === '639') {
                // Format: 639123456789 -> +639123456789
                $phone = '+' . $phone;
            } elseif (strlen($phone) === 12 && substr($phone, 0, 2) === '63') {
                // Format: 6391234567890 -> +6391234567890 (if already has country code)
                $phone = '+' . $phone;
            } elseif (strlen($phone) === 10 && substr($phone, 0, 1) !== '0') {
                // Format: 9123456789 -> +639123456789
                $phone = '+63' . $phone;
            } elseif (strlen($phone) === 13 && substr($phone, 0, 3) === '+63') {
                // Already formatted correctly
            } else {
                // Default: assume it needs country code
                if (strlen($phone) === 10) {
                    $phone = '+63' . $phone;
                }
            }
            
            // Validate final format
            if (preg_match('/^\+63[0-9]{10}$/', $phone)) {
                $booking['passenger_phone_number'] = $phone;
                $booking['passenger_f_name'] = $passenger['passenger_f_name'] ?? '';
                $booking['passenger_l_name'] = $passenger['passenger_l_name'] ?? '';
                error_log('Using passenger phone for booking #' . $booking_id . ': ' . $booking['passenger_phone_number']);
            } else {
                error_log('WARNING: Invalid phone number format after processing: ' . $phone . ' for booking #' . $booking_id);
            }
        } else {
            error_log('WARNING: Phone number too short: ' . $phone . ' (length: ' . strlen($phone) . ') for booking #' . $booking_id);
        }
    } 
    
    // Fallback to user phone if passenger phone is not available
    if (empty($booking['passenger_phone_number']) && !empty($booking['user_phone'])) {
        $phone = trim((string)$booking['user_phone']);
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        if (strlen($phone) >= 10) {
            if (strlen($phone) === 10 && substr($phone, 0, 2) === '09') {
                $phone = '+63' . substr($phone, 1);
            } elseif (strlen($phone) === 11 && substr($phone, 0, 3) === '639') {
                $phone = '+' . $phone;
            } elseif (strlen($phone) === 10 && substr($phone, 0, 1) !== '0') {
                $phone = '+63' . $phone;
            }
            
            // Validate final format
            if (preg_match('/^\+63[0-9]{10}$/', $phone)) {
                $booking['passenger_phone_number'] = $phone;
                error_log('Using user phone as fallback for booking #' . $booking_id . ': ' . $booking['passenger_phone_number']);
            } else {
                error_log('WARNING: Invalid user phone format after processing: ' . $phone . ' for booking #' . $booking_id);
            }
        }
    }
    
    if (empty($booking['passenger_phone_number'])) {
        error_log('WARNING: No valid phone number found for booking #' . $booking_id . ' (passenger: ' . ($passenger['passenger_phone_number'] ?? 'N/A') . ', user: ' . ($booking['user_phone'] ?? 'N/A') . ')');
    }
    
} catch (Exception $e) {
    ob_end_clean();
    http_response_code(404);
    exit(json_encode([
        'success' => false, 
        'error' => 'Booking not found: ' . $e->getMessage()
    ]));
}

function get_paypal_token()
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => PAYPAL_API_BASE . "/v1/oauth2/token",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET,
        CURLOPT_HTTPHEADER => ["Accept: application/json"]
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        error_log("PayPal token error: HTTP $http_code - $response");
        return false;
    }

    $data = json_decode($response, true);
    return $data['access_token'] ?? false;
}

function send_confirmation_email($to, $phone, $booking_ref, $amount, $txn_id, $aircraftName, $destination, $departure_time)
{
    if (empty(GMAIL_SMTP_USER) || empty(GMAIL_SMTP_PASS) || empty(GMAIL_FROM_EMAIL)) {
        error_log('Email not sent: Gmail credentials not configured');
        return false;
    }
    
    if (empty($to) || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log('Email not sent: Invalid recipient email address: ' . $to);
        return false;
    }
    
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = GMAIL_SMTP_USER;
        $mail->Password = GMAIL_SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->SMTPDebug = 0;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 30;

        $mail->setFrom(GMAIL_FROM_EMAIL, GMAIL_FROM_NAME);
        $mail->addAddress($to);

        $mail->isHTML(true);
        $mail->Subject = 'AirLyft Booking Confirmation #' . $booking_ref;

        $departure_formatted = date('F d, Y h:i A', strtotime($departure_time));
        
        $mail->Body = "
<div style='max-width:600px;margin:0 auto;font-family:Arial,Helvetica,sans-serif;background:#ffffff;border-radius:8px;overflow:hidden;border:1px solid #e5e5e5;'>

  <!-- Header -->
  <div style='background:#0d6efd;color:#ffffff;padding:24px;text-align:center;'>
    <h2 style='margin:0;font-size:26px;'>✈️ Payment Confirmed</h2>
    <p style='margin:6px 0 0;font-size:14px;opacity:0.9;'>Official Booking Confirmation</p>
  </div>

  <!-- Body -->
  <div style='padding:24px;color:#333333;'>
    <p style='font-size:16px;margin-bottom:20px;'>
      Thank you for choosing <strong>AirLyft</strong>. Your payment has been successfully received.
    </p>

    <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse;font-size:15px;'>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Booking Reference</td>
        <td style='padding:10px 0;text-align:right;'>#{$booking_ref}</td>
      </tr>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Transaction ID</td>
        <td style='padding:10px 0;text-align:right;'>{$txn_id}</td>
      </tr>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Amount Paid</td>
        <td style='padding:10px 0;text-align:right;color:#198754;font-weight:bold;'>
          ₱ " . number_format($amount, 2) . "
        </td>
      </tr>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Aircraft</td>
        <td style='padding:10px 0;text-align:right;'>{$aircraftName}</td>
      </tr>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Destination</td>
        <td style='padding:10px 0;text-align:right;'>{$destination}</td>
      </tr>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Departure</td>
        <td style='padding:10px 0;text-align:right;'>{$departure_formatted}</td>
      </tr>
      <tr>
        <td style='padding:10px 0;font-weight:bold;'>Contact Phone</td>
        <td style='padding:10px 0;text-align:right;'>{$phone}</td>
      </tr>
    </table>

    <hr style='border:none;border-top:1px solid #e5e5e5;margin:24px 0;'>

    <p style='font-size:15px;line-height:1.6;'>
      Our flight operations team will contact you shortly with your complete itinerary and boarding details.
    </p>

    <p style='font-size:15px;margin-top:16px;'>
      We look forward to welcoming you on board.
    </p>

    <p style='margin-top:20px;font-weight:bold;'>— AirLyft Aviation Team</p>
  </div>

  <!-- Footer -->
  <div style='background:#f8f9fa;padding:16px;text-align:center;font-size:12px;color:#6c757d;'>
    © " . date('Y') . " AirLyft. All rights reserved.<br>
    This is an automated message. Please do not reply.
  </div>

</div>
";

        if ($mail->send()) {
            error_log('Confirmation email sent successfully to: ' . $to);
            return true;
        } else {
            error_log('Email send failed: ' . $mail->ErrorInfo);
            return false;
        }
    } catch (Exception $e) {
        error_log('Email exception: ' . $e->getMessage() . ' | PHPMailer Error: ' . ($mail->ErrorInfo ?? 'N/A'));
        return false;
    }
}

function send_sms($phone, $message)
{
    if (!defined('SMS_GATEWAY_USERNAME') || !defined('SMS_GATEWAY_PASSWORD') || !defined('SMS_GATEWAY_API')) {
        error_log('SMS not sent: SMS Gateway credentials not configured in config.php');
        return false;
    }
    
    if (empty(SMS_GATEWAY_USERNAME) || empty(SMS_GATEWAY_PASSWORD) || empty(SMS_GATEWAY_API)) {
        error_log('SMS not sent: SMS Gateway credentials are empty');
        return false;
    }
    
    if (empty($phone)) {
        error_log('SMS not sent: Phone number is empty');
        return false;
    }
    
    // Clean and format phone number
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    
    // Format for Philippines (+63XXXXXXXXXX)
    if (strlen($phone_clean) === 10 && substr($phone_clean, 0, 2) === '09') {
        $phone_formatted = '+63' . substr($phone_clean, 1);
    } elseif (strlen($phone_clean) === 11 && substr($phone_clean, 0, 3) === '639') {
        $phone_formatted = '+' . $phone_clean;
    } elseif (strlen($phone_clean) === 12 && substr($phone_clean, 0, 2) === '63') {
        $phone_formatted = '+' . $phone_clean;
    } elseif (strlen($phone_clean) === 10 && substr($phone_clean, 0, 1) !== '0') {
        $phone_formatted = '+63' . $phone_clean;
    } else {
        $phone_formatted = $phone;
    }
    
    // Final validation for Philippine numbers
    if (!preg_match('/^\+63[0-9]{10}$/', $phone_formatted)) {
        error_log('SMS not sent: Invalid Philippine phone format: ' . $phone_formatted . ' (original: ' . $phone . ')');
        return false;
    }
    
    error_log('SMS sending to: ' . $phone_formatted . ' (original: ' . $phone . ')');
    
    $payload = [
        'textMessage' => ['text' => $message],
        'phoneNumbers' => [$phone_formatted]
    ];

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => rtrim(SMS_GATEWAY_API, '/') . '/messages',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(SMS_GATEWAY_USERNAME . ":" . SMS_GATEWAY_PASSWORD)
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        error_log('SMS cURL error: ' . $curl_error);
        return false;
    }
    
    error_log('SMS Gateway Response - HTTP: ' . $http_code . ' | Response: ' . $response);
    
    // SUCCESS: HTTP 202 (Accepted) or 200 (OK) - SMS Gate returns 202 for "Accepted"
    if ($http_code === 202 || $http_code === 200) {
        error_log('SMS sent successfully to: ' . $phone_formatted);
        return true;
    } else {
        error_log('SMS send failed. HTTP Code: ' . $http_code . ' | Response: ' . $response);
        return false;
    }
}

function send_confirmation_sms($phone, $booking_ref, $amount, $txn_id, $aircraft, $departure_time)
{
    $departure_formatted = date('F d, Y h:i A', strtotime($departure_time));
    
    $message = "AirLyft Booking Confirmed\nRef: #{$booking_ref}\nTxn: {$txn_id}\nAmount: ₱" . number_format($amount, 2) . "\nAircraft: {$aircraft}\nDeparture: {$departure_formatted}\nStatus: Confirmed\nOur team will contact you shortly.";
    
    return send_sms($phone, $message);
}

function send_snack_sms($phone, $booking_ref)
{
    $message = "AirLyft Complimentary Snack Selection\n----------------------------------\nBooking: #{$booking_ref}\nPlease reply with your choice:\nA - Chips & Nuts\nB - Sandwich & Juice\nC - Pasta & Champagne\n\nReply with A, B, or C only.";
    
    return send_sms($phone, $message);
}

$token = get_paypal_token();
if (!$token) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'PayPal authentication failed'
    ]));
}

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => PAYPAL_API_BASE . "/v2/checkout/orders/{$order_id}/capture",
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $token",
        "Prefer: return=representation"
    ]
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($response, true);
if (!is_array($data)) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'Invalid PayPal response'
    ]));
}

if ($http_code >= 200 && $http_code < 300 && ($data['status'] ?? '') === 'COMPLETED') {

    $capture = $data['purchase_units'][0]['payments']['captures'][0] ?? [];
    $transaction_id = $capture['id'] ?? 'TXN-' . time();
    $amount = $capture['amount']['value'] ?? $booking['total_amount'];
    
    try {
        $conn->begin_transaction();
        
        if (empty($booking['payment_id'])) {
            throw new Exception('Payment ID not found for this booking');
        }
        
        $update_payment = $conn->prepare("
            UPDATE Payment 
            SET payment_status = 'Paid', 
                paid_at = NOW(),
                transaction_id = ?
            WHERE payment_id = ?
        ");
        $update_payment->bind_param("si", $transaction_id, $booking['payment_id']);
        $update_payment->execute();
        
        if ($update_payment->affected_rows === 0) {
            throw new Exception('Failed to update payment record');
        }
        $update_payment->close();
        
        $update_booking = $conn->prepare("
            UPDATE Booking 
            SET booking_status = 'Confirmed'
            WHERE booking_id = ?
        ");
        $update_booking->bind_param("i", $booking_id);
        $update_booking->execute();
        
        if ($update_booking->affected_rows === 0) {
            throw new Exception('Failed to update booking status');
        }
        $update_booking->close();
        
        $conn->commit();
        
        $email_subject = 'AirLyft Booking Confirmation #' . $booking_id;
        $email_recipient = $booking['user_email'] ?? '';
        
        $emailSent = false;
        $emailStatus = 'Failed';
        try {
            if (!empty($email_recipient)) {
                $emailSent = send_confirmation_email(
                    $email_recipient,
                    $booking['passenger_phone_number'] ?? '',
                    $booking_id,
                    $amount,
                    $transaction_id,
                    $booking['aircraft_name'],
                    $booking['place_name'],
                    $booking['departure_time']
                );
                $emailStatus = $emailSent ? 'Sent' : 'Failed';
            } else {
                error_log('Email not sent: User email is empty for booking #' . $booking_id);
                $emailStatus = 'Failed';
            }
        } catch (Exception $e) {
            error_log('Email sending exception: ' . $e->getMessage());
            $emailStatus = 'Failed';
        }
        
        try {
            $email_notif_stmt = $conn->prepare("
                INSERT INTO emailnotification (booking_id, recipient, subject, email_notif_status, sent_at)
                VALUES (?, ?, ?, ?, NOW())
            ");
            $email_notif_stmt->bind_param("isss", 
                $booking_id, 
                $email_recipient, 
                $email_subject, 
                $emailStatus
            );
            $email_notif_stmt->execute();
            $email_notif_stmt->close();
        } catch (Exception $e) {
            error_log('Failed to log email notification: ' . $e->getMessage());
        }
        
        // Prepare SMS confirmation message
        $sms_message = "AirLyft Booking Confirmed\nRef: #{$booking_id}\nTxn: {$transaction_id}\nAmount: ₱" . number_format($amount, 2) . "\nAircraft: {$booking['aircraft_name']}\nDeparture: " . date('F d, Y h:i A', strtotime($booking['departure_time'])) . "\nStatus: Confirmed\nOur team will contact you shortly.";
        
        $smsSent = false;
        $smsStatus = 'Failed';
        $snackSmsSent = false;
        $snackSmsStatus = 'Failed';
        
        // SMS should be sent to the user's phone (booking_id references user_id)
        // Format and validate user phone number
        $phone_to_use = null;
        if (!empty($booking['user_phone'])) {
            $phone = trim((string)$booking['user_phone']);
            $phone_clean = preg_replace('/[^0-9]/', '', $phone);
            
            // Format Philippine phone numbers
            if (strlen($phone_clean) >= 10) {
                if (strlen($phone_clean) === 10 && substr($phone_clean, 0, 2) === '09') {
                    // Format: 09123456789 -> +639123456789
                    $phone_formatted = '+63' . substr($phone_clean, 1);
                } elseif (strlen($phone_clean) === 11 && substr($phone_clean, 0, 3) === '639') {
                    // Format: 639123456789 -> +639123456789
                    $phone_formatted = '+' . $phone_clean;
                } elseif (strlen($phone_clean) === 12 && substr($phone_clean, 0, 2) === '63') {
                    // Format: 6391234567890 -> +6391234567890
                    $phone_formatted = '+' . $phone_clean;
                } elseif (strlen($phone_clean) === 10 && substr($phone_clean, 0, 1) !== '0') {
                    // Format: 9123456789 -> +639123456789
                    $phone_formatted = '+63' . $phone_clean;
                } else {
                    // Default: assume it needs country code
                    if (strlen($phone_clean) === 10) {
                        $phone_formatted = '+63' . $phone_clean;
                    } else {
                        $phone_formatted = $phone; // Use original if can't format
                    }
                }
                
                // Validate final format
                if (preg_match('/^\+63[0-9]{10}$/', $phone_formatted)) {
                    $phone_to_use = $phone_formatted;
                    error_log('Using user phone number for SMS: ' . $phone_to_use . ' (original: ' . $phone . ', booking #' . $booking_id . ')');
                } else {
                    error_log('WARNING: Invalid user phone format after processing: ' . $phone_formatted . ' (original: ' . $phone . ') for booking #' . $booking_id);
                }
            } else {
                error_log('WARNING: User phone number too short: ' . $phone . ' (cleaned length: ' . strlen($phone_clean) . ') for booking #' . $booking_id);
            }
        }
        
        // Fallback to passenger phone if user phone is not available/valid
        if (empty($phone_to_use) && !empty($booking['passenger_phone_number'])) {
            $phone_to_use = $booking['passenger_phone_number'];
            error_log('Using passenger phone number as fallback for SMS: ' . $phone_to_use . ' (booking #' . $booking_id . ')');
        }
        
        // Send confirmation SMS to user phone number
        if (!empty($phone_to_use)) {
            try {
                error_log('Attempting to send confirmation SMS to: ' . $phone_to_use . ' for booking #' . $booking_id);
                $smsSent = send_confirmation_sms(
                    $phone_to_use,
                    $booking_id,
                    $amount,
                    $transaction_id,
                    $booking['aircraft_name'],
                    $booking['departure_time']
                );
                $smsStatus = $smsSent ? 'Sent' : 'Failed';
                
                if ($smsSent) {
                    error_log('Confirmation SMS sent successfully to ' . $phone_to_use . ' for booking #' . $booking_id);
                    
                    // Log confirmation SMS notification
                    try {
                        $sms_notif_stmt = $conn->prepare("
                            INSERT INTO smsnotification (booking_id, message, sms_status)
                            VALUES (?, ?, ?)
                        ");
                        $sms_notif_stmt->bind_param("iss", 
                            $booking_id, 
                            $sms_message, 
                            $smsStatus
                        );
                        $sms_notif_stmt->execute();
                        $sms_notif_stmt->close();
                    } catch (Exception $e) {
                        error_log('Failed to log SMS notification: ' . $e->getMessage());
                    }
                    
                    // Wait a moment then send snack selection SMS
                    sleep(2);
                    $snackMessage = "AirLyft Complimentary Snack Selection\n----------------------------------\nBooking: #{$booking_id}\nPlease reply with your choice:\nA - Chips & Nuts\nB - Sandwich & Juice\nC - Pasta & Champagne\n\nReply with A, B, or C only.";
                    
                    $snackSmsSent = send_sms($phone_to_use, $snackMessage);
                    $snackSmsStatus = $snackSmsSent ? 'Sent' : 'Failed';
                    
                    if ($snackSmsSent) {
                        error_log('Snack selection SMS sent successfully to ' . $phone_to_use . ' for booking #' . $booking_id);
                        
                        // Log snack SMS notification
                        try {
                            $snack_sms_notif_stmt = $conn->prepare("
                                INSERT INTO smsnotification (booking_id, message, sms_status)
                                VALUES (?, ?, ?)
                            ");
                            $snack_sms_notif_stmt->bind_param("iss", 
                                $booking_id, 
                                $snackMessage, 
                                $snackSmsStatus
                            );
                            $snack_sms_notif_stmt->execute();
                            $snack_sms_notif_stmt->close();
                        } catch (Exception $e) {
                            error_log('Failed to log snack SMS notification: ' . $e->getMessage());
                        }
                    } else {
                        error_log('Snack selection SMS failed for booking #' . $booking_id);
                    }
                } else {
                    error_log('Confirmation SMS failed for booking #' . $booking_id . ' to ' . $phone_to_use);
                    
                    // Log failed SMS attempt
                    try {
                        $sms_notif_stmt = $conn->prepare("
                            INSERT INTO smsnotification (booking_id, message, sms_status)
                            VALUES (?, ?, ?)
                        ");
                        $sms_notif_stmt->bind_param("iss", 
                            $booking_id, 
                            $sms_message, 
                            $smsStatus
                        );
                        $sms_notif_stmt->execute();
                        $sms_notif_stmt->close();
                    } catch (Exception $e) {
                        error_log('Failed to log SMS notification: ' . $e->getMessage());
                    }
                }
            } catch (Exception $e) {
                error_log('SMS sending exception for booking #' . $booking_id . ': ' . $e->getMessage());
                $smsStatus = 'Failed';
                
                // Log failed SMS attempt
                try {
                    $sms_notif_stmt = $conn->prepare("
                        INSERT INTO smsnotification (booking_id, message, sms_status)
                        VALUES (?, ?, ?)
                    ");
                    $sms_notif_stmt->bind_param("iss", 
                        $booking_id, 
                        $sms_message, 
                        $smsStatus
                    );
                    $sms_notif_stmt->execute();
                    $sms_notif_stmt->close();
                } catch (Exception $e2) {
                    error_log('Failed to log SMS notification: ' . $e2->getMessage());
                }
            }
        } else {
            error_log('SMS not sent: No valid phone number found for booking #' . $booking_id . ' (passenger phone: ' . ($booking['passenger_phone_number'] ?? 'empty') . ', user phone: ' . ($booking['user_phone'] ?? 'empty') . ')');
            
            // Log failed SMS attempt due to missing phone number
            try {
                $sms_notif_stmt = $conn->prepare("
                    INSERT INTO smsnotification (booking_id, message, sms_status)
                    VALUES (?, ?, ?)
                ");
                $sms_notif_stmt->bind_param("iss", 
                    $booking_id, 
                    $sms_message, 
                    $smsStatus
                );
                $sms_notif_stmt->execute();
                $sms_notif_stmt->close();
            } catch (Exception $e) {
                error_log('Failed to log SMS notification: ' . $e->getMessage());
            }
        }
        
        ob_end_clean();
        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent,
            'snack_sms_sent' => $snackSmsSent,
            'redirect' => "../booking/bookingSuccess.php?booking_id=$booking_id&tx=$transaction_id&amount=$amount"
        ]);
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        ob_end_clean();
        http_response_code(500);
        exit(json_encode([
            'success' => false,
            'error' => 'Database update failed: ' . $e->getMessage()
        ]));
    }
}

ob_end_clean();
http_response_code($http_code ?: 500);
exit(json_encode([
    'success' => false,
    'error' => $data['message'] ?? 'Payment capture failed',
    'paypal_response' => $data
]));