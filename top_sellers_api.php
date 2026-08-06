<?php
header('Content-Type: application/json');
require_once 'db_conn.php';

try {
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.username, u.fullname, u.shop_name, u.featured_order,
               COUNT(o.id) AS total_orders
        FROM users u
        LEFT JOIN orders o ON o.seller_id = u.user_id AND o.status != 'cancelled'
        WHERE u.role = 'seller' AND u.is_top_seller = 1 AND u.status = 'active'
        GROUP BY u.user_id
        ORDER BY u.featured_order ASC, u.user_id ASC
    ");
    $stmt->execute();
    $sellers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'sellers' => $sellers]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch top sellers']);
}
