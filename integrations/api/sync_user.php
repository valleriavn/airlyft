<?php
require_once __DIR__ . '/../../db/connect.php';

header('Content-Type: application/json');

$api_key_env = getenv('GROUP1_API_KEY');
$headers = getallheaders() ?: [];
$apiKey  = $headers['X-API-KEY'] ?? $_SERVER['HTTP_X_API_KEY'] ?? null;

if ($apiKey !== $api_key_env) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data || empty($data['email']) || empty($data['password'])) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Email and hashed password required']);
    exit;
}

$fname = $data['first_name'] ?? $data['fname'] ?? 'n/a';
$lname = $data['last_name'] ?? $data['lname'] ?? 'n/a';
$email = $data['email'];
$phone = $data['phone'] ?? $data['cp_number'] ?? 'n/a';
$pass = $data['password'] ?? 'n/a';
$source = $data['origin_system'] ?? $data['source_system'] ?? 'Airlyft';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

$stmt = $conn->prepare("SELECT user_id FROM Users WHERE email=?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    // FIX: Use $pass instead of $password
    $update = $conn->prepare("UPDATE Users SET first_name=?, last_name=?, phone=?, password=? WHERE email=?");
    $update->bind_param("sssss", $fname, $lname, $phone, $pass, $email);

    if ($update->execute()) {
        echo json_encode(['success' => true, 'message' => 'User updated']);
    } else {
        echo json_encode(['success' => false, 'message' => $update->error]);
    }
    exit;
} else {
    // FIX: Use $pass instead of $password, and match table structure
    $insert = $conn->prepare("INSERT INTO Users (first_name, last_name, email, phone, password, role, source_system) VALUES (?, ?, ?, ?, ?, 'Client', ?)");
    $insert->bind_param("ssssss", $fname, $lname, $email, $phone, $pass, $source);

    if ($insert->execute()) {
        echo json_encode(['success' => true, 'message' => 'User synced successfully']);
    } else {
        echo json_encode(['success' => false, 'message' => $insert->error]);
    }
}
?>