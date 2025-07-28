<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in
requireAuth();

$user_type = $_SESSION['user_type'];
$user_id = $_SESSION['user_id'];

// Initialize variables
$grade_data = [];
$attendance_data = [];
$performance_data = [];
$subject_stats = [];
$class_stats = [];

$pdo = getDBConnection();

try {
    if (hasPermission('admin')) {
        // Admin can see all data
        $grade_data = getGradeAnalytics();
        $attendance_data = getAttendanceAnalytics();
        $performance_data = getPerformanceAnalytics();
        $subject_stats = getSubjectStatistics();
        $class_stats = getClassStatistics();
    } elseif (hasPermission('teacher')) {
        // Teacher sees data for their subjects/classes
        $grade_data = getGradeAnalytics($user_id);
        $attendance_data = getAttendanceAnalytics($user_id);
        $performance_data = getPerformanceAnalytics($user_id);
        $subject_stats = getSubjectStatistics($user_id);
        $class_stats = getClassStatistics($user_id);
    } elseif ($_SESSION['user_type'] === 'student') {
        // Student sees their own data
        $grade_data = getStudentGradeAnalytics($user_id);
        $attendance_data = getStudentAttendanceAnalytics($user_id);
        $performance_data = getStudentPerformanceAnalytics($user_id);
        $subject_stats = getStudentSubjectStatistics($user_id);
    }
} catch (Exception $e) {
    // Handle any database errors gracefully
    $grade_data = [];
    $attendance_data = [];
    $performance_data = [];
    $subject_stats = [];
    $class_stats = [];
    $error_message = "Some data could not be loaded. Please try again later.";
}

// Helper functions for analytics
function getGradeAnalytics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            CASE 
                WHEN percentage >= 90 THEN 'A+'
                WHEN percentage >= 80 THEN 'A'
                WHEN percentage >= 70 THEN 'B'
                WHEN percentage >= 60 THEN 'C'
                WHEN percentage >= 50 THEN 'D'
                ELSE 'F'
            END as grade,
            COUNT(*) as count
        FROM (
            SELECT (marks_obtained / total_marks * 100) as percentage
            FROM marks m
            JOIN subjects s ON m.subject_id = s.id
            WHERE 1=1
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= ") as grade_calc
        GROUP BY grade
        ORDER BY 
            CASE grade
                WHEN 'A+' THEN 1
                WHEN 'A' THEN 2
                WHEN 'B' THEN 3
                WHEN 'C' THEN 4
                WHEN 'D' THEN 5
                WHEN 'F' THEN 6
            END";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getAttendanceAnalytics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            status,
            COUNT(*) as count,
            DATE_FORMAT(date, '%Y-%m') as month
        FROM attendance a
        JOIN subjects s ON a.subject_id = s.id
        WHERE date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= " GROUP BY status, month ORDER BY month, status";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getPerformanceAnalytics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams,
            MAX(m.exam_date) as last_exam
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        WHERE m.exam_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= " GROUP BY s.id, s.name ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getSubjectStatistics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            COUNT(DISTINCT m.student_id) as students,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams
        FROM subjects s
        LEFT JOIN marks m ON s.id = m.subject_id
        WHERE 1=1
    ";
    
    if ($teacher_id) {
        $sql .= " AND s.teacher_id = :teacher_id";
    }
    
    $sql .= " GROUP BY s.id, s.name ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getClassStatistics($teacher_id = null) {
    global $pdo;
    
    $sql = "
        SELECT 
            c.name as class_name,
            COUNT(DISTINCT se.student_id) as total_students,
            AVG(att.attendance_percentage) as avg_attendance,
            AVG(marks.avg_percentage) as avg_performance
        FROM classes c
        LEFT JOIN student_enrollments se ON c.id = se.class_id
        LEFT JOIN (
            SELECT 
                a.class_id,
                a.student_id,
                (SUM(CASE WHEN a.status IN ('present', 'late') THEN 1 ELSE 0 END) / COUNT(*) * 100) as attendance_percentage
            FROM attendance a
            GROUP BY a.class_id, a.student_id
        ) att ON c.id = att.class_id
        LEFT JOIN (
            SELECT 
                m.class_id,
                m.student_id,
                AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage
            FROM marks m
            GROUP BY m.class_id, m.student_id
        ) marks ON c.id = marks.class_id
        WHERE 1=1
    ";
    
    if ($teacher_id) {
        $sql .= " AND c.id IN (SELECT DISTINCT a.class_id FROM attendance a JOIN subjects s ON a.subject_id = s.id WHERE s.teacher_id = :teacher_id)";
    }
    
    $sql .= " GROUP BY c.id, c.name ORDER BY avg_performance DESC";
    
    $stmt = $pdo->prepare($sql);
    if ($teacher_id) {
        $stmt->bindParam(':teacher_id', $teacher_id);
    }
    $stmt->execute();
    
    return $stmt->fetchAll();
}

// Student-specific analytics functions
function getStudentGradeAnalytics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            CASE 
                WHEN percentage >= 90 THEN 'A+'
                WHEN percentage >= 80 THEN 'A'
                WHEN percentage >= 70 THEN 'B'
                WHEN percentage >= 60 THEN 'C'
                WHEN percentage >= 50 THEN 'D'
                ELSE 'F'
            END as grade,
            COUNT(*) as count
        FROM (
            SELECT (marks_obtained / total_marks * 100) as percentage
            FROM marks
            WHERE student_id = :student_id
        ) as grade_calc
        GROUP BY grade
        ORDER BY 
            CASE grade
                WHEN 'A+' THEN 1
                WHEN 'A' THEN 2
                WHEN 'B' THEN 3
                WHEN 'C' THEN 4
                WHEN 'D' THEN 5
                WHEN 'F' THEN 6
            END";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentAttendanceAnalytics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            status,
            COUNT(*) as count,
            DATE_FORMAT(date, '%Y-%m') as month
        FROM attendance
        WHERE student_id = :student_id 
        AND date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY status, month 
        ORDER BY month, status";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentPerformanceAnalytics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams,
            MAX(m.exam_date) as last_exam
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        WHERE m.student_id = :student_id
        AND m.exam_date >= DATE_SUB(CURDATE(), INTERVAL 3 MONTH)
        GROUP BY s.id, s.name 
        ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

function getStudentSubjectStatistics($student_id) {
    global $pdo;
    
    $sql = "
        SELECT 
            s.name as subject_name,
            AVG(m.marks_obtained / m.total_marks * 100) as avg_percentage,
            COUNT(*) as total_exams,
            MIN(m.marks_obtained / m.total_marks * 100) as min_percentage,
            MAX(m.marks_obtained / m.total_marks * 100) as max_percentage
        FROM subjects s
        JOIN marks m ON s.id = m.subject_id
        WHERE m.student_id = :student_id
        GROUP BY s.id, s.name 
        ORDER BY avg_percentage DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':student_id', $student_id);
    $stmt->execute();
    
    return $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics Dashboard - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        .analytics-card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            margin-bottom: 24px;
            transition: all 0.25s ease-in-out;
        }

        .analytics-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }

        .analytics-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1.5rem;
        }

        .analytics-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-blue-light), var(--primary-blue-light));
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-blue);
            font-size: 1.25rem;
            font-weight: 600;
        }

        .analytics-title {
            font-size: 1.25rem;
            font-weight: var(--font-semibold);
            color: var(--gray-900);
            margin: 0;
        }

        .chart-container {
            position: relative;
            height: 300px;
            margin-top: 1rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .stat-item {
            background: var(--gray-50);
            padding: 1rem;
            border-radius: 8px;
            text-align: center;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: var(--font-bold);
            color: var(--primary-blue);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: var(--font-medium);
        }

        .performance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .performance-table th,
        .performance-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        .performance-table th {
            background: var(--gray-50);
            font-weight: var(--font-semibold);
            color: var(--gray-900);
            font-size: 0.875rem;
        }

        .performance-table td {
            color: var(--gray-700);
        }

        .grade-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: var(--font-semibold);
            text-transform: uppercase;
        }

        .grade-a { background: var(--success-light); color: var(--success); }
        .grade-b { background: #fef3c7; color: #d97706; }
        .grade-c { background: #fef3c7; color: #f59e0b; }
        .grade-d { background: #fee2e2; color: #dc2626; }
        .grade-f { background: #fee2e2; color: #dc2626; }

        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .chart-container {
                height: 250px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
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
                    
                    <?php if (hasPermission('admin')): ?>
                    <!-- Admin Pages -->
                    <li class="nav-item">
                        <a href="users.php" class="nav-link">
                            <i class="fas fa-users"></i>
                            <span>Users</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php" class="nav-link">
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="classes.php" class="nav-link">
                            <i class="fas fa-chalkboard"></i>
                            <span>Classes</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="subjects.php" class="nav-link">
                            <i class="fas fa-book"></i>
                            <span>Subjects</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="activity.php" class="nav-link">
                            <i class="fas fa-history"></i>
                            <span>Activity Logs</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if (hasPermission('teacher')): ?>
                    <!-- Teacher Pages -->
                    <li class="nav-item">
                        <a href="attendance.php" class="nav-link">
                            <i class="fas fa-calendar-check"></i>
                            <span>Take Attendance</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="marks.php" class="nav-link">
                            <i class="fas fa-chart-line"></i>
                            <span>Manage Marks</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php if ($_SESSION['user_type'] === 'student'): ?>
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
                        <a href="my_profile.php" class="nav-link">
                            <i class="fas fa-user"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <!-- Analytics (All Users) -->
                    <li class="nav-item">
                        <a href="analytics.php" class="nav-link active">
                            <i class="fas fa-chart-bar"></i>
                            <span>Analytics</span>
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
                        <h1 class="mb-2">Analytics Dashboard</h1>
                        <p class="text-muted mb-0">Comprehensive insights and performance analytics</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if (isset($error_message)): ?>
                <div class="alert alert-warning alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle"></i>
                    <?php echo htmlspecialchars($error_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <!-- Grade Distribution Chart -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <div class="analytics-icon">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3 class="analytics-title">Grade Distribution</h3>
                    </div>
                    
                    <div class="stats-grid">
                        <?php
                        $total_grades = array_sum(array_column($grade_data, 'count'));
                        $a_grades = 0;
                        $b_grades = 0;
                        $c_grades = 0;
                        $d_grades = 0;
                        $f_grades = 0;
                        
                        foreach ($grade_data as $grade) {
                            switch ($grade['grade']) {
                                case 'A+':
                                case 'A':
                                    $a_grades += $grade['count'];
                                    break;
                                case 'B':
                                    $b_grades += $grade['count'];
                                    break;
                                case 'C':
                                    $c_grades += $grade['count'];
                                    break;
                                case 'D':
                                    $d_grades += $grade['count'];
                                    break;
                                case 'F':
                                    $f_grades += $grade['count'];
                                    break;
                            }
                        }
                        ?>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $a_grades; ?></div>
                            <div class="stat-label">A Grades</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $b_grades; ?></div>
                            <div class="stat-label">B Grades</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $c_grades; ?></div>
                            <div class="stat-label">C Grades</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $d_grades; ?></div>
                            <div class="stat-label">D Grades</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number"><?php echo $f_grades; ?></div>
                            <div class="stat-label">F Grades</div>
                        </div>
                    </div>

                    <?php if (empty($grade_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-pie text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No Grade Data Available</h5>
                        <p class="text-muted">Grade distribution will appear here once marks are recorded.</p>
                    </div>
                    <?php else: ?>
                    <div class="chart-container">
                        <canvas id="gradeChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Attendance Trends -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <div class="analytics-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <h3 class="analytics-title">Attendance Trends</h3>
                    </div>
                    
                    <?php if (empty($attendance_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-calendar-alt text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No Attendance Data Available</h5>
                        <p class="text-muted">Attendance trends will appear here once attendance is recorded.</p>
                    </div>
                    <?php else: ?>
                    <div class="chart-container">
                        <canvas id="attendanceChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Performance Analytics -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <div class="analytics-icon">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="analytics-title">Subject Performance</h3>
                    </div>
                    
                    <?php if (empty($performance_data)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-line text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">No Performance Data Available</h5>
                        <p class="text-muted">Performance analytics will appear here once marks are recorded.</p>
                    </div>
                    <?php else: ?>
                    <div class="chart-container">
                        <canvas id="performanceChart"></canvas>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Subject Statistics Table -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <div class="analytics-icon">
                            <i class="fas fa-table"></i>
                        </div>
                        <h3 class="analytics-title">Subject Statistics</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="performance-table">
                            <thead>
                                <tr>
                                    <th>Subject</th>
                                    <th>Students</th>
                                    <th>Average Grade</th>
                                    <th>Total Exams</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subject_stats as $subject): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($subject['subject_name']); ?></strong>
                                    </td>
                                    <td><?php echo $subject['students'] ?? 0; ?></td>
                                    <td>
                                        <?php 
                                        $avg = round($subject['avg_percentage'] ?? 0, 1);
                                        $grade_class = '';
                                        if ($avg >= 90) $grade_class = 'grade-a';
                                        elseif ($avg >= 80) $grade_class = 'grade-a';
                                        elseif ($avg >= 70) $grade_class = 'grade-b';
                                        elseif ($avg >= 60) $grade_class = 'grade-c';
                                        elseif ($avg >= 50) $grade_class = 'grade-d';
                                        else $grade_class = 'grade-f';
                                        ?>
                                        <span class="grade-badge <?php echo $grade_class; ?>">
                                            <?php echo $avg; ?>%
                                        </span>
                                    </td>
                                    <td><?php echo $subject['total_exams'] ?? 0; ?></td>
                                    <td>
                                        <div class="progress" style="height: 8px;">
                                            <div class="progress-bar bg-primary" 
                                                 style="width: <?php echo min(100, $avg); ?>%"></div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?php if (hasPermission('admin') || hasPermission('teacher')): ?>
                <!-- Class Performance (Admin/Teacher Only) -->
                <div class="analytics-card">
                    <div class="analytics-header">
                        <div class="analytics-icon">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <h3 class="analytics-title">Class Performance</h3>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="performance-table">
                            <thead>
                                <tr>
                                    <th>Class</th>
                                    <th>Students</th>
                                    <th>Avg Attendance</th>
                                    <th>Avg Performance</th>
                                    <th>Overall Score</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($class_stats as $class): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>
                                    </td>
                                    <td><?php echo $class['total_students'] ?? 0; ?></td>
                                    <td>
                                        <?php 
                                        $attendance = round($class['avg_attendance'] ?? 0, 1);
                                        echo $attendance . '%';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $performance = round($class['avg_performance'] ?? 0, 1);
                                        echo $performance . '%';
                                        ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $overall = round(($attendance + $performance) / 2, 1);
                                        $score_class = '';
                                        if ($overall >= 90) $score_class = 'grade-a';
                                        elseif ($overall >= 80) $score_class = 'grade-a';
                                        elseif ($overall >= 70) $score_class = 'grade-b';
                                        elseif ($overall >= 60) $score_class = 'grade-c';
                                        elseif ($overall >= 50) $score_class = 'grade-d';
                                        else $score_class = 'grade-f';
                                        ?>
                                        <span class="grade-badge <?php echo $score_class; ?>">
                                            <?php echo $overall; ?>%
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Grade Distribution Chart
        const gradeCtx = document.getElementById('gradeChart');
        if (gradeCtx) {
            const gradeChart = new Chart(gradeCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($grade_data, 'grade')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($grade_data, 'count')); ?>,
                    backgroundColor: [
                        '#10b981', // A - Green
                        '#f59e0b', // B - Orange
                        '#f59e0b', // C - Orange
                        '#ef4444', // D - Red
                        '#ef4444'  // F - Red
                    ],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true
                        }
                    }
                }
            }
        });

        // Attendance Trends Chart
        const attendanceCtx = document.getElementById('attendanceChart');
        if (attendanceCtx) {
            const attendanceCtx2d = attendanceCtx.getContext('2d');
        
        // Process attendance data for chart
        const attendanceData = <?php echo json_encode($attendance_data); ?>;
        const months = [...new Set(array_column(attendanceData, 'month'))];
        const statuses = ['present', 'absent', 'late', 'excused'];
        
        const datasets = statuses.map(status => {
            const data = months.map(month => {
                const record = attendanceData.find(r => r.status === status && r.month === month);
                return record ? record.count : 0;
            });
            
            const colors = {
                'present': '#10b981',
                'absent': '#ef4444',
                'late': '#f59e0b',
                'excused': '#3b82f6'
            };
            
            return {
                label: status.charAt(0).toUpperCase() + status.slice(1),
                data: data,
                backgroundColor: colors[status],
                borderColor: colors[status],
                borderWidth: 2,
                fill: false
            };
        });

        const attendanceChart = new Chart(attendanceCtx2d, {
            type: 'line',
            data: {
                labels: months.map(month => {
                    const date = new Date(month + '-01');
                    return date.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                }),
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        });
        }

        // Performance Chart
        const performanceChart = new Chart(performanceCtx2d, {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_column($performance_data, 'subject_name')); ?>,
                datasets: [{
                    label: 'Average Percentage',
                    data: <?php echo json_encode(array_column($performance_data, 'avg_percentage')); ?>,
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
        }

        // Helper function for array_column equivalent
        function array_column(array, column) {
            return array.map(item => item[column]);
        }
    </script>
</body>
</html> 