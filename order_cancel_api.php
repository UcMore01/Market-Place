<?php
require_once "db_conn.php";
header('Content-Type: application/json');
session_start();
require_once 'csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized: Please log in."]);
    exit;
}

require_csrf_token();

$user_id = $_SESSION['user_id'];
$data = json_decode(file_get_contents("php://input"), true);
$order_id = intval($data['order_id'] ?? 0);

if (!$order_id) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Order ID is required."]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, status, user_id FROM orders WHERE id = ? AND user_id = ?");
$stmt->execute([$order_id, $user_id]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(["status" => "error", "message" => "Order not found."]);
    exit;
}

$status = strtolower($order['status']);
if ($status !== 'pending') {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Only pending orders can be cancelled."]);
    exit;
}

try {
    $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?")->execute([$order_id]);
    echo json_encode(["status" => "success", "message" => "Order #{$order_id} has been cancelled."]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Failed to cancel order."]);
}
