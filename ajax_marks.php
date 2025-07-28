<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in and has permission
if (!isset($_SESSION['user_id']) || !hasPermission('teacher')) {
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

$student_id = $_POST['student_id'] ?? '';
$subject_id = $_POST['subject_id'] ?? '';
$class_id = $_POST['class_id'] ?? '';

if (empty($student_id) || empty($subject_id) || empty($class_id)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$pdo = getDBConnection();

try {
    // Get student and subject information
    $stmt = $pdo->prepare("
        SELECT u.full_name as student_name, s.name as subject_name
        FROM users u, subjects s
        WHERE u.id = ? AND s.id = ?
    ");
    $stmt->execute([$student_id, $subject_id]);
    $info = $stmt->fetch();
    
    if (!$info) {
        http_response_code(404);
        echo json_encode(['error' => 'Student or subject not found']);
        exit();
    }
    
    // Get existing marks for this student/subject/class combination
    $stmt = $pdo->prepare("
        SELECT exam_type, marks_obtained, total_marks, exam_date, remarks
        FROM marks
        WHERE student_id = ? AND subject_id = ? AND class_id = ?
        ORDER BY exam_date DESC
        LIMIT 5
    ");
    $stmt->execute([$student_id, $subject_id, $class_id]);
    $existing_marks = $stmt->fetchAll();
    
    // Prepare response data
    $response = [
        'student_id' => $student_id,
        'subject_id' => $subject_id,
        'class_id' => $class_id,
        'student_name' => $info['student_name'],
        'subject_name' => $info['subject_name'],
        'existing_marks' => $existing_marks
    ];
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 