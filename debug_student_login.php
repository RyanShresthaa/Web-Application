<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== Student Login Debug ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Check all students in database
    echo "1. All students in database:\n";
    $stmt = $pdo->prepare('SELECT id, username, user_type, is_active FROM users WHERE user_type = "student"');
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    if (empty($students)) {
        echo "❌ NO STUDENTS FOUND! This is the problem.\n";
        
        // Check if users exist with different types
        echo "\n2. All users in database:\n";
        $stmt = $pdo->prepare('SELECT id, username, user_type, is_active FROM users ORDER BY id');
        $stmt->execute();
        $allUsers = $stmt->fetchAll();
        
        foreach ($allUsers as $user) {
            echo "- ID: {$user['id']}, Username: {$user['username']}, Type: '{$user['user_type']}', Active: {$user['is_active']}\n";
        }
        
    } else {
        foreach ($students as $student) {
            echo "✓ ID: {$student['id']}, Username: {$student['username']}, Type: {$student['user_type']}, Active: {$student['is_active']}\n";
        }
        
        // Test authentication for first student
        echo "\n3. Testing authentication for student1:\n";
        $testUser = authenticateUser('student1', 'student123', 'student');
        if ($testUser) {
            echo "✓ Authentication successful for student1\n";
            echo "  User ID: {$testUser['id']}\n";
            echo "  Full Name: {$testUser['full_name']}\n";
        } else {
            echo "❌ Authentication failed for student1\n";
            
            // Check if user exists but with wrong password or type
            $stmt = $pdo->prepare('SELECT * FROM users WHERE username = "student1"');
            $stmt->execute();
            $user = $stmt->fetch();
            
            if ($user) {
                echo "  User exists but login failed:\n";
                echo "  - User Type: '{$user['user_type']}'\n";
                echo "  - Is Active: {$user['is_active']}\n";
                echo "  - Password Check: " . (password_verify('student123', $user['password']) ? 'PASS' : 'FAIL') . "\n";
            } else {
                echo "  User 'student1' does not exist in database!\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Debug Complete ===\n";
?>
