<?php
require_once "db_conn.php";
header('Content-Type: application/json');
session_start();
require_once 'csrf_helper.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "User not logged in"]);
    exit;
}

require_csrf_token();

$data = json_decode(file_get_contents("php://input"), true);
if (!$data || !isset($data['cart']) || !is_array($data['cart']) || count($data['cart']) === 0) {
    echo json_encode(["status" => "error", "message" => "Cart is empty"]);
    exit;
}

$user_id = $_SESSION['user_id'];
$cart = $data['cart'];

try {
    $pdo->beginTransaction();

    $total_price = 0;
    $order_items = [];
    $productStmt = $pdo->prepare("SELECT id, name, price FROM products WHERE id = ?");

    // Validate all products and calculate total using DB values
    foreach ($cart as $item) {
        $productStmt->execute([$item['product_id']]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        if (!$product) {
            throw new Exception("Product not found (ID: {$item['product_id']})");
        }
        $quantity = (int)$item['quantity'];
        $productTotal = $product['price'] * $quantity;
        $total_price += $productTotal;
        $order_items[] = [
            "product_id" => $product['id'],
            "name" => $product['name'],
            "quantity" => $quantity,
            "price" => $product['price']
        ];
    }

    // Insert order
    $estimated_delivery = date('Y-m-d', strtotime('+5 days'));
    $stmt = $pdo->prepare("INSERT INTO orders (user_id, total_price, estimated_delivery) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $total_price, $estimated_delivery]);
    $order_id = $pdo->lastInsertId();

    // Insert items
    $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    foreach ($order_items as $item) {
        $itemStmt->execute([$order_id, $item['product_id'], $item['quantity'], $item['price']]);
    }

    // Clear cart after successful order
    $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$user_id]);

    $pdo->commit();

    echo json_encode([
        "status" => "success",
        "message" => "Order placed successfully",
        "order_id" => $order_id,
        "estimated_delivery" => $estimated_delivery,
        "items" => $order_items,
        "total" => $total_price
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(["status" => "error", "message" => "Order failed: " . $e->getMessage()]);
}
?>
