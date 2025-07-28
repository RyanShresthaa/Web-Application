<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if it's a GET request
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit();
}

$format = $_GET['format'] ?? 'json';
$type = $_GET['type'] ?? '';
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

$pdo = getDBConnection();

try {
    $data = [];
    
    switch ($type) {
        case 'students':
            if (!hasPermission('teacher')) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                SELECT u.id, u.full_name, u.username, u.email, sp.roll_number, sp.phone, sp.date_of_birth
                FROM users u
                LEFT JOIN student_profiles sp ON u.id = sp.student_id
                WHERE u.user_type = 'student' AND u.is_active = 1
                ORDER BY sp.roll_number, u.full_name
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            break;
            
        case 'attendance':
            if ($user_type === 'student') {
                $stmt = $pdo->prepare("
                    SELECT a.date, a.status, s.name as subject_name, c.name as class_name
                    FROM attendance a
                    JOIN subjects s ON a.subject_id = s.id
                    JOIN classes c ON a.class_id = c.id
                    WHERE a.student_id = ?
                    ORDER BY a.date DESC
                ");
                $stmt->execute([$user_id]);
            } else {
                if (!hasPermission('teacher')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Insufficient permissions']);
                    exit();
                }
                
                $stmt = $pdo->prepare("
                    SELECT a.date, a.status, u.full_name as student_name, s.name as subject_name, c.name as class_name
                    FROM attendance a
                    JOIN users u ON a.student_id = u.id
                    JOIN subjects s ON a.subject_id = s.id
                    JOIN classes c ON a.class_id = c.id
                    WHERE s.teacher_id = ?
                    ORDER BY a.date DESC
                ");
                $stmt->execute([$user_id]);
            }
            $data = $stmt->fetchAll();
            break;
            
        case 'marks':
            if ($user_type === 'student') {
                $stmt = $pdo->prepare("
                    SELECT m.exam_type, m.marks_obtained, m.total_marks, m.exam_date, s.name as subject_name, c.name as class_name
                    FROM marks m
                    JOIN subjects s ON m.subject_id = s.id
                    JOIN classes c ON m.class_id = c.id
                    WHERE m.student_id = ?
                    ORDER BY m.exam_date DESC
                ");
                $stmt->execute([$user_id]);
            } else {
                if (!hasPermission('teacher')) {
                    http_response_code(403);
                    echo json_encode(['error' => 'Insufficient permissions']);
                    exit();
                }
                
                $stmt = $pdo->prepare("
                    SELECT m.exam_type, m.marks_obtained, m.total_marks, m.exam_date, u.full_name as student_name, s.name as subject_name, c.name as class_name
                    FROM marks m
                    JOIN users u ON m.student_id = u.id
                    JOIN subjects s ON m.subject_id = s.id
                    JOIN classes c ON m.class_id = c.id
                    WHERE s.teacher_id = ?
                    ORDER BY m.exam_date DESC
                ");
                $stmt->execute([$user_id]);
            }
            $data = $stmt->fetchAll();
            break;
            
        case 'classes':
            if (!hasPermission('teacher')) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                SELECT c.id, c.name, c.grade_level, c.academic_year, u.full_name as teacher_name
                FROM classes c
                LEFT JOIN users u ON c.teacher_id = u.id
                WHERE c.is_active = 1
                ORDER BY c.grade_level, c.name
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            break;
            
        case 'subjects':
            if (!hasPermission('teacher')) {
                http_response_code(403);
                echo json_encode(['error' => 'Insufficient permissions']);
                exit();
            }
            
            $stmt = $pdo->prepare("
                SELECT s.id, s.name, s.code, s.description, u.full_name as teacher_name
                FROM subjects s
                LEFT JOIN users u ON s.teacher_id = u.id
                WHERE s.is_active = 1
                ORDER BY s.name
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid data type']);
            exit();
    }
    
    // Return data in requested format
    if ($format === 'xml') {
        header('Content-Type: application/xml');
        echo arrayToXml($data, $type);
    } else {
        header('Content-Type: application/json');
        echo json_encode($data);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

// Function to convert array to XML
function arrayToXml($data, $rootElement) {
    $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
    $xml .= '<' . $rootElement . '>' . "\n";
    
    foreach ($data as $item) {
        $xml .= '  <item>' . "\n";
        foreach ($item as $key => $value) {
            $xml .= '    <' . $key . '>' . htmlspecialchars($value) . '</' . $key . '>' . "\n";
        }
        $xml .= '  </item>' . "\n";
    }
    
    $xml .= '</' . $rootElement . '>';
    return $xml;
}
?> 