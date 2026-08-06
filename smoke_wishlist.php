<?php
chdir(__DIR__ . '/..');
// Simulate a GET request without authentication
$_SERVER['REQUEST_METHOD'] = 'GET';
// ensure no user session
unset($_SESSION['user_id']);
require_once 'wishlist_api.php';
