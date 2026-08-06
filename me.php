<?php
require_once __DIR__ . '/../session.php';
header('Content-Type: application/json');

if (!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

echo json_encode([
    'admin_id' => $_SESSION['admin_id'],
    'admin_role' => $_SESSION['admin_role']
]);
