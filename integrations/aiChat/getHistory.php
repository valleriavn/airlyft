<?php
// /integrations/aiChat/getHistory.php

ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db/connect.php';

header('Content-Type: application/json');

$response = ['messages' => []];

try {
    $session_token = $_COOKIE['airlyft_chat_session'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$session_token) {
        echo json_encode($response);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT session_id 
        FROM ai_chat_sessions 
        WHERE session_token = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("s", $session_token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode($response);
        exit;
    }

    $session = $result->fetch_assoc();
    $session_id = $session['session_id'];

    $stmt = $conn->prepare("
        SELECT 
            sender_type,
            message,
            DATE_FORMAT(created_at, '%H:%i') as time
        FROM ai_chat_messages 
        WHERE session_id = ?
        ORDER BY message_id ASC
        LIMIT 15
    ");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();
    $messages_result = $stmt->get_result();

    $messages = [];
    while ($row = $messages_result->fetch_assoc()) {
        $messages[] = [
            'type' => $row['sender_type'],
            'text' => $row['message'],
            'time' => $row['time']
        ];
    }

    $response = [
        'messages' => $messages,
        'session_id' => $session_id,
        'count' => count($messages)
    ];
} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

ob_clean();
echo json_encode($response);
