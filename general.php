<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    echo json_encode($pdo->query("SELECT * FROM site_settings")->fetchAll(PDO::FETCH_ASSOC));
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    foreach ($data as $key => $value) {
        $stmt = $pdo->prepare("UPDATE site_settings SET value=? WHERE key=?");
        $stmt->execute([$value, $key]);
    }
    echo json_encode(['success' => true]);
}
