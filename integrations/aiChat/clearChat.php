<?php
ob_start();
error_reporting(0);
ini_set('display_errors', 0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../db/connect.php';

header('Content-Type: application/json');

$response = ['success' => false, 'error' => 'Not implemented'];

try {
    $session_token = $_COOKIE['airlyft_chat_session'] ?? null;
    $user_id = $_SESSION['user_id'] ?? null;

    if (!$session_token || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Not authenticated']);
        exit;
    }

    $stmt = $conn->prepare("SELECT session_id FROM ai_chat_sessions WHERE session_token = ? AND user_id = ?");
    $stmt->bind_param("si", $session_token, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'error' => 'Session not found']);
        exit;
    }

    $session = $result->fetch_assoc();
    $session_id = $session['session_id'];

    $stmt = $conn->prepare("DELETE FROM ai_chat_messages WHERE session_id = ?");
    $stmt->bind_param("i", $session_id);
    $stmt->execute();

    ob_clean();
    echo json_encode([
        'success' => true,
        'message' => 'Chat history cleared successfully',
        'deleted_count' => $stmt->affected_rows
    ]);
} catch (Exception $e) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
