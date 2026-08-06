<?php
header('Content-Type: application/json');
require_once 'session.php';
require_once 'db_conn.php';

function normalizeImage($image) {
    if (empty($image)) return null;
    if (preg_match('~^(?:f|ht)tps?://~i', $image)) return $image;
    if (strpos($image, 'uploads/blog/') === 0) return $image;
    return 'uploads/blog/' . ltrim($image, '/');
}

$uid = $_SESSION['user_id'] ?? null;

if (isset($_GET['categories'])) {
    $rows = $pdo->query("SELECT DISTINCT category FROM blog_posts WHERE category IS NOT NULL AND category <> '' AND status='published' ORDER BY category ASC")->fetchAll(PDO::FETCH_COLUMN);
    echo json_encode(['categories' => $rows]);
    exit;
}

if (isset($_GET['id']) && intval($_GET['id']) > 0) {
    $id = intval($_GET['id']);
    $stmt = $pdo->prepare('SELECT * FROM blog_posts WHERE id = ? AND status = ?');
    $stmt->execute([$id, 'published']);
    $post = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }
    $post['image'] = normalizeImage($post['image']);
    $post['reactions'] = (int)$pdo->query("SELECT COUNT(*) FROM blog_reactions WHERE target_type='post' AND target_id=$id")->fetchColumn();
    $post['comment_count'] = (int)$pdo->query("SELECT COUNT(*) FROM blog_comments WHERE post_id=$id")->fetchColumn();
    $post['user_reacted'] = $uid ? (bool)$pdo->query("SELECT 1 FROM blog_reactions WHERE target_type='post' AND target_id=$id AND user_id=$uid")->fetchColumn() : false;
    echo json_encode($post);
    exit;
}

$page = max(1, intval($_GET['page'] ?? 1));
$limit = max(1, min(intval($_GET['limit'] ?? 9), 50));
$offset = ($page - 1) * $limit;
$category = trim($_GET['category'] ?? '');
$search = trim($_GET['search'] ?? '');
$author = trim($_GET['author'] ?? '');
$sort = $_GET['sort'] ?? 'recent';

$where = "WHERE status = 'published'";
$params = [];
if ($category !== '') { $where .= " AND category = ?"; $params[] = $category; }
if ($search !== '') { $where .= " AND (title LIKE ? OR summary LIKE ? OR content LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; $params[] = "%$search%"; }
if ($author !== '') { $where .= " AND author_name = ?"; $params[] = $author; }

$reactSub = "(SELECT COUNT(*) FROM blog_reactions r WHERE r.target_type='post' AND r.target_id=p.id)";
$commentSub = "(SELECT COUNT(*) FROM blog_comments c WHERE c.post_id=p.id)";
$sql = "SELECT p.*, $reactSub AS reactions, $commentSub AS comment_count FROM blog_posts p $where";
$sql .= ($sort === 'trending')
    ? " ORDER BY (reactions + comment_count * 2) DESC, p.created_at DESC"
    : " ORDER BY p.created_at DESC";

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM blog_posts p $where");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$sql .= " LIMIT ? OFFSET ?";
$stmt = $pdo->prepare($sql);
foreach ($params as $i => $v) { $stmt->bindValue($i + 1, $v); }
$stmt->bindValue(count($params) + 1, $limit, PDO::PARAM_INT);
$stmt->bindValue(count($params) + 2, $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($posts as &$p) {
    $p['image'] = normalizeImage($p['image']);
    $p['reactions'] = (int)$p['reactions'];
    $p['comment_count'] = (int)$p['comment_count'];
}
unset($p);

if ($uid && $posts) {
    $ids = array_column($posts, 'id');
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $rstmt = $pdo->prepare("SELECT target_id FROM blog_reactions WHERE target_type='post' AND user_id=? AND target_id IN ($ph)");
    $rstmt->execute(array_merge([$uid], $ids));
    $reacted = array_flip($rstmt->fetchAll(PDO::FETCH_COLUMN));
    foreach ($posts as &$p) { $p['user_reacted'] = isset($reacted[$p['id']]); }
    unset($p);
}

echo json_encode([
    'posts' => $posts,
    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'total_pages' => ceil($total / $limit)]
]);
