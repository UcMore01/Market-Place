<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$data = (function(){ require_once __DIR__ . '/../admin_api/request.php'; return get_request_data(); })();

$fullname = trim($data['fullname'] ?? $data['name'] ?? '');
$email = trim($data['email'] ?? '');
$role = trim($data['role'] ?? 'user');
$status = trim($data['status'] ?? 'active');

if (!$fullname || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Name and email are required']);
    exit;
}

$username = trim($data['username'] ?? substr($email, 0, strpos($email, '@')));
if (!$username) {
    $username = preg_replace('/[^a-z0-9]+/i', '_', strtolower($fullname));
}

$password = bin2hex(random_bytes(8));
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, role, status) VALUES (?, ?, ?, ?, ?, ?)");
$success = $stmt->execute([$fullname, $email, $username, $passwordHash, $role, $status]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Insert failed']);
}
