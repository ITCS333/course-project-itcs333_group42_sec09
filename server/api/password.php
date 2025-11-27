<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Users update their own password via POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Method not allowed.', 405);
}

$user = require_login();
$data = json_input();
$current = $data['current_password'] ?? '';
$new = $data['new_password'] ?? '';

if (!$current || !$new) {
    error_response('Current and new passwords are required.', 422);
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id');
$stmt->execute(['id' => $user['id']]);
$record = $stmt->fetch();

if (!$record || !password_verify($current, $record['password_hash'])) {
    error_response('Current password is incorrect.', 401);
}

if (strlen($new) < 8) {
    error_response('Password must be at least 8 characters.', 422);
}

$stmt = $pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
$stmt->execute([
    'hash' => password_hash($new, PASSWORD_DEFAULT),
    'id' => $user['id'],
]);

json_response(['message' => 'Password updated successfully.']);
