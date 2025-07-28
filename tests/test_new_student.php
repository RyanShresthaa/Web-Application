<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';

echo "Testing new student creation with fix...\n";

// Create a new test student
$user = new User();
$user->setUsername('newstudent');
$user->setPassword('password123');
$user->setFullName('New Test Student');
$user->setEmail('newtest@student.com');
$user->setUserType('student');
$user->setIsActive(true);

if ($user->save()) {
    echo "New student created successfully! ID: " . $user->getId() . "\n";
    
    // Check the created student
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'newstudent'");
    $stmt->execute();
    $student = $stmt->fetch();
    
    echo "Student details:\n";
    echo "ID: {$student['id']}\n";
    echo "Username: {$student['username']}\n";
    echo "Full Name: {$student['full_name']}\n";
    echo "User Type: {$student['user_type']}\n";
    echo "Is Active: {$student['is_active']}\n";
    
    // Now test getAllStudents
    $students = getAllStudents();
    echo "\nStudents found by getAllStudents(): " . count($students) . "\n";
    
    foreach ($students as $student) {
        echo "ID: {$student['id']}, Name: {$student['full_name']}, Type: {$student['user_type']}, Active: {$student['is_active']}\n";
    }
} else {
    echo "Failed to create student.\n";
}

echo "\nTest completed.\n";
?> 