<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Weekly breakdown endpoint with support for comments (?type=comments).
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'items';

if ($type === 'comments') {
    handle_weekly_comments($pdo, $method);
    exit;
}

switch ($method) {
    case 'GET':
        require_login();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM weekly_entries WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $entry = $stmt->fetch();
            if (!$entry) {
                error_response('Entry not found.', 404);
            }
            json_response($entry);
        } else {
            $stmt = $pdo->query('SELECT * FROM weekly_entries ORDER BY week_number ASC');
            json_response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $user = require_admin();
        $data = json_input();
        $weekNumber = (int) ($data['week_number'] ?? 0);
        $title = sanitize_string($data['title'] ?? '');
        $description = sanitize_string($data['description'] ?? '');
        $notes = sanitize_string($data['notes'] ?? '');
        $links = sanitize_string($data['links'] ?? '');

        if ($weekNumber <= 0 || !$title) {
            error_response('Week number and title are required.', 422);
        }

        $stmt = $pdo->prepare('INSERT INTO weekly_entries (week_number, title, description, notes, links, created_by) VALUES (:week_number, :title, :description, :notes, :links, :created_by)');
        $stmt->execute([
            'week_number' => $weekNumber,
            'title' => $title,
            'description' => $description,
            'notes' => $notes,
            'links' => $links,
            'created_by' => $user['id'],
        ]);

        json_response(['message' => 'Weekly entry created.'], 201);
        break;

    case 'PUT':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Entry id is required.', 422);
        }
        $weekNumber = (int) ($data['week_number'] ?? 0);
        $title = sanitize_string($data['title'] ?? '');
        if ($weekNumber <= 0 || !$title) {
            error_response('Week number and title are required.', 422);
        }

        $stmt = $pdo->prepare('UPDATE weekly_entries SET week_number = :week_number, title = :title, description = :description, notes = :notes, links = :links WHERE id = :id');
        $stmt->execute([
            'week_number' => $weekNumber,
            'title' => $title,
            'description' => sanitize_string($data['description'] ?? ''),
            'notes' => sanitize_string($data['notes'] ?? ''),
            'links' => sanitize_string($data['links'] ?? ''),
            'id' => $id,
        ]);

        json_response(['message' => 'Weekly entry updated.']);
        break;

    case 'DELETE':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Entry id is required.', 422);
        }
        $pdo->prepare('DELETE FROM weekly_entries WHERE id = :id')->execute(['id' => $id]);
        json_response(['message' => 'Weekly entry deleted.']);
        break;

    default:
        error_response('Method not allowed.', 405);
}

function handle_weekly_comments(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'GET':
            require_login();
            $weeklyId = isset($_GET['weekly_id']) ? (int) $_GET['weekly_id'] : 0;
            if ($weeklyId <= 0) {
                error_response('weekly_id is required.', 422);
            }
            $stmt = $pdo->prepare('SELECT wc.id, wc.comment, wc.created_at, u.name AS author
                FROM weekly_comments wc
                JOIN users u ON wc.user_id = u.id
                WHERE wc.weekly_id = :weekly_id
                ORDER BY wc.created_at ASC');
            $stmt->execute(['weekly_id' => $weeklyId]);
            json_response($stmt->fetchAll());
            break;

        case 'POST':
            $user = require_login();
            $data = json_input();
            $weeklyId = (int) ($data['weekly_id'] ?? 0);
            $comment = sanitize_string($data['comment'] ?? '');
            if ($weeklyId <= 0 || !$comment) {
                error_response('weekly_id and comment are required.', 422);
            }
            $stmt = $pdo->prepare('INSERT INTO weekly_comments (weekly_id, user_id, comment) VALUES (:weekly_id, :user_id, :comment)');
            $stmt->execute([
                'weekly_id' => $weeklyId,
                'user_id' => $user['id'],
                'comment' => $comment,
            ]);
            json_response(['message' => 'Comment added.'], 201);
            break;

        case 'DELETE':
            $user = require_login();
            $data = json_input();
            $commentId = (int) ($data['id'] ?? 0);
            if ($commentId <= 0) {
                error_response('Comment id is required.', 422);
            }
            $stmt = $pdo->prepare('SELECT user_id FROM weekly_comments WHERE id = :id');
            $stmt->execute(['id' => $commentId]);
            $comment = $stmt->fetch();
            if (!$comment) {
                error_response('Comment not found.', 404);
            }
            ensure_owner_or_admin((int) $comment['user_id'], $user);
            $pdo->prepare('DELETE FROM weekly_comments WHERE id = :id')->execute(['id' => $commentId]);
            json_response(['message' => 'Comment removed.']);
            break;

        default:
            error_response('Method not allowed.', 405);
    }
}
