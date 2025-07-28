<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== Final Test ===\n\n";

try {
    // Test getAllStudents function
    $students = getAllStudents();
    
    echo "getAllStudents() returned " . count($students) . " students:\n";
    
    foreach ($students as $student) {
        echo "✓ ID: {$student['id']}, Name: {$student['full_name']}, Roll: " . ($student['roll_number'] ?: 'NULL') . "\n";
    }
    
    if (count($students) > 0) {
        echo "\n✅ SUCCESS: Students are now showing up correctly in the table!\n";
    } else {
        echo "\n❌ ISSUE: No students found.\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
