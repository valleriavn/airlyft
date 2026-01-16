<?php
/**
 * SMS Gateway Test Script
 * Tests the SMS gateway connectivity and configuration
 */

// Load configuration
require_once(__DIR__ . '/../../auth/config.php');

echo "=== AirLyft SMS Gateway Test ===\n\n";

// Check if config loaded
echo "1. Checking SMS Configuration:\n";
echo "   SMS_GATEWAY_USERNAME: " . (defined('SMS_GATEWAY_USERNAME') ? (empty(SMS_GATEWAY_USERNAME) ? "DEFINED but EMPTY" : "SET") : "NOT DEFINED") . "\n";
echo "   SMS_GATEWAY_PASSWORD: " . (defined('SMS_GATEWAY_PASSWORD') ? (empty(SMS_GATEWAY_PASSWORD) ? "DEFINED but EMPTY" : "SET") : "NOT DEFINED") . "\n";
echo "   SMS_GATEWAY_API: " . (defined('SMS_GATEWAY_API') ? SMS_GATEWAY_API : "NOT DEFINED") . "\n";

if (!defined('SMS_GATEWAY_USERNAME') || empty(SMS_GATEWAY_USERNAME)) {
    echo "\n   ❌ ERROR: SMS Gateway username not configured!\n";
    exit(1);
}

if (!defined('SMS_GATEWAY_PASSWORD') || empty(SMS_GATEWAY_PASSWORD)) {
    echo "\n   ❌ ERROR: SMS Gateway password not configured!\n";
    exit(1);
}

if (!defined('SMS_GATEWAY_API') || empty(SMS_GATEWAY_API)) {
    echo "\n   ❌ ERROR: SMS Gateway API URL not configured!\n";
    exit(1);
}

echo "   ✓ Configuration looks good\n\n";

// Test phone number formatting
echo "2. Testing Phone Number Formatting:\n";
$test_phones = [
    '09123456789',
    '+639123456789',
    '639123456789',
    '+63 9123456789',
    '09-123-456-789'
];

foreach ($test_phones as $test_phone) {
    $phone = trim((string)$test_phone);
    $phone_clean = preg_replace('/[^0-9]/', '', $phone);
    
    echo "   Input: '$test_phone'\n";
    echo "   Cleaned: '$phone_clean'\n";
    
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
    
    echo "   Formatted: '$phone_formatted'\n";
    
    if (preg_match('/^\+63\d{9,12}$/', $phone_formatted)) {
        echo "   ✓ Valid format\n";
    } else {
        echo "   ❌ Invalid format\n";
    }
    echo "\n";
}

// Test gateway connectivity
echo "3. Testing SMS Gateway Connectivity:\n";
echo "   Gateway URL: " . SMS_GATEWAY_API . "\n";

$test_payload = json_encode([
    'phoneNumbers' => ['+639123456789'],
    'message' => 'AirLyft Test Message'
]);

echo "   Test Payload: " . $test_payload . "\n\n";

// Create authentication header
$auth_header = "Authorization: Basic " . base64_encode(SMS_GATEWAY_USERNAME . ":" . SMS_GATEWAY_PASSWORD);
echo "   Auth Header: Authorization: Basic " . base64_encode(SMS_GATEWAY_USERNAME . ":" . SMS_GATEWAY_PASSWORD) . "\n";
echo "   (Username: " . SMS_GATEWAY_USERNAME . ")\n\n";

// Create stream context
$context = stream_context_create([
    'http' => [
        'method'  => 'POST',
        'header'  => "Content-Type: application/json\r\n" . $auth_header,
        'content' => $test_payload,
        'timeout' => 10,
        'ignore_errors' => true
    ],
    'ssl' => [
        'verify_peer'      => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    ]
]);

echo "   Sending test request to gateway...\n";

$response = @file_get_contents(SMS_GATEWAY_API, false, $context);

if ($response === false) {
    $error = error_get_last();
    echo "   ❌ FAILED: No response from gateway\n";
    echo "   Error: " . ($error['message'] ?? 'Unknown error') . "\n\n";
    echo "   This could mean:\n";
    echo "   - SMS gateway at " . SMS_GATEWAY_API . " is unreachable\n";
    echo "   - Network connectivity issue\n";
    echo "   - Authentication failed\n";
} else {
    echo "   ✓ Gateway responded\n";
    echo "   Response size: " . strlen($response) . " bytes\n";
    echo "   Response content: " . substr($response, 0, 200) . "\n\n";
    
    // Try to parse response
    $response_data = json_decode($response, true);
    if (is_array($response_data)) {
        echo "   Parsed as JSON:\n";
        echo "   " . json_encode($response_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n\n";
        
        if (isset($response_data['success']) && $response_data['success'] === true) {
            echo "   ✓ SUCCESS indicator found\n";
        } elseif (isset($response_data['status']) && in_array($response_data['status'], ['success', 'ok', 'sent', 'Success'])) {
            echo "   ✓ SUCCESS status found\n";
        } else {
            echo "   ⚠ No clear success indicator, but gateway responded\n";
        }
    }
}

// Check database connection
echo "\n4. Testing Database Connection:\n";
if (isset($conn) && $conn) {
    echo "   ✓ Database connection established\n";
    
    // Check if smsnotification table exists
    $result = $conn->query("DESCRIBE smsnotification");
    if ($result) {
        echo "   ✓ smsnotification table exists\n";
        echo "   Columns:\n";
        while ($row = $result->fetch_assoc()) {
            echo "      - " . $row['Field'] . " (" . $row['Type'] . ")\n";
        }
    } else {
        echo "   ❌ smsnotification table not found\n";
    }
} else {
    echo "   ❌ Database connection failed\n";
}

echo "\n=== Test Complete ===\n";
?>
