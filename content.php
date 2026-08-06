<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($pdo->query("SELECT * FROM content_items")->fetchAll(PDO::FETCH_ASSOC));
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $stmt = $pdo->prepare("INSERT INTO content_items (type, title, image_url, body, status) VALUES (?, ?, ?, ?, ?)");
    $type = $data['type'] ?? 'page';
    $success = $stmt->execute([$type, $data['title'], $data['image_url'] ?? null, $data['body'] ?? null, $data['status'] ?? 'inactive']);
    if ($success) echo json_encode(['success' => true]);
    else {
        http_response_code(500);
        echo json_encode(['error' => 'Add failed']);
    }
}
