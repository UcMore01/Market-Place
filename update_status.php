<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$data = (function(){
    require_once __DIR__ . '/../admin_api/request.php';
    return get_request_data();
})();
$id = $data['id'] ?? $data['order_id'] ?? null;
$status = $data['status'] ?? null;
if (!$id || !$status) {
    http_response_code(400);
    echo json_encode(['error' => 'ID and status are required']);
    exit;
}
    $stmt = $pdo->prepare("UPDATE orders SET status=? WHERE id=?");
$success = $stmt->execute([$status, $id]);
if ($success) echo json_encode(['success' => true]);
else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
