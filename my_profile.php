<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in and is a student
requireAuth();
if ($_SESSION['user_type'] !== 'student') {
    header('Location: dashboard.php?error=Access denied');
    exit();
}

$pdo = getDBConnection();
$student_id = $_SESSION['user_id'];

// Get student information
$stmt = $pdo->prepare("
    SELECT u.*, sp.* 
    FROM users u 
    LEFT JOIN student_profiles sp ON u.id = sp.student_id 
    WHERE u.id = ? AND u.user_type = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

// Get student's class enrollment
$stmt = $pdo->prepare("
    SELECT c.*, u.full_name as teacher_name
    FROM student_enrollments se
    JOIN classes c ON se.class_id = c.id
    JOIN users u ON c.teacher_id = u.id
    WHERE se.student_id = ? AND se.status = 'active'
    ORDER BY c.grade_level, c.name
");
$stmt->execute([$student_id]);
$enrolled_classes = $stmt->fetchAll();

// Calculate age
$age = 0;
if ($student['date_of_birth']) {
    $birth_date = new DateTime($student['date_of_birth']);
    $today = new DateTime();
    $age = $today->diff($birth_date)->y;
}

$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #3b82f6;
            --primary-blue-light: #dbeafe;
            --white: #ffffff;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-600: #64748b;
            --gray-900: #1e293b;
            --error: #ef4444;
            --error-light: #fee2e2;
            --success: #10b981;
            --success-light: #d1fae5;
            --warning: #f59e0b;
            --warning-light: #fef3c7;
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --spacing-6: 1.5rem;
            --font-size-xl: 1.25rem;
            --font-bold: 700;
            --font-semibold: 600;
            --font-medium: 500;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background-color: var(--gray-50);
            color: var(--gray-900);
            margin: 0;
            padding: 0;
        }
        
        .dashboard-container {
            min-height: 100vh;
            background-color: var(--gray-50);
            width: 100%;
            padding-top: 64px;
        }
        
        .main-content {
            padding: var(--spacing-6);
            background-color: var(--gray-50);
            min-height: calc(100vh - 64px);
            overflow-y: auto;
            max-width: 1200px;
            margin-left: 280px;
            transition: margin-left 0.3s ease;
        }
        
        .navbar {
            background: var(--white);
            border-bottom: 1px solid var(--gray-200);
            height: 64px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            display: flex;
            align-items: center;
            padding: 0 var(--spacing-6);
            box-shadow: var(--shadow-md);
        }
        
        .navbar-brand {
            font-size: var(--font-size-xl);
            font-weight: var(--font-bold);
            color: var(--primary-blue);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        /* Sidebar Styles */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: 280px;
            background: var(--white);
            border-right: 1px solid var(--gray-200);
            box-shadow: var(--shadow-md);
            z-index: 1001;
            display: flex;
            flex-direction: column;
            transition: all 0.3s ease;
        }
        
        .sidebar-header {
            padding: var(--spacing-6);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: var(--font-size-xl);
            font-weight: var(--font-bold);
            color: var(--primary-blue);
        }
        
        .sidebar-brand i {
            font-size: 1.5rem;
            color: var(--primary-blue);
        }
        
        .sidebar-nav {
            flex: 1;
            padding: var(--spacing-6) 0;
        }
        
        .nav-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .nav-item {
            margin: 0;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.875rem var(--spacing-6);
            color: var(--gray-600);
            text-decoration: none;
            font-weight: var(--font-medium);
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }
        
        .nav-link:hover {
            background: var(--gray-50);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
        }
        
        .nav-link.active {
            background: var(--primary-blue-light);
            color: var(--primary-blue);
            border-left-color: var(--primary-blue);
            font-weight: var(--font-semibold);
        }
        
        .nav-link i {
            width: 1.25rem;
            text-align: center;
            font-size: 1rem;
        }
        
        .sidebar-footer {
            padding: var(--spacing-6);
            border-top: 1px solid var(--gray-200);
            background: var(--gray-50);
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }
        
        .user-avatar {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            background: var(--primary-blue-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 1.25rem;
        }
        
        .user-details {
            flex: 1;
        }
        
        .user-name {
            font-weight: var(--font-semibold);
            color: var(--gray-900);
            font-size: 0.875rem;
        }
        
        .user-role {
            font-size: 0.75rem;
            color: var(--gray-600);
            text-transform: capitalize;
        }
        
        .logout-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.75rem;
            color: var(--error);
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: var(--font-medium);
            border-radius: 0.5rem;
            transition: all 0.2s ease;
        }
        
        .logout-btn:hover {
            background: var(--error-light);
            color: var(--error);
        }
        
        /* Profile Cards */
        .profile-card {
            background: var(--white);
            border-radius: 12px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .profile-header {
            background: var(--gray-50);
            padding: 24px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .profile-header h5 {
            margin: 0;
            color: var(--gray-900);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .profile-header h5 i {
            color: var(--primary-blue);
        }
        
        .profile-body {
            padding: 24px;
        }
        
        /* Profile Info Grid */
        .profile-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
        }
        
        .info-group {
            margin-bottom: 20px;
        }
        
        .info-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-600);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
        }
        
        .info-value {
            font-size: 1rem;
            color: var(--gray-900);
            font-weight: 500;
            padding: 12px 16px;
            background: var(--gray-50);
            border-radius: 8px;
            border: 1px solid var(--gray-200);
        }
        
        .info-value.empty {
            color: var(--gray-600);
            font-style: italic;
        }
        
        /* Profile Avatar */
        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--primary-blue-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 3rem;
            margin: 0 auto 24px;
            border: 4px solid var(--white);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }
        
        /* Status Badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.025em;
            white-space: nowrap;
        }
        
        .status-badge.active {
            background: #d1fae5;
            color: #059669;
        }
        
        .status-badge.inactive {
            background: #fee2e2;
            color: #dc2626;
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: var(--gray-600);
            margin-bottom: 1rem;
        }
        
        .empty-state h4 {
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: var(--gray-600);
            margin-bottom: 0;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .profile-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }
            
            .profile-body {
                padding: 16px;
            }
            
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
        }
</style>
</head>
<body>
    <div class="dashboard-container">
        <!-- Navigation -->
        <nav class="navbar navbar-expand-lg navbar-dark">
            <div class="container-fluid">
                <a class="navbar-brand" href="dashboard.php">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student Management System</span>
                </a>
                
                <div class="navbar-nav ms-auto">
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i>
                            <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                            <li><a class="dropdown-item" href="change_password.php"><i class="fas fa-key"></i> Change Password</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </nav>

            <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-brand">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student Management System</span>
                </div>
            </div>
            
            <nav class="sidebar-nav">
                <ul class="nav-list">
                    <!-- Dashboard -->
                    <li class="nav-item">
                        <a href="dashboard.php" class="nav-link">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- Student Only Pages -->
                    <li class="nav-item">
                        <a href="my_attendance.php" class="nav-link">
                            <i class="fas fa-calendar-check"></i>
                            <span>My Attendance</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="my_marks.php" class="nav-link">
                            <i class="fas fa-chart-line"></i>
                            <span>My Marks</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="my_transcript.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            <span>My Transcript</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="my_notifications.php" class="nav-link">
                            <i class="fas fa-bell"></i>
                            <span>Notifications</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="my_schedule.php" class="nav-link">
                            <i class="fas fa-calendar-alt"></i>
                            <span>My Schedule</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="my_profile.php" class="nav-link active">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="sidebar-footer">
                <div class="user-info">
                    <div class="user-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="user-details">
                        <div class="user-name"><?php echo $_SESSION['full_name'] ?? 'User'; ?></div>
                        <div class="user-role"><?php echo ucfirst($_SESSION['user_type'] ?? 'User'); ?></div>
                    </div>
                </div>
                <a href="logout.php" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
        </div>
        </aside>

            <!-- Main Content -->
            <main class="main-content">
                <div class="container-fluid">
                    <!-- Page Header -->
                    <div class="d-flex justify-content-between align-items-center mb-5">
                        <div>
                            <h1 class="mb-2">My Profile</h1>
                        <p class="text-muted mb-0">View and manage your personal information</p>
                        </div>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                    </div>

                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                <!-- Personal Information -->
                <div class="profile-card">
                    <div class="profile-header">
                        <h5><i class="fas fa-user"></i> Personal Information</h5>
                    </div>
                    <div class="profile-body">
                        <div class="text-center mb-4">
                            <div class="profile-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <h3><?php echo htmlspecialchars($student['full_name']); ?></h3>
                            <p class="text-muted">Student ID: <?php echo htmlspecialchars($student['username']); ?></p>
                            <span class="status-badge <?php echo $student['is_active'] ? 'active' : 'inactive'; ?>">
                                <i class="fas fa-circle"></i>
                                <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                    </div>

                        <div class="profile-grid">
                            <div class="info-group">
                                            <div class="info-label">Full Name</div>
                                            <div class="info-value"><?php echo htmlspecialchars($student['full_name']); ?></div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Email Address</div>
                                            <div class="info-value"><?php echo htmlspecialchars($student['email']); ?></div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Student ID</div>
                                <div class="info-value"><?php echo htmlspecialchars($student['username']); ?></div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Roll Number</div>
                                <div class="info-value <?php echo empty($student['roll_number']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['roll_number'] ?? 'Not assigned'); ?>
                                </div>
                                        </div>
                                        
                            <div class="info-group">
                                            <div class="info-label">Date of Birth</div>
                                <div class="info-value <?php echo empty($student['date_of_birth']) ? 'empty' : ''; ?>">
                                    <?php echo $student['date_of_birth'] ? date('M j, Y', strtotime($student['date_of_birth'])) . " ({$age} years old)" : 'Not provided'; ?>
                                </div>
                                        </div>
                                        
                            <div class="info-group">
                                            <div class="info-label">Gender</div>
                                <div class="info-value <?php echo empty($student['gender']) ? 'empty' : ''; ?>">
                                    <?php echo $student['gender'] ? ucfirst($student['gender']) : 'Not specified'; ?>
                                        </div>
                                    </div>
                            
                            <div class="info-group">
                                <div class="info-label">Phone Number</div>
                                <div class="info-value <?php echo empty($student['phone']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?>
                                </div>
                            </div>

                            <div class="info-group">
                                <div class="info-label">Blood Group</div>
                                <div class="info-value <?php echo empty($student['blood_group']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['blood_group'] ?? 'Not specified'); ?>
                                    </div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Address</div>
                                <div class="info-value <?php echo empty($student['address']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['address'] ?? 'Not provided'); ?>
                                </div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Admission Date</div>
                                <div class="info-value <?php echo empty($student['admission_date']) ? 'empty' : ''; ?>">
                                    <?php echo $student['admission_date'] ? date('M j, Y', strtotime($student['admission_date'])) : 'Not specified'; ?>
                                </div>
                            </div>
                                        </div>
                                    </div>
                                </div>

                <!-- Parent/Guardian Information -->
                <div class="profile-card">
                    <div class="profile-header">
                        <h5><i class="fas fa-users"></i> Parent/Guardian Information</h5>
                    </div>
                    <div class="profile-body">
                        <div class="profile-grid">
                            <div class="info-group">
                                <div class="info-label">Parent Name</div>
                                <div class="info-value <?php echo empty($student['parent_name']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['parent_name'] ?? 'Not provided'); ?>
                                    </div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Parent Phone</div>
                                <div class="info-value <?php echo empty($student['parent_phone']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['parent_phone'] ?? 'Not provided'); ?>
                                            </div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Parent Email</div>
                                <div class="info-value <?php echo empty($student['parent_email']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['parent_email'] ?? 'Not provided'); ?>
                                </div>
                                        </div>
                                        
                            <div class="info-group">
                                <div class="info-label">Emergency Contact</div>
                                <div class="info-value <?php echo empty($student['emergency_contact']) ? 'empty' : ''; ?>">
                                    <?php echo htmlspecialchars($student['emergency_contact'] ?? 'Not provided'); ?>
                                </div>
                            </div>
                                </div>
                            </div>
                        </div>

                <!-- Academic Information -->
                <div class="profile-card">
                    <div class="profile-header">
                        <h5><i class="fas fa-graduation-cap"></i> Academic Information</h5>
                    </div>
                    <div class="profile-body">
                        <?php if (empty($enrolled_classes)): ?>
                            <div class="empty-state">
                                <i class="fas fa-chalkboard"></i>
                                <h4>No Classes Enrolled</h4>
                                <p>You are not enrolled in any classes yet.</p>
                            </div>
                        <?php else: ?>
                            <div class="profile-grid">
                                <?php foreach ($enrolled_classes as $class): ?>
                                <div class="info-group">
                                    <div class="info-label">Class</div>
                                    <div class="info-value"><?php echo htmlspecialchars($class['name']); ?></div>
                                        </div>
                                
                                <div class="info-group">
                                    <div class="info-label">Grade Level</div>
                                    <div class="info-value"><?php echo htmlspecialchars($class['grade_level']); ?></div>
                                        </div>
                                
                                <div class="info-group">
                                    <div class="info-label">Academic Year</div>
                                    <div class="info-value"><?php echo htmlspecialchars($class['academic_year']); ?></div>
                                    </div>
                                
                                <div class="info-group">
                                    <div class="info-label">Class Teacher</div>
                                    <div class="info-value"><?php echo htmlspecialchars($class['teacher_name']); ?></div>
                                </div>
                                <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                        </div>
                </div>
                </div>
            </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 