<?php
// Load .env file for configuration
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $env_lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($env_lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Define constants for configuration
            if (!defined($key)) {
                define($key, $value);
            }
        }
    }
}

// Database Configuration
$host = defined('DB_HOST') ? DB_HOST : 'localhost';
$dbname = defined('DB_NAME') ? DB_NAME : 'airlyftdb';
$username = defined('DB_USER') ? DB_USER : 'root';
$password = defined('DB_PASS') ? DB_PASS : '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Set the correct table name case sensitivity
    $conn->query("SET sql_mode = ''");
    $conn->set_charset("utf8mb4");

    // Debug: Check if table exists
    $result = $conn->query("SHOW TABLES LIKE 'users'");
    if ($result->num_rows === 0) {
        error_log("WARNING: 'users' table not found!");
        $result = $conn->query("SHOW TABLES LIKE 'Users'");
        if ($result->num_rows > 0) {
            error_log("INFO: 'Users' table exists (uppercase)");
        }
    }
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage());
}
