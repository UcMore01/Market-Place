<?php
session_start();
require_once "db_conn.php";
header("Content-Type: application/json");

$method = $_SERVER["REQUEST_METHOD"];

if ($method === "GET") {
    fetchProducts();
} elseif ($method === "POST") {
    if (!isset($_SESSION["user_id"])) {
        http_response_code(401);
        echo json_encode(["status" => "error", "message" => "Unauthorized access. Please log in."]);
        exit();
    }
    addProduct();
} else {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

function fetchProducts()
{
    global $pdo;
    try {
        $category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : null;
        $seller_id = isset($_GET['seller_id']) ? intval($_GET['seller_id']) : null;
        $seller_username = trim($_GET['seller_username'] ?? '');

        $sql = "SELECT p.id, p.name, p.description, p.price, p.image_url, p.category_id,
                        u.username as seller_name, u.shop_name as seller_shop
                 FROM products p
                 LEFT JOIN users u ON p.seller_id = u.user_id
                 WHERE 1=1";
        $params = [];

        if ($category_id) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category_id;
        }
        if ($seller_id) {
            $sql .= " AND p.seller_id = ?";
            $params[] = $seller_id;
        }
        if ($seller_username !== '') {
            $sql .= " AND u.username = ?";
            $params[] = $seller_username;
        }

        $sql .= " ORDER BY p.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["status" => "success", "products" => $products]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to fetch products"]);
    }
}

function addProduct()
{
    global $pdo;
    $seller_id = $_SESSION["user_id"];
    $name = trim($_POST["name"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $price = filter_var($_POST["price"] ?? 0, FILTER_VALIDATE_FLOAT);
    $category_id = intval($_POST["category_id"] ?? 0);

    if (!$name || $price === false || $category_id <= 0) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Missing or invalid required fields"]);
        exit();
    }

    if (!isset($_FILES["image"]) || $_FILES["image"]["error"] != UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Invalid image file"]);
        exit();
    }
    $allowedExt = ['jpg','jpeg','png','gif','webp'];
    $fileInfo = pathinfo($_FILES["image"]["name"]);
    $ext = strtolower($fileInfo['extension'] ?? '');
    if (!in_array($ext, $allowedExt)) {
        http_response_code(400);
        echo json_encode(["status" => "error", "message" => "Only JPG, PNG, GIF, or WEBP images allowed"]);
        exit();
    }
    $uploadDir = "uploads/";
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $uniqueName = uniqid('img_') . '.' . $ext;
    $imagePath = $uploadDir . $uniqueName;
    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $imagePath)) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to upload image"]);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, description, price, image_url, category_id) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$seller_id, $name, $description, $price, $imagePath, $category_id]);
        $product_id = $pdo->lastInsertId();
        http_response_code(201);
        echo json_encode(["status" => "success", "message" => "Product added successfully", "product_id" => $product_id]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Failed to add product"]);
    }
}
?>
