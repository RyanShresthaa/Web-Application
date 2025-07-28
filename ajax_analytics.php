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

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';

$pdo = getDBConnection();

try {
    switch ($type) {
        case 'grade_distribution':
            if (hasPermission('admin')) {
                $data = getGradeAnalytics();
            } elseif (hasPermission('teacher')) {
                $data = getGradeAnalytics($user_id);
            } else {
                $data = getStudentGradeAnalytics($user_id);
            }
            break;
            
        case 'attendance_trends':
            if (hasPermission('admin')) {
                $data = getAttendanceAnalytics();
            } elseif (hasPermission('teacher')) {
                $data = getAttendanceAnalytics($user_id);
            } else {
                $data = getStudentAttendanceAnalytics($user_id);
            }
            break;
            
        case 'performance':
            if (hasPermission('admin')) {
                $data = getPerformanceAnalytics();
            } elseif (hasPermission('teacher')) {
                $data = getPerformanceAnalytics($user_id);
            } else {
                $data = getStudentPerformanceAnalytics($user_id);
            }
            break;
            
        case 'subject_stats':
            if (hasPermission('admin')) {
                $data = getSubjectStatistics();
            } elseif (hasPermission('teacher')) {
                $data = getSubjectStatistics($user_id);
            } else {
                $data = getStudentSubjectStatistics($user_id);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid analytics type']);
            exit();
    }
    
    // Return data as JSON
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'data' => $data]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

// Helper functions (same as in analytics.php)
function getGradeAnalytics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            CASE 
                WHEN percentage >= 90 THEN 'A+'
                WHEN percentage >= 80 THEN 'A'
                WHEN percentage >= 70 THEN 'B'
                WHEN percentage >= 60 THEN 'C'
                WHEN percentage >= 50 THEN 'D'
                ELSE 'F'
            END as grade,
            COUNT(*) as count
        FROM (
            SELECT (marks_obtained / total_marks * 100) as percentage
            FROM marks m
            JOIN subjects s ON m.subject_id = s.id
            WHERE 1=1
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= ") as grade_calc
        GROUP BY grade
        ORDER BY 
            CASE grade
                WHEN 'A+' THEN 1
                WHEN 'A' THEN 2
                WHEN 'B' THEN 3
                WHEN 'C' THEN 4
                WHEN 'D' THEN 5
                WHEN 'F' THEN 6
            END";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getAttendanceAnalytics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            status,
            COUNT(*) as count,
            DATE_FORMAT(date, '%Y-%m') as month
        FROM attendance a
        JOIN subjects s ON a.subject_id = s.id
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= " GROUP BY status, month ORDER BY month, status";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getPerformanceAnalytics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams,
            MAX(m.exam_date) as last_exam
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        WHERE m.exam_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= " GROUP BY s.id, s.name ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getSubjectStatistics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            COUNT(DISTINCT m.student_id) as students,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams
        FROM subjects s
        LEFT JOIN marks m ON s.id = m.subject_id
        WHERE 1=1
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= " GROUP BY s.id, s.name ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentGradeAnalytics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            CASE 
                WHEN percentage >= 90 THEN 'A+'
                WHEN percentage >= 80 THEN 'A'
                WHEN percentage >= 70 THEN 'B'
                WHEN percentage >= 60 THEN 'C'
                WHEN percentage >= 50 THEN 'D'
                ELSE 'F'
            END as grade,
            COUNT(*) as count
        FROM (
            SELECT (marks_obtained / total_marks * 100) as percentage
            FROM marks
            WHERE student_id = :student_id
        ) as grade_calc
        GROUP BY grade
        ORDER BY 
            CASE grade
                WHEN 'A+' THEN 1
                WHEN 'A' THEN 2
                WHEN 'B' THEN 3
                WHEN 'C' THEN 4
                WHEN 'D' THEN 5
                WHEN 'F' THEN 6
            END";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentAttendanceAnalytics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            status,
            COUNT(*) as count,
            DATE_FORMAT(date, '%Y-%m') as month
        FROM attendance
        WHERE student_id = :student_id 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY status, month 
        ORDER BY month, status";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentPerformanceAnalytics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams,
            MAX(m.exam_date) as last_exam
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        WHERE m.student_id = :student_id
        AND m.exam_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY s.id, s.name 
        ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentSubjectStatistics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams,
            MIN(m.marks_obtained / m.total_marks * 100) as min_percentage,
            MAX(m.marks_obtained / m.total_marks * 100) as max_percentage
        FROM subjects s
        JOIN marks m ON s.id = m.subject_id
        WHERE m.student_id = :student_id
        GROUP BY s.id, s.name 
        ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}
?> 