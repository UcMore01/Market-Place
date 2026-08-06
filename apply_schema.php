<?php
// apply_schema.php - run to apply db_schema.sql to the local marketplace database
function connect_to_marketplace() {
    $hosts = ['127.0.0.1', 'localhost'];
    $ports = [3307, 3306, 3308];
    foreach ($hosts as $host) {
        foreach ($ports as $port) {
            try {
                $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";
                $pdo = new PDO($dsn, 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
                $pdo->exec("CREATE DATABASE IF NOT EXISTS `marketplace` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                return new PDO("mysql:host=$host;port=$port;dbname=marketplace;charset=utf8mb4", 'root', '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    throw new RuntimeException('Unable to connect to MariaDB/MySQL on localhost ports 3306/3307/3308.');
}

try {
    $pdo = connect_to_marketplace();
} catch (Exception $e) {
    echo "DB Connection failed: " . $e->getMessage() . "\n";
    exit(1);
}
$sql = file_get_contents(__DIR__ . '/db_schema.sql');
if ($sql === false) { echo "Could not read db_schema.sql\n"; exit(1); }
try {
    $pdo->exec($sql);
    echo "Schema applied successfully.\n";
} catch (Exception $e) {
    echo "Error applying schema: " . $e->getMessage() . "\n";
    exit(1);
}

// Ensure the users.status enum supports all statuses used by the app
// (CREATE TABLE IF NOT EXISTS won't alter an existing table).
try {
    $pdo->exec("ALTER TABLE users MODIFY status ENUM('active','pending','suspended','banned','blocked') NOT NULL DEFAULT 'active'");
    echo "Ensured users.status enum is up to date.\n";
} catch (Exception $e) {
    echo "Note: could not alter users.status (" . $e->getMessage() . ").\n";
}

// Ensure updated_at columns so content edits are detected by the auto-refresh
// version check (CREATE TABLE IF NOT EXISTS won't alter existing tables).
foreach (['users','categories','products','orders','content_items','site_settings','featured_products','blog_posts'] as $tbl) {
    try {
        $pdo->exec("ALTER TABLE `$tbl` ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
    } catch (Exception $e) {
        // column likely already exists
    }
}
echo "Ensured updated_at columns exist.\n";

// Ensure reviews.helpful_count exists for the helpful voting feature.
try {
    $pdo->exec("ALTER TABLE `reviews` ADD COLUMN helpful_count INT NOT NULL DEFAULT 0");
} catch (Exception $e) {
    // column likely already exists
}
echo "Ensured reviews.helpful_count exists.\n";

// Blog enhancements: add author/category/status/slug columns and threaded
// comments, plus the reactions & notifications tables (idempotent).
$blogCols = [
    'blog_posts' => ['slug VARCHAR(255) DEFAULT NULL', 'category VARCHAR(100) DEFAULT NULL', "status VARCHAR(20) DEFAULT 'published'", 'author_id INT DEFAULT NULL', "author_type VARCHAR(20) DEFAULT 'admin'", 'author_name VARCHAR(255) DEFAULT NULL', 'updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP'],
    'blog_comments' => ['parent_id INT DEFAULT NULL', "status VARCHAR(20) DEFAULT 'visible'"]
];
foreach ($blogCols as $tbl => $cols) {
    foreach ($cols as $col) {
        try {
            $pdo->exec("ALTER TABLE `$tbl` ADD COLUMN $col");
        } catch (Exception $e) { /* likely exists */ }
    }
}
$extraTables = <<<'SQL'
CREATE TABLE IF NOT EXISTS `blog_reactions` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `target_type` VARCHAR(20) NOT NULL,
  `target_id` INT NOT NULL,
  `reaction_type` VARCHAR(20) DEFAULT 'like',
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_reaction` (`user_id`, `target_type`, `target_id`, `reaction_type`),
  INDEX (`target_type`, `target_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT NOT NULL AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `type` VARCHAR(40) DEFAULT 'general',
  `message` TEXT DEFAULT NULL,
  `link` VARCHAR(512) DEFAULT NULL,
  `is_read` TINYINT(1) DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX (`user_id`),
  INDEX (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
SQL;
try {
    $pdo->exec($extraTables);
    echo "Ensured blog reactions & notifications tables exist.\n";
} catch (Exception $e) {
    echo "Note: could not create blog extra tables (" . $e->getMessage() . ").\n";
}

// Bootstrap a default admin so the admin panel is reachable.
// Change these credentials after first login.
$check = $pdo->query("SELECT COUNT(*) FROM admins");
if ($check && (int)$check->fetchColumn() === 0) {
    $hash = password_hash('Admin@1234', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO admins (fullname, username, email, password, role) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['Site Admin', 'superadmin', 'admin@marketplace.local', $hash, 'admin']);
    echo "Default admin created (username: superadmin / password: Admin@1234). Change it after login.\n";
}

// Seed editable info pages (About, FAQ, Terms, Privacy, Refund) into content_items.
if (file_exists(__DIR__ . '/seed_content.php')) {
    require_once __DIR__ . '/seed_content.php';
}
