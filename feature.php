<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("
        SELECT user_id, username, fullname, shop_name, is_top_seller, featured_order
        FROM users
        WHERE role = 'seller'
        ORDER BY featured_order ASC, user_id ASC
    ");
    echo json_encode(['success' => true, 'sellers' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

$data = (function(){ require_once __DIR__ . '/../admin_api/request.php'; return get_request_data(); })();

$user_id = $data['user_id'] ?? $data['id'] ?? null;
$is_top_seller = intval($data['is_top_seller'] ?? 0);
$featured_order = intval($data['featured_order'] ?? 0);

if (!$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Seller ID required']);
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET is_top_seller = ?, featured_order = ? WHERE user_id = ? AND role = 'seller'");
$success = $stmt->execute([$is_top_seller, $featured_order, $user_id]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Update failed']);
}
