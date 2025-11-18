<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Resources endpoint handles CRUD plus nested comments (?type=comments).
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'items';

if ($type === 'comments') {
    handle_comments($pdo, $method);
    exit;
}

switch ($method) {
    case 'GET':
        require_login();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM resources WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $resource = $stmt->fetch();
            if (!$resource) {
                error_response('Resource not found.', 404);
            }
            json_response($resource);
        } else {
            $stmt = $pdo->query('SELECT * FROM resources ORDER BY created_at DESC');
            json_response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $user = require_admin();
        $data = json_input();
        $title = sanitize_string($data['title'] ?? '');
        $description = sanitize_string($data['description'] ?? '');
        $link = sanitize_string($data['link'] ?? '');

        if (!$title) {
            error_response('Title is required.', 422);
        }

        $stmt = $pdo->prepare('INSERT INTO resources (title, description, link, created_by) VALUES (:title, :description, :link, :created_by)');
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'created_by' => $user['id'],
        ]);

        json_response(['message' => 'Resource created.'], 201);
        break;

    case 'PUT':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Resource id is required.', 422);
        }

        $title = sanitize_string($data['title'] ?? '');
        $description = sanitize_string($data['description'] ?? '');
        $link = sanitize_string($data['link'] ?? '');

        if (!$title) {
            error_response('Title is required.', 422);
        }

        $stmt = $pdo->prepare('UPDATE resources SET title = :title, description = :description, link = :link WHERE id = :id');
        $stmt->execute([
            'title' => $title,
            'description' => $description,
            'link' => $link,
            'id' => $id,
        ]);

        json_response(['message' => 'Resource updated.']);
        break;

    case 'DELETE':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Resource id is required.', 422);
        }

        $stmt = $pdo->prepare('DELETE FROM resources WHERE id = :id');
        $stmt->execute(['id' => $id]);
        json_response(['message' => 'Resource deleted.']);
        break;

    default:
        error_response('Method not allowed.', 405);
}

function handle_comments(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'GET':
            require_login();
            $resourceId = isset($_GET['resource_id']) ? (int) $_GET['resource_id'] : 0;
            if ($resourceId <= 0) {
                error_response('resource_id is required.', 422);
            }
            $stmt = $pdo->prepare('SELECT rc.id, rc.comment, rc.created_at, u.name AS author
                FROM resource_comments rc
                JOIN users u ON rc.user_id = u.id
                WHERE rc.resource_id = :resource_id
                ORDER BY rc.created_at ASC');
            $stmt->execute(['resource_id' => $resourceId]);
            json_response($stmt->fetchAll());
            break;

        case 'POST':
            $user = require_login();
            $data = json_input();
            $resourceId = (int) ($data['resource_id'] ?? 0);
            $comment = sanitize_string($data['comment'] ?? '');
            if ($resourceId <= 0 || !$comment) {
                error_response('resource_id and comment are required.', 422);
            }
            $stmt = $pdo->prepare('INSERT INTO resource_comments (resource_id, user_id, comment) VALUES (:resource_id, :user_id, :comment)');
            $stmt->execute([
                'resource_id' => $resourceId,
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
            $stmt = $pdo->prepare('SELECT user_id FROM resource_comments WHERE id = :id');
            $stmt->execute(['id' => $commentId]);
            $comment = $stmt->fetch();
            if (!$comment) {
                error_response('Comment not found.', 404);
            }
            ensure_owner_or_admin((int) $comment['user_id'], $user);
            $pdo->prepare('DELETE FROM resource_comments WHERE id = :id')->execute(['id' => $commentId]);
            json_response(['message' => 'Comment removed.']);
            break;

        default:
            error_response('Method not allowed.', 405);
    }
}
