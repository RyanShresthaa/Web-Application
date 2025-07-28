<?php
require_once 'config/database.php';
require_once 'includes/User.php';
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        $user = new User();
                    if ($user->loadByEmail($email)) {
                $token = bin2hex(random_bytes(16));
                if ($user->setResetToken($token)) {
                    $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/Multi-Login/reset_password.php?email=" . urlencode($email) . "&token=" . $token;
                    // You can use mail() or any mail library to send this link via email.
                    // mail($email, "Password Reset", "Click this link to reset your password: $resetLink");
                    $success = "A password reset link has been sent to your email. For demo purposes, you can use this link: <br><a href='$resetLink' target='_blank'>$resetLink</a>";
                } else {
                    $error = 'Could not initiate reset process. Please try again later.';
                }
            } else {
                $error = 'No account found with that email address.';
            }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Multi-Login System</title>
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
        
        .forgot-password-card {
            background: #FFFFFF;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
            max-width: 480px;
            animation: slideIn 0.6s ease-out;
        }
        
        .forgot-password-header {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            color: #FFFFFF;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }
        
        .forgot-password-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .forgot-password-header p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
        }
        
        .forgot-password-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
        
        .forgot-password-icon i {
            font-size: 2rem;
            color: #FFFFFF;
        }
        
        .forgot-password-body {
            padding: 3rem 2rem;
        }
        
        .form-group {
            margin-bottom: 2rem;
        }
        
        .form-label {
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.5rem;
            display: block;
        }
        
        .form-control {
            border: 2px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.25s;
            background: #F9FAFB;
            width: 100%;
            font-family: inherit;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: #FFFFFF;
        }
        
        .input-group {
            position: relative;
        }
        
        .input-group .form-control {
            padding-left: 3rem;
        }
        
        .input-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #9CA3AF;
            z-index: 2;
        }
        
        .btn-reset {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            color: #FFFFFF;
            width: 100%;
            transition: all 0.25s;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        
        .btn-back {
            background: transparent;
            border: 2px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-weight: 500;
            font-size: 1rem;
            color: #6B7280;
            width: 100%;
            transition: all 0.25s;
            margin-top: 1rem;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            cursor: pointer;
            font-family: inherit;
        }
        
        .btn-back:hover {
            border-color: #2563EB;
            color: #2563EB;
            background: rgba(37, 99, 235, 0.1);
            text-decoration: none;
        }
        
        .alert {
            border-radius: 0.75rem;
            border: none;
            padding: 1rem;
            margin-bottom: 2rem;
            font-weight: 500;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #10B981;
        }
        
        .alert-danger {
            background: #FEE2E2;
            color: #EF4444;
        }
        
        .forgot-password-footer {
            text-align: center;
            padding: 2rem;
            background: #F9FAFB;
            border-top: 1px solid #E5E7EB;
        }
        
        .forgot-password-footer p {
            margin: 0 0 0.5rem 0;
            color: #6B7280;
        }
        
        .forgot-password-footer a {
            color: #2563EB;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s;
            cursor: pointer;
        }
        
        .forgot-password-footer a:hover {
            color: #1E40AF;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>
<body>
    <div class="forgot-password-card">
        <div class="forgot-password-header">
            <div class="forgot-password-icon">
                <i class="fas fa-lock"></i>
            </div>
            <h1>Forgot Password?</h1>
            <p>Enter your email address and we'll send you a link to reset your password</p>
        </div>
        
        <div class="forgot-password-body">
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle" style="margin-right: 0.5rem;"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle" style="margin-right: 0.5rem;"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <div class="input-group">
                        <i class="fas fa-envelope input-icon"></i>
                        <input 
                            type="email" 
                            class="form-control" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email address"
                            value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                            required
                        >
                    </div>
                </div>
                
                <button type="submit" class="btn-reset">
                    <i class="fas fa-paper-plane" style="margin-right: 0.5rem;"></i>
                    Send Reset Link
                </button>
                
                <a href="index.php" class="btn-back">
                    <i class="fas fa-arrow-left" style="margin-right: 0.5rem;"></i>
                    Back to Login
                </a>
            </form>
        </div>
        
        <div class="forgot-password-footer">
            <p>
                Remember your password? 
                <a href="index.php">Sign in here</a>
            </p>
        </div>
    </div>
    

</body>
</html> 