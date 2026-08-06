<?php
require_once __DIR__ . '/admin_api/auth_check.php';
require_once __DIR__ . '/db_conn.php';
header('Content-Type: application/json');

function normalizeImage($image) {
    if (empty($image)) return null;
    if (preg_match('~^(?:f|ht)tps?://~i', $image)) return $image;
    if (strpos($image, 'uploads/blog/') === 0) return $image;
    return 'uploads/blog/' . ltrim($image, '/');
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->query("SELECT * FROM blog_posts ORDER BY created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) { $r['image'] = normalizeImage($r['image']); }
    unset($r);
    echo json_encode($rows);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    parse_str(file_get_contents('php://input'), $d);
    $id = intval($d['id'] ?? 0);
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'id required']);
        exit;
    }
    $pdo->prepare("DELETE FROM blog_posts WHERE id=?")->execute([$id]);
    echo json_encode(['success' => true]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = intval($data['id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $summary = trim($data['summary'] ?? '');
    $content = trim($data['content'] ?? '');
    $image = trim($data['image'] ?? '');
    $category = trim($data['category'] ?? '');
    $status = trim($data['status'] ?? 'published');

    if ($title === '' || $content === '') {
        http_response_code(400);
        echo json_encode(['error' => 'title and content are required']);
        exit;
    }

    if ($id > 0) {
        $stmt = $pdo->prepare("UPDATE blog_posts SET title=?, summary=?, content=?, image=?, category=?, status=? WHERE id=?");
        $stmt->execute([$title, $summary, $content, $image, $category, $status, $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    $aname = 'Admin';
    if (isset($_SESSION['admin_id'])) {
        $a = $pdo->prepare("SELECT fullname, username FROM admins WHERE admin_id=?");
        $a->execute([$_SESSION['admin_id']]);
        $adm = $a->fetch();
        if ($adm) $aname = $adm['fullname'] ?: $adm['username'];
    }
    $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $title));
    $stmt = $pdo->prepare("INSERT INTO blog_posts (title, slug, summary, content, image, category, status, author_id, author_type, author_name) VALUES (?,?,?,?,?,?,?,?,'admin',?)");
    $stmt->execute([$title, $slug, $summary, $content, $image, $category, $status, $_SESSION['admin_id'] ?? null, $aname]);
    echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
