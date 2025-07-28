<?php
require_once 'config/database.php';

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'teststudent'");
$stmt->execute();
$student = $stmt->fetch();

if ($student) {
    echo "Student found:\n";
    echo "ID: {$student['id']}\n";
    echo "Username: {$student['username']}\n";
    echo "Full Name: {$student['full_name']}\n";
    echo "User Type: {$student['user_type']}\n";
    echo "Is Active: {$student['is_active']}\n";
    echo "Email: {$student['email']}\n";
} else {
    echo "Student not found.\n";
}
?> 