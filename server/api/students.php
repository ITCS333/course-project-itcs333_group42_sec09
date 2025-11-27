<?php
declare(strict_types=1);

require_once __DIR__ . '/../helpers.php';

// Handles CRUD for student accounts (admin only).
$pdo = get_pdo();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        require_admin();
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        if ($id) {
            $stmt = $pdo->prepare('SELECT id, name, email, student_id FROM users WHERE id = :id AND role = "student"');
            $stmt->execute(['id' => $id]);
            $student = $stmt->fetch();
            if (!$student) {
                error_response('Student not found.', 404);
            }
            json_response($student);
        } else {
            $stmt = $pdo->query('SELECT id, name, email, student_id FROM users WHERE role = "student" ORDER BY created_at DESC');
            json_response($stmt->fetchAll());
        }
        break;

    case 'POST':
        require_admin();
        $data = json_input();
        $name = sanitize_string($data['name'] ?? '');
        $studentId = sanitize_string($data['student_id'] ?? '');
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $password = $data['password'] ?? 'Password123!';

        if (!$name || !$studentId || !$email) {
            error_response('Name, student ID, and email are required.', 422);
        }

        $stmt = $pdo->prepare('INSERT INTO users (name, email, student_id, role, password_hash) VALUES (:name, :email, :student_id, "student", :password_hash)');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'student_id' => $studentId,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
        ]);

        json_response([
            'message' => 'Student added.',
            'student' => [
                'id' => (int) $pdo->lastInsertId(),
                'name' => $name,
                'email' => $email,
                'student_id' => $studentId,
            ],
        ], 201);
        break;

    case 'PUT':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Student id is required.', 422);
        }

        $name = sanitize_string($data['name'] ?? '');
        $studentId = sanitize_string($data['student_id'] ?? '');
        $email = filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL);

        if (!$name || !$studentId || !$email) {
            error_response('Name, student ID, and email are required.', 422);
        }

        $stmt = $pdo->prepare('UPDATE users SET name = :name, email = :email, student_id = :student_id WHERE id = :id AND role = "student"');
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'student_id' => $studentId,
            'id' => $id,
        ]);

        json_response(['message' => 'Student updated.']);
        break;

    case 'DELETE':
        require_admin();
        $data = json_input();
        $id = (int) ($data['id'] ?? 0);
        if ($id <= 0) {
            error_response('Student id is required.', 422);
        }

        $stmt = $pdo->prepare('DELETE FROM users WHERE id = :id AND role = "student"');
        $stmt->execute(['id' => $id]);
        json_response(['message' => 'Student removed.']);
        break;

    default:
        error_response('Method not allowed.', 405);
}
