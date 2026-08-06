<?php
require_once 'session.php';
require_once 'db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'No image uploaded.']);
    exit;
}

$image = $_FILES['image'];
$ext = strtolower(pathinfo($image['name'], PATHINFO_EXTENSION));
$allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
if (!in_array($ext, $allowed)) {
    http_response_code(400);
    echo json_encode(['error' => 'Only JPG, PNG, GIF, or WEBP images are allowed.']);
    exit;
}
if ($image['size'] > 25 * 1024 * 1024) {
    http_response_code(400);
    echo json_encode(['error' => 'Image size must be under 25MB.']);
    exit;
}

$dir = __DIR__ . '/uploads/blog/';
if (!is_dir($dir)) mkdir($dir, 0775, true);

$filename = uniqid('blog_', true) . '.' . $ext;
if (!move_uploaded_file($image['tmp_name'], $dir . $filename)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save image.']);
    exit;
}

echo json_encode(['success' => true, 'image' => 'uploads/blog/' . $filename]);
