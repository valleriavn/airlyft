<?php
// integrations/paypal/capture_order.php
// Start output buffering FIRST to catch any errors
ob_start();
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);

session_start();

// Load .env file from project root
$envPath = dirname(__DIR__, 2) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || $line[0] === '#')
            continue;

        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);

            // Remove quotes
            if (
                (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                (str_starts_with($value, "'") && str_ends_with($value, "'"))
            ) {
                $value = substr($value, 1, -1);
            }

            if (!defined($key)) {
                define($key, $value);
            }
            $_ENV[$key] = $value;
        }
    }
}

// Suppress any output from included files
ob_start();
try {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/../../db/connect.php';

    // Check if database connection was established
    if (!isset($conn) || !$conn instanceof mysqli) {
        throw new Exception('Database connection not established');
    }
} catch (Exception $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'Configuration error: ' . $e->getMessage()
    ]));
} catch (Error $e) {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'Fatal error: ' . $e->getMessage()
    ]));
}
ob_end_clean();

// Now set JSON header
header('Content-Type: application/json; charset=utf-8');

// Register shutdown function to catch fatal errors
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        ob_end_clean();
        header('Content-Type: application/json; charset=utf-8');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Fatal error: ' . $error['message'] . ' in ' . basename($error['file']) . ' on line ' . $error['line']
        ]);
        exit;
    }
});

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

// Suppress any output from PHPMailer includes
ob_start();
require_once __DIR__ . '/../../vendor/PHPMailer/Exception.php';
require_once __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../../vendor/PHPMailer/SMTP.php';
ob_end_clean();

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
        $phone = trim((string) $passenger['passenger_phone_number']);

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
        $phone = trim((string) $booking['user_phone']);
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
    // Validate PayPal credentials exist
    if (!defined('PAYPAL_CLIENT_ID')) {
        error_log('PayPal ERROR: PAYPAL_CLIENT_ID constant not defined');
        return false;
    }
    if (!defined('PAYPAL_CLIENT_SECRET')) {
        error_log('PayPal ERROR: PAYPAL_CLIENT_SECRET constant not defined');
        return false;
    }
    if (!defined('PAYPAL_API_BASE')) {
        error_log('PayPal ERROR: PAYPAL_API_BASE constant not defined');
        return false;
    }

    if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_CLIENT_SECRET) || empty(PAYPAL_API_BASE)) {
        error_log('PayPal token error: Missing credentials - CLIENT_ID: ' . (PAYPAL_CLIENT_ID ? 'SET' : 'EMPTY') . ', SECRET: ' . (PAYPAL_CLIENT_SECRET ? 'SET' : 'EMPTY') . ', API_BASE: ' . (PAYPAL_API_BASE ? 'SET' : 'EMPTY'));
        return false;
    }

    error_log('PayPal token request - API: ' . PAYPAL_API_BASE . ', Mode: ' . (defined('PAYPAL_MODE') ? PAYPAL_MODE : 'UNDEFINED'));

    $token_url = PAYPAL_API_BASE . "/v1/oauth2/token";
    error_log('PayPal token URL: ' . $token_url);

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $token_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ":" . PAYPAL_CLIENT_SECRET,
        CURLOPT_HTTPHEADER => ["Accept: application/json"],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10
    ]);

    error_log('PayPal: Sending token request...');
    
    $response = curl_exec($ch);
    $curl_error = curl_error($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);

    error_log('PayPal token response HTTP code: ' . $http_code);

    if ($curl_error) {
        error_log("PayPal token curl error: $curl_error");
        return false;
    }

    if ($http_code !== 200) {
        error_log("PayPal token error: HTTP $http_code - Response: " . substr($response, 0, 500));
        return false;
    }

    // Check if response is HTML (error page)
    if (strpos(trim($response), '<') === 0) {
        error_log("PayPal token error: Received HTML instead of JSON. Response: " . substr($response, 0, 200));
        return false;
    }

    // Check if response is empty
    if (empty($response)) {
        error_log("PayPal token error: Empty response from server");
        return false;
    }

    $data = json_decode($response, true);
    if (!is_array($data)) {
        error_log("PayPal token error: Response is not valid JSON - " . substr($response, 0, 200));
        return false;
    }

    if (!isset($data['access_token'])) {
        error_log("PayPal token error: No access_token in response - " . json_encode($data));
        return false;
    }

    error_log('PayPal token obtained successfully');
    return $data['access_token'];
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

function send_sms($phone, $message, $booking_id = null, $conn = null)
{
    // Check credentials
    if (!defined('SMS_GATEWAY_USERNAME') || empty(SMS_GATEWAY_USERNAME)) {
        error_log('SMS not sent: SMS Gateway username not configured');
        return false;
    }

    if (!defined('SMS_GATEWAY_PASSWORD') || empty(SMS_GATEWAY_PASSWORD)) {
        error_log('SMS not sent: SMS Gateway password not configured');
        return false;
    }

    if (!defined('SMS_GATEWAY_API') || empty(SMS_GATEWAY_API)) {
        error_log('SMS not sent: SMS Gateway API not configured');
        return false;
    }

    if (empty($phone)) {
        error_log('SMS not sent: Phone number is empty');
        return false;
    }

    if (empty($message)) {
        error_log('SMS not sent: Message is empty');
        return false;
    }

    // Format phone number
    $phone = trim((string)$phone);
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    
    error_log('SMS Debug: Original phone: ' . $phone . ', Cleaned: ' . $phone_clean . ', Length: ' . strlen($phone_clean));
    
    // Format Philippine phone numbers
    if (strlen($phone_clean) === 10 && substr($phone_clean, 0, 2) === '09') {
        $phone_formatted = '+63' . substr($phone_clean, 1);
    } elseif (strlen($phone_clean) === 11 && substr($phone_clean, 0, 3) === '639') {
        $phone_formatted = '+' . $phone_clean;
    } elseif (strlen($phone_clean) === 12 && substr($phone_clean, 0, 2) === '63') {
        $phone_formatted = '+' . $phone_clean;
    } else {
        $phone_formatted = '+63' . $phone_clean;
    }
    
    error_log('SMS Debug: Formatted phone: ' . $phone_formatted);
    
    // Validate format
    if (!preg_match('/^\+63\d{9,12}$/', $phone_formatted)) {
        error_log('SMS Invalid format - ' . $phone_formatted . ' does not match +63 pattern');
        return false;
    }

    error_log('SMS Sending to: ' . $phone_formatted . ' (original: ' . $phone . ')');
    error_log('SMS Message: ' . substr($message, 0, 100));

    // Build request payload
    $payload = json_encode([
        'phoneNumbers' => [$phone_formatted],
        'message' => $message
    ]);

    error_log('SMS Payload JSON: ' . $payload);

    // Create authentication header
    $auth_header = "Authorization: Basic " . base64_encode(SMS_GATEWAY_USERNAME . ":" . SMS_GATEWAY_PASSWORD);
    error_log('SMS Auth Header (masked): Authorization: Basic [redacted]');

    // Create stream context with SSL configuration
    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\n" . $auth_header,
            'content' => $payload,
            'timeout' => 10,
            'ignore_errors' => true
        ],
        'ssl' => [
            'verify_peer'      => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ]);

    error_log('Attempting to send SMS to: ' . SMS_GATEWAY_API);
    
    // Send SMS via gateway
    $response = @file_get_contents(SMS_GATEWAY_API, false, $context);
    
    if ($response === false) {
        $error = error_get_last();
        error_log('SMS FAILED: No response from gateway - ' . ($error['message'] ?? 'Unknown error'));
        error_log('SMS Gateway URL: ' . SMS_GATEWAY_API);
        error_log('SMS Gateway may be unreachable or credentials are wrong');
        return false;
    }

    error_log('SMS Gateway Response (' . strlen($response) . ' bytes): ' . $response);
    
    // Try to decode response
    $response_data = json_decode($response, true);
    
    // Check for success indicators
    if (is_array($response_data)) {
        error_log('SMS Response decoded as JSON: ' . json_encode($response_data));
        
        if (isset($response_data['success']) && $response_data['success'] === true) {
            error_log('SMS SUCCESS: Sent to ' . $phone_formatted);
            return true;
        }
        
        if (isset($response_data['status']) && in_array($response_data['status'], ['success', 'ok', 'sent', 'Success'])) {
            error_log('SMS SUCCESS: Sent to ' . $phone_formatted);
            return true;
        }

        if (isset($response_data['code']) && ($response_data['code'] == 0 || $response_data['code'] == '0')) {
            error_log('SMS SUCCESS: Sent to ' . $phone_formatted);
            return true;
        }
        
        error_log('SMS FAILED: Gateway returned error - ' . json_encode($response_data));
        return false;
    }
    
    // Some gateways return empty response on success
    if (empty($response)) {
        error_log('SMS SUCCESS: Empty response from gateway (typically indicates success)');
        return true;
    }
    
    error_log('SMS FAILED: Invalid response format - ' . substr($response, 0, 200));
    return false;
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
    ],
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_SSL_VERIFYHOST => 2,
    CURLOPT_TIMEOUT => 30,
    CURLOPT_CONNECTTIMEOUT => 10
]);

$response = curl_exec($ch);
$curl_error = curl_error($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Check for curl errors first
if ($curl_error) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'PayPal API connection error: ' . $curl_error
    ]));
}

// Check if response is empty
if (empty($response)) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'Empty response from PayPal API'
    ]));
}

// Check if response starts with HTML (error page)
if (strpos(trim($response), '<') === 0) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'PayPal API returned HTML instead of JSON. HTTP Code: ' . $http_code
    ]));
}

$data = json_decode($response, true);
if (!is_array($data)) {
    ob_end_clean();
    http_response_code(500);
    exit(json_encode([
        'success' => false,
        'error' => 'Invalid PayPal response: ' . substr($response, 0, 200),
        'http_code' => $http_code
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
                paid_at = NOW()
            WHERE payment_id = ?
        ");
        $update_payment->bind_param("i", $booking['payment_id']);
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
            $email_notif_stmt->bind_param(
                "isss",
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

        // SMS should be sent to the user's phone (from Users table)
        $phone_to_use = $booking['user_phone'] ?? null;
        
        error_log('SMS Debug: user_phone from booking: ' . ($phone_to_use ?? 'EMPTY'));

        // Send confirmation SMS to user phone number
        if (!empty($phone_to_use)) {
            try {
                error_log('SMS: Attempting to send confirmation SMS to: ' . $phone_to_use . ' for booking #' . $booking_id);
                
                // First, insert the SMS record into database (initially with Pending status)
                try {
                    $sms_insert_stmt = $conn->prepare("
                        INSERT INTO smsnotification (booking_id, message, sms_status)
                        VALUES (?, ?, 'Pending')
                    ");
                    $sms_insert_stmt->bind_param(
                        "is",
                        $booking_id,
                        $sms_message
                    );
                    $sms_insert_stmt->execute();
                    $sms_insert_stmt->close();
                    error_log('SMS: Record inserted into database for booking #' . $booking_id);
                } catch (Exception $e) {
                    error_log('SMS: Failed to insert SMS record into database: ' . $e->getMessage());
                }
                
                // Send SMS via gateway
                $smsSent = send_sms($phone_to_use, $sms_message, $booking_id, $conn);
                
                if ($smsSent) {
                    error_log('SMS: Confirmation SMS sent successfully to ' . $phone_to_use . ' for booking #' . $booking_id);
                    $smsStatus = 'Sent';
                    
                    // Update SMS notification status to Sent
                    try {
                        $sms_update_stmt = $conn->prepare("UPDATE smsnotification SET sms_status = 'Sent' WHERE booking_id = ? ORDER BY sms_id DESC LIMIT 1");
                        $sms_update_stmt->bind_param("i", $booking_id);
                        $sms_update_stmt->execute();
                        $sms_update_stmt->close();
                    } catch (Exception $e) {
                        error_log('Failed to update SMS status to Sent: ' . $e->getMessage());
                    }
                } else {
                    error_log('SMS: Confirmation SMS failed for booking #' . $booking_id . ' to ' . $phone_to_use);
                    $smsStatus = 'Failed';
                }
            } catch (Exception $e) {
                error_log('SMS exception for booking #' . $booking_id . ': ' . $e->getMessage());
                $smsStatus = 'Failed';
            }
        } else {
            error_log('SMS: No valid phone number found for booking #' . $booking_id);
            $smsStatus = 'Failed';
        }

        ob_end_clean();
        echo json_encode([
            'success' => true,
            'booking_id' => $booking_id,
            'transaction_id' => $transaction_id,
            'amount' => $amount,
            'email_sent' => $emailSent,
            'sms_sent' => $smsSent,
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