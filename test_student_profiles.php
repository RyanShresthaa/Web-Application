<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('
        SELECT u.id, u.username, u.full_name, sp.roll_number
        FROM users u 
        LEFT JOIN student_profiles sp ON u.id = sp.student_id 
        WHERE u.user_type = "student" AND u.is_active = 1
        ORDER BY u.full_name
    ');
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "Students in database:\n";
    foreach ($students as $student) {
        $profile_status = $student['roll_number'] ? 'HAS PROFILE' : 'MISSING PROFILE';
        echo "ID: {$student['id']}, Username: {$student['username']}, Name: {$student['full_name']}, Status: $profile_status\n";
    }
    echo "Total students: " . count($students) . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
