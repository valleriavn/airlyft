<?php
$envPath = __DIR__ . '/../.env'; 
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) continue;
        putenv($line); // set environment variable
    }
}

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

	$conn->set_charset("utf8mb4");
} catch (Exception $e) {
	die("Database connection failed: " . $e->getMessage());
}
