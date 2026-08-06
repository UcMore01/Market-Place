<?php
session_start();
require_once 'session.php';
require_once "db_conn.php";
require_once 'csrf_helper.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access"]);
    exit();
}

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? '';

if ($action === "place_order" && $_SERVER['REQUEST_METHOD'] === "POST") {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    $requestData = [];
    if (strpos($contentType, 'application/json') !== false) {
        $raw = file_get_contents('php://input');
        $requestData = json_decode($raw, true);
        if (!is_array($requestData)) {
            $requestData = [];
        }
    } else {
        $requestData = $_POST;
    }
    $token = $requestData['csrf_token'] ?? null;
    if ($token === null) {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    require_csrf_token($token);
}

$response = ["status" => "error", "message" => "Invalid request"];

// --- PLACE ORDER ---
if ($action === "place_order") {
    try {
        $pdo->beginTransaction();

        // Get cart items
        $cartStmt = $pdo->prepare("SELECT product_id, quantity FROM cart WHERE user_id = ?");
        $cartStmt->execute([$user_id]);
        $cartItems = $cartStmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$cartItems) {
            echo json_encode(["status" => "error", "message" => "Your cart is empty."]);
            exit();
        }

        $orderIds = [];
        foreach ($cartItems as $item) {
            // Get product price
            $prodStmt = $pdo->prepare("SELECT price, name, seller_id FROM products WHERE id = ?");
            $prodStmt->execute([$item['product_id']]);
            $product = $prodStmt->fetch(PDO::FETCH_ASSOC);
            if (!$product) {
                throw new Exception("Product not found (ID: {$item['product_id']})");
            }
            $total_price = $item['quantity'] * $product['price'];

            // Insert order row (products link via order_items, not a column on orders)
            $orderStmt = $pdo->prepare("INSERT INTO orders (user_id, seller_id, total_price, status) VALUES (?, ?, ?, 'Pending')");
            $orderStmt->execute([$user_id, $product['seller_id'], $total_price]);
            $orderId = $pdo->lastInsertId();
            $orderIds[] = $orderId;

            // Insert the order line item
            $itemStmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
            $itemStmt->execute([$orderId, $item['product_id'], $item['quantity'], $product['price']]);
        }

        // Clear cart
        $clearCart = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
        $clearCart->execute([$user_id]);

        $pdo->commit();
        $response = [
            "status" => "success",
            "message" => "Order placed successfully",
            "order_ids" => $orderIds
        ];
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Order placement error: " . $e->getMessage());
        $response = ["status" => "error", "message" => "Order placement failed: " . $e->getMessage()];
    }
}

// --- VIEW ORDERS ---
elseif ($action === "view_orders") {
    try {
        $query = "SELECT 
                    o.id AS order_id,
                    o.created_at AS date,
                    oi.quantity,
                    COALESCE(p.name, 'Deleted Product') AS item_name,
                    o.total_price,
                    o.status
                  FROM orders o
                  JOIN order_items oi ON oi.order_id = o.id
                  LEFT JOIN products p ON p.id = oi.product_id
                  WHERE o.user_id = ?
                  ORDER BY o.created_at DESC";
        $stmt = $pdo->prepare($query);
        $stmt->execute([$user_id]);
        $orders = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $row['date'] = date("Y-m-d H:i", strtotime($row['date']));
            $row['items'] = "{$row['quantity']} x {$row['item_name']}";
            unset($row['item_name'], $row['quantity']);
            $orders[] = $row;
        }
        $response = ["status" => "success", "orders" => $orders];
    } catch (Exception $e) {
        error_log("View orders error: " . $e->getMessage());
        $response = ["status" => "error", "message" => "Could not fetch orders"];
    }
}

echo json_encode($response);
?>
