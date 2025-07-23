<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Only allow admin users
requireUserType('admin');

$pdo = getDBConnection();

echo "<h2>Debug Information</h2>";

// Check all users
echo "<h3>All Users:</h3>";
$stmt = $pdo->prepare("SELECT id, username, full_name, user_type, is_active FROM users ORDER BY id");
$stmt->execute();
$users = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>User Type</th><th>Active</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>" . $user['id'] . "</td>";
    echo "<td>" . htmlspecialchars($user['username']) . "</td>";
    echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
    echo "<td>" . ($user['is_active'] ? 'Yes' : 'No') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test authentication
echo "<h3>Test Authentication:</h3>";
$test_users = [
    ['admin', 'admin123', 'admin'],
    ['teacher1', 'teacher123', 'teacher'],
    ['teacher2', 'teacher123', 'teacher'],
    ['student1', 'student123', 'student']
];

foreach ($test_users as $test) {
    $user = authenticateUser($test[0], $test[1], $test[2]);
    if ($user) {
        echo "<p style='color: green;'>✓ Login successful: {$test[0]} ({$test[2]})</p>";
    } else {
        echo "<p style='color: red;'>✗ Login failed: {$test[0]} ({$test[2]})</p>";
    }
}

// Check subjects
echo "<h3>Subjects:</h3>";
$stmt = $pdo->prepare("SELECT s.*, u.username as teacher_username FROM subjects s LEFT JOIN users u ON s.teacher_id = u.id");
$stmt->execute();
$subjects = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Code</th><th>Teacher</th></tr>";
foreach ($subjects as $subject) {
    echo "<tr>";
    echo "<td>" . $subject['id'] . "</td>";
    echo "<td>" . htmlspecialchars($subject['name']) . "</td>";
    echo "<td>" . htmlspecialchars($subject['code']) . "</td>";
    echo "<td>" . htmlspecialchars($subject['teacher_username'] ?? 'None') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check classes
echo "<h3>Classes:</h3>";
$stmt = $pdo->prepare("SELECT c.*, u.username as teacher_username FROM classes c LEFT JOIN users u ON c.teacher_id = u.id");
$stmt->execute();
$classes = $stmt->fetchAll();

echo "<table border='1' style='border-collapse: collapse;'>";
echo "<tr><th>ID</th><th>Name</th><th>Grade</th><th>Year</th><th>Teacher</th></tr>";
foreach ($classes as $class) {
    echo "<tr>";
    echo "<td>" . $class['id'] . "</td>";
    echo "<td>" . htmlspecialchars($class['name']) . "</td>";
    echo "<td>" . htmlspecialchars($class['grade_level']) . "</td>";
    echo "<td>" . htmlspecialchars($class['academic_year']) . "</td>";
    echo "<td>" . htmlspecialchars($class['teacher_username'] ?? 'None') . "</td>";
    echo "</tr>";
}
echo "</table>";
?>
    <style>
        html {
            box-sizing: border-box;
        }
        *, *:before, *:after {
            box-sizing: inherit;
        }
        body * {
            margin: 0;
            padding: 0;
            border: 0;
        }
        body {
            background-color: #f8f9fa;
        }
        .main-content {
            padding: 20px;
        }
        @media (max-width: 991.98px) {
            .main-content {
                padding: 10px;
            }
        }
    </style> 