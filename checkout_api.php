<?php
require_once "db_conn.php"; // Connects to database
require_once 'csrf_helper.php';

if (session_status() == PHP_SESSION_NONE) session_start();

header("Content-Type: application/json");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not authenticated"]);
    exit;
}

require_csrf_token();

$user_id = $_SESSION['user_id'];
$total_price = 0;
$cart_items = json_decode(file_get_contents("php://input"), true);

if (empty($cart_items)) {
    echo json_encode(["status" => "error", "message" => "Cart is empty"]);
    exit;
}

try {
    $pdo->beginTransaction();

    $seller_id = null;
    if (!empty($cart_items[0]['seller_id'])) {
        $seller_id = intval($cart_items[0]['seller_id']);
    }

    $stmt = $pdo->prepare("INSERT INTO orders (user_id, seller_id, total_price, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->execute([$user_id, $seller_id, $total_price]);
    $order_id = $pdo->lastInsertId();

    foreach ($cart_items as $item) {
        $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        $stmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
        $total_price += $item['quantity'] * $item['price'];
    }

    $stmt = $pdo->prepare("UPDATE orders SET total_price = ? WHERE id = ?");
    $stmt->execute([$total_price, $order_id]);

    $pdo->commit();
    echo json_encode(["status" => "success", "message" => "Order placed", "order_id" => $order_id]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "Checkout failed. Please try again."]);
}
?>
