<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(intval($_GET['limit'] ?? 10), 100));
$offset = ($page - 1) * $limit;

$search = trim($_GET['search'] ?? '');
$params = [];
$where = 'WHERE 1=1';
if ($search !== '') {
    $where .= " AND (username LIKE ? OR email LIKE ? OR fullname LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$totalStmt = $pdo->prepare("SELECT COUNT(*) FROM users $where");
$totalStmt->execute($params);
$total = $totalStmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY user_id DESC LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'success' => true,
    'users' => $rows,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => ceil($total / $limit)
    ]
]);

