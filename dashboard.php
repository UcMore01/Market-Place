<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$stats = [
    'total_users' => (int) $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_orders' => (int) $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_sales' => (float) $pdo->query("SELECT COALESCE(SUM(total_price), 0) FROM orders")->fetchColumn(),
    'total_products' => (int) $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_posts' => (int) $pdo->query("SELECT COUNT(*) FROM blog_posts")->fetchColumn(),
    'total_comments' => (int) $pdo->query("SELECT COUNT(*) FROM blog_comments")->fetchColumn()
];

echo json_encode($stats);
