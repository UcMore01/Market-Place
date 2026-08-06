<?php
header('Content-Type: application/json');
require_once 'db_conn.php';

try {
    $stmt = $pdo->prepare("
        SELECT id, title, image_url, body, sort_order
        FROM content_items
        WHERE type = 'banner' AND status = 'active'
        ORDER BY sort_order ASC, id ASC
    ");
    $stmt->execute();
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'banners' => $banners]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Failed to fetch banners']);
}
