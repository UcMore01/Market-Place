<?php
header('Content-Type: application/json');
session_start();
require_once 'db_conn.php';
require_once 'csrf_helper.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

require_csrf_token();

$data = json_decode(file_get_contents('php://input'), true);
$token = trim($data['token'] ?? '');
$password = $data['password'] ?? '';

if (!$token || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Token and password are required.']);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

$stmt = $pdo->prepare("SELECT id, email FROM password_resets WHERE token = ? AND expires_at > NOW()");
$stmt->execute([$token]);
$reset = $stmt->fetch();

if (!$reset) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or expired token.']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
$pdo->prepare("UPDATE users SET password = ? WHERE email = ?")->execute([$hash, $reset['email']]);
$pdo->prepare("DELETE FROM password_resets WHERE id = ?")->execute([$reset['id']]);

echo json_encode(['status' => 'success', 'message' => 'Password reset successful. You can now log in.']);
