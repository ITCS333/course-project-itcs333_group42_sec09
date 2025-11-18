<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Lightweight endpoint to check session state.
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    error_response('Method not allowed.', 405);
}

$user = current_user();
if (!$user) {
    json_response(['authenticated' => false]);
}

json_response([
    'authenticated' => true,
    'user' => $user,
]);
