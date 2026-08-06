<?php
header('Content-Type: application/json');
require_once __DIR__ . '/db_conn.php';

// Returns a version hash that changes whenever content is added OR edited,
// so public pages can auto-reload via auto_refresh.js.

function safeVal($pdo, $sql) {
    try {
        $v = $pdo->query($sql)->fetchColumn();
        return $v === null ? '0' : (string)$v;
    } catch (Exception $e) {
        return '0';
    }
}

try {
    $signal = '';

    // Tables whose rows are edited (need updated_at tracking).
    $tracked = ['users', 'categories', 'products', 'orders', 'content_items', 'site_settings', 'featured_products', 'blog_posts', 'order_tracking'];
    foreach ($tracked as $t) {
        $signal .= safeVal($pdo, "SELECT COALESCE(MAX(updated_at), '0') FROM `$t`");
        $signal .= safeVal($pdo, "SELECT COALESCE(MAX(created_at), '0') FROM `$t`");
    }

    // Insert-heavy tables (counts catch additions/removals).
    $counted = ['orders', 'products', 'users', 'cart', 'wishlist', 'featured_products', 'reviews', 'blog_posts', 'content_items'];
    foreach ($counted as $t) {
        $signal .= safeVal($pdo, "SELECT COUNT(*) FROM `$t`");
    }

    echo json_encode(['version' => md5($signal)]);
} catch (Exception $e) {
    echo json_encode(['version' => '']);
}
