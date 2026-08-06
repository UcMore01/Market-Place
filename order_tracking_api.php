<?php
require_once "db_conn.php"; // Make sure $pdo is initialized here
header('Content-Type: application/json');
session_start();

// --- 1. Authorization: Check Login ---
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized: Please log in."]);
    exit;
}
$user_id = $_SESSION['user_id'];

// --- 2. Input Validation ---
if (!isset($_GET['order_id']) || !is_numeric($_GET['order_id'])) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid or missing order ID."]);
    exit;
}
$order_id = intval($_GET['order_id']);

// --- 3. Fetch Order Meta (and owner) ---
$orderStmt = $pdo->prepare(
    "SELECT o.id, o.total_price, o.status, o.estimated_delivery, o.created_at, o.user_id
     FROM orders o
     WHERE o.id = ?"
);
$orderStmt->execute([$order_id]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Order not found."]);
    exit;
}

// --- 4. Ownership Check ---
if ($order['user_id'] != $user_id) {
    http_response_code(403);
    echo json_encode(["status" => "error", "message" => "You are not authorized to view this order."]);
    exit;
}

// --- 5. Fetch Items ---
$itemsStmt = $pdo->prepare(
    "SELECT oi.product_id, COALESCE(p.name, 'Deleted Product') AS name, oi.quantity, oi.price
     FROM order_items oi
     LEFT JOIN products p ON oi.product_id = p.id
     WHERE oi.order_id = ?"
);
$itemsStmt->execute([$order_id]);
$items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 6. (Optional) Fetch Tracking Info ---
$trackingStmt = $pdo->prepare(
    "SELECT status, location, updated_at
     FROM order_tracking
     WHERE order_id = ?
     ORDER BY updated_at ASC"
);
$trackingStmt->execute([$order_id]);
$tracking = $trackingStmt->fetchAll(PDO::FETCH_ASSOC);

// --- 7. Respond ---
$response = [
    "status" => "success",
    "order" => [
        "order_id" => $order['id'],
        "total_price" => $order['total_price'],
        "status" => $order['status'],
        "estimated_delivery" => $order['estimated_delivery'],
        "created_at" => $order['created_at'],
        "items" => $items,
        "tracking" => $tracking // remove or comment if not needed
    ]
];
echo json_encode($response);
?>
