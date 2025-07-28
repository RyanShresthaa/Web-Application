<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "=== Debugging Student Issues ===\n\n";
    
    // Check what's actually in the users table
    echo "1. All users in the database:\n";
    $stmt = $pdo->prepare('SELECT id, username, full_name, user_type, is_active FROM users ORDER BY id');
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "- ID: {$user['id']}, Username: {$user['username']}, Name: {$user['full_name']}, Type: '{$user['user_type']}', Active: {$user['is_active']}\n";
    }
    
    echo "\n2. Students specifically:\n";
    $stmt = $pdo->prepare('SELECT id, username, full_name, user_type, is_active FROM users WHERE user_type = "student"');
    $stmt->execute();
    $students_direct = $stmt->fetchAll();
    
    if (empty($students_direct)) {
        echo "No students found with direct query!\n";
        
        // Let's check if there are any students with different user_type values
        $stmt = $pdo->prepare('SELECT id, username, full_name, user_type, is_active FROM users WHERE user_type LIKE "%student%"');
        $stmt->execute();
        $similar = $stmt->fetchAll();
        
        if (!empty($similar)) {
            echo "Found similar user_types:\n";
            foreach ($similar as $user) {
                echo "- ID: {$user['id']}, Type: '{$user['user_type']}'\n";
            }
        }
        
    } else {
        foreach ($students_direct as $student) {
            echo "- ID: {$student['id']}, Username: {$student['username']}, Active: {$student['is_active']}\n";
        }
    }
    
    echo "\n3. Student profiles:\n";
    $stmt = $pdo->prepare('SELECT * FROM student_profiles ORDER BY id');
    $stmt->execute();
    $profiles = $stmt->fetchAll();
    
    foreach ($profiles as $profile) {
        echo "- Profile ID: {$profile['id']}, Student ID: {$profile['student_id']}, Roll: {$profile['roll_number']}\n";
    }
    
    echo "\n4. Testing the exact getAllStudents() query:\n";
    $stmt = $pdo->prepare("
        SELECT u.*, sp.roll_number, sp.date_of_birth, sp.gender, sp.phone, sp.address
        FROM users u 
        LEFT JOIN student_profiles sp ON u.id = sp.student_id 
        WHERE u.user_type = 'student' AND u.is_active = 1
        ORDER BY u.full_name
    ");
    $stmt->execute();
    $result = $stmt->fetchAll();
    
    echo "Query returned " . count($result) . " rows:\n";
    foreach ($result as $row) {
        echo "- ID: {$row['id']}, Name: {$row['full_name']}, Roll: " . ($row['roll_number'] ?: 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
