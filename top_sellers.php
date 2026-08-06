<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$limit = intval($_GET['limit'] ?? 10);
$limit = max(1, min($limit, 100));

$stmt = $pdo->prepare("
    SELECT u.user_id, u.username, u.fullname, u.shop_name,
           COUNT(o.id) AS total_orders,
           COALESCE(SUM(o.total_price), 0) AS total_sales
    FROM users u
    LEFT JOIN orders o ON o.seller_id = u.user_id
    WHERE u.role = 'seller' AND u.status = 'active'
    GROUP BY u.user_id
    ORDER BY total_sales DESC
    LIMIT ?
");
$stmt->bindValue(1, $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'top_sellers' => $rows]);
