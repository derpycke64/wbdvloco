<?php
// Shared PDO connection for all LOCO pages.
// Local WAMP defaults are kept for convenience; environment variables can override them.
$host = getenv('LOCO_DB_HOST') ?: '127.0.0.1';
$port = getenv('LOCO_DB_PORT') ?: '3306';
$db = getenv('LOCO_DB_NAME') ?: 'loco_db';
$user = getenv('LOCO_DB_USER') ?: 'root';
$pass = getenv('LOCO_DB_PASS') !== false ? getenv('LOCO_DB_PASS') : '';
$charset = 'utf8mb4';

$dsn = "mysql:host={$host};port={$port};dbname={$db};charset={$charset}";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    error_log('LOCO database connection failed: ' . $e->getMessage());
    http_response_code(500);
    exit('Unable to connect to the LOCO database. Check the database settings in Loco/db.php or your environment variables.');
}
?>