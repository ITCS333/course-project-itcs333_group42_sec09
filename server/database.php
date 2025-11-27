<?php
declare(strict_types=1);

$config = require __DIR__ . '/config.php';

/**
 * Returns a shared PDO instance configured for MySQL.
 * Keeps the connection persistent across requests in the same PHP process.
 */
function get_pdo(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
        $config['db_host'],
        $config['db_port'],
        $config['db_name']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    return $pdo;
}
