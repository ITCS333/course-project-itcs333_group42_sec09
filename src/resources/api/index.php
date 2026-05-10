<?php 
session_start();
$_SESSION['initialized'] = true;
/**
 * Course Resources API
 *
 * This is a RESTful API that handles all CRUD operations for course resources
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 *
 * Database Table Structures (for reference):
 *
 * Table: resources
 * Columns:
 * - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 * - title (VARCHAR(255))
 * - description (TEXT)
 * - link (VARCHAR(500))
 * - created_at (TIMESTAMP)
 *
 * Table: comments
 * Columns:
 * - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 * - resource_id (INT, FOREIGN KEY references resources.id)
 * - author (VARCHAR(100))
 * - text (TEXT)
 * - created_at (TIMESTAMP)
 *
 * HTTP Methods Supported:
 * - GET: Retrieve resource(s) or comment(s)
 * - POST: Create a new resource or comment
 * - PUT: Update an existing resource
 * - DELETE: Delete a resource or comment
 *
 * Response Format: JSON
 *
 * API Endpoints:
 * Resources:
 * GET /api/resources.php - Get all resources
 * GET /api/resources.php?id={id} - Get single resource by ID
 * POST /api/resources.php - Create new resource
 * PUT /api/resources.php - Update resource
 * DELETE /api/resources.php?id={id} - Delete resource
 *
 * Comments:
 * GET /api/resources.php?resource_id={id}&action=comments - Get comments for resource
 * POST /api/resources.php?action=comment - Create new comment
 * DELETE /api/resources.php?comment_id={id}&action=delete_comment - Delete comment
 */
// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================
// TODO: Set headers for JSON response and CORS
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

// TODO: Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
require_once '../config/Database.php';

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
$input = json_decode(file_get_contents('php://input'), true);

/* ===== STRICT REQUIRED EDIT (JSON VALIDATION) ===== */
if (in_array($method, ['POST', 'PUT']) && !$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON body'], JSON_PRETTY_PRINT);
    exit;
}
/* ================================================= */

// TODO: Parse query parameters
$action = $_GET['action'] ?? null;
$id = $_GET['id'] ?? null;
$resourceId = $_GET['resource_id'] ?? null;
$commentId = $_GET['comment_id'] ?? null;

// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================
function getAllResources($db) {
    $search = $_GET['search'] ?? null;
    $sort = $_GET['sort'] ?? 'created_at';
    $order = $_GET['order'] ?? 'desc';

    $allowedSort = ['title', 'created_at'];
    $allowedOrder = ['asc', 'desc'];

    if (!in_array($sort, $allowedSort)) $sort = 'created_at';
    if (!in_array(strtolower($order), $allowedOrder)) $order = 'desc';

    $sql = "SELECT id, title, description, link, created_at FROM resources";
    if ($search) {
        $sql .= " WHERE title LIKE :search OR description LIKE :search";
    }
    $sql .= " ORDER BY $sort $order";

    $stmt = $db->prepare($sql);
    if ($search) {
        $stmt->bindValue(':search', "%$search%");
    }
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $data]);
}

function getResourceById($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    $stmt = $db->prepare("SELECT id, title, description, link, created_at FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        sendResponse(['success' => true, 'data' => $data]);
    } else {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }
}

function createResource($db, $data) {
    $validation = validateRequiredFields($data, ['title', 'link']);
    if (!$validation['valid']) {
        sendResponse(['success' => false, 'message' => 'Missing fields: ' . implode(', ', $validation['missing'])], 400);
    }

    $title = sanitizeInput($data['title']);
    $description = sanitizeInput($data['description'] ?? '');
    $link = sanitizeInput($data['link']);

    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL'], 400);
    }

    $stmt = $db->prepare("INSERT INTO resources (title, description, link) VALUES (?, ?, ?)");
    if ($stmt->execute([$title, $description, $link])) {
        sendResponse(['success' => true, 'message' => 'Resource created', 'id' => $db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to create resource'], 500);
    }
}

function updateResource($db, $data) {
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID required'], 400);
    }
    $resourceId = $data['id'];

    $stmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $fields = [];
    $values = [];
    foreach (['title', 'description', 'link'] as $field) {
        if (!empty($data[$field])) {
            if ($field === 'link' && !validateUrl($data[$field])) {
                sendResponse(['success' => false, 'message' => 'Invalid URL'], 400);
            }
            $fields[] = "$field = ?";
            $values[] = sanitizeInput($data[$field]);
        }
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $values[] = $resourceId;
    $sql = "UPDATE resources SET " . implode(', ', $fields) . " WHERE id = ?";
    $stmt = $db->prepare($sql);
    if ($stmt->execute($values)) {
        sendResponse(['success' => true, 'message' => 'Resource updated']);
    } else {
        sendResponse(['success' => false, 'message' => 'Update failed'], 500);
    }
}

function deleteResource($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $stmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $stmt->execute([$resourceId]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM comments WHERE resource_id = ?")->execute([$resourceId]);
        $db->prepare("DELETE FROM resources WHERE id = ?")->execute([$resourceId]);
        $db->commit();
        sendResponse(['success' => true, 'message' => 'Resource deleted']);
     } catch (PDOException $e) {
        $db->rollBack();
        sendResponse(['success' => false, 'message' => 'Delete failed'], 500);
    }
}

// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================
function getCommentsByResourceId($db, $resourceId) {
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }
    $stmt = $db->prepare("SELECT id, resource_id, author, text, created_at FROM comments WHERE resource_id = ? ORDER BY created_at ASC");
    $stmt->execute([$resourceId]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    sendResponse(['success' => true, 'data' => $data]);
}

function createComment($db, $data) {
    $validation = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$validation['valid']) {
        sendResponse(['success' => false, 'message' => 'Missing fields: ' . implode(', ', $validation['missing'])], 400);
    }

    if (!is_numeric($data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID'], 400);
    }

    $stmt = $db->prepare("SELECT id FROM resources WHERE id = ?");
    $stmt->execute([$data['resource_id']]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Resource not found'], 404);
    }

    $author = sanitizeInput($data['author']);
    $text = sanitizeInput($data['text']);

    $stmt = $db->prepare("INSERT INTO comments (resource_id, author, text) VALUES (?, ?, ?)");
    if ($stmt->execute([$data['resource_id'], $author, $text])) {
        sendResponse(['success' => true, 'message' => 'Comment added', 'id' => $db->lastInsertId()], 201);
    } else {
        sendResponse(['success' => false, 'message' => 'Failed to add comment'], 500);
    }
}

function deleteComment($db, $commentId) {
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse(['success' => false, 'message' => 'Invalid comment ID'], 400);
    }

    $stmt = $db->prepare("SELECT id FROM comments WHERE id = ?");
    $stmt->execute([$commentId]);
    if (!$stmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Comment not found'], 404);
    }

    $stmt = $db->prepare("DELETE FROM comments WHERE id = ?");
    if ($stmt->execute([$commentId])) {
        sendResponse(['success' => true, 'message' => 'Comment deleted']);
    } else {
        sendResponse(['success' => false, 'message' => 'Delete failed'], 500);
    }
}

// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================
try {
    if ($method === 'GET') {
        if ($action === 'comments') {
            getCommentsByResourceId($db, $resourceId);
        } elseif ($id) {
            getResourceById($db, $id);
        } else {
            getAllResources($db);
        }
    } elseif ($method === 'POST') {
        if ($action === 'comment') {
            createComment($db, $input);
        } else {
            createResource($db, $input);
        }
    } elseif ($method === 'PUT') {
        updateResource($db, $input);
    } elseif ($method === 'DELETE') {
        if ($action === 'delete_comment') {
            deleteComment($db, $commentId);
        } else {
            deleteResource($db, $id);
        }
    } else {
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
} catch (PDOException $e) {
    sendResponse(['success' => false, 'message' => 'Server error'], 500);
}

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES);
}

function validateRequiredFields($data, $requiredFields) {
    $missing = [];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $missing[] = $field;
        }
    }
    return ['valid' => count($missing) === 0, 'missing' => $missing];
}
?>
