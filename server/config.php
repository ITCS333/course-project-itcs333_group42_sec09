<?php
declare(strict_types=1);

/**
 * Central configuration for the PHP backend.
 * Environment variables override the defaults, making deployments flexible.
 */
return [
    'db_host' => getenv('DB_HOST') ?: '127.0.0.1',
    'db_port' => (int) (getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'itcs333_course',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_port' => (int) (getenv('DB_PORT') ?: 3306),
    'db_name' => getenv('DB_NAME') ?: 'itcs333_course',
    'db_user' => getenv('DB_USER') ?: 'root',
    'db_pass' => getenv('DB_PASS') ?: '123',
];
