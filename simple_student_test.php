<!DOCTYPE html>
<html>
<head>
    <title>Student Login Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .form-group { margin: 15px 0; }
        .btn { padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer; }
        .alert { padding: 15px; margin: 10px 0; border-radius: 4px; }
        .alert-success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .alert-danger { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
    </style>
</head>
<body>
    <h1>🧪 Student Login Test</h1>
    
    <?php
    session_start();
    require_once 'config/database.php';
    require_once 'includes/functions.php';
    
    $message = '';
    $error = '';
    
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        
        echo "<h3>🔍 Debug Information:</h3>";
        echo "<p><strong>Username:</strong> " . htmlspecialchars($username) . "</p>";
        echo "<p><strong>Password:</strong> " . htmlspecialchars($password) . "</p>";
        echo "<p><strong>User Type:</strong> student (hardcoded)</p>";
        
        // Try authentication
        $user = authenticateUser($username, $password, 'student');
        
        if ($user) {
            echo "<div class='alert alert-success'>";
            echo "<h4>✅ Login Successful!</h4>";
            echo "<p><strong>User ID:</strong> " . $user['id'] . "</p>";
            echo "<p><strong>Full Name:</strong> " . $user['full_name'] . "</p>";
            echo "<p><strong>User Type:</strong> " . $user['user_type'] . "</p>";
            echo "<p><a href='dashboard.php'>Go to Dashboard</a></p>";
            echo "</div>";
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            
        } else {
            echo "<div class='alert alert-danger'>";
            echo "<h4>❌ Login Failed!</h4>";
            echo "<p>Authentication returned false</p>";
            echo "</div>";
        }
    }
    ?>
    
    <h3>🎒 Try Student Login:</h3>
    <form method="POST">
        <div class="form-group">
            <label>Username:</label><br>
            <input type="text" name="username" value="student1" required style="width: 200px; padding: 8px;">
        </div>
        
        <div class="form-group">
            <label>Password:</label><br>
            <input type="password" name="password" value="student123" required style="width: 200px; padding: 8px;">
        </div>
        
        <div class="form-group">
            <button type="submit" class="btn">🔐 Login as Student</button>
        </div>
    </form>
    
    <hr>
    
    <h3>📋 Available Student Accounts:</h3>
    <?php
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('SELECT username, full_name FROM users WHERE user_type = "student" AND is_active = 1');
    $stmt->execute();
    $students = $stmt->fetchAll();
    
    echo "<ul>";
    foreach ($students as $student) {
        echo "<li><strong>" . $student['username'] . "</strong> - " . $student['full_name'] . " (password: student123)</li>";
    }
    echo "</ul>";
    ?>
    
    <p><a href="index.php">← Back to Main Login Page</a></p>
</body>
</html>
