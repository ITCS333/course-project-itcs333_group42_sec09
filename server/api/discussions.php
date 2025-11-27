<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Discussion board endpoint: topics + nested comments (?type=comments).
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];
$type = $_GET['type'] ?? 'topics';

if ($type === 'comments') {
    handle_topic_comments($pdo, $method);
    exit;
}

switch ($method) {
    case 'GET':
        require_login();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT dt.*, u.name AS author
                FROM discussion_topics dt
                JOIN users u ON dt.user_id = u.id
                WHERE dt.id = :id');
            $stmt->execute(['id' => $id]);
            $topic = $stmt->fetch();
            if (!$topic) {
                error_response('Topic not found.', 404);
            }
            json_response($topic);
        } else {
            $stmt = $pdo->query('SELECT dt.id, dt.subject, dt.body, dt.created_at, u.name AS author
                FROM discussion_topics dt
                JOIN users u ON dt.user_id = u.id
                ORDER BY dt.created_at DESC');
            json_response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $user = require_login();
        $data = json_input();
        $subject = sanitize_string($data['subject'] ?? '');
        $body = sanitize_string($data['body'] ?? '');
        if (!$subject || !$body) {
            error_response('Subject and body are required.', 422);
        }
        $stmt = $pdo->prepare('INSERT INTO discussion_topics (subject, body, user_id) VALUES (:subject, :body, :user_id)');
        $stmt->execute([
            'subject' => $subject,
            'body' => $body,
            'user_id' => $user['id'],
        ]);
        json_response(['message' => 'Topic created.'], 201);
        break;

    case 'PUT':
        $user = require_login();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Topic id is required.', 422);
        }
        $stmt = $pdo->prepare('SELECT user_id FROM discussion_topics WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $topic = $stmt->fetch();
        if (!$topic) {
            error_response('Topic not found.', 404);
        }
        ensure_owner_or_admin((int) $topic['user_id'], $user);

        $subject = sanitize_string($data['subject'] ?? '');
        $body = sanitize_string($data['body'] ?? '');
        if (!$subject || !$body) {
            error_response('Subject and body are required.', 422);
        }

        $stmt = $pdo->prepare('UPDATE discussion_topics SET subject = :subject, body = :body WHERE id = :id');
        $stmt->execute([
            'subject' => $subject,
            'body' => $body,
            'id' => $id,
        ]);
        json_response(['message' => 'Topic updated.']);
        break;

    case 'DELETE':
        $user = require_login();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Topic id is required.', 422);
        }
        $stmt = $pdo->prepare('SELECT user_id FROM discussion_topics WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $topic = $stmt->fetch();
        if (!$topic) {
            error_response('Topic not found.', 404);
        }
        ensure_owner_or_admin((int) $topic['user_id'], $user);
        $pdo->prepare('DELETE FROM discussion_topics WHERE id = :id')->execute(['id' => $id]);
        json_response(['message' => 'Topic deleted.']);
        break;

    default:
        error_response('Method not allowed.', 405);
}

function handle_topic_comments(PDO $pdo, string $method): void
{
    switch ($method) {
        case 'GET':
            require_login();
            $topicId = isset($_GET['topic_id']) ? (int) $_GET['topic_id'] : 0;
            if ($topicId <= 0) {
                error_response('topic_id is required.', 422);
            }
            $stmt = $pdo->prepare('SELECT dc.id, dc.comment, dc.created_at, u.name AS author
                FROM discussion_comments dc
                JOIN users u ON dc.user_id = u.id
                WHERE dc.topic_id = :topic_id
                ORDER BY dc.created_at ASC');
            $stmt->execute(['topic_id' => $topicId]);
            json_response($stmt->fetchAll());
            break;

        case 'POST':
            $user = require_login();
            $data = json_input();
            $topicId = (int) ($data['topic_id'] ?? 0);
            $comment = sanitize_string($data['comment'] ?? '');
            if ($topicId <= 0 || !$comment) {
                error_response('topic_id and comment are required.', 422);
            }
            $stmt = $pdo->prepare('INSERT INTO discussion_comments (topic_id, user_id, comment) VALUES (:topic_id, :user_id, :comment)');
            $stmt->execute([
                'topic_id' => $topicId,
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
            $stmt = $pdo->prepare('SELECT user_id FROM discussion_comments WHERE id = :id');
            $stmt->execute(['id' => $commentId]);
            $comment = $stmt->fetch();
            if (!$comment) {
                error_response('Comment not found.', 404);
            }
            ensure_owner_or_admin((int) $comment['user_id'], $user);
            $pdo->prepare('DELETE FROM discussion_comments WHERE id = :id')->execute(['id' => $commentId]);
            json_response(['message' => 'Comment removed.']);
            break;

        default:
            error_response('Method not allowed.', 405);
    }
}
