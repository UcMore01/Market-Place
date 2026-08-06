<?php
require_once 'session.php';
require_once 'db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$title = trim($input['title'] ?? '');
$summary = trim($input['summary'] ?? '');
$content = trim($input['content'] ?? '');
$category = trim($input['category'] ?? '');
$image = trim($input['image'] ?? '');

if ($title === '' || $content === '') {
    http_response_code(400);
    echo json_encode(['error' => 'title and content are required']);
    exit;
}

$role = $_SESSION['user_role'] ?? 'user';
$aname = $_SESSION['username'] ?? 'Member';
$slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));

$stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, summary, content, image, category, status, author_id, author_type, author_name) VALUES (?,?,?,?,?,?,'pending',?,?,?)");
$stmt->execute([$title, $slug, $summary, $content, $image, $category, $_SESSION['user_id'], $role, $aname]);
echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
