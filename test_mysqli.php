<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
$user = 'root';
$pass = '';
$db   = 'marketplace';
$connected = false;

foreach (['127.0.0.1', 'localhost'] as $host) {
    foreach ([3307, 3306, 3308] as $port) {
        try {
            $mysqli = new mysqli($host, $user, $pass, $db, $port);
            if ($mysqli->connect_error) {
                throw new Exception($mysqli->connect_error);
            }
            echo "MySQLi connected OK to DB: $db on $host:$port\n";
            $result = $mysqli->query("SHOW TABLES");
            if ($result) {
                echo "Tables:\n";
                while ($row = $result->fetch_row()) echo "  - " . $row[0] . "\n";
            }
            $mysqli->close();
            $connected = true;
            break 2;
        } catch (Throwable $e) {
            continue;
        }
    }
}

if (!$connected) {
    die('Unable to connect to MariaDB/MySQL on localhost ports 3306/3307/3308.');
}

