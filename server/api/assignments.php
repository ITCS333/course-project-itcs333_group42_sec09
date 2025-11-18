<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Assignments endpoint handles CRUD plus nested comments (?type=comments).
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'items';

if ($type === 'comments') {
    handle_assignment_comments($pdo, $method);
    exit;
}

switch ($method) {
    case 'GET':
        require_login();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM assignments WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $assignment = $stmt->fetch();
            if (!$assignment) {
                error_response('Assignment not found.', 404);
            }
            json_response($assignment);
        } else {
            $stmt = $pdo->query('SELECT * FROM assignments ORDER BY due_date ASC');
            json_response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $user = require_admin();
        $data = json_input();
        $title = sanitize_string($data['title'] ?? '');
        $description = sanitize_string($data['description'] ?? '');
        $dueDate = sanitize_string($data['due_date'] ?? '');
        $attachment = sanitize_string($data['attachment_url'] ?? '');

        if (!$title || !$dueDate) {
            error_response('Title and due date are required.', 422);
        }

        $stmt = $pdo->prepare('INSERT INTO assignments (title, description, due_date, attachment_url, created_by) VALUES (:title, :description, :due_date, :attachment_url, :created_by)');
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'due_date' => $dueDate,
            'attachment_url' => $attachment,
            'created_by' => $user['id'],
        ]);

        json_response(['message' => 'Assignment created.'], 201);
        break;

    case 'PUT':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Assignment id is required.', 422);
        }

        $title = sanitize_string($data['title'] ?? '');
        $dueDate = sanitize_string($data['due_date'] ?? '');
        if (!$title || !$dueDate) {
            error_response('Title and due date are required.', 422);
        }

        $stmt = $pdo->prepare('UPDATE assignments SET title = :title, description = :description, due_date = :due_date, attachment_url = :attachment_url WHERE id = :id');
        $stmt->execute([
            'title' => $title,
            'description' => sanitize_string($data['description'] ?? ''),
            'due_date' => $dueDate,
            'attachment_url' => sanitize_string($data['attachment_url'] ?? ''),
            'id' => $id,
        ]);

        json_response(['message' => 'Assignment updated.']);
        break;

    case 'DELETE':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Assignment id is required.', 422);
        }
        $pdo->prepare('DELETE FROM assignments WHERE id = :id')->execute(['id' => $id]);
        json_response(['message' => 'Assignment deleted.']);
        break;

    default:
        error_response('Method not allowed.', 405);
}

function handle_assignment_comments(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'GET':
            require_login();
            $assignmentId = isset($_GET['assignment_id']) ? (int) $_GET['assignment_id'] : 0;
            if ($assignmentId <= 0) {
                error_response('assignment_id is required.', 422);
            }
            $stmt = $pdo->prepare('SELECT ac.id, ac.comment, ac.created_at, u.name AS author
                FROM assignment_comments ac
                JOIN users u ON ac.user_id = u.id
                WHERE ac.assignment_id = :assignment_id
                ORDER BY ac.created_at ASC');
            $stmt->execute(['assignment_id' => $assignmentId]);
            json_response($stmt->fetchAll());
            break;

        case 'POST':
            $user = require_login();
            $data = json_input();
            $assignmentId = (int) ($data['assignment_id'] ?? 0);
            $comment = sanitize_string($data['comment'] ?? '');
            if ($assignmentId <= 0 || !$comment) {
                error_response('assignment_id and comment are required.', 422);
            }
            $stmt = $pdo->prepare('INSERT INTO assignment_comments (assignment_id, user_id, comment) VALUES (:assignment_id, :user_id, :comment)');
            $stmt->execute([
                'assignment_id' => $assignmentId,
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
            $stmt = $pdo->prepare('SELECT user_id FROM assignment_comments WHERE id = :id');
            $stmt->execute(['id' => $commentId]);
            $comment = $stmt->fetch();
            if (!$comment) {
                error_response('Comment not found.', 404);
            }
            ensure_owner_or_admin((int) $comment['user_id'], $user);
            $pdo->prepare('DELETE FROM assignment_comments WHERE id = :id')->execute(['id' => $commentId]);
            json_response(['message' => 'Comment removed.']);
            break;

        default:
            error_response('Method not allowed.', 405);
    }
}
