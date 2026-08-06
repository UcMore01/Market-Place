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
$email = trim($data['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['status' => 'error', 'message' => 'No account found with that email.']);
    exit;
}

$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

$stmt = $pdo->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$email, $token, $expires]);

$resetLink = "http://{$_SERVER['HTTP_HOST']}/My-Marketplace-main/reset_password.html?token={$token}";

error_log("Password reset link for {$email}: {$resetLink}");

echo json_encode([
    'status' => 'success',
    'message' => 'Password reset link sent. Please check your email.',
    'reset_link' => $resetLink,
    'dev_note' => 'In production, send this link via email. For local testing, check server error log.'
]);
