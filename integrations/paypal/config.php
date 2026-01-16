<?php
// integrations/paypal/config.php

// Load environment variables from .env file
$envPath = __DIR__ . '/../../.env';
$env_vars = [];

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
            
            $env_vars[$key] = $value;
        }
    }
}

// PayPal Configuration
define('PAYPAL_CLIENT_ID', $env_vars['PAYPAL_CLIENT_ID'] ?? '');
define('PAYPAL_CLIENT_SECRET', $env_vars['PAYPAL_CLIENT_SECRET'] ?? '');
define('PAYPAL_MODE', $env_vars['PAYPAL_MODE'] ?? 'sandbox');

// Validate PayPal credentials
if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_CLIENT_SECRET)) {
    error_log('ERROR: PayPal credentials not configured in .env - CLIENT_ID: ' . (PAYPAL_CLIENT_ID ? 'SET' : 'MISSING') . ', SECRET: ' . (PAYPAL_CLIENT_SECRET ? 'SET' : 'MISSING'));
}

define('PAYPAL_API_BASE', PAYPAL_MODE === 'sandbox'
    ? ($env_vars['PAYPAL_API_SANDBOX'] ?? 'https://api-m.sandbox.paypal.com')
    : ($env_vars['PAYPAL_API_LIVE'] ?? 'https://api-m.paypal.com'));

// Gmail SMTP Configuration
define('GMAIL_SMTP_USER', $env_vars['GMAIL_SMTP_USER'] ?? '');
define('GMAIL_SMTP_PASS', $env_vars['GMAIL_SMTP_PASS'] ?? '');
define('GMAIL_FROM_NAME', $env_vars['GMAIL_FROM_NAME'] ?? 'AirLyft');
define('GMAIL_FROM_EMAIL', $env_vars['GMAIL_FROM_EMAIL'] ?? $env_vars['GMAIL_SMTP_USER'] ?? '');

// Validate Gmail credentials
if (empty(GMAIL_SMTP_USER) || empty(GMAIL_SMTP_PASS)) {
    error_log('WARNING: Gmail credentials not configured in .env');
}

// SMS Gateway Configuration
define('SMS_GATEWAY_USERNAME', $env_vars['SMS_GATEWAY_USERNAME'] ?? '');
define('SMS_GATEWAY_PASSWORD', $env_vars['SMS_GATEWAY_PASSWORD'] ?? '');
define('SMS_GATEWAY_API', $env_vars['SMS_GATEWAY_API'] ?? '');

// Validate SMS credentials
if (empty(SMS_GATEWAY_USERNAME) || empty(SMS_GATEWAY_PASSWORD)) {
    error_log('WARNING: SMS Gateway credentials not configured in .env');
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