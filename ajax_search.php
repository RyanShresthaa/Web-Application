<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
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

$search = trim($_POST['search'] ?? '');
$type = $_POST['type'] ?? '';

if (empty($search) || empty($type)) {
    echo json_encode([]);
    exit();
}

$pdo = getDBConnection();
$results = [];

try {
    switch ($type) {
        case 'student':
            $stmt = $pdo->prepare("
                SELECT id, full_name as name, username as details, roll_number
                FROM users u 
                LEFT JOIN student_profiles sp ON u.id = sp.student_id
                WHERE u.user_type = 'student' 
                AND (u.full_name LIKE ? OR u.username LIKE ? OR sp.roll_number LIKE ?)
                AND u.is_active = 1
                LIMIT 10
            ");
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $results = $stmt->fetchAll();
            break;
            
        case 'subject':
            $stmt = $pdo->prepare("
                SELECT id, name, code as details
                FROM subjects 
                WHERE (name LIKE ? OR code LIKE ?) AND is_active = 1
                LIMIT 10
            ");
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $results = $stmt->fetchAll();
            break;
            
        case 'class':
            $stmt = $pdo->prepare("
                SELECT id, name, CONCAT(grade_level, ' - ', academic_year) as details
                FROM classes 
                WHERE (name LIKE ? OR grade_level LIKE ? OR academic_year LIKE ?) AND is_active = 1
                LIMIT 10
            ");
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
            $results = $stmt->fetchAll();
            break;
            
        case 'teacher':
            $stmt = $pdo->prepare("
                SELECT id, full_name as name, username as details
                FROM users 
                WHERE user_type = 'teacher' 
                AND (full_name LIKE ? OR username LIKE ?) AND is_active = 1
                LIMIT 10
            ");
            $searchTerm = "%$search%";
            $stmt->execute([$searchTerm, $searchTerm]);
            $results = $stmt->fetchAll();
            break;
            
        default:
            echo json_encode([]);
            exit();
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($results);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 