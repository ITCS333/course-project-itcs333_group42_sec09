<?php
/**
 * Simple PDO connection helper for the course project.
 * Falls back to environment variables if no constants are provided.
 */

class Database {
    public function getConnection(): PDO {
        // Use TCP by default to avoid missing socket errors; override via env.
        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $db   = getenv('DB_NAME') ?: 'course_db';
        $user = getenv('DB_USER') ?: 'root';
        // Default dev password set to '123' to match local setup; override with DB_PASS in production.
        $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '123';
        $charset = 'utf8mb4';
        $port = getenv('DB_PORT') ?: '3306';
        $socket = getenv('DB_SOCKET') ?: '';

        $dsnParts = [
            "mysql:host={$host}",
            "dbname={$db}",
            "charset={$charset}",
        ];

        // If a socket path is provided, append it; otherwise default to TCP/port.
        if (!empty($socket)) {
            $dsnParts[] = "unix_socket={$socket}";
        } else {
            $dsnParts[] = "port={$port}";
        }

        $dsn = implode(';', $dsnParts);
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        return new PDO($dsn, $user, $pass, $options);
    }
}

if (!function_exists('getDBConnection')) {
    function getDBConnection(): PDO {
        $database = new Database();
        return $database->getConnection();
    }
}
