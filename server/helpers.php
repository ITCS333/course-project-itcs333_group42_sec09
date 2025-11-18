<?php
declare(strict_types=1);

/**
 * Shared helpers for all API endpoints (sessions, database, responses, auth).
 */
require_once __DIR__ . '/session.php';
require_once __DIR__ . '/database.php';

/**
 * Reads JSON from php://input and returns an array.
 */
function json_input(): array
{
    $data = file_get_contents('php://input');
    if (!$data) {
        return [];
    }

    $decoded = json_decode($data, true);
    return is_array($decoded) ? $decoded : [];
}

/**
 * Sends a JSON response with status code and exits.
 */
function json_response(array $payload, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

function error_response(string $message, int $status = 400): void
{
    json_response(['error' => $message], $status);
}

/**
 * Returns the current session user or null.
 */
function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

/**
 * Ensures the request originates from an authenticated user.
 */
function require_login(): array
{
    $user = current_user();
    if (!$user) {
        error_response('Authentication required.', 401);
    }
    return $user;
}

/**
 * Ensures the current user is an admin.
 */
function require_admin(): array
{
    $user = require_login();
    if ($user['role'] !== 'admin') {
        error_response('Admin access required.', 403);
    }
    return $user;
}

function is_admin(array $user): bool
{
    return ($user['role'] ?? '') === 'admin';
}

/**
 * Allows access only for the owner or an admin.
 */
function ensure_owner_or_admin(int $ownerId, array $user): void
{
    if (!is_admin($user) && $user['id'] !== $ownerId) {
        error_response('Not authorized to perform this action.', 403);
    }
}

function sanitize_string(?string $value): string
{
    return trim((string) $value);
}
