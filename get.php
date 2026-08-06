<?php
require_once __DIR__ . '/../admin_api/auth_check.php';
require_once __DIR__ . '/../db_conn.php';
header('Content-Type: application/json');

$rows = $pdo->query("SELECT * FROM site_settings")->fetchAll(PDO::FETCH_ASSOC);
$settings = [];
foreach ($rows as $row) {
    $settings[$row['key']] = $row['value'];
}
echo json_encode($settings);
