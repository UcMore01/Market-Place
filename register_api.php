<?php
require_once "db_conn.php";
header("Content-Type: application/json");
session_start();
require_once 'csrf_helper.php';

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

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
if (!$token || !validate_csrf_token($token)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing CSRF token.']);
    exit;
}

$user_type = $requestData['user_type'] ?? 'buyer';
if (!in_array($user_type, ['buyer', 'seller'])) {
    echo json_encode(["status" => "error", "message" => "Invalid account type."]);
    exit();
}
$fullname = trim($requestData['fullname'] ?? '');
$email = trim($requestData['email'] ?? '');
$username = trim($requestData['username'] ?? '');
$password = $requestData['password'] ?? '';
$shop_name = trim($requestData['store_name'] ?? '');

if (!$fullname || !$email || !$username || !$password) {
    echo json_encode(["status" => "error", "message" => "All fields are required."]);
    exit();
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email address."]);
    exit();
}
if (strlen($password) < 6) {
    echo json_encode(["status" => "error", "message" => "Password must be at least 6 characters."]);
    exit();
}

$role = $user_type;
$status = 'active';
$seller_document = null;
if ($role === 'seller') {
    $status = 'pending';
    if (!$shop_name) {
        echo json_encode(["status" => "error", "message" => "Store name is required for sellers."]);
        exit();
    }
    if (!isset($_FILES['seller_doc']) || $_FILES['seller_doc']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["status" => "error", "message" => "Seller document required."]);
        exit();
    }
    $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($_FILES['seller_doc']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        echo json_encode(["status" => "error", "message" => "Invalid document type."]);
        exit();
    }
    $upload_dir = 'uploads/seller_docs/';
    if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
    $uniqueName = uniqid('seller_') . '.' . $ext;
    $seller_document = $upload_dir . $uniqueName;
    if (!move_uploaded_file($_FILES['seller_doc']['tmp_name'], $seller_document)) {
        echo json_encode(["status" => "error", "message" => "Failed to upload seller document."]);
        exit();
    }
}

$stmt = $pdo->prepare("SELECT user_id FROM users WHERE email=? OR username=?");
$stmt->execute([$email, $username]);
if ($stmt->fetch()) {
    echo json_encode(["status" => "error", "message" => "Email or username already exists."]);
    exit();
}

$hash = password_hash($password, PASSWORD_DEFAULT);

try {
    $stmt = $pdo->prepare("INSERT INTO users (fullname, email, username, password, shop_name, seller_document, role, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $fullname, $email, $username, $hash,
        ($role === 'seller' ? $shop_name : null),
        $seller_document,
        $role,
        $status
    ]);
    echo json_encode(["status" => "success", "message" => "Registration successful!"]);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => "Registration failed."]);
}
?>
