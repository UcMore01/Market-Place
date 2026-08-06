<?php
session_start();
require_once "db_conn.php";
header("Content-Type: application/json");

require_once "csrf_helper.php";
require_csrf_token();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method not allowed"]);
    exit();
}

$data = get_request_data_safe();
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if (!$username || !$password) {
    echo json_encode(["status" => "error", "message" => "Username and password required."]);
    exit();
}

// Accept login by username or email
$stmt = $pdo->prepare("SELECT * FROM users WHERE username=? OR email=?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
    exit();
}

// Admins must use the admin portal
if (($user['role'] ?? '') === 'admin') {
    echo json_encode([
        "status" => "redirect_admin",
        "message" => "Admins must login via the admin portal.",
        "redirect" => "admin_login.php"
    ]);
    exit();
}

if (password_verify($password, $user['password'])) {
    if (($user['status'] ?? '') !== 'active') {
        echo json_encode([
            "status" => "error",
            "message" => "Your account is " . ($user['status'] ?? 'inactive') . ". Contact support."
        ]);
        exit();
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['user_role'] = $user['role'];

    if ($user['role'] === 'seller') {
        echo json_encode([
            "status" => "success",
            "role" => "seller",
            "message" => "Seller login successful!",
            "redirect" => "seller_dashboard.html"
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "role" => "buyer",
            "message" => "Buyer login successful!",
            "redirect" => "buyer_dashboard.html"
        ]);
    }
} else {
    echo json_encode(["status" => "error", "message" => "Invalid credentials."]);
}

// Helper kept local to avoid depending on request.php include order
function get_request_data_safe() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (is_array($data)) return $data;
    if (!empty($_POST)) return $_POST;
    return [];
}
