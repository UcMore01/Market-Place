<?php
require_once __DIR__ . '/../session.php';

if (!isset($_SESSION['admin_id']) || ($_SESSION['admin_role'] ?? '') !== 'admin') {
    // Browser page loads (admin_*.php wrappers) should be sent to login.
    // API/XHR calls expect a JSON 401 so the frontend can react.
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isPageLoad = strpos($accept, 'text/html') !== false;
    if ($isPageLoad) {
        header('Location: admin_login.php');
        exit;
    }
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}
