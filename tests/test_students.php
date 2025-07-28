<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "Testing student creation and retrieval...\n";

// Get current student count
$pdo = getDBConnection();
$stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_type = "student"');
$stmt->execute();
$current_count = $stmt->fetchColumn();
echo "Current students in database: $current_count\n";

// Get all students using the function
$students = getAllStudents();
echo "Students retrieved by getAllStudents(): " . count($students) . "\n";

// Show details of each student
foreach ($students as $student) {
    echo "ID: {$student['id']}, Name: {$student['full_name']}, Username: {$student['username']}, Type: {$student['user_type']}, Active: {$student['is_active']}\n";
}

echo "\nTest completed.\n";
?> 