<?php
require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== Testing Student Login Process ===\n\n";

// Test the exact same process as index.php
$username = 'student1';
$password = 'student123';
$user_type = 'student';

echo "1. Testing login for:\n";
echo "   Username: $username\n";
echo "   Password: $password\n";
echo "   User Type: $user_type\n\n";

// Step 1: Authentication
echo "2. Running authenticateUser()...\n";
$user = authenticateUser($username, $password, $user_type);

if ($user) {
    echo "✓ Authentication successful!\n";
    echo "   User ID: {$user['id']}\n";
    echo "   Username: {$user['username']}\n";
    echo "   Full Name: {$user['full_name']}\n";
    echo "   User Type: {$user['user_type']}\n";
    echo "   Is Active: {$user['is_active']}\n";
    
    // Test what would happen in session
    echo "\n3. Testing session variables that would be set:\n";
    echo "   \$_SESSION['user_id'] = {$user['id']}\n";
    echo "   \$_SESSION['username'] = {$user['username']}\n";
    echo "   \$_SESSION['user_type'] = {$user['user_type']}\n";
    echo "   \$_SESSION['full_name'] = {$user['full_name']}\n";
    
    echo "\n✅ Student login should work! The user exists and authentication passes.\n";
    
} else {
    echo "❌ Authentication failed!\n";
    
    // Debug why it failed
    echo "\n3. Debugging authentication failure...\n";
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND user_type = ? AND is_active = 1");
    $stmt->execute([$username, $user_type]);
    $dbUser = $stmt->fetch();
    
    if (!$dbUser) {
        echo "   ❌ User not found with username '$username', type '$user_type', and active status\n";
        
        // Check if user exists with different criteria
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $anyUser = $stmt->fetch();
        
        if ($anyUser) {
            echo "   ℹ️ User exists but:\n";
            echo "      - User Type: '{$anyUser['user_type']}' (expected: '$user_type')\n";
            echo "      - Is Active: {$anyUser['is_active']} (expected: 1)\n";
        } else {
            echo "   ❌ User '$username' does not exist at all\n";
        }
    } else {
        echo "   ✓ User found in database\n";
        echo "   🔍 Testing password verification...\n";
        
        if (password_verify($password, $dbUser['password'])) {
            echo "   ✓ Password is correct\n";
            echo "   ❓ Unknown authentication issue\n";
        } else {
            echo "   ❌ Password verification failed\n";
            echo "   ℹ️ Stored password hash: {$dbUser['password']}\n";
        }
    }
}

echo "\n=== Test Complete ===\n";
?>
