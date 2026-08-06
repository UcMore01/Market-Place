<?php
session_start();
require_once 'session.php';
require_once "db_conn.php"; // $pdo (PDO instance)
require_once 'csrf_helper.php';
header("Content-Type: application/json");

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(["success" => false, "message" => "You must be logged in."]);
    exit();
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === "POST") {
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
    $data = $requestData;
}

if ($method === "POST") {
    if (!isset($data['action']) || !isset($data['product_id'])) {
        http_response_code(400);
        echo json_encode(["success" => false, "message" => "Invalid request."]);
        exit();
    }
    $productId = intval($data['product_id']);

    try {
        if ($data['action'] === "add") {
            // Add or increment quantity
            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE quantity = quantity + 1
            ");
            $result = $stmt->execute([$userId, $productId]);
            echo json_encode(["success" => $result]);
        } elseif ($data['action'] === "decrease") {
            // Decrease quantity or remove if only 1
            $stmt = $pdo->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$userId, $productId]);
            $current = $stmt->fetchColumn();
            if ($current > 1) {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = quantity - 1 WHERE user_id = ? AND product_id = ?");
                $result = $stmt->execute([$userId, $productId]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $result = $stmt->execute([$userId, $productId]);
            }
            echo json_encode(["success" => true]);
        } elseif ($data['action'] === "update") {
            $quantity = isset($data['quantity']) ? intval($data['quantity']) : 1;
            if ($quantity < 1) {
                $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
                $result = $stmt->execute([$userId, $productId]);
            } else {
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
                $result = $stmt->execute([$quantity, $userId, $productId]);
            }
            echo json_encode(["success" => $result]);
        } elseif ($data['action'] === "remove") {
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $result = $stmt->execute([$userId, $productId]);
            echo json_encode(["success" => $result]);
        } else {
            http_response_code(400);
            echo json_encode(["success" => false, "message" => "Unknown action."]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit();
}

// GET: View cart items
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['action']) && $_GET['action'] === "view") {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                p.id AS product_id, p.name, p.price, p.image_url, c.quantity 
            FROM cart c
            JOIN products p ON c.product_id = p.id
            WHERE c.user_id = ?
        ");
        $stmt->execute([$userId]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode([
            "success" => true,
            "cart_count" => count($items),
            "items" => $items
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    exit();
}

// If no valid endpoint matched
http_response_code(400);
echo json_encode(["success" => false, "message" => "Invalid request."]);
?>
