<?php
header("Content-Type: application/json");
require_once "db_conn.php";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['list']) && $_GET['list'] === 'categories') {
            $stmt = $pdo->query("SELECT category_id as id, name, description FROM categories WHERE status = 'active' ORDER BY name ASC");
            echo json_encode(['success' => true, 'categories' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
        if (isset($_GET['category_id']) && intval($_GET['category_id']) > 0) {
            $category_id = intval($_GET['category_id']);
            $stmt = $pdo->prepare("SELECT p.* FROM products p LEFT JOIN users u ON p.seller_id = u.user_id WHERE p.category_id = ?");
            $stmt->execute([$category_id]);
            echo json_encode(['success' => true, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            exit;
        }
        $stmt = $pdo->query("SELECT p.* FROM products p LEFT JOIN users u ON p.seller_id = u.user_id");
        echo json_encode(['success' => true, 'products' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch data']);
}

