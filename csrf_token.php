<?php
require_once 'session.php';
header('Content-Type: application/json');
echo json_encode(['csrf_token' => csrf_token()]);
