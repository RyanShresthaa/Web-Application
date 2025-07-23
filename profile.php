<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/User.php';

requireAuth();

$user = getUserInfo();
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    try {
        if (empty($full_name) || empty($email)) {
            throw new Exception('Please fill in all fields.');
        }
        // Check for duplicate email (if changed)
        if ($email !== $user['email']) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND id != ?');
            $stmt->execute([$email, $user['id']]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Email already exists.');
            }
        }
        // Use User class for update
        $userObj = new User();
        $userObj->loadById($user['id']);
        $userObj->setFullName($full_name);
        $userObj->setEmail($email);
        if ($userObj->update()) {
            $message = 'Profile updated successfully!';
            $_SESSION['full_name'] = $full_name;
            $user = getUserInfo();
        } else {
            throw new Exception('Failed to update profile.');
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
    <title>My Profile - Multi-Login System</title>
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
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 600;
        }
        .sidebar {
            min-height: calc(100vh - 56px);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            padding: 20px;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        .user-type-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .user-type-admin {
            background: #dc3545;
            color: white;
        }
        .user-type-moderator {
            background: #fd7e14;
            color: white;
        }
        .user-type-user {
            background: #28a745;
            color: white;
        }
        .btn-custom {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2.5rem;
            margin: 0 auto 20px;
        }
        @media (max-width: 991.98px) {
            .sidebar {
                min-height: auto;
            }
            .main-content {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users"></i> Multi-Login System
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="<?php echo getUserTypeIcon($_SESSION['user_type']); ?>"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="user-type-badge user-type-<?php echo $_SESSION['user_type']; ?>">
                            <?php echo getUserTypeDisplayName($_SESSION['user_type']); ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if (hasPermission('admin')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="activity.php">
                                <i class="fas fa-chart-line"></i> Activity Log
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('moderator')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="moderation.php">
                                <i class="fas fa-shield-alt"></i> Moderation
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user"></i> My Profile
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content">
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <h2 class="mb-4">
                        <i class="fas fa-user"></i> My Profile
                    </h2>

                    <div class="row">
                        <!-- Profile Information -->
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user-edit"></i> Edit Profile</h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="username" class="form-label">
                                                        <i class="fas fa-user"></i> Username
                                                    </label>
                                                    <input type="text" class="form-control" id="username" 
                                                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                                                           readonly>
                                                    <small class="text-muted">Username cannot be changed</small>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="user_type" class="form-label">
                                                        <i class="fas fa-shield-alt"></i> User Type
                                                    </label>
                                                    <input type="text" class="form-control" id="user_type" 
                                                           value="<?php echo getUserTypeDisplayName($user['user_type']); ?>" 
                                                           readonly>
                                                    <small class="text-muted">User type cannot be changed</small>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="full_name" class="form-label">
                                                        <i class="fas fa-id-card"></i> Full Name
                                                    </label>
                                                    <input type="text" class="form-control" id="full_name" name="full_name" 
                                                           value="<?php echo htmlspecialchars($user['full_name']); ?>" 
                                                           required>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="email" class="form-label">
                                                        <i class="fas fa-envelope"></i> Email Address
                                                    </label>
                                                    <input type="email" class="form-control" id="email" name="email" 
                                                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                           required>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="created_at" class="form-label">
                                                        <i class="fas fa-calendar"></i> Member Since
                                                    </label>
                                                    <input type="text" class="form-control" id="created_at" 
                                                           value="<?php echo date('F j, Y', strtotime($user['created_at'])); ?>" 
                                                           readonly>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="status" class="form-label">
                                                        <i class="fas fa-circle"></i> Account Status
                                                    </label>
                                                    <input type="text" class="form-control" id="status" 
                                                           value="<?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>" 
                                                           readonly>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary btn-custom">
                                            <i class="fas fa-save"></i> Update Profile
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Profile Summary -->
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user-circle"></i> Profile Summary</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="profile-avatar">
                                        <i class="<?php echo getUserTypeIcon($user['user_type']); ?>"></i>
                                    </div>
                                    
                                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                                    
                                    <span class="user-type-badge user-type-<?php echo $user['user_type']; ?>">
                                        <?php echo getUserTypeDisplayName($user['user_type']); ?>
                                    </span>
                                    
                                    <hr>
                                    
                                    <div class="row text-center">
                                        <div class="col-6">
                                            <h6 class="text-muted">Email</h6>
                                            <small><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                        <div class="col-6">
                                            <h6 class="text-muted">Status</h6>
                                            <small class="<?php echo $user['is_active'] ? 'text-success' : 'text-danger'; ?>">
                                                <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </small>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <div class="d-grid gap-2">
                                        <a href="change_password.php" class="btn btn-outline-warning btn-custom">
                                            <i class="fas fa-key"></i> Change Password
                                        </a>
                                        <a href="dashboard.php" class="btn btn-outline-primary btn-custom">
                                            <i class="fas fa-arrow-left"></i> Back to Dashboard
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 