<?php
session_start();
require_once 'includes/functions.php';

// Don't require authentication for contact support - anyone should be able to contact support

$message = '';
$error = '';

// Handle contact form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $category = $_POST['category'];
    $message_text = trim($_POST['message']);
    
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Here you would typically send the email or save to database
        // For now, we'll just show a success message
        $message = 'Thank you for contacting us! We will get back to you within 24 hours.';
        
        // Clear form data after successful submission
        $name = $email = $subject = $message_text = '';
        $category = 'general';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - Multi-Login System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="admin-theme.css" rel="stylesheet">
    <style>
        .contact-support-container {
            min-height: 100vh;
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .contact-support-card {
            background: #FFFFFF;
            border-radius: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            width: 100%;
            max-width: 800px;
            animation: slideIn 0.6s ease-out;
        }

        .contact-support-header {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            color: #FFFFFF;
            padding: 3rem 2rem;
            text-align: center;
            position: relative;
        }

        .contact-support-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/><circle cx="10" cy="60" r="0.5" fill="white" opacity="0.1"/><circle cx="90" cy="40" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .contact-support-header h1 {
            font-size: 2.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .contact-support-header p {
            font-size: 1rem;
            opacity: 0.9;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        .contact-support-icon {
            width: 80px;
            height: 80px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            position: relative;
            z-index: 1;
        }

        .contact-support-icon i {
            font-size: 2rem;
            color: #FFFFFF;
        }

        .contact-support-body {
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

        .form-control, .form-select {
            border: 2px solid #E5E7EB;
            border-radius: 0.75rem;
            padding: 1rem;
            font-size: 1rem;
            transition: all 0.25s;
            background: #F9FAFB;
        }

        .form-control:focus, .form-select:focus {
            border-color: #2563EB;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
            background: #FFFFFF;
        }

        .form-control::placeholder {
            color: #9CA3AF;
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

        .btn-submit {
            background: linear-gradient(135deg, #2563EB 0%, #1E40AF 100%);
            border: none;
            border-radius: 0.75rem;
            padding: 1rem 2rem;
            font-weight: 600;
            font-size: 1rem;
            color: #FFFFFF;
            width: 100%;
            transition: all 0.25s;
            position: relative;
            overflow: hidden;
        }

        .btn-submit::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }

        .btn-submit:hover::before {
            left: 100%;
        }

        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }

        .btn-submit:active {
            transform: translateY(0);
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

        .contact-info {
            background: #F9FAFB;
            border-radius: 0.75rem;
            padding: 2rem;
            margin-top: 2rem;
            border: 1px solid #E5E7EB;
        }

        .contact-info h6 {
            color: #374151;
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .contact-method {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
            padding: 1rem;
            background: #FFFFFF;
            border-radius: 0.5rem;
            border: 1px solid #E5E7EB;
            transition: all 0.25s;
        }

        .contact-method:hover {
            border-color: #2563EB;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .contact-method i {
            width: 40px;
            height: 40px;
            background: #DBEAFE;
            color: #2563EB;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.1rem;
        }

        .contact-method .contact-details h6 {
            margin: 0;
            font-size: 0.9rem;
            color: #374151;
        }

        .contact-method .contact-details p {
            margin: 0;
            font-size: 0.875rem;
            color: #6B7280;
        }

        .contact-support-footer {
            text-align: center;
            padding: 2rem;
            background: #F9FAFB;
            border-top: 1px solid #E5E7EB;
        }

        .contact-support-footer a {
            color: #2563EB;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.15s;
        }

        .contact-support-footer a:hover {
            color: #1E40AF;
        }

        @media (max-width: 768px) {
            .contact-support-container {
                padding: 1rem;
            }
            
            .contact-support-card {
                max-width: 100%;
                margin: 1rem;
            }
            
            .contact-support-header,
            .contact-support-body {
                padding: 2rem 1rem;
            }
            
            .contact-support-header h1 {
                font-size: 1.875rem;
            }
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
    <div class="contact-support-container">
        <div class="contact-support-card">
            <div class="contact-support-header">
                <div class="contact-support-icon">
                    <i class="fas fa-headset"></i>
                </div>
                <h1>Contact Support</h1>
                <p>We're here to help! Get in touch with our support team</p>
            </div>
            
            <div class="contact-support-body">
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="" id="contactForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="name" class="form-label">Full Name *</label>
                                <div class="input-group">
                                    <i class="fas fa-user input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="name" 
                                        name="name" 
                                        placeholder="Enter your full name"
                                        value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address *</label>
                                <div class="input-group">
                                    <i class="fas fa-envelope input-icon"></i>
                                    <input 
                                        type="email" 
                                        class="form-control" 
                                        id="email" 
                                        name="email" 
                                        placeholder="Enter your email address"
                                        value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category" class="form-label">Support Category</label>
                                <select class="form-select" id="category" name="category">
                                    <option value="general" <?php echo (isset($category) && $category == 'general') ? 'selected' : ''; ?>>General Inquiry</option>
                                    <option value="technical" <?php echo (isset($category) && $category == 'technical') ? 'selected' : ''; ?>>Technical Support</option>
                                    <option value="account" <?php echo (isset($category) && $category == 'account') ? 'selected' : ''; ?>>Account Issues</option>
                                    <option value="billing" <?php echo (isset($category) && $category == 'billing') ? 'selected' : ''; ?>>Billing & Payment</option>
                                    <option value="feature" <?php echo (isset($category) && $category == 'feature') ? 'selected' : ''; ?>>Feature Request</option>
                                    <option value="bug" <?php echo (isset($category) && $category == 'bug') ? 'selected' : ''; ?>>Bug Report</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="subject" class="form-label">Subject *</label>
                                <div class="input-group">
                                    <i class="fas fa-tag input-icon"></i>
                                    <input 
                                        type="text" 
                                        class="form-control" 
                                        id="subject" 
                                        name="subject" 
                                        placeholder="Brief description of your issue"
                                        value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>"
                                        required
                                    >
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="message" class="form-label">Message *</label>
                        <textarea 
                            class="form-control" 
                            id="message" 
                            name="message" 
                            rows="6" 
                            placeholder="Please provide detailed information about your issue or question..."
                            required
                        ><?php echo isset($message_text) ? htmlspecialchars($message_text) : ''; ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane me-2"></i>
                        Send Message
                    </button>
                    
                    <a href="dashboard.php" class="btn-back">
                        <i class="fas fa-arrow-left me-2"></i>
                        Back to Dashboard
                    </a>
                </form>
                
                <div class="contact-info">
                    <h6><i class="fas fa-info-circle me-2"></i>Other Ways to Get Help</h6>
                    
                    <div class="contact-method">
                        <i class="fas fa-envelope"></i>
                        <div class="contact-details">
                            <h6>Email Support</h6>
                            <p>support@multilogin.com</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <i class="fas fa-phone"></i>
                        <div class="contact-details">
                            <h6>Phone Support</h6>
                            <p>+1 (555) 123-4567</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <i class="fas fa-clock"></i>
                        <div class="contact-details">
                            <h6>Support Hours</h6>
                            <p>Monday - Friday: 9:00 AM - 6:00 PM EST</p>
                        </div>
                    </div>
                    
                    <div class="contact-method">
                        <i class="fas fa-book"></i>
                        <div class="contact-details">
                            <h6>Documentation</h6>
                            <p>Check our help center for guides and FAQs</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="contact-support-footer">
                <p class="mb-0">
                    Need immediate assistance? 
                    <a href="dashboard.php">Return to Dashboard</a>
                </p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 