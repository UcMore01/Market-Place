<?php
require_once 'session.php';
header('Content-Type: application/json');
echo json_encode(['logged_in' => isset($_SESSION['user_id'])]);
