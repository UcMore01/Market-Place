<?php
header('Content-Type: application/json');
require_once 'db_conn.php';
require_once 'session.php';

function get_request_data() {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    if (is_array($data)) return $data;
    parse_str($input, $data);
    return $data;
}

$uid = $_SESSION['user_id'] ?? null;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $postId = intval($_GET['post_id'] ?? 0);
    if ($postId <= 0) {
        http_response_code(400);
        echo json_encode(['error' => 'post_id is required']);
        exit;
    }
    $stmt = $pdo->prepare('SELECT c.*, (SELECT COUNT(*) FROM blog_reactions r WHERE r.target_type="comment" AND r.target_id=c.id) AS reactions FROM blog_comments c WHERE c.post_id = ? ORDER BY c.created_at ASC');
    $stmt->execute([$postId]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($uid && $comments) {
        $ids = array_column($comments, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $rstmt = $pdo->prepare("SELECT target_id FROM blog_reactions WHERE target_type='comment' AND user_id=? AND target_id IN ($ph)");
        $rstmt->execute(array_merge([$uid], $ids));
        $reacted = array_flip($rstmt->fetchAll(PDO::FETCH_COLUMN));
        foreach ($comments as &$c) { $c['user_reacted'] = isset($reacted[$c['id']]); }
        unset($c);
    }
    foreach ($comments as &$c) { $c['reactions'] = (int)$c['reactions']; }
    unset($c);
    echo json_encode($comments);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$uid) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    $data = get_request_data();
    $postId = intval($data['post_id'] ?? 0);
    $parentId = isset($data['parent_id']) ? intval($data['parent_id']) : null;
    $text = trim($data['comment_text'] ?? '');
    if ($postId <= 0 || $text === '') {
        http_response_code(400);
        echo json_encode(['error' => 'post_id and comment_text are required']);
        exit;
    }
    $chk = $pdo->prepare("SELECT id FROM blog_posts WHERE id=? AND status='published'");
    $chk->execute([$postId]);
    if (!$chk->fetch()) {
        http_response_code(404);
        echo json_encode(['error' => 'Post not found']);
        exit;
    }
    $authorName = $_SESSION['username'] ?? 'Anonymous';
    $stmt = $pdo->prepare('INSERT INTO blog_comments (post_id, user_id, parent_id, author_name, comment_text) VALUES (?, ?, ?, ?, ?)');
    $stmt->execute([$postId, $uid, $parentId, $authorName, $text]);
    $newId = $pdo->lastInsertId();

    if ($parentId) {
        $pstmt = $pdo->prepare("SELECT user_id, author_name FROM blog_comments WHERE id=?");
        $pstmt->execute([$parentId]);
        $parent = $pstmt->fetch(PDO::FETCH_ASSOC);
        if ($parent && $parent['user_id'] && $parent['user_id'] != $uid) {
            $nstmt = $pdo->prepare("INSERT INTO notifications (user_id, type, message, link) VALUES (?, 'reply', ?, ?)");
            $nstmt->execute([$parent['user_id'], $authorName . ' replied to your comment.', 'blog_post.php?id=' . $postId]);
        }
    }
    echo json_encode(['success' => true, 'id' => $newId]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
