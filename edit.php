<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

    // normalize input
    $data = (function(){ require_once __DIR__ . '/../admin_api/request.php'; return get_request_data(); })();

    $id = $data['id'] ?? null;
    $fullname = trim($data['fullname'] ?? $data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $role = trim($data['role'] ?? 'user');
    $status = trim($data['status'] ?? 'active');

if (!$id || !$fullname || !$email) {
    http_response_code(400);
    echo json_encode(['error' => 'ID, name, and email are required']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET fullname=?, email=?, role=?, status=? WHERE user_id=?");
$success = $stmt->execute([$fullname, $email, $role, $status, $id]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
