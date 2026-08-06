<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

// A buyer/seller session must not be used to authenticate into the admin area.
if (isset($_SESSION['user_id']) && in_array($_SESSION['user_role'] ?? '', ['buyer', 'seller'])) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Admin access denied for this account.']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

$stmt = $pdo->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->execute([$username]);
$admin = $stmt->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_id'] = $admin['admin_id'];
    $_SESSION['admin_role'] = $admin['role'];
    $_SESSION['admin_username'] = $admin['username'];
    echo json_encode([
        'status' => 'success',
        'redirect' => 'admin_dashboard.php'
    ]);
} else {
    http_response_code(401);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid credentials'
    ]);
}
