<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in and has admin permissions
if (!isset($_SESSION['user_id']) || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$format = $_POST['format'] ?? 'json';
$type = $_POST['type'] ?? '';
$data = $_POST['data'] ?? '';

if (empty($type) || empty($data)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$pdo = getDBConnection();

try {
    $imported_data = [];
    
    if ($format === 'xml') {
        $imported_data = xmlToArray($data);
    } else {
        $imported_data = json_decode($data, true);
    }
    
    if (!$imported_data) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid data format']);
        exit();
    }
    
    $success_count = 0;
    $error_count = 0;
    $errors = [];
    
    switch ($type) {
        case 'students':
            foreach ($imported_data as $student) {
                try {
                    // Check if student already exists
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                    $stmt->execute([$student['username'], $student['email']]);
                    if ($stmt->fetch()) {
                        $error_count++;
                        $errors[] = "Student with username '{$student['username']}' or email '{$student['email']}' already exists";
                        continue;
                    }
                    
                    // Create user
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, full_name, email, user_type, is_active)
                        VALUES (?, ?, ?, ?, 'student', 1)
                    ");
                    $hashed_password = password_hash($student['password'] ?? 'student123', PASSWORD_DEFAULT);
                    $stmt->execute([
                        $student['username'],
                        $hashed_password,
                        $student['full_name'],
                        $student['email']
                    ]);
                    
                    $student_id = $pdo->lastInsertId();
                    
                    // Create student profile
                    $stmt = $pdo->prepare("
                        INSERT INTO student_profiles (student_id, roll_number, phone, date_of_birth)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $student_id,
                        $student['roll_number'] ?? null,
                        $student['phone'] ?? null,
                        $student['date_of_birth'] ?? null
                    ]);
                    
                    $success_count++;
                } catch (Exception $e) {
                    $error_count++;
                    $errors[] = "Error importing student '{$student['username']}': " . $e->getMessage();
                }
            }
            break;
            
        case 'classes':
            foreach ($imported_data as $class) {
                try {
                    // Check if class already exists
                    $stmt = $pdo->prepare("SELECT id FROM classes WHERE name = ? AND grade_level = ? AND academic_year = ?");
                    $stmt->execute([$class['name'], $class['grade_level'], $class['academic_year']]);
                    if ($stmt->fetch()) {
                        $error_count++;
                        $errors[] = "Class '{$class['name']}' already exists for grade {$class['grade_level']} year {$class['academic_year']}";
                        continue;
                    }
                    
                    // Create class
                    $stmt = $pdo->prepare("
                        INSERT INTO classes (name, grade_level, academic_year, is_active)
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $class['name'],
                        $class['grade_level'],
                        $class['academic_year']
                    ]);
                    
                    $success_count++;
                } catch (Exception $e) {
                    $error_count++;
                    $errors[] = "Error importing class '{$class['name']}': " . $e->getMessage();
                }
            }
            break;
            
        case 'subjects':
            foreach ($imported_data as $subject) {
                try {
                    // Check if subject already exists
                    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE code = ?");
                    $stmt->execute([$subject['code']]);
                    if ($stmt->fetch()) {
                        $error_count++;
                        $errors[] = "Subject with code '{$subject['code']}' already exists";
                        continue;
                    }
                    
                    // Create subject
                    $stmt = $pdo->prepare("
                        INSERT INTO subjects (name, code, description, is_active)
                        VALUES (?, ?, ?, 1)
                    ");
                    $stmt->execute([
                        $subject['name'],
                        $subject['code'],
                        $subject['description'] ?? null
                    ]);
                    
                    $success_count++;
                } catch (Exception $e) {
                    $error_count++;
                    $errors[] = "Error importing subject '{$subject['name']}': " . $e->getMessage();
                }
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data type']);
            exit();
    }
    
    // Return import results
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'imported' => $success_count,
        'errors' => $error_count,
        'error_details' => $errors
    ]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

// Function to convert XML to array
function xmlToArray($xml_string) {
    $xml = simplexml_load_string($xml_string);
    if ($xml === false) {
        return false;
    }
    
    $array = [];
    foreach ($xml->item as $item) {
        $row = [];
        foreach ($item->children() as $child) {
            $row[$child->getName()] = (string)$child;
        }
        $array[] = $row;
    }
    
    return $array;
}
?> 