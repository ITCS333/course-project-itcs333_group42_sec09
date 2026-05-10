<?php
/**
 * Student Management API
 * 
 * This is a RESTful API that handles all CRUD operations for student management.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structure (for reference):
 * Table: students
 * Columns:
 *   - id (INT, PRIMARY KEY, AUTO_INCREMENT)
 *   - student_id (VARCHAR(50), UNIQUE) - The student's university ID
 *   - name (VARCHAR(100))
 *   - email (VARCHAR(100), UNIQUE)
 *   - password (VARCHAR(255)) - Hashed password
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET: Retrieve student(s)
 *   - POST: Create a new student OR change password
 *   - PUT: Update an existing student
 *   - DELETE: Delete a student
 * 
 * Response Format: JSON
 */

session_start();
// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
// Allow cross-origin requests (CORS) if needed
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
// Allow specific headers (Content-Type, Authorization)
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// TODO: Include the database connection class
// Assume the Database class has a method getConnection() that returns a PDO instance
if (file_exists(__DIR__ . '/../../config/database.php')) {
    require_once __DIR__ . '/../../config/database.php';
}

// TODO: Get the PDO database connection
$database = new Database();
$db = $database->getConnection();

if (empty($_SESSION['logged_in']) || ($_SESSION['role'] ?? '') !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// TODO: Get the HTTP request method
// Use $_SERVER['REQUEST_METHOD']
$method = $_SERVER['REQUEST_METHOD'];

// TODO: Get the request body for POST and PUT requests
// Use file_get_contents('php://input') to get raw POST data
// Decode JSON data using json_decode()
$inputData = [];
if (in_array($method, ['POST', 'PUT', 'DELETE'], true)) {
    $raw = file_get_contents('php://input');
    $inputData = json_decode($raw, true) ?? [];
}

// TODO: Parse query parameters for filtering and searching
$queryParams = $_GET;

/**
 * Function: Get all students or search for specific students
 * Method: GET
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by name, student_id, or email
 *   - sort: Optional field to sort by (name, student_id, email)
 *   - order: Optional sort order (asc or desc)
 */
function getStudents($db) {
    $search = isset($_GET['search']) ? trim($_GET['search']) : null;
    $sort = isset($_GET['sort']) ? strtolower(trim($_GET['sort'])) : null;
    $order = isset($_GET['order']) ? strtolower(trim($_GET['order'])) : 'asc';

    $sql = "SELECT student_id, name, email, created_at FROM students";
    $params = [];

    if (!empty($search)) {
        $sql .= " WHERE name LIKE :term OR student_id LIKE :term OR email LIKE :term";
        $params[':term'] = '%' . $search . '%';
    }

    $allowedSortFields = ['name', 'student_id', 'email'];
    if ($sort && in_array($sort, $allowedSortFields, true)) {
        $order = $order === 'desc' ? 'DESC' : 'ASC';
        $sql .= " ORDER BY {$sort} {$order}";
    }

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    sendResponse(['success' => true, 'data' => $students]);
}


/**
 * Function: Get a single student by student_id
 * Method: GET
 * 
 * Query Parameters:
 *   - student_id: The student's university ID
 */
function getStudentById($db, $studentId) {
    $stmt = $db->prepare("SELECT student_id, name, email, created_at FROM students WHERE student_id = :student_id");
    $stmt->bindValue(':student_id', $studentId);
    $stmt->execute();
    $student = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($student) {
        sendResponse(['success' => true, 'data' => $student]);
    }

    sendResponse(['success' => false, 'message' => 'Student not found'], 404);
}


/**
 * Function: Create a new student
 * Method: POST
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (must be unique)
 *   - name: Student's full name
 *   - email: Student's email (must be unique)
 *   - password: Default password (will be hashed)
 */
function createStudent($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, name, email, and password are provided
    // If any field is missing, return error response with 400 status
    $required = ['student_id', 'name', 'email', 'password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(['success' => false, 'message' => "Missing field: {$field}"], 400);
        }
    }

    // TODO: Sanitize input data
    // Trim whitespace from all fields
    // Validate email format using filter_var()
    $studentId = sanitizeInput($data['student_id']);
    $name = sanitizeInput($data['name']);
    $email = sanitizeInput($data['email']);
    $password = $data['password'];

    if (!validateEmail($email)) {
        sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    // TODO: Check if student_id or email already exists
    // Prepare and execute a SELECT query to check for duplicates
    // If duplicate found, return error response with 409 status (Conflict)
    $checkStmt = $db->prepare("SELECT id FROM students WHERE student_id = :student_id OR email = :email LIMIT 1");
    $checkStmt->execute([':student_id' => $studentId, ':email' => $email]);
    if ($checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student ID or email already exists'], 409);
    }

    // TODO: Hash the password
    // Use password_hash() with PASSWORD_DEFAULT
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // TODO: Prepare INSERT query
    $insert = $db->prepare("
        INSERT INTO students (student_id, name, email, password, created_at)
        VALUES (:student_id, :name, :email, :password, NOW())
    ");

    // TODO: Bind parameters
    // Bind student_id, name, email, and hashed password
    $insert->bindValue(':student_id', $studentId);
    $insert->bindValue(':name', $name);
    $insert->bindValue(':email', $email);
    $insert->bindValue(':password', $hashedPassword);

    // TODO: Execute the query
    // TODO: Check if insert was successful
    // If yes, return success response with 201 status (Created)
    // If no, return error response with 500 status
    if ($insert->execute()) {
        sendResponse(['success' => true, 'message' => 'Student created successfully'], 201);
    }

    sendResponse(['success' => false, 'message' => 'Failed to create student'], 500);
}


/**
 * Function: Update an existing student
 * Method: PUT
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (to identify which student to update)
 *   - name: Updated student name (optional)
 *   - email: Updated student email (optional)
 */
function updateStudent($db, $data) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($data['student_id'])) {
        sendResponse(['success' => false, 'message' => 'student_id is required'], 400);
    }

    $studentId = sanitizeInput($data['student_id']);
    $name = isset($data['name']) ? sanitizeInput($data['name']) : null;
    $email = isset($data['email']) ? sanitizeInput($data['email']) : null;

    // TODO: Check if student exists
    // Prepare and execute a SELECT query to find the student
    // If not found, return error response with 404 status
    $existingStmt = $db->prepare("SELECT id, student_id, email FROM students WHERE student_id = :student_id LIMIT 1");
    $existingStmt->execute([':student_id' => $studentId]);
    $existing = $existingStmt->fetch(PDO::FETCH_ASSOC);

    if (!$existing) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }

    // TODO: Build UPDATE query dynamically based on provided fields
    // Only update fields that are provided in the request
    $fields = [];
    $params = [':student_id' => $studentId];

    if ($name !== null) {
        $fields[] = 'name = :name';
        $params[':name'] = $name;
    }

    if ($email !== null) {
        if (!validateEmail($email)) {
            sendResponse(['success' => false, 'message' => 'Invalid email format'], 400);
        }

        // TODO: If email is being updated, check if new email already exists
        // Prepare and execute a SELECT query
        // Exclude the current student from the check
        // If duplicate found, return error response with 409 status
        $emailCheck = $db->prepare("SELECT id FROM students WHERE email = :email AND student_id != :student_id");
        $emailCheck->execute([':email' => $email, ':student_id' => $studentId]);
        if ($emailCheck->fetch()) {
            sendResponse(['success' => false, 'message' => 'Email already in use'], 409);
        }

        $fields[] = 'email = :email';
        $params[':email'] = $email;
    }

    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update'], 400);
    }

    $fields[] = 'updated_at = NOW()';
    $sql = 'UPDATE students SET ' . implode(', ', $fields) . ' WHERE student_id = :student_id';
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    // TODO: Execute the query
    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($stmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Student updated successfully']);
    }

    sendResponse(['success' => false, 'message' => 'Failed to update student'], 500);
}


/**
 * Function: Delete a student
 * Method: DELETE
 * 
 * Query Parameters or JSON Body:
 *   - student_id: The student's university ID
 */
function deleteStudent($db, $studentId) {
    // TODO: Validate that student_id is provided
    // If not, return error response with 400 status
    if (empty($studentId)) {
        sendResponse(['success' => false, 'message' => 'student_id is required'], 400);
    }

    // TODO: Check if student exists
    // Prepare and execute a SELECT query
    // If not found, return error response with 404 status
    $checkStmt = $db->prepare("SELECT id FROM students WHERE student_id = :student_id");
    $checkStmt->execute([':student_id' => $studentId]);
    if (!$checkStmt->fetch()) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }

    // TODO: Prepare DELETE query
    $deleteStmt = $db->prepare("DELETE FROM students WHERE student_id = :student_id");

    // TODO: Bind the student_id parameter
    $deleteStmt->bindValue(':student_id', $studentId);

    // TODO: Execute the query
    // TODO: Check if delete was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($deleteStmt->execute()) {
        sendResponse(['success' => true, 'message' => 'Student deleted successfully']);
    }

    sendResponse(['success' => false, 'message' => 'Failed to delete student'], 500);
}


/**
 * Function: Change password
 * Method: POST with action=change_password
 * 
 * Required JSON Body:
 *   - student_id: The student's university ID (identifies whose password to change)
 *   - current_password: The student's current password
 *   - new_password: The new password to set
 */
function changePassword($db, $data) {
    // TODO: Validate required fields
    // Check if student_id, current_password, and new_password are provided
    // If any field is missing, return error response with 400 status
    $required = ['student_id', 'current_password', 'new_password'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            sendResponse(['success' => false, 'message' => "Missing field: {$field}"], 400);
        }
    }

    $studentId = sanitizeInput($data['student_id']);
    $currentPassword = $data['current_password'];
    $newPassword = $data['new_password'];

    // TODO: Validate new password strength
    // Check minimum length (at least 8 characters)
    // If validation fails, return error response with 400 status
    if (strlen($newPassword) < 8) {
        sendResponse(['success' => false, 'message' => 'New password must be at least 8 characters'], 400);
    }

    // TODO: Retrieve current password hash from database
    // Prepare and execute SELECT query to get password
    $stmt = $db->prepare("SELECT password FROM students WHERE student_id = :student_id");
    $stmt->execute([':student_id' => $studentId]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        sendResponse(['success' => false, 'message' => 'Student not found'], 404);
    }

    // TODO: Verify current password
    // Use password_verify() to check if current_password matches the hash
    // If verification fails, return error response with 401 status (Unauthorized)
    if (!password_verify($currentPassword, $result['password'])) {
        sendResponse(['success' => false, 'message' => 'Current password is incorrect'], 401);
    }

    // TODO: Hash the new password
    // Use password_hash() with PASSWORD_DEFAULT
    $newHashed = password_hash($newPassword, PASSWORD_DEFAULT);

    // TODO: Update password in database
    // Prepare UPDATE query
    $update = $db->prepare("UPDATE students SET password = :password, updated_at = NOW() WHERE student_id = :student_id");

    // TODO: Bind parameters and execute
    $update->bindValue(':password', $newHashed);
    $update->bindValue(':student_id', $studentId);
    $update->execute();

    // TODO: Check if update was successful
    // If yes, return success response
    // If no, return error response with 500 status
    if ($update->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Password changed successfully']);
    }

    sendResponse(['success' => false, 'message' => 'Failed to change password'], 500);
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on HTTP method
    if ($method === 'GET') {
        // TODO: Check if student_id is provided in query parameters
        // If yes, call getStudentById()
        // If no, call getStudents() to get all students (with optional search/sort)
        if (!empty($queryParams['student_id'])) {
            getStudentById($db, sanitizeInput($queryParams['student_id']));
        } else {
            getStudents($db);
        }
        
    } elseif ($method === 'POST') {
        // TODO: Check if this is a change password request
        // Look for action=change_password in query parameters
        // If yes, call changePassword()
        // If no, call createStudent()
        $action = isset($queryParams['action']) ? $queryParams['action'] : null;
        if ($action === 'change_password') {
            changePassword($db, $inputData);
        } else {
            createStudent($db, $inputData);
        }
        
    } elseif ($method === 'PUT') {
        // TODO: Call updateStudent()
        updateStudent($db, $inputData);
        
    } elseif ($method === 'DELETE') {
        // TODO: Get student_id from query parameter or request body
        // Call deleteStudent()
        $studentId = $queryParams['student_id'] ?? ($inputData['student_id'] ?? null);
        deleteStudent($db, sanitizeInput((string) $studentId));
        
    } else {
        // TODO: Return error for unsupported methods
        // Set HTTP status to 405 (Method Not Allowed)
        // Return JSON error message
        sendResponse(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    
} catch (PDOException $e) {
    // TODO: Handle database errors
    // Log the error message (optional)
    // Return generic error response with 500 status
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'Database error occurred'], 500);
    
} catch (Exception $e) {
    // TODO: Handle general errors
    // Return error response with 500 status
    error_log($e->getMessage());
    sendResponse(['success' => false, 'message' => 'An unexpected error occurred'], 500);
}


// ============================================================================
// HELPER FUNCTIONS (Optional but Recommended)
// ============================================================================

/**
 * Helper function to send JSON response
 * 
 * @param mixed $data - Data to send
 * @param int $statusCode - HTTP status code
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set HTTP response code
    http_response_code($statusCode);

    // TODO: Echo JSON encoded data
    echo json_encode($data);

    // TODO: Exit to prevent further execution
    exit;
}


/**
 * Helper function to validate email format
 * 
 * @param string $email - Email address to validate
 * @return bool - True if valid, false otherwise
 */
function validateEmail($email) {
    // TODO: Use filter_var with FILTER_VALIDATE_EMAIL
    // Return true if valid, false otherwise
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
}


/**
 * Helper function to sanitize input
 * 
 * @param string $data - Data to sanitize
 * @return string - Sanitized data
 */
function sanitizeInput($data) {
    // TODO: Trim whitespace
    // TODO: Strip HTML tags using strip_tags()
    // TODO: Convert special characters using htmlspecialchars()
    // Return sanitized data
    $data = trim($data);
    $data = strip_tags($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

?>
