<?php
require_once 'config/database.php';

try {
    $pdo = getDBConnection();
    
    echo "=== Checking Database Schema ===\n\n";
    
    // Check the users table structure
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "Users table structure:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']} (Default: {$column['Default']})\n";
    }
    
    // Check for ENUM constraints
    $stmt = $pdo->prepare("SHOW CREATE TABLE users");
    $stmt->execute();
    $result = $stmt->fetch();
    
    echo "\nTable creation SQL:\n";
    echo $result['Create Table'] . "\n\n";
    
    // Try to force update with explicit transaction
    echo "=== Force Updating User Types ===\n";
    
    $pdo->beginTransaction();
    
    try {
        // Update specific users with ENUM values
        $updates = [
            ['student1', 'student'],
            ['student2', 'student'], 
            ['student3', 'student'],
            ['teststudent', 'student'],
            ['newstudent', 'student'],
            ['teststudent7200', 'student'],
            ['teacher1', 'teacher'],
            ['teacher2', 'teacher'],
            ['Ryan', 'student']
        ];
        
        foreach ($updates as $update) {
            $stmt = $pdo->prepare("UPDATE users SET user_type = ? WHERE username = ?");
            $result = $stmt->execute($update);
            echo "Updated {$update[0]} to {$update[1]}: " . ($result ? 'Success' : 'Failed') . "\n";
        }
        
        $pdo->commit();
        echo "\nTransaction committed successfully!\n";
        
    } catch (Exception $e) {
        $pdo->rollback();
        echo "\nTransaction failed: " . $e->getMessage() . "\n";
    }
    
    // Check the current state
    echo "\n=== Current User Types ===\n";
    $stmt = $pdo->prepare("SELECT id, username, user_type FROM users ORDER BY id");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    foreach ($users as $user) {
        echo "ID: {$user['id']}, Username: {$user['username']}, Type: '{$user['user_type']}'\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
