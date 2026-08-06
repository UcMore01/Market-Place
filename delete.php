<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$data = (function(){ require_once __DIR__ . '/../admin_api/request.php'; return get_request_data(); })();
$id = $data['id'] ?? $data['user_id'] ?? null;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID is required']);
    exit;
}

$stmt = $pdo->prepare("DELETE FROM users WHERE user_id=?");
$success = $stmt->execute([$id]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Delete failed']);
}
