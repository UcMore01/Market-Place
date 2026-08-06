<?php
header('Content-Type: application/xml; charset=utf-8');
header('Cache-Control: max-age=3600');

$baseUrl = 'http://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME']);
$baseUrl = rtrim($baseUrl, '/') . '/';

$pages = [
    'index.html' => ['priority' => '1.0', 'changefreq' => 'daily'],
    'products.html' => ['priority' => '0.9', 'changefreq' => 'daily'],
    'categories.html' => ['priority' => '0.8', 'changefreq' => 'weekly'],
    'blog.html' => ['priority' => '0.8', 'changefreq' => 'daily'],
    'about_us.html' => ['priority' => '0.5', 'changefreq' => 'monthly'],
    'contact.html' => ['priority' => '0.5', 'changefreq' => 'monthly'],
    'faq.html' => ['priority' => '0.5', 'changefreq' => 'monthly'],
    'privacy_policy.html' => ['priority' => '0.3', 'changefreq' => 'yearly'],
    'terms_condition.html' => ['priority' => '0.3', 'changefreq' => 'yearly'],
    'refund_return_policy.html' => ['priority' => '0.3', 'changefreq' => 'yearly'],
    'customer_support.html' => ['priority' => '0.4', 'changefreq' => 'monthly'],
    'login.html' => ['priority' => '0.6', 'changefreq' => 'monthly'],
    'register.html' => ['priority' => '0.6', 'changefreq' => 'monthly'],
    'cart.html' => ['priority' => '0.7', 'changefreq' => 'daily'],
    'checkout.html' => ['priority' => '0.7', 'changefreq' => 'daily'],
    'orders.html' => ['priority' => '0.6', 'changefreq' => 'daily'],
    'wishlist.html' => ['priority' => '0.5', 'changefreq' => 'weekly'],
];

try {
    foreach (['127.0.0.1', 'localhost'] as $host) {
        foreach ([3307, 3306, 3308] as $port) {
            try {
                $pdo = new PDO("mysql:host=$host;port=$port;dbname=marketplace;charset=utf8mb4", 'root', '');
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                break 2;
            } catch (Throwable $e) {
                continue;
            }
        }
    }
    if (!isset($pdo)) {
        throw new RuntimeException('Unable to connect to MariaDB/MySQL.');
    }

    $stmt = $pdo->query("SELECT p.id, p.slug, p.created_at FROM products p WHERE p.status = 'active'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'] ?: ('product-' . $row['id']);
        $pages['product_details.html?id=' . $row['id']] = [
            'priority' => '0.7',
            'changefreq' => 'weekly',
            'lastmod' => date('c', strtotime($row['created_at']))
        ];
    }

    $stmt = $pdo->query("SELECT id, slug, created_at FROM blog_posts WHERE status = 'published'");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $slug = $row['slug'] ?: ('blog-' . $row['id']);
        $pages['blog_post.html?id=' . $row['id']] = [
            'priority' => '0.6',
            'changefreq' => 'weekly',
            'lastmod' => date('c', strtotime($row['created_at']))
        ];
    }
} catch (Exception $e) {
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

foreach ($pages as $path => $info) {
    $loc = htmlspecialchars($baseUrl . ltrim($path, '/'), ENT_XML1, 'UTF-8');
    $lastmod = isset($info['lastmod']) ? $info['lastmod'] : date('c');
    $changefreq = htmlspecialchars($info['changefreq'], ENT_XML1, 'UTF-8');
    $priority = htmlspecialchars($info['priority'], ENT_XML1, 'UTF-8');
    echo "  <url>\n";
    echo "    <loc>{$loc}</loc>\n";
    echo "    <lastmod>{$lastmod}</lastmod>\n";
    echo "    <changefreq>{$changefreq}</changefreq>\n";
    echo "    <priority>{$priority}</priority>\n";
    echo "  </url>\n";
}

echo '</urlset>' . "\n";