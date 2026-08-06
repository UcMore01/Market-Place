<?php
// Reusable auth guard for user-facing pages (buyer/seller).
// Redirects to login if the session is not authenticated.
require_once __DIR__ . '/session.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.html');
    exit;
}
