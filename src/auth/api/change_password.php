<?php
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if (empty($_SESSION['logged_in'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? [];

$required = ['current_password', 'new_password'];
foreach ($required as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing field: ' . $field]);
        exit;
    }
}

$currentPassword = $data['current_password'];
$newPassword = $data['new_password'];

if (strlen($newPassword) < 8) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'New password must be at least 8 characters']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT password FROM users WHERE id = :id");
    $stmt->execute([':id' => $_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user || !password_verify($currentPassword, $user['password'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
        exit;
    }

    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $update = $db->prepare("UPDATE users SET password = :password, updated_at = NOW() WHERE id = :id");
    $update->execute([':password' => $newHash, ':id' => $_SESSION['user_id']]);

    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Password updated']);
    exit;
} catch (PDOException $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}
