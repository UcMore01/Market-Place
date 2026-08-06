<?php
require_once __DIR__ . '/admin_api/auth_check.php';
header('Content-Type: text/html; charset=utf-8');
echo file_get_contents(__DIR__ . '/admin_manage_content.html');
?>
