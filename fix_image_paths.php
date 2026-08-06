<?php
require_once 'db_conn.php';
$stmt = $pdo->query("SELECT id, image FROM blog_posts WHERE image LIKE '/uploads/blog/%'");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo 'Found ' . count($posts) . ' posts with absolute image path' . PHP_EOL;
foreach ($posts as $p) {
    $fixed = ltrim($p['image'], '/');
    if ($fixed !== $p['image']) {
        $upd = $pdo->prepare("UPDATE blog_posts SET image = ? WHERE id = ?");
        $upd->execute([$fixed, $p['id']]);
        echo '  Fixed post ' . $p['id'] . ': ' . $p['image'] . ' -> ' . $fixed . PHP_EOL;
    }
}
