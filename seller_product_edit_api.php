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

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $seller_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($product) {
        echo json_encode(['success' => true, 'product' => $product]);
    } else {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = intval($_POST['product_id'] ?? 0);
    if (!$product_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    $stmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ?");
    $stmt->execute([$product_id, $seller_id]);
    if (!$stmt->fetch()) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Not authorized']);
        exit;
    }

    $name = trim($_POST['name'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $category_id = intval($_POST['category_id'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $stock = intval($_POST['stock'] ?? 0);
    $status = trim($_POST['status'] ?? 'active');

    if (!$name || $price <= 0 || $category_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Name, price, and category are required']);
        exit;
    }

    $image_url = null;
    if (!empty($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (!in_array($ext, $allowed)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }
        if ($_FILES['image']['size'] > 25 * 1024 * 1024) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Image size must be under 25MB']);
            exit;
        }
        $upload_dir = __DIR__ . '/uploads/products/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);
        $filename = uniqid('prod_', true) . '.' . $ext;
        $target = $upload_dir . $filename;
        if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to upload image']);
            exit;
        }
        $image_url = 'uploads/products/' . $filename;
    }

    $sql = "UPDATE products SET name=?, price=?, category_id=?, description=?, stock=?, status=?";
    $params = [$name, $price, $category_id, $description, $stock, $status];

    if ($image_url) {
        $sql .= ", image_url=?";
        $params[] = $image_url;
    }
    $sql .= " WHERE id=? AND seller_id=?";
    $params[] = $product_id;
    $params[] = $seller_id;

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(['success' => true]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    exit;
}

http_response_code(405);
echo json_encode(['success' => false, 'message' => 'Method not allowed']);
