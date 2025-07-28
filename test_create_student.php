<?php
require_once 'config/database.php';
require_once 'includes/User.php';
require_once 'includes/functions.php';

echo "=== Student Creation Test ===\n\n";

try {
    // Check initial state
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_type = "student"');
    $stmt->execute();
    $initialCount = $stmt->fetchColumn();
    echo "Initial student count: $initialCount\n";

    // Create a test student using the User class
    $user = new User();
    $user->setUsername('teststudent' . rand(1000, 9999));
    $user->setPassword('password123');
    $user->setFullName('Test Student ' . rand(100, 999));
    $user->setEmail('test' . rand(1000, 9999) . '@student.com');
    $user->setUserType('student');
    $user->setIsActive(true);

    echo "Creating student with username: " . $user->getUsername() . "\n";
    echo "Student full name: " . $user->getFullName() . "\n";
    
    // Save the student
    $result = $user->save();
    
    if ($result) {
        echo "✓ Student created successfully with ID: " . $user->getId() . "\n";
        
        // Check if profile was created automatically
        $stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE student_id = ?');
        $stmt->execute([$user->getId()]);
        $profile = $stmt->fetch();
        
        if ($profile) {
            echo "✓ Student profile created automatically\n";
            echo "  Roll number: " . $profile['roll_number'] . "\n";
            echo "  Admission date: " . $profile['admission_date'] . "\n";
        } else {
            echo "✗ Student profile NOT created - this is the issue!\n";
        }
        
        // Test the getAllStudents function
        $students = getAllStudents();
        $ourStudent = null;
        foreach ($students as $student) {
            if ($student['id'] == $user->getId()) {
                $ourStudent = $student;
                break;
            }
        }
        
        if ($ourStudent) {
            echo "✓ Student appears in getAllStudents() result\n";
            echo "  Roll number in result: " . ($ourStudent['roll_number'] ?: 'NULL') . "\n";
        } else {
            echo "✗ Student does NOT appear in getAllStudents() result - this is the issue!\n";
        }
        
    } else {
        echo "✗ Failed to create student\n";
    }

    // Final count
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_type = "student"');
    $stmt->execute();
    $finalCount = $stmt->fetchColumn();
    echo "\nFinal student count: $finalCount\n";
    
    // List all students
    echo "\nAll students in system:\n";
    $students = getAllStudents();
    foreach ($students as $student) {
        echo "- ID: {$student['id']}, Name: {$student['full_name']}, Roll: " . ($student['roll_number'] ?: 'NULL') . "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== Test Complete ===\n";
?>
