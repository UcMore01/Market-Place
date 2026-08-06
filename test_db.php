<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
try {
    $db = 'marketplace';
    $user = 'root';
    $pass = '';
    $charset = 'utf8mb4';
    $connected = false;

    foreach (['127.0.0.1', 'localhost'] as $host) {
        foreach ([3307, 3306, 3308] as $port) {
            try {
                $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
                $pdo = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                echo "Connection OK to $host:$port\n";
                $connected = true;
                break 2;
            } catch (Throwable $e) {
                continue;
            }
        }
    }

    if (!$connected) {
        throw new Exception('Unable to connect to MariaDB/MySQL on localhost ports 3306/3307/3308.');
    }
} catch (\PDOException $e) {
    echo "PDO Error: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>