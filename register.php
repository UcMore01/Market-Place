<?php
require_once __DIR__ . '/../session.php';
require_once __DIR__ . '/../db_conn.php';
require_once __DIR__ . '/../csrf_helper.php';
require_once __DIR__ . '/request.php';
header('Content-Type: application/json');

// When no admin exists yet, this endpoint is open so the first admin can be
// created. After that, only an authenticated admin may create more.
$adminCount = (int)$pdo->query("SELECT COUNT(*) FROM admins")->fetchColumn();
if ($adminCount > 0) {
    if (!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'Only an admin can create admin accounts.']);
        exit;
    }
}

$data = get_request_data();

require_csrf_token($data['csrf_token'] ?? null);

$fullname = trim($data['fullname'] ?? '');
$username = trim($data['username'] ?? '');
$email = trim($data['email'] ?? '');
$password = $data['password'] ?? '';
$role = 'admin';

if (!$fullname || !$username || !$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}
if (strlen($password) < 6) {
    echo json_encode(['status' => 'error', 'message' => 'Password must be at least 6 characters.']);
    exit;
}

$stmt = $pdo->prepare("SELECT admin_id FROM admins WHERE username=? OR email=?");
$stmt->execute([$username, $email]);
if ($stmt->fetch()) {
    echo json_encode(['status' => 'error', 'message' => 'Username or email already exists.']);
    exit;
}

$hash = password_hash($password, PASSWORD_DEFAULT);
try {
    $stmt = $pdo->prepare("INSERT INTO admins (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$fullname, $username, $email, $hash, $role]);
    echo json_encode(['status' => 'success', 'message' => 'Admin account created.']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Failed to create admin account.']);
}
