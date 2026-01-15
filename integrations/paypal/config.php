<?php
// integrations/paypal/config.php

// Load environment variables
$envPath = __DIR__ . '/../../.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        putenv($line);
    }
}

// PayPal Configuration - Load from environment variables
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
define('PAYPAL_CLIENT_SECRET', getenv('PAYPAL_CLIENT_SECRET') ?: '');
define('PAYPAL_MODE', getenv('PAYPAL_MODE') ?: 'sandbox');

// Validate PayPal credentials
if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_CLIENT_SECRET)) {
    error_log('WARNING: PayPal credentials not configured in .env file');
}

define('PAYPAL_API_BASE', PAYPAL_MODE === 'sandbox'
    ? (getenv('PAYPAL_API_SANDBOX') ?: 'https://api-m.sandbox.paypal.com')
    : (getenv('PAYPAL_API_LIVE') ?: 'https://api-m.paypal.com'));

// Gmail SMTP Configuration - Load from environment variables
define('GMAIL_SMTP_USER', getenv('GMAIL_SMTP_USER') ?: '');
define('GMAIL_SMTP_PASS', getenv('GMAIL_SMTP_PASS') ?: '');
define('GMAIL_FROM_NAME', getenv('GMAIL_FROM_NAME') ?: 'AirLyft');
define('GMAIL_FROM_EMAIL', (getenv('GMAIL_FROM_EMAIL') ?: getenv('GMAIL_SMTP_USER')) ?: '');

// Validate Gmail credentials
if (empty(GMAIL_SMTP_USER) || empty(GMAIL_SMTP_PASS)) {
    error_log('WARNING: Gmail credentials not configured in .env file');
}

// SMS Gateway Cloud Configuration - Load from environment variables
define('SMS_GATEWAY_USERNAME', getenv('SMS_GATEWAY_USERNAME') ?: '');
define('SMS_GATEWAY_PASSWORD', getenv('SMS_GATEWAY_PASSWORD') ?: '');
define('SMS_GATEWAY_API', getenv('SMS_GATEWAY_API') ?: 'https://api.sms-gate.app/3rdparty/v1');

// Validate SMS credentials
if (empty(SMS_GATEWAY_USERNAME) || empty(SMS_GATEWAY_PASSWORD)) {
    error_log('WARNING: SMS Gateway credentials not configured in .env file');
}

// Application Environment
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('APP_DEBUG', getenv('APP_DEBUG') === 'true');

// CSRF Protection
if (!isset($_SESSION)) {
    session_start();
}
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Error reporting based on environment
if (APP_ENV === 'development' || APP_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    ini_set('error_log', __DIR__ . '/../../logs/error.log');
}