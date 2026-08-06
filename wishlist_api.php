<?php
session_start();
require_once 'session.php';
require_once "db_conn.php";
require_once 'csrf_helper.php';
header("Content-Type: application/json");

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized"]);
    exit();
}

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
} elseif ($method === "DELETE") {
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
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

$data = $requestData ?? [];

if ($method === "GET") {
    try {
        $stmt = $pdo->prepare(
        "SELECT w.id, w.product_id, 
                COALESCE(p.name, 'Deleted Product') AS name, 
                COALESCE(p.price, 0) AS price, 
                COALESCE(p.image_url, '') AS image
         FROM wishlist w 
             LEFT JOIN products p ON w.product_id = p.id 
             WHERE w.user_id = ?
             ORDER BY w.id DESC"
        );
        $stmt->execute([$user_id]);
        $wishlist = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "wishlist" => $wishlist]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to load wishlist"]);
    }
    exit();
}

if ($method === "POST" && isset($data['action']) && $data['action'] === 'merge_guest') {
    $product_ids = array_map('intval', $data['product_ids'] ?? []);
    if (empty($product_ids)) {
        echo json_encode(["status" => "success", "message" => "Nothing to merge"]);
        exit();
    }

    try {
        $added = 0;
        $skipped = 0;
        foreach ($product_ids as $pid) {
            if (!$pid) { $skipped++; continue; }
            $check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $check->execute([$user_id, $pid]);
            if ($check->fetch()) {
                $skipped++;
                continue;
            }
            $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            if ($stmt->execute([$user_id, $pid])) $added++;
        }
        echo json_encode(["status" => "success", "added" => $added, "skipped" => $skipped]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Merge failed"]);
    }
    exit();
}

if ($method === "POST") {
    $product_id = intval($data['product_id'] ?? 0);
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "product_id required"]);
        exit();
    }

    if (isset($data['action']) && $data['action'] === 'move_to_cart') {
        try {
            $pdo->beginTransaction();

            $delete_stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $delete_stmt->execute([$user_id, $product_id]);

            $cart_stmt = $pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity) 
                VALUES (?, ?, 1)
                ON DUPLICATE KEY UPDATE quantity = quantity + 1
            ");
            $cart_stmt->execute([$user_id, $product_id]);

            $pdo->commit();
            echo json_encode(["status" => "success", "message" => "Item moved to cart"]);
        } catch (Exception $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to move item"]);
        }
        exit();
    }

    try {
        $check = $pdo->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->execute([$user_id, $product_id]);
        if ($check->fetch()) {
            echo json_encode(["status" => "error", "message" => "Already in wishlist"]);
            exit();
        }

        $stmt = $pdo->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        if ($stmt->execute([$user_id, $product_id])) {
            echo json_encode(["status" => "success", "message" => "Product added to wishlist"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to add product"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to add product"]);
    }
    exit();
}

if ($method === "DELETE") {
    $product_id = intval($data['product_id'] ?? 0);
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "product_id required"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        if ($stmt->execute([$user_id, $product_id])) {
            echo json_encode(["status" => "success", "message" => "Product removed from wishlist"]);
        } else {
            http_response_code(500);
            echo json_encode(["status" => "error", "message" => "Failed to remove product"]);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to remove product"]);
    }
    exit();
}

http_response_code(405);
echo json_encode(["status" => "error", "message" => "Method not allowed"]);
