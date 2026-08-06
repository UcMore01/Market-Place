<?php
chdir(__DIR__ . '/..');
// Simulate a GET request for product id 1
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['id'] = 1;
require_once 'product_details_api.php';
