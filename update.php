<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);
if (!$data) {
    $data = $_POST;
}

if (!$data || !is_array($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
}

foreach ($data as $key => $value) {
    $stmt = $pdo->prepare("UPDATE site_settings SET value=? WHERE `key`=?");
    $stmt->execute([$value, $key]);
}

echo json_encode(['success' => true]);
