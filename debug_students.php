<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "Debugging getAllStudents function...\n";

// Direct database query
$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_type = 'student'");
$stmt->execute();
$direct_students = $stmt->fetchAll();
echo "Direct query - Students found: " . count($direct_students) . "\n";

foreach ($direct_students as $student) {
    echo "ID: {$student['id']}, Name: {$student['full_name']}, Type: {$student['user_type']}, Active: {$student['is_active']}\n";
}

echo "\n---\n";

// Test getAllStudents function
$students = getAllStudents();
echo "getAllStudents function - Students found: " . count($students) . "\n";

foreach ($students as $student) {
    echo "ID: {$student['id']}, Name: {$student['full_name']}, Type: {$student['user_type']}, Active: {$student['is_active']}\n";
}

echo "\nTest completed.\n";
?> 