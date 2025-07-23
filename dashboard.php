<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in
requireAuth();

$user = getUserInfo();
$user_type = $_SESSION['user_type'];
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Get data based on user type
$users = [];
$login_activity = [];
$students = [];
$teachers = [];
$classes = [];
$subjects = [];

if (hasPermission('admin')) {
    $users = getAllUsers();
    $login_activity = getLoginActivity(10);
    $students = getAllStudents();
    $teachers = getAllTeachers();
    $classes = getAllClasses();
    $subjects = getAllSubjects();
} elseif (hasPermission('teacher')) {
    $teacher_classes = getTeacherClasses($_SESSION['user_id']);
    $teacher_subjects = getTeacherSubjects($_SESSION['user_id']);
    $students = getAllStudents();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Multi-Login System</title>
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
            transition: transform 0.3s ease;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px 15px 0 0 !important;
            border: none;
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .stats-card .card-body {
            padding: 1.5rem;
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
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
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-custom {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
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
                        <i class="<?php echo getUserTypeIcon($user_type); ?>"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="user-type-badge user-type-<?php echo $user_type; ?>">
                            <?php echo getUserTypeDisplayName($user_type); ?>
                        </span>
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

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2">
                <div class="sidebar p-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
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
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-user-graduate"></i> Manage Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="classes.php">
                                <i class="fas fa-chalkboard"></i> Manage Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="subjects.php">
                                <i class="fas fa-book"></i> Manage Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="activity.php">
                                <i class="fas fa-chart-line"></i> Activity Log
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if (hasPermission('teacher')): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="marks.php">
                                <i class="fas fa-chart-bar"></i> Marks
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-user-graduate"></i> Students
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_attendance.php">
                                <i class="fas fa-calendar-check"></i> My Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_marks.php">
                                <i class="fas fa-chart-bar"></i> My Marks
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
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

                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <h2 class="mb-4">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                        <small class="text-muted">Welcome back, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small>
                    </h2>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <?php if (hasPermission('admin')): ?>
                        <div class="col-md-2">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-shield fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($users); ?></div>
                                    <div>Total Users</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-graduate fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($students); ?></div>
                                    <div>Students</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($teachers); ?></div>
                                    <div>Teachers</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chalkboard fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($classes); ?></div>
                                    <div>Classes</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-book fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($subjects); ?></div>
                                    <div>Subjects</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-2">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($login_activity); ?></div>
                                    <div>Recent Logins</div>
                                </div>
                            </div>
                        </div>
                        
                        <?php elseif (hasPermission('teacher')): ?>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($teacher_classes); ?></div>
                                    <div>My Classes</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-book fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($teacher_subjects); ?></div>
                                    <div>My Subjects</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-graduate fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo count($students); ?></div>
                                    <div>Total Students</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo date('d'); ?></div>
                                    <div>Today's Date</div>
                                </div>
                            </div>
                        </div>
                        
                        <?php else: ?>
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-user-graduate fa-2x mb-2"></i>
                                    <div class="stats-number">Student</div>
                                    <div>Welcome!</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <div class="stats-number">View</div>
                                    <div>My Attendance</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                    <div class="stats-number">View</div>
                                    <div>My Marks</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>

                    <div class="row">
                        <!-- User Information -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user"></i> Your Information</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <strong>Username:</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <strong>Full Name:</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <strong>Email:</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <strong>User Type:</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            <span class="user-type-badge user-type-<?php echo $user_type; ?>">
                                                <?php echo getUserTypeDisplayName($user_type); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-sm-4">
                                            <strong>Member Since:</strong>
                                        </div>
                                        <div class="col-sm-8">
                                            <?php echo date('F j, Y', strtotime($user['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quick Actions -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="profile.php" class="btn btn-outline-primary btn-custom">
                                            <i class="fas fa-user-edit"></i> Edit Profile
                                        </a>
                                        <a href="change_password.php" class="btn btn-outline-warning btn-custom">
                                            <i class="fas fa-key"></i> Change Password
                                        </a>
                                        
                                        <?php if (hasPermission('admin')): ?>
                                        <a href="users.php" class="btn btn-outline-success btn-custom">
                                            <i class="fas fa-users-cog"></i> Manage Users
                                        </a>
                                        <a href="students.php" class="btn btn-outline-info btn-custom">
                                            <i class="fas fa-user-graduate"></i> Manage Students
                                        </a>
                                        <a href="classes.php" class="btn btn-outline-warning btn-custom">
                                            <i class="fas fa-chalkboard"></i> Manage Classes
                                        </a>
                                        <a href="subjects.php" class="btn btn-outline-secondary btn-custom">
                                            <i class="fas fa-book"></i> Manage Subjects
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if (hasPermission('teacher')): ?>
                                        <a href="attendance.php" class="btn btn-outline-success btn-custom">
                                            <i class="fas fa-calendar-check"></i> Record Attendance
                                        </a>
                                        <a href="marks.php" class="btn btn-outline-info btn-custom">
                                            <i class="fas fa-chart-bar"></i> Record Marks
                                        </a>
                                        <a href="students.php" class="btn btn-outline-warning btn-custom">
                                            <i class="fas fa-user-graduate"></i> View Students
                                        </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($_SESSION['user_type'] === 'student'): ?>
                                        <a href="my_attendance.php" class="btn btn-outline-success btn-custom">
                                            <i class="fas fa-calendar-check"></i> My Attendance
                                        </a>
                                        <a href="my_marks.php" class="btn btn-outline-info btn-custom">
                                            <i class="fas fa-chart-bar"></i> My Marks
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (hasPermission('admin') && !empty($login_activity)): ?>
                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-history"></i> Recent Login Activity</h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>User</th>
                                                    <th>Type</th>
                                                    <th>Login Time</th>
                                                    <th>IP Address</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($login_activity as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                                        <br><small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="user-type-badge user-type-<?php echo $activity['user_type']; ?>">
                                                            <?php echo getUserTypeDisplayName($activity['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td><?php echo date('M j, Y g:i A', strtotime($activity['login_time'])); ?></td>
                                                    <td><code><?php echo htmlspecialchars($activity['ip_address']); ?></code></td>
                                                    <td>
                                                        <?php if ($activity['status'] === 'success'): ?>
                                                            <span class="badge bg-success">Success</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-danger">Failed</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 