<?php
session_start();

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Include database connection
require_once 'config/database.php';
require_once 'includes/functions.php';

$error = '';
$message = $_GET['message'] ?? '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $user_type = $_POST['user_type'];
    
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $user = authenticateUser($username, $password, $user_type);
        
        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['user_type'] = $user['user_type'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Log login activity
            logLoginActivity($user['id'], $user_type);
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = 'Invalid credentials or user type.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Multi-Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
        
        html {
            box-sizing: border-box;
        }
        
        *, *:before, *:after {
            box-sizing: inherit;
        }
        
        body {
            margin: 0;
            padding: 0;
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(-45deg, #667eea, #764ba2, #f093fb, #f5576c);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow-x: hidden;
        }
        
        /* Animated background */
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        /* Floating particles effect */
        body::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="20" cy="20" r="2" fill="rgba(255,255,255,0.1)"/><circle cx="80" cy="80" r="3" fill="rgba(255,255,255,0.05)"/><circle cx="40" cy="70" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="90" cy="10" r="2" fill="rgba(255,255,255,0.05)"/><circle cx="10" cy="90" r="1.5" fill="rgba(255,255,255,0.1)"/><circle cx="70" cy="30" r="2.5" fill="rgba(255,255,255,0.05)"/></svg>') repeat;
            animation: float 20s ease-in-out infinite;
            pointer-events: none;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-30px) rotate(120deg); }
            66% { transform: translateY(30px) rotate(240deg); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 
                0 25px 50px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.2);
            overflow: hidden;
            width: 100%;
            max-width: 450px;
            transform: translateY(0);
            animation: slideUp 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            position: relative;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(50px) scale(0.9);
            }
            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }
        
        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            color: white;
            padding: 40px 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .login-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: shine 3s ease-in-out infinite;
        }
        
        @keyframes shine {
            0% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
            50% { transform: translateX(100%) translateY(100%) rotate(45deg); }
            100% { transform: translateX(200%) translateY(200%) rotate(45deg); }
        }
        
        .login-header h2 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
            text-shadow: 0 2px 10px rgba(0,0,0,0.2);
            position: relative;
            z-index: 1;
        }
        
        .login-header p {
            margin: 10px 0 0 0;
            font-size: 1rem;
            opacity: 0.9;
            font-weight: 300;
            position: relative;
            z-index: 1;
        }
        
        .login-body {
            padding: 40px 35px;
            background: white;
        }
        
        .user-type-selector {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin-bottom: 25px;
        }
        
        .user-type-btn {
            padding: 18px 10px;
            border: 2px solid #e8ecf4;
            background: #fafbfc;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            text-align: center;
            font-size: 0.85rem;
            font-weight: 500;
            color: #64748b;
            position: relative;
            overflow: hidden;
        }
        
        .user-type-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(102, 126, 234, 0.1), transparent);
            transition: left 0.6s;
        }
        
        .user-type-btn:hover::before {
            left: 100%;
        }
        
        .user-type-btn:hover {
            border-color: #667eea;
            background: #f8faff;
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.15);
        }
        
        .user-type-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-color: #667eea;
            color: white;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .user-type-btn i {
            display: block;
            font-size: 1.5rem;
            margin-bottom: 8px;
            transition: transform 0.3s ease;
        }
        
        .user-type-btn:hover i,
        .user-type-btn.active i {
            transform: scale(1.1);
        }
        
        .role-indicator {
            text-align: center;
            margin-bottom: 25px;
            padding: 12px 20px;
            background: linear-gradient(135deg, #f8faff 0%, #f1f5ff 100%);
            border-radius: 12px;
            border-left: 4px solid #667eea;
        }
        
        .role-indicator small {
            color: #64748b;
            font-weight: 500;
        }
        
        .role-indicator strong {
            color: #667eea;
            font-weight: 600;
        }
        
        .form-group {
            margin-bottom: 25px;
            position: relative;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
        }
        
        .form-label i {
            color: #667eea;
            margin-right: 8px;
            width: 16px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px 20px;
            border: 2px solid #e8ecf4;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 400;
            background: #fafbfc;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            outline: none;
        }
        
        .form-control:focus {
            border-color: #667eea;
            background: white;
            box-shadow: 
                0 0 0 3px rgba(102, 126, 234, 0.1),
                0 1px 3px rgba(0, 0, 0, 0.1);
            transform: translateY(-1px);
        }
        
        .form-control::placeholder {
            color: #9ca3af;
        }
        
        .btn-login {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 12px;
            color: white;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }
        
        .btn-login::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            transition: left 0.6s;
        }
        
        .btn-login:hover::before {
            left: 100%;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            border: none;
            margin-bottom: 20px;
            font-weight: 500;
            animation: slideDown 0.5s ease;
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .alert-danger {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #dc2626;
            border-left: 4px solid #ef4444;
        }
        
        .alert-success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
            border-left: 4px solid #10b981;
        }
        
        .register-link {
            text-align: center;
            margin-top: 25px;
        }
        
        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .register-link a:hover {
            color: #5a67d8;
            transform: translateY(-1px);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 600px) {
            .login-container {
                margin: 20px;
                max-width: none;
            }
            
            .login-header {
                padding: 30px 20px;
            }
            
            .login-header h2 {
                font-size: 1.75rem;
            }
            
            .login-body {
                padding: 30px 25px;
            }
            
            .user-type-btn {
                padding: 15px 8px;
                font-size: 0.8rem;
            }
            
            .user-type-btn i {
                font-size: 1.3rem;
            }
        }
        
        @media (max-width: 400px) {
            .user-type-selector {
                grid-template-columns: 1fr;
                gap: 10px;
            }
            
            .user-type-btn {
                padding: 15px;
            }
        }
        
        /* Loading animation */
        .loading {
            opacity: 0.7;
            pointer-events: none;
        }
        
        .loading .btn-login {
            position: relative;
        }
        
        .loading .btn-login::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h2><i class="fas fa-users"></i> Multi-Login System</h2>
            <p class="mb-0">Choose your role and sign in</p>
        </div>
        
        <div class="login-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($message === 'logged_out'): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> You have been successfully logged out.
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="user-type-selector">
                    <div class="user-type-btn active" data-type="admin">
                        <i class="fas fa-user-shield"></i><br>
                        Admin
                    </div>
                    <div class="user-type-btn" data-type="teacher">
                        <i class="fas fa-chalkboard-teacher"></i><br>
                        Teacher
                    </div>
                    <div class="user-type-btn" data-type="student">
                        <i class="fas fa-user-graduate"></i><br>
                        Student
                    </div>
                </div>
                
                <div class="role-indicator">
                    <small>Selected Role: <strong id="selected-role">Admin</strong></small>
                </div>
                
                <input type="hidden" name="user_type" id="user_type" value="admin">
                
                <div class="form-group">
                    <label for="username" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input type="text" class="form-control" id="username" name="username" 
                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                           placeholder="Enter your username"
                           required>
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password"
                           required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </form>
            
            <div class="register-link">
                <a href="register.php">
                    <i class="fas fa-user-plus"></i> Create New Account
                </a>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // User type selector functionality
        document.querySelectorAll('.user-type-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.user-type-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                // Update hidden input
                document.getElementById('user_type').value = this.dataset.type;
                // Update role indicator
                const roleName = this.dataset.type.charAt(0).toUpperCase() + this.dataset.type.slice(1);
                document.getElementById('selected-role').textContent = roleName;
            });
        });

        // Client-side validation for login form
        document.querySelector('form').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const password = document.getElementById('password').value;
            if (!username || !password) {
                alert('Please fill in all fields.');
                e.preventDefault();
                return;
            }
            if (password.length < 6) {
                alert('Password must be at least 6 characters.');
                e.preventDefault();
            }
        });
    </script>
</body>
</html> 