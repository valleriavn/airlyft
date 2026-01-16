<?php
/**
 * Direct SMS Test - Send a test SMS
 */

// Load configuration  
require_once(__DIR__ . '/../../auth/config.php');

// Get test phone from query string
$test_phone = isset($_GET['phone']) ? $_GET['phone'] : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['phone'])) {
    $test_phone = $_POST['phone'];
    
    echo "<h2>SMS Test Results</h2>\n";
    echo "<p>Testing phone: <strong>" . htmlspecialchars($test_phone) . "</strong></p>\n";
    echo "<hr>\n";
    
    // Check credentials
    if (!defined('SMS_GATEWAY_USERNAME') || empty(SMS_GATEWAY_USERNAME)) {
        echo "<p style='color:red'><strong>ERROR:</strong> SMS Gateway username not configured</p>\n";
    } elseif (!defined('SMS_GATEWAY_PASSWORD') || empty(SMS_GATEWAY_PASSWORD)) {
        echo "<p style='color:red'><strong>ERROR:</strong> SMS Gateway password not configured</p>\n";
    } elseif (!defined('SMS_GATEWAY_API') || empty(SMS_GATEWAY_API)) {
        echo "<p style='color:red'><strong>ERROR:</strong> SMS Gateway API URL not configured</p>\n";
    } else {
        // Format phone
        $phone = trim((string)$test_phone);
        $phone_clean = preg_replace('/[^0-9]/', '', $phone);
        
        echo "<p>Phone cleanup:</p>\n";
        echo "<ul>\n";
        echo "<li>Original: <code>" . htmlspecialchars($phone) . "</code></li>\n";
        echo "<li>Cleaned: <code>" . htmlspecialchars($phone_clean) . "</code> (length: " . strlen($phone_clean) . ")</li>\n";
        
        // Format
        if (strlen($phone_clean) === 10 && substr($phone_clean, 0, 2) === '09') {
            $phone_formatted = '+63' . substr($phone_clean, 1);
        } elseif (strlen($phone_clean) === 11 && substr($phone_clean, 0, 3) === '639') {
            $phone_formatted = '+' . $phone_clean;
        } elseif (strlen($phone_clean) === 12 && substr($phone_clean, 0, 2) === '63') {
            $phone_formatted = '+' . $phone_clean;
        } else {
            $phone_formatted = '+63' . $phone_clean;
        }
        
        echo "<li>Formatted: <code>" . htmlspecialchars($phone_formatted) . "</code></li>\n";
        
        if (!preg_match('/^\+63\d{9,12}$/', $phone_formatted)) {
            echo "<li style='color:red'><strong>VALIDATION FAILED:</strong> Format does not match pattern</li>\n";
        } else {
            echo "<li style='color:green'><strong>✓ VALID FORMAT</strong></li>\n";
        }
        echo "</ul>\n";
        
        echo "<hr>\n";
        echo "<p>Attempting to send test SMS...</p>\n";
        
        // Build payload
        $message = "AirLyft Test SMS - " . date('Y-m-d H:i:s');
        $payload = json_encode([
            'phoneNumbers' => [$phone_formatted],
            'message' => $message
        ]);
        
        echo "<p>Payload:</p>\n";
        echo "<pre style='background:#f0f0f0;padding:10px;border-radius:4px;overflow-x:auto;'>" . htmlspecialchars(json_encode(json_decode($payload), JSON_PRETTY_PRINT)) . "</pre>\n";
        
        // Send
        $auth_header = "Authorization: Basic " . base64_encode(SMS_GATEWAY_USERNAME . ":" . SMS_GATEWAY_PASSWORD);
        
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
        
        echo "<p>Gateway URL: <code>" . htmlspecialchars(SMS_GATEWAY_API) . "</code></p>\n";
        echo "<p>Sending...</p>\n";
        
        $response = @file_get_contents(SMS_GATEWAY_API, false, $context);
        
        if ($response === false) {
            $error = error_get_last();
            echo "<p style='color:red'><strong>❌ FAILED:</strong> No response from gateway</p>\n";
            echo "<p>Error: " . htmlspecialchars($error['message'] ?? 'Unknown error') . "</p>\n";
            echo "<p>Possible causes:</p>\n";
            echo "<ul>\n";
            echo "<li>SMS gateway is unreachable at " . htmlspecialchars(SMS_GATEWAY_API) . "</li>\n";
            echo "<li>Network connectivity issue</li>\n";
            echo "<li>Authentication failed</li>\n";
            echo "</ul>\n";
        } else {
            echo "<p style='color:green'><strong>✓ Gateway responded</strong></p>\n";
            echo "<p>Response size: " . strlen($response) . " bytes</p>\n";
            echo "<p>Response content:</p>\n";
            echo "<pre style='background:#f0f0f0;padding:10px;border-radius:4px;overflow-x:auto;'>" . htmlspecialchars(substr($response, 0, 500)) . "</pre>\n";
            
            // Parse response
            $response_data = json_decode($response, true);
            if (is_array($response_data)) {
                echo "<p>Parsed as JSON:</p>\n";
                echo "<pre style='background:#f0f0f0;padding:10px;border-radius:4px;overflow-x:auto;'>" . htmlspecialchars(json_encode($response_data, JSON_PRETTY_PRINT)) . "</pre>\n";
                
                if (isset($response_data['success']) && $response_data['success'] === true) {
                    echo "<p style='color:green'><strong>✓ SUCCESS!</strong> SMS may have been sent</p>\n";
                } elseif (isset($response_data['status']) && in_array($response_data['status'], ['success', 'ok', 'sent', 'Success'])) {
                    echo "<p style='color:green'><strong>✓ SUCCESS!</strong> SMS status indicates sent</p>\n";
                } elseif (isset($response_data['code']) && ($response_data['code'] == 0 || $response_data['code'] == '0')) {
                    echo "<p style='color:green'><strong>✓ SUCCESS!</strong> SMS code indicates sent</p>\n";
                } else {
                    echo "<p style='color:orange'><strong>⚠ Unknown response</strong> - Gateway responded but success unclear</p>\n";
                }
            } else {
                echo "<p>Response is not JSON format</p>\n";
            }
        }
    }
    
    echo "<hr>\n";
    echo "<a href='" . htmlspecialchars($_SERVER['PHP_SELF']) . "'>← Back to test form</a>\n";
    
} else {
    // Show form
    ?>
<!DOCTYPE html>
<html>
<head>
    <title>AirLyft SMS Direct Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .form-group {
            margin: 15px 0;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"],
        input[type="tel"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        button:hover {
            background: #0056b3;
        }
        .info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .info h3 {
            margin-top: 0;
            color: #004085;
        }
    </style>
</head>
<body>
    <h1>AirLyft SMS Direct Test</h1>
    
    <div class="info">
        <h3>Configuration Status</h3>
        <p>
            <strong>Username:</strong> <?php echo defined('SMS_GATEWAY_USERNAME') && !empty(SMS_GATEWAY_USERNAME) ? '✓ Configured' : '❌ Not configured'; ?><br>
            <strong>Password:</strong> <?php echo defined('SMS_GATEWAY_PASSWORD') && !empty(SMS_GATEWAY_PASSWORD) ? '✓ Configured' : '❌ Not configured'; ?><br>
            <strong>API URL:</strong> <?php echo defined('SMS_GATEWAY_API') && !empty(SMS_GATEWAY_API) ? '<code>' . htmlspecialchars(SMS_GATEWAY_API) . '</code>' : '❌ Not configured'; ?>
        </p>
    </div>
    
    <form method="post">
        <div class="form-group">
            <label for="phone">Test Phone Number:</label>
            <input type="tel" id="phone" name="phone" placeholder="e.g., 09123456789 or +639123456789" required>
            <small>Enter any Philippine phone number format</small>
        </div>
        
        <button type="submit">Send Test SMS</button>
    </form>
    
    <hr>
    <p><small>This test sends an actual SMS to the provided number. Use a valid test number.</small></p>
</body>
</html>
    <?php
}
?>
