<?php
declare(strict_types=1);

/**
 * Bootstraps PHP sessions with secure cookie defaults.
 * Required before any request touches $_SESSION.
 */
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Lax',
    ]);
}
