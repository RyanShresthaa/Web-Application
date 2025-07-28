<?php
session_start();
require_once 'includes/functions.php';

// Require authentication and admin permission
requireAuth();
if (!hasPermission('admin')) {
    die("Access denied. Admin privileges required.");
}

echo "<h2>Student Profile Fix Utility</h2>";

// Fix students without profiles
$fixedCount = fixStudentsWithoutProfiles();

if ($fixedCount > 0) {
    echo "<p style='color: green;'>Successfully created profiles for $fixedCount students.</p>";
} else {
    echo "<p style='color: blue;'>All students already have profiles. No fix needed.</p>";
}

// Show all students and their profile status
echo "<h3>Current Student Status</h3>";
$pdo = getDBConnection();
$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.full_name, u.email, 
           sp.roll_number, sp.admission_date,
           CASE WHEN sp.student_id IS NULL THEN 'Missing Profile' ELSE 'Profile OK' END as profile_status
    FROM users u 
    LEFT JOIN student_profiles sp ON u.id = sp.student_id 
    WHERE u.user_type = 'student' AND u.is_active = 1
    ORDER BY u.full_name
");
$stmt->execute();
$students = $stmt->fetchAll();

echo "<table border='1' cellpadding='10'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Roll Number</th><th>Profile Status</th></tr>";

foreach ($students as $student) {
    $statusColor = $student['profile_status'] === 'Profile OK' ? 'green' : 'red';
    echo "<tr>";
    echo "<td>" . $student['id'] . "</td>";
    echo "<td>" . htmlspecialchars($student['username']) . "</td>";
    echo "<td>" . htmlspecialchars($student['full_name']) . "</td>";
    echo "<td>" . htmlspecialchars($student['email']) . "</td>";
    echo "<td>" . ($student['roll_number'] ?: 'N/A') . "</td>";
    echo "<td style='color: $statusColor;'>" . $student['profile_status'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<br><a href='students.php'>Back to Students</a>";
?>
