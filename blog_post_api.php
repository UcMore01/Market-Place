<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_conn.php';

try {
    $stmt = $pdo->prepare("SELECT id, title, summary, image, created_at FROM blog_posts ORDER BY created_at DESC");
    $stmt->execute();
    $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($posts as &$post) {
        if (!empty($post['image']) && !preg_match('~^(?:f|ht)tps?://~i', $post['image'])) {
            if (strpos($post['image'], 'uploads/blog/') !== 0) {
                $post['image'] = 'uploads/blog/' . ltrim($post['image'], '/');
            }
        }
    }

    echo json_encode($posts);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to fetch blog posts.']);
}
