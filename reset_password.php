<?php
require_once 'config/database.php';
require_once 'includes/User.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

// Check if email and token are provided
if (!isset($_GET['email']) || !isset($_GET['token'])) {
    header('Location: forgot_password.php?error=Invalid reset link');
    exit();
}

$email = $_GET['email'];
$token = $_GET['token'];

// Load user by email
$user = new User();
if (!$user->loadByEmail($email)) {
    header('Location: forgot_password.php?error=Invalid email address');
    exit();
}

// Check if token is valid
$tokenData = $user->getResetToken();
if (!$tokenData || $tokenData['reset_token'] !== $token) {
    header('Location: forgot_password.php?error=Invalid or expired reset token');
    exit();
}

// Check if token has expired
if (strtotime($tokenData['reset_token_expires']) < time()) {
    header('Location: forgot_password.php?error=Reset token has expired');
    exit();
}

// Handle password reset form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (empty($new_password)) {
        $error = 'Please enter a new password.';
    } elseif (strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Update password and clear reset token
        if ($user->updatePassword($new_password)) {
            $user->clearResetToken();
            $success = 'Your password has been reset successfully. You can now login with your new password.';
        } else {
            $error = 'Could not reset password. Please try again later.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Student Management System</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }
        
        .reset-password-card {
            background: #FFFFFF;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
            animation: slideIn 0.6s ease-out;
        }
        
        .reset-password-header {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            color: #FFFFFF;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .reset-password-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .reset-password-header p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .reset-password-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        
        .reset-password-icon i {
            font-size: 2rem;
            color: #FFFFFF;
        }
        
        .reset-password-form {
            padding: 3rem 2rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            font-size: 0.875rem;
        }
        
        .form-group input {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid #E5E7EB;
            border-radius: 0.75rem;
            font-size: 1rem;
            transition: all 0.2s ease;
            background: #FFFFFF;
        }
        
        .form-group input:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }
        
        .form-group input.error {
            border-color: #EF4444;
        }
        
        .btn {
            width: 100%;
            padding: 1rem;
            border: none;
            border-radius: 0.75rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            color: #FFFFFF;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(37, 99, 235, 0.4);
        }
        
        .btn-secondary {
            background: #F3F4F6;
            color: #374151;
            margin-top: 1rem;
        }
        
        .btn-secondary:hover {
            background: #E5E7EB;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1.5rem;
            font-size: 0.875rem;
        }
        
        .alert-error {
            background: #FEF2F2;
            color: #DC2626;
            border: 1px solid #FECACA;
        }
        
        .alert-success {
            background: #F0FDF4;
            color: #16A34A;
            border: 1px solid #BBF7D0;
        }
        
        .password-requirements {
            background: #F8FAFC;
            border: 1px solid #E2E8F0;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .password-requirements h4 {
            font-size: 0.875rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.5rem;
        }
        
        .password-requirements ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .password-requirements li {
            font-size: 0.75rem;
            color: #6B7280;
            margin-bottom: 0.25rem;
            display: flex;
            align-items: center;
        }
        
        .password-requirements li i {
            margin-right: 0.5rem;
            font-size: 0.625rem;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @media (max-width: 480px) {
            .reset-password-card {
                margin: 1rem;
            }
            
            .reset-password-header,
            .reset-password-form {
                padding: 2rem 1.5rem;
            }
            
            .reset-password-header h1 {
                font-size: 1.875rem;
            }
        }
    </style>
</head>
<body>
    <div class="reset-password-card">
        <div class="reset-password-header">
            <div class="reset-password-icon">
                <i class="fas fa-key"></i>
            </div>
            <h1>Reset Password</h1>
            <p>Enter your new password below</p>
        </div>
        
        <div class="reset-password-form">
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                </div>
                <a href="index.php" class="btn btn-primary">Go to Login</a>
            <?php else: ?>
                <form method="POST">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                    <div class="password-requirements">
                        <h4>Password Requirements:</h4>
                        <ul>
                            <li><i class="fas fa-circle"></i> At least 6 characters long</li>
                            <li><i class="fas fa-circle"></i> Should be memorable but secure</li>
                        </ul>
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Reset Password
                    </button>
                </form>
                
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Login
                </a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 