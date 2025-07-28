<?php
require_once 'config/database.php';

echo "=== Fixing User Types ===\n\n";

try {
    $pdo = getDBConnection();
    
    // Map usernames to correct user types based on naming patterns
    $userTypeMappings = [
        'admin' => 'admin',
        'teacher1' => 'teacher',
        'teacher2' => 'teacher', 
        'student1' => 'student',
        'student2' => 'student',
        'student3' => 'student',
        'teststudent' => 'student',
        'newstudent' => 'student',
        'teststudent7200' => 'student'
    ];
    
    // Fix known users based on their usernames
    foreach ($userTypeMappings as $username => $correctType) {
        $stmt = $pdo->prepare('UPDATE users SET user_type = ? WHERE username = ?');
        $result = $stmt->execute([$correctType, $username]);
        if ($result) {
            echo "✓ Updated user '$username' to type '$correctType'\n";
        }
    }
    
    // Fix any users with empty user_type - assume they are students if their username contains 'student'
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE user_type = '' OR user_type IS NULL");
    $stmt->execute();
    $emptyTypeUsers = $stmt->fetchAll();
    
    foreach ($emptyTypeUsers as $user) {
        $newType = 'student'; // Default to student
        if (strpos($user['username'], 'teacher') !== false) {
            $newType = 'teacher';
        } elseif (strpos($user['username'], 'admin') !== false) {
            $newType = 'admin';
        } elseif (strpos($user['username'], 'moderator') !== false) {
            $newType = 'teacher'; // Moderators are teachers
        }
        
        $stmt = $pdo->prepare('UPDATE users SET user_type = ? WHERE id = ?');
        $result = $stmt->execute([$newType, $user['id']]);
        if ($result) {
            echo "✓ Fixed user '{$user['username']}' (ID: {$user['id']}) to type '$newType'\n";
        }
    }
    
    // Show final state
    echo "\n=== Final User Types ===\n";
    $stmt = $pdo->prepare('SELECT username, user_type FROM users ORDER BY user_type, username');
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "- {$user['username']}: {$user['user_type']}\n";
    }
    
    // Count students
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE user_type = "student"');
    $stmt->execute();
    $studentCount = $stmt->fetchColumn();
    echo "\nTotal students: $studentCount\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Fix Complete ===\n";
?>
