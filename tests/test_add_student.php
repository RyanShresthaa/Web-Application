<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';

echo "Testing student creation...\n";

// Create a test student
$user = new User();
$user->setUsername('teststudent');
$user->setPassword('password123');
$user->setFullName('Test Student');
$user->setEmail('test@student.com');
$user->setUserType('student');
$user->setIsActive(true);

if ($user->save()) {
    echo "Student created successfully! ID: " . $user->getId() . "\n";
    
    // Now test getAllStudents
    $students = getAllStudents();
    echo "Students found: " . count($students) . "\n";
    
    foreach ($students as $student) {
        echo "ID: {$student['id']}, Name: {$student['full_name']}, Type: {$student['user_type']}, Active: {$student['is_active']}\n";
    }
} else {
    echo "Failed to create student.\n";
}

echo "\nTest completed.\n";
?> 