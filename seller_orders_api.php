<?php
session_start();
header('Content-Type: application/json');
require_once 'db_conn.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$seller_id = $_SESSION['user_id'];

$action = $_GET['action'] ?? 'view_orders';

if ($action === 'view_orders') {
    try {
        $stmt = $pdo->prepare(
            "SELECT id AS order_id, total_price, status, estimated_delivery, created_at
             FROM orders
             WHERE seller_id = ?
             ORDER BY created_at DESC"
        );
        $stmt->execute([$seller_id]);
        $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($orders as &$order) {
            $order['date'] = date("Y-m-d H:i", strtotime($order['created_at']));
            $itemsStmt = $pdo->prepare(
                "SELECT oi.quantity, COALESCE(p.name, 'Deleted Product') AS name, oi.price
                 FROM order_items oi
                 LEFT JOIN products p ON oi.product_id = p.id
                 WHERE oi.order_id = ? AND (p.seller_id = ? OR p.id IS NULL)"
            );
            $itemsStmt->execute([$order['order_id'], $seller_id]);
            $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
            $order['items'] = [];
            foreach ($items as $item) {
                $order['items'][] = "{$item['quantity']} x {$item['name']}";
            }
            $order['items'] = implode(', ', $order['items']);
            unset($order['created_at']);
        }

        echo json_encode(['success' => true, 'orders' => $orders]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Could not fetch orders.']);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request.']);
