<?php
require_once 'db_conn.php';
$stmt = $pdo->query("SELECT id, title, image FROM blog_posts");
$posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($posts as $p) {
    echo 'ID: ' . $p['id'] . ' | Title: ' . $p['title'] . ' | Image: ' . ($p['image'] ?? '(none)') . PHP_EOL;
}
