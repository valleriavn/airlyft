<?php
/**
 * SMS Configuration Check - Quick Status Report
 */

echo "=== AirLyft SMS Configuration Status ===\n\n";

// Check .env file
echo "1. Checking .env file:\n";
$env_file = __DIR__ . '/../../.env';

if (file_exists($env_file)) {
    echo "   ✓ .env file found at: $env_file\n";
    
    // Parse .env
    $env_content = file_get_contents($env_file);
    $env_lines = explode("\n", $env_content);
    
    $sms_config = [];
    foreach ($env_lines as $line) {
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            if (strpos($key, 'SMS_') === 0 || strpos($key, 'PAYPAL_') === 0 || strpos($key, 'GMAIL_') === 0) {
                $sms_config[$key] = $value;
            }
        }
    }
    
    echo "   Configuration entries found:\n";
    foreach ($sms_config as $key => $value) {
        $display_value = ($value && strlen($value) > 0) ? substr($value, 0, 30) . (strlen($value) > 30 ? '...' : '') : '(empty)';
        echo "      $key = $display_value\n";
    }
} else {
    echo "   ✗ .env file NOT found at: $env_file\n";
    echo "   Create .env file with SMS_GATEWAY_* and PAYPAL_* configuration\n";
}

echo "\n2. Checking config.php loading:\n";

// Load config
require_once(__DIR__ . '/../../auth/config.php');

echo "   SMS_GATEWAY_USERNAME: " . (defined('SMS_GATEWAY_USERNAME') ? (SMS_GATEWAY_USERNAME ? "✓ SET" : "✗ EMPTY") : "✗ NOT DEFINED") . "\n";
echo "   SMS_GATEWAY_PASSWORD: " . (defined('SMS_GATEWAY_PASSWORD') ? (SMS_GATEWAY_PASSWORD ? "✓ SET" : "✗ EMPTY") : "✗ NOT DEFINED") . "\n";
echo "   SMS_GATEWAY_API: " . (defined('SMS_GATEWAY_API') ? SMS_GATEWAY_API : "✗ NOT DEFINED") . "\n";

echo "\n3. Checking database:\n";

// Try to connect
if (isset($conn) && $conn) {
    echo "   ✓ Database connection OK\n";
    
    // Check smsnotification table
    $tables = [];
    $result = $conn->query("SHOW TABLES");
    
    while ($row = $result->fetch_assoc()) {
        $tables[] = reset($row);
    }
    
    if (in_array('smsnotification', $tables)) {
        echo "   ✓ smsnotification table exists\n";
        
        // Check recent records
        $sms_result = $conn->query("SELECT COUNT(*) as count FROM smsnotification");
        $sms_count = $sms_result->fetch_assoc();
        echo "      Total SMS records: " . $sms_count['count'] . "\n";
        
        // Check last 5
        $recent = $conn->query("SELECT sms_id, booking_id, sms_status, DATE(FROM_UNIXTIME(SUBSTRING_INDEX(sms_id, '', 1))) as date FROM smsnotification ORDER BY sms_id DESC LIMIT 5");
        if ($recent->num_rows > 0) {
            echo "      Last 5 records:\n";
            while ($row = $recent->fetch_assoc()) {
                echo "         ID: {$row['sms_id']}, Booking: {$row['booking_id']}, Status: {$row['sms_status']}\n";
            }
        }
    } else {
        echo "   ✗ smsnotification table NOT found\n";
        echo "      Available tables: " . implode(', ', $tables) . "\n";
    }
} else {
    echo "   ✗ Database connection FAILED\n";
}

echo "\n4. Checking recent error logs:\n";

$log_file = 'C:\\xampp\\apache\\logs\\error.log';

if (file_exists($log_file)) {
    echo "   ✓ Error log found at: $log_file\n";
    
    // Count SMS entries in last 100 lines
    $lines = array_slice(file($log_file, FILE_IGNORE_NEW_LINES), -100);
    $sms_count = 0;
    $sms_recent = [];
    
    foreach ($lines as $line) {
        if (stripos($line, 'sms') !== false) {
            $sms_count++;
            if (count($sms_recent) < 3) {
                $sms_recent[] = $line;
            }
        }
    }
    
    echo "      SMS entries in last 100 lines: $sms_count\n";
    
    if ($sms_count > 0) {
        echo "      Recent SMS log entries:\n";
        foreach ($sms_recent as $line) {
            // Show only the relevant part
            if (preg_match('/SMS.*/', $line, $matches)) {
                echo "         " . substr($matches[0], 0, 80) . "...\n";
            }
        }
    }
} else {
    echo "   ✗ Error log NOT found at: $log_file\n";
}

echo "\n=== Summary ===\n";

$issues = [];

if (!file_exists($env_file)) {
    $issues[] = ".env file missing";
}

if (!defined('SMS_GATEWAY_USERNAME') || !SMS_GATEWAY_USERNAME) {
    $issues[] = "SMS_GATEWAY_USERNAME not configured";
}

if (!defined('SMS_GATEWAY_PASSWORD') || !SMS_GATEWAY_PASSWORD) {
    $issues[] = "SMS_GATEWAY_PASSWORD not configured";
}

if (!defined('SMS_GATEWAY_API') || !SMS_GATEWAY_API) {
    $issues[] = "SMS_GATEWAY_API not configured";
}

if (empty($issues)) {
    echo "✓ All configuration appears to be OK\n";
    echo "\nNext steps:\n";
    echo "1. Visit http://localhost/airlyft/integrations/paypal/test_sms_direct.php\n";
    echo "2. Send a test SMS to verify gateway connectivity\n";
    echo "3. Check error logs for detailed information\n";
} else {
    echo "✗ Issues found:\n";
    foreach ($issues as $issue) {
        echo "  - $issue\n";
    }
    echo "\nFix these issues before SMS can work properly\n";
}

echo "\n";
?>
