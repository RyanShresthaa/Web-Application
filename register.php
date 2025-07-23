<?php
session_start();

require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/User.php';

// Check if user is already logged in (but allow admins to create students)
if (isset($_SESSION['user_id']) && !hasPermission('admin')) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $user_type = $_POST['user_type'];
    
    try {
        if (empty($username) || empty($password) || empty($confirm_password) || empty($full_name) || empty($email)) {
            throw new Exception('Please fill in all fields.');
        }
        if ($password !== $confirm_password) {
            throw new Exception('Passwords do not match.');
        }
        // Check for duplicate username/email
        $userObj = new User();
        if ($userObj->loadByUsername($username)) {
            throw new Exception('Username already exists.');
        }
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Email already exists.');
        }
        // Set user properties using mutators
        $user = new User();
        $user->setUsername($username);
        $user->setPassword($password);
        $user->setFullName($full_name);
        $user->setEmail($email);
        // Ensure user type is set correctly
        if ($user_type === 'moderator') {
            $user->setUserType('teacher');
        } else {
            $user->setUserType('student');
        }
        $user->setIsActive(true);
        if ($user->save()) {
            if (isset($_SESSION['user_id']) && hasPermission('admin')) {
                $success = 'Student added successfully!';
                // Redirect to students page after a short delay
                header("Refresh: 2; URL=students.php?success=Student added successfully!");
            } else {
                $success = 'Account created successfully! You can now login.';
            }
        } else {
            throw new Exception('Failed to create user.');
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($_SESSION['user_id']) && hasPermission('admin') ? 'Add New Student' : 'Register'; ?> - Multi-Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .register-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            width: 100%;
            max-width: 500px;
        }
        .register-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .register-body {
            padding: 40px;
        }
        .form-control {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
            transition: all 0.3s ease;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-register {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .user-type-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .user-type-btn {
            flex: 1;
            padding: 15px;
            border: 2px solid #e9ecef;
            background: white;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }
        .user-type-btn.active {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .alert {
            border-radius: 10px;
            border: none;
        }
        .password-strength {
            height: 5px;
            border-radius: 3px;
            margin-top: 5px;
            transition: all 0.3s ease;
        }
        .strength-weak { background: #dc3545; }
        .strength-medium { background: #ffc107; }
        .strength-strong { background: #28a745; }
        @media (max-width: 600px) {
            .register-body {
                padding: 20px;
            }
            .register-header {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <?php if (isset($_SESSION['user_id']) && hasPermission('admin')): ?>
    <!-- Admin Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); position: fixed; top: 0; width: 100%; z-index: 1000;">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> Student Management System
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="students.php">
                    <i class="fas fa-users"></i> Back to Students
                </a>
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </div>
        </div>
    </nav>
    <div style="height: 70px;"></div> <!-- Spacer for fixed navbar -->
    <?php endif; ?>
    
    <div class="register-container">
        <div class="register-header">
            <h2><i class="fas fa-user-plus"></i> <?php echo isset($_SESSION['user_id']) && hasPermission('admin') ? 'Add New Student' : 'Create Account'; ?></h2>
            <p class="mb-0"><?php echo isset($_SESSION['user_id']) && hasPermission('admin') ? 'Add a new student to the system' : 'Join our multi-login system'; ?></p>
        </div>
        
        <div class="register-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    <br><br>
                    <?php if (isset($_SESSION['user_id']) && hasPermission('admin')): ?>
                        <a href="students.php" class="btn btn-success btn-sm">
                            <i class="fas fa-users"></i> Back to Students
                        </a>
                    <?php else: ?>
                        <a href="index.php" class="btn btn-success btn-sm">
                            <i class="fas fa-sign-in-alt"></i> Go to Login
                        </a>
                    <?php endif; ?>
                </div>
            <?php else: ?>
            
            <form method="POST" action="" id="registerForm">
                <?php if (!isset($_SESSION['user_id']) || !hasPermission('admin')): ?>
                <div class="user-type-selector">
                    <div class="user-type-btn active" data-type="user">
                        <i class="fas fa-user"></i><br>
                        Regular User
                    </div>
                    <div class="user-type-btn" data-type="moderator">
                        <i class="fas fa-user-tie"></i><br>
                        Moderator
                    </div>
                </div>
                
                <input type="hidden" name="user_type" id="user_type" value="user">
                <?php else: ?>
                <input type="hidden" name="user_type" id="user_type" value="student">
                <?php endif; ?>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="username" class="form-label">
                                <i class="fas fa-user"></i> Username
                            </label>
                            <input type="text" class="form-control" id="username" name="username" 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                <i class="fas fa-id-card"></i> Full Name
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name" 
                                   value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" 
                                   required>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="email" class="form-label">
                        <i class="fas fa-envelope"></i> Email Address
                    </label>
                    <input type="email" class="form-control" id="email" name="email" 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                           required>
                </div>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="password" class="form-label">
                                <i class="fas fa-lock"></i> Password
                            </label>
                            <input type="password" class="form-control" id="password" name="password" required>
                            <div class="password-strength" id="passwordStrength"></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">
                                <i class="fas fa-lock"></i> Confirm Password
                            </label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                    </div>
                </div>
                
                <button type="submit" class="btn btn-primary btn-register w-100">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>
            
            <div class="text-center mt-3">
                <?php if (isset($_SESSION['user_id']) && hasPermission('admin')): ?>
                    <a href="students.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Back to Students
                    </a>
                <?php else: ?>
                    <a href="index.php" class="text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Back to Login
                    </a>
                <?php endif; ?>
            </div>
            
            <?php endif; ?>
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
            });
        });

        // Password strength checker
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('passwordStrength');
            let strength = 0;
            if (password.length >= 6) strength++;
            if (password.match(/[a-z]/)) strength++;
            if (password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;
            strengthBar.className = 'password-strength';
            if (strength <= 2) {
                strengthBar.classList.add('strength-weak');
            } else if (strength <= 3) {
                strengthBar.classList.add('strength-medium');
            } else {
                strengthBar.classList.add('strength-strong');
            }
        });

        // Password confirmation checker
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
            } else {
                this.setCustomValidity('');
            }
        });

        // Additional client-side validation for registration form
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            const username = document.getElementById('username').value.trim();
            const fullName = document.getElementById('full_name').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!username || !fullName || !email || !password || !confirmPassword) {
                alert('Please fill in all fields.');
                e.preventDefault();
                return;
            }
            if (!emailPattern.test(email)) {
                alert('Please enter a valid email address.');
                e.preventDefault();
                return;
            }
            if (password.length < 6) {
                alert('Password must be at least 6 characters.');
                e.preventDefault();
                return;
            }
            if (password !== confirmPassword) {
                alert('Passwords do not match.');
                e.preventDefault();
                return;
            }
        });
    </script>
</body>
</html> 