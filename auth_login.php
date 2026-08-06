<?php
session_start();
require_once 'db_conn.php';
require_once 'csrf_helper.php';
header('Content-Type: application/json');

require_csrf_token();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit;
}

// Read and decode JSON input
$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON']);
    exit;
}

$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

// Basic input validation
if ($username === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Username and password are required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
$stmt->execute([$username, $username]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    if (($user['status'] ?? 'active') !== 'active') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Your account is ' . ($user['status'] ?? 'inactive') . '.']);
        exit;
    }
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_role'] = $user['role'] ?? 'buyer';
    $_SESSION['username'] = $user['username'];

    $role = $_SESSION['user_role'];
    if ($role === 'seller') {
        $redirect = 'seller_dashboard.html';
    } elseif ($role === 'admin') {
        $redirect = 'admin_login.php';
    } else {
        $redirect = 'buyer_dashboard.html';
    }

    http_response_code(200);
    echo json_encode([
        'status' => 'success',
        'redirect' => $redirect,
        'message' => 'Login successful'
    ]);
} else {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Invalid username or password']);
}
