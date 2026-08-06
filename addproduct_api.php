<?php
session_start();
header('Content-Type: application/json');
require_once 'db_conn.php';
require_once 'csrf_helper.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Sellers only.']);
    exit;
}

require_csrf_token();

// --- VALIDATION ---
$name = trim($_POST['name'] ?? '');
$price = floatval($_POST['price'] ?? 0);
$category_id = intval($_POST['category_id'] ?? 0);
$description = trim($_POST['description'] ?? '');
$image = $_FILES['image'] ?? null;

$errors = [];
if ($name === '') $errors[] = 'Product name is required.';
if ($price <= 0) $errors[] = 'Price must be greater than zero.';
if ($category_id <= 0) $errors[] = 'Category is required.';
if ($description === '') $errors[] = 'Description is required.';
if (!$image || $image['error'] !== 0) $errors[] = 'Product image is required.';

if ($image && $image['error'] === 0) {
    $ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array($ext, $allowed)) $errors[] = 'Only JPG, PNG, GIF, or WEBP images are allowed.';
    if ($image['size'] > 25 * 1024 * 1024) $errors[] = 'Image size must be under 25MB.';
}

if ($errors) {
    echo json_encode(['success' => false, 'message' => implode(' ', $errors)]);
    exit;
}

// --- IMAGE UPLOAD ---
$upload_dir = __DIR__ . '/uploads/products/';
if (!is_dir($upload_dir)) mkdir($upload_dir, 0775, true);

$filename = uniqid('prod_', true) . '.' . $ext;
$target = $upload_dir . $filename;
if (!move_uploaded_file($image['tmp_name'], $target)) {
    echo json_encode(['success' => false, 'message' => 'Failed to upload image.']);
    exit;
}
$image_path = 'uploads/products/' . $filename;

// --- INSERT PRODUCT ---
try {
    $stmt = $pdo->prepare("INSERT INTO products (seller_id, name, price, category_id, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $name, $price, $category_id, $description, $image_path]);
    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
