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

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$pdo = getDBConnection();

try {
    $count = 0;
    
    if ($user_type === 'student') {
        // Count recent attendance and marks updates for students
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM (
                SELECT created_at FROM attendance 
                WHERE student_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                UNION ALL
                SELECT created_at FROM marks 
                WHERE student_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as recent_activity
        ");
        $stmt->execute([$user_id, $user_id]);
        $result = $stmt->fetch();
        $count = $result['count'];
        
    } elseif ($user_type === 'teacher') {
        // Count recent activity for teachers
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM (
                SELECT created_at FROM attendance a
                JOIN subjects s ON a.subject_id = s.id
                WHERE s.teacher_id = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                UNION ALL
                SELECT created_at FROM marks m
                JOIN subjects s ON m.subject_id = s.id
                WHERE s.teacher_id = ? AND m.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
            ) as recent_activity
        ");
        $stmt->execute([$user_id, $user_id]);
        $result = $stmt->fetch();
        $count = $result['count'];
        
    } elseif ($user_type === 'admin') {
        // Count recent system activity for admins
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM (
                SELECT created_at FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                UNION ALL
                SELECT created_at FROM login_activity WHERE login_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)
            ) as recent_activity
        ");
        $stmt->execute();
        $result = $stmt->fetch();
        $count = $result['count'];
    }
    
    // Return count as JSON
    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}
?> 