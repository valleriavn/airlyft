<?php
// db/connect.php
$host = 'localhost';
$dbname = 'airlyftdb';
$username = 'root';
$password = '';

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
