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

$class_id = $_POST['class_id'] ?? '';
$subject_id = $_POST['subject_id'] ?? '';
$date = $_POST['date'] ?? '';

if (empty($class_id) || empty($subject_id) || empty($date)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required parameters']);
    exit();
}

$pdo = getDBConnection();

try {
    // Get students in the class
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, sp.roll_number
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.student_id
        JOIN student_enrollments se ON u.id = se.student_id
        WHERE se.class_id = ? AND u.user_type = 'student' AND u.is_active = 1
        ORDER BY sp.roll_number, u.full_name
    ");
    $stmt->execute([$class_id]);
    $students = $stmt->fetchAll();
    
    // Get existing attendance for this date
    $stmt = $pdo->prepare("
        SELECT student_id, status
        FROM attendance
        WHERE class_id = ? AND subject_id = ? AND date = ?
    ");
    $stmt->execute([$class_id, $subject_id, $date]);
    $existing_attendance = $stmt->fetchAll();
    
    // Create attendance lookup
    $attendance_lookup = [];
    foreach ($existing_attendance as $att) {
        $attendance_lookup[$att['student_id']] = $att['status'];
    }
    
    // Prepare response data
    $response = [
        'class_id' => $class_id,
        'subject_id' => $subject_id,
        'date' => $date,
        'students' => []
    ];
    
    foreach ($students as $student) {
        $response['students'][] = [
            'id' => $student['id'],
            'full_name' => $student['full_name'],
            'roll_number' => $student['roll_number'],
            'status' => $attendance_lookup[$student['id']] ?? 'present'
        ];
    }
    
    // Return results as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 