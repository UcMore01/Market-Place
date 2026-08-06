<?php
header('Content-Type: application/json');
require_once 'db_conn.php';

try {
        $stmt = $pdo->prepare("
            SELECT fp.sort_order, p.id, p.name, p.description, p.price, p.image_url,
                   u.username as seller_name, u.shop_name as seller_shop
            FROM featured_products fp
            JOIN products p ON fp.product_id = p.id
            LEFT JOIN users u ON p.seller_id = u.user_id
            WHERE p.status = 'active'
            ORDER BY fp.sort_order ASC, fp.id ASC
        ");
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'products' => $products]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch featured products']);
}
