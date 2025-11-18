<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Logout is handled via POST to make it explicit/intentional.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Method not allowed.', 405);
}

$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
}
session_destroy();

json_response(['message' => 'Logged out.']);
