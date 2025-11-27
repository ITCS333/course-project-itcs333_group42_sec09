<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Only POST requests are accepted for login.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_response('Method not allowed.', 405);
}

$input = json_input();
$email = filter_var($input['email'] ?? '', FILTER_VALIDATE_EMAIL);
$password = $input['password'] ?? '';

if (!$email || !$password) {
    error_response('Email and password are required.', 422);
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT id, name, email, role, student_id, password_hash FROM users WHERE email = :email LIMIT 1');
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    error_response('Invalid credentials.', 401);
}

unset($user['password_hash']);
$_SESSION['user'] = $user;
session_regenerate_id(true);

json_response([
    'message' => 'Login successful.',
    'user' => $user,
]);
