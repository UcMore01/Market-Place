<?php
function get_mariadb_connection_options() {
    $hostCandidates = ['127.0.0.1', 'localhost'];
    $portCandidates = [3307, 3306, 3308];
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    foreach ($hostCandidates as $host) {
        foreach ($portCandidates as $port) {
            yield [
                'host' => $host,
                'port' => $port,
                'options' => $options,
            ];
        }
    }
}

function ensure_marketplace_database() {
    $db = 'marketplace';
    $user = 'root';
    $pass = '';

    foreach (get_mariadb_connection_options() as $cfg) {
        $host = $cfg['host'];
        $port = $cfg['port'];
        $dsn = "mysql:host=$host;port=$port;charset=utf8mb4";

        try {
            $pdo = new PDO($dsn, $user, $pass, $cfg['options']);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            return new PDO("mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4", $user, $pass, $cfg['options']);
        } catch (Throwable $e) {
            continue;
        }
    }

    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "Database bootstrap failed: no MariaDB/MySQL connection available on the default local ports." . PHP_EOL);
        exit(1);
    }

    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'The MariaDB/MySQL database is not available yet. Please start the MariaDB service and refresh the page.'
    ]);
    exit;
}

function ensure_database_schema(PDO $pdo) {
    $schemaPath = __DIR__ . '/db_schema.sql';
    if (!is_file($schemaPath)) {
        return;
    }

    $schema = file_get_contents($schemaPath);
    if ($schema === false || trim($schema) === '') {
        return;
    }

    try {
        $pdo->exec($schema);
    } catch (Throwable $e) {
        // Ignore schema issues if the tables already exist.
    }
}

$db = 'marketplace';
$user = 'root';
$pass = '';
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

$pdo = null;
foreach (get_mariadb_connection_options() as $cfg) {
    $host = $cfg['host'];
    $port = $cfg['port'];
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";

    try {
        $pdo = new PDO($dsn, $user, $pass, $cfg['options']);
        break;
    } catch (Throwable $e) {
        continue;
    }
}

if ($pdo === null) {
    $pdo = ensure_marketplace_database();
}

try {
    ensure_database_schema($pdo);
} catch (Throwable $e) {
    // Keep the app resilient even if schema bootstrap is blocked.
}

