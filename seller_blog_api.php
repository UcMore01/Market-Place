<?php
require_once 'session.php';
require_once 'db_conn.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'seller') {
    http_response_code(403);
    echo json_encode(['error' => 'Sellers only']);
    exit;
}

function normalizeImage($image) {
    if (empty($image)) return null;
    if (preg_match('~^(?:f|ht)tps?://~i', $image)) return $image;
    if (strpos($image, 'uploads/blog/') === 0) return $image;
    return 'uploads/blog/' . ltrim($image, '/');
}

$sellerId = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("SELECT p.*, (SELECT COUNT(*) FROM blog_reactions r WHERE r.target_type='post' AND r.target_id=p.id) AS reactions, (SELECT COUNT(*) FROM blog_comments c WHERE c.post_id=p.id) AS comment_count FROM blog_posts p WHERE p.author_id = ? AND p.author_type = 'seller' ORDER BY p.created_at DESC");
    $stmt->execute([$sellerId]);
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($posts as &$p) {
        $p['image'] = normalizeImage($p['image']);
        $p['reactions'] = (int)$p['reactions'];
        $p['comment_count'] = (int)$p['comment_count'];
    }
    unset($p);
    echo json_encode(['success' => true, 'posts' => $posts]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = isset($input['id']) ? intval($input['id']) : 0;
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

    if ($id > 0) {
        $chk = $pdo->prepare("SELECT id FROM blog_posts WHERE id=? AND author_id=? AND author_type='seller'");
        $chk->execute([$id, $sellerId]);
        if (!$chk->fetch()) {
            http_response_code(403);
            echo json_encode(['error' => 'Not authorized']);
            exit;
        }
        $stmt = $pdo->prepare("UPDATE blog_posts SET title=?, summary=?, content=?, image=?, category=? WHERE id=?");
        $stmt->execute([$title, $summary, $content, $image, $category, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $aname = $_SESSION['username'] ?? 'Seller';
    $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, summary, content, image, category, status, author_id, author_type, author_name) VALUES (?,?,?,?,?,?,'published',?,'seller',?)");
    $stmt->execute([$title, $slug, $summary, $content, $image, $category, $sellerId, $aname]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $input);
    $id = isset($input['id']) ? intval($input['id']) : 0;
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'id required']);
        exit;
    }
    $chk = $pdo->prepare("SELECT id FROM blog_posts WHERE id=? AND author_id=? AND author_type='seller'");
    $chk->execute([$id, $sellerId]);
    if (!$chk->fetch()) {
        http_response_code(403);
        echo json_encode(['error' => 'Not authorized']);
        exit;
    }
    $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
