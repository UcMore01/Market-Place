<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
require_once 'session.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
$uid = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    if (is_array($ids) && count($ids) > 0) {
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND id IN ($ph)");
        $stmt->execute(array_merge([$uid], array_map('intval', $ids)));
    } else {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=?")->execute([$uid]);
    }
    echo json_encode(['success' => true]);
    exit;
}

$stmt = $pdo->prepare("SELECT id, type, message, link, is_read, created_at FROM notifications WHERE user_id=? ORDER BY created_at DESC LIMIT 20");
$stmt->execute([$uid]);
$notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$unread = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
$unread->execute([$uid]);
echo json_encode(['notifications' => $notes, 'unread' => (int)$unread->fetchColumn()]);
