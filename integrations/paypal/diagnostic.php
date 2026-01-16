<?php
/**
 * Booking SMS Diagnostic Report
 */

require_once(__DIR__ . '/../../db/connect.php');

$booking_id = isset($_GET['booking_id']) ? (int)$_GET['booking_id'] : null;

if (!$booking_id) {
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>Booking SMS Diagnostic</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        input {
            padding: 8px;
            font-size: 14px;
            width: 150px;
        }
        button {
            padding: 8px 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>Booking SMS Diagnostic</h1>
    <p>Enter a booking ID to see SMS diagnostics:</p>
    <form method="get">
        <input type="number" name="booking_id" placeholder="Booking ID" min="1" required>
        <button type="submit">Check Booking</button>
    </form>
</body>
</html>
    <?php
    exit;
}

echo "<!DOCTYPE html>\n";
echo "<html>\n<head>\n<title>Booking #$booking_id SMS Diagnostic</title>\n";
echo "<style>\n";
echo "body { font-family: Arial, sans-serif; max-width: 1000px; margin: 20px auto; padding: 20px; background: #f5f5f5; }\n";
echo "h1 { color: #333; }\n";
echo "h2 { color: #555; border-bottom: 2px solid #007bff; padding-bottom: 10px; }\n";
echo "table { width: 100%; border-collapse: collapse; background: white; margin: 20px 0; }\n";
echo "th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }\n";
echo "th { background: #007bff; color: white; }\n";
echo "tr:hover { background: #f0f0f0; }\n";
echo ".empty { color: #999; font-style: italic; }\n";
echo ".success { color: green; }\n";
echo ".error { color: red; }\n";
echo ".pending { color: orange; }\n";
echo ".code { background: #f5f5f5; padding: 2px 5px; font-family: monospace; border-radius: 3px; }\n";
echo ".log-box { background: #fff; border: 1px solid #ddd; padding: 15px; border-radius: 4px; max-height: 400px; overflow-y: auto; }\n";
echo "pre { margin: 0; white-space: pre-wrap; word-break: break-word; }\n";
echo ".back { margin: 20px 0; }\n";
echo "a { color: #007bff; text-decoration: none; }\n";
echo "a:hover { text-decoration: underline; }\n";
echo "</style>\n</head>\n<body>\n";

echo "<h1>Booking #$booking_id - SMS Diagnostic Report</h1>\n";
echo "<p><a href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>← Back to form</a></p>\n";
echo "<hr>\n";

// Get booking information
echo "<h2>Booking Information</h2>\n";
$booking_query = $conn->prepare("
    SELECT 
        b.*,
        u.phone as user_phone,
        u.email,
        u.first_name,
        u.last_name,
        p.passenger_phone_number,
        ac.aircraft_name,
        s.departure_time
    FROM Booking b
    LEFT JOIN Users u ON b.user_id = u.user_id
    LEFT JOIN Passenger p ON b.passenger_id = p.passenger_id
    LEFT JOIN aircraft ac ON b.aircraft_id = ac.aircraft_id
    LEFT JOIN schedule s ON b.schedule_id = s.schedule_id
    WHERE b.booking_id = ?
");
$booking_query->bind_param("i", $booking_id);
$booking_query->execute();
$booking_result = $booking_query->get_result();

if ($booking_result->num_rows === 0) {
    echo "<p class='error'><strong>❌ Booking not found!</strong></p>\n";
} else {
    $booking = $booking_result->fetch_assoc();
    
    echo "<table>\n";
    echo "<tr><th>Field</th><th>Value</th></tr>\n";
    echo "<tr><td>Booking ID</td><td>" . htmlspecialchars($booking['booking_id']) . "</td></tr>\n";
    echo "<tr><td>User</td><td>" . htmlspecialchars($booking['first_name'] . ' ' . $booking['last_name']) . " (ID: " . $booking['user_id'] . ")</td></tr>\n";
    echo "<tr><td>User Phone</td><td>" . (empty($booking['user_phone']) ? "<span class='empty'>(empty)</span>" : "<span class='code'>" . htmlspecialchars($booking['user_phone']) . "</span>") . "</td></tr>\n";
    echo "<tr><td>User Email</td><td>" . htmlspecialchars($booking['email']) . "</td></tr>\n";
    echo "<tr><td>Passenger Phone</td><td>" . (empty($booking['passenger_phone_number']) ? "<span class='empty'>(empty)</span>" : "<span class='code'>" . htmlspecialchars($booking['passenger_phone_number']) . "</span>") . "</td></tr>\n";
    echo "<tr><td>Aircraft</td><td>" . htmlspecialchars($booking['aircraft_name'] ?? 'N/A') . "</td></tr>\n";
    echo "<tr><td>Status</td><td>" . htmlspecialchars($booking['status']) . "</td></tr>\n";
    echo "<tr><td>Departure</td><td>" . htmlspecialchars($booking['departure_time'] ?? 'N/A') . "</td></tr>\n";
    echo "</table>\n";
}

// Get payment information
echo "<h2>Payment Information</h2>\n";
$payment_query = $conn->prepare("
    SELECT * FROM Payment 
    WHERE booking_id = ?
    ORDER BY payment_date DESC
    LIMIT 1
");
$payment_query->bind_param("i", $booking_id);
$payment_query->execute();
$payment_result = $payment_query->get_result();

if ($payment_result->num_rows === 0) {
    echo "<p class='pending'>No payment record found</p>\n";
} else {
    $payment = $payment_result->fetch_assoc();
    
    echo "<table>\n";
    echo "<tr><th>Field</th><th>Value</th></tr>\n";
    echo "<tr><td>Payment ID</td><td>" . htmlspecialchars($payment['payment_id']) . "</td></tr>\n";
    echo "<tr><td>Amount</td><td>₱" . number_format($payment['amount'] ?? 0, 2) . "</td></tr>\n";
    echo "<tr><td>Status</td><td class='" . strtolower($payment['payment_status'] ?? '') . "'>" . htmlspecialchars($payment['payment_status'] ?? 'Unknown') . "</td></tr>\n";
    echo "<tr><td>Payment Date</td><td>" . htmlspecialchars($payment['payment_date'] ?? 'N/A') . "</td></tr>\n";
    echo "<tr><td>Email Notif Status</td><td class='" . strtolower($payment['email_notif_status'] ?? '') . "'>" . htmlspecialchars($payment['email_notif_status'] ?? 'Unknown') . "</td></tr>\n";
    echo "<tr><td>SMS Notif Status</td><td class='" . strtolower($payment['sms_notif_status'] ?? '') . "'>" . htmlspecialchars($payment['sms_notif_status'] ?? 'Unknown') . "</td></tr>\n";
    echo "</table>\n";
}

// Get SMS notification records
echo "<h2>SMS Notifications</h2>\n";
$sms_query = $conn->prepare("
    SELECT * FROM smsnotification
    WHERE booking_id = ?
    ORDER BY sms_id DESC
");
$sms_query->bind_param("i", $booking_id);
$sms_query->execute();
$sms_result = $sms_query->get_result();

if ($sms_result->num_rows === 0) {
    echo "<p class='pending'>No SMS records found</p>\n";
} else {
    echo "<table>\n";
    echo "<tr><th>SMS ID</th><th>Status</th><th>Message</th></tr>\n";
    
    while ($sms = $sms_result->fetch_assoc()) {
        echo "<tr>\n";
        echo "<td>" . htmlspecialchars($sms['sms_id']) . "</td>\n";
        echo "<td class='" . strtolower($sms['sms_status'] ?? '') . "'>" . htmlspecialchars($sms['sms_status'] ?? 'Unknown') . "</td>\n";
        echo "<td><small>" . htmlspecialchars(substr($sms['message'] ?? '', 0, 100)) . "</small></td>\n";
        echo "</tr>\n";
    }
    
    echo "</table>\n";
}

// Show recent error logs (last 50 lines mentioning booking #X or SMS)
echo "<h2>Recent Error Logs (SMS & Booking #$booking_id)</h2>\n";
echo "<p>Last lines from error.log containing SMS or Booking #$booking_id:</p>\n";
echo "<div class='log-box'>\n";
echo "<pre>";

$log_file = 'C:\\xampp\\apache\\logs\\error.log';

if (file_exists($log_file)) {
    // Read last 200 lines of log file
    $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        echo "❌ Could not read error.log\n";
    } else {
        // Get last 200 lines
        $last_lines = array_slice($lines, -200);
        
        // Filter for SMS or booking mentions
        $matching = [];
        foreach ($last_lines as $line) {
            if (stripos($line, 'sms') !== false || stripos($line, 'booking #' . $booking_id) !== false) {
                $matching[] = $line;
            }
        }
        
        if (empty($matching)) {
            echo "No SMS or booking-related log entries found in last 200 lines.\n";
            echo "\nLast 20 lines of error.log:\n";
            echo str_repeat("-", 50) . "\n";
            foreach (array_slice($last_lines, -20) as $line) {
                echo htmlspecialchars($line) . "\n";
            }
        } else {
            echo "Found " . count($matching) . " matching log entries:\n\n";
            foreach (array_slice($matching, -50) as $line) {
                echo htmlspecialchars($line) . "\n";
            }
        }
    }
} else {
    echo "❌ Error log not found at: $log_file\n";
    echo "\nTry checking the XAMPP error log at: C:\\xampp\\apache\\logs\\error.log\n";
}

echo "</pre>\n";
echo "</div>\n";

echo "<hr>\n";
echo "<p><a href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>← Back to form</a></p>\n";

echo "</body>\n</html>\n";

$booking_query->close();
$payment_query->close();
$sms_query->close();
$conn->close();
?>
