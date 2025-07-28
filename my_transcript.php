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

// Get student's transcript data
$stmt = $pdo->prepare("
    SELECT 
        s.name as subject_name,
        c.name as class_name,
        COUNT(m.id) as total_exams,
        AVG((m.marks_obtained / m.total_marks) * 100) as average_percentage,
        SUM(m.marks_obtained) as total_marks_obtained,
        SUM(m.total_marks) as total_possible_marks,
        MAX(m.exam_date) as last_exam_date
    FROM subjects s
    JOIN marks m ON s.id = m.subject_id AND m.student_id = ?
    JOIN classes c ON m.class_id = c.id
    GROUP BY s.id, s.name, c.name
    ORDER BY c.name, s.name
");
$stmt->execute([$student_id]);
$transcript_data = $stmt->fetchAll();

// Calculate overall statistics
$total_subjects = count($transcript_data);
$overall_average = 0;
$total_marks_obtained = 0;
$total_possible_marks = 0;

foreach ($transcript_data as $subject) {
    $overall_average += $subject['average_percentage'];
    $total_marks_obtained += $subject['total_marks_obtained'];
    $total_possible_marks += $subject['total_possible_marks'];
}

$overall_average = $total_subjects > 0 ? round($overall_average / $total_subjects, 1) : 0;

// Calculate GPA (assuming A=4.0, B=3.0, C=2.0, D=1.0, F=0.0)
$total_gpa_points = 0;
$total_credits = 0;

foreach ($transcript_data as $subject) {
    $percentage = $subject['average_percentage'];
    $gpa_points = 0;
    
    if ($percentage >= 90) {
        $gpa_points = 4.0;
    } elseif ($percentage >= 80) {
        $gpa_points = 3.0;
    } elseif ($percentage >= 70) {
        $gpa_points = 2.0;
    } elseif ($percentage >= 60) {
        $gpa_points = 1.0;
    } else {
        $gpa_points = 0.0;
    }
    
    $total_gpa_points += $gpa_points;
    $total_credits += 1; // Assuming 1 credit per subject
}

$gpa = $total_credits > 0 ? round($total_gpa_points / $total_credits, 2) : 0.00;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transcript - Student Management System</title>
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
        
        /* Metric Cards */
        .metric-card {
            background: var(--white);
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
            height: 100%;
            transition: all 0.25s ease-in-out;
        }
        
        .metric-card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            transform: translateY(-2px);
        }
        
        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-blue), #6366f1);
        }
        
        .metric-card .metric-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-blue-light), var(--primary-blue-light));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            color: var(--primary-blue);
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .metric-card .metric-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--gray-900);
            line-height: 1;
            margin-bottom: 8px;
        }
        
        .metric-card .metric-label {
            font-size: 0.875rem;
            color: var(--gray-600);
            font-weight: 500;
            margin-bottom: 0;
        }
        
        /* Data Table - FIXED VERSION */
        .data-table {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .table-header {
            background: var(--gray-50);
            padding: 24px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .table-header h5 {
            margin: 0;
            color: var(--gray-900);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .table-header h5 i {
            color: var(--primary-blue);
        }
        
        /* Table Responsive Container */
        .table-container {
            overflow-x: auto;
            width: 100%;
        }
        
        /* Modern Table */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 0.875rem;
            background: white;
        }
        
        .modern-table thead {
            background: #f8fafc;
        }
        
        .modern-table th {
            padding: 20px 24px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
            white-space: nowrap;
        }
        
        .modern-table td {
            padding: 20px 24px;
            color: #1f2937;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }
        
        .modern-table tbody tr:hover {
            background: #f8fafc;
            transition: background-color 0.2s ease;
        }
        
        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        /* Grade Badges */
        .grade-badge {
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
            min-width: 80px;
            justify-content: center;
        }
        
        .grade-badge.grade-a {
            background: #d1fae5;
            color: #059669;
        }
        
        .grade-badge.grade-b {
            background: #dbeafe;
            color: #2563eb;
        }
        
        .grade-badge.grade-c {
            background: #fef3c7;
            color: #d97706;
        }
        
        .grade-badge.grade-d {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .grade-badge.grade-f {
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
        
        /* Subject styling */
        .subject-name {
            font-weight: 600;
            color: #1f2937;
            line-height: 1.2;
        }
        
        .class-name {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        .percentage {
            font-weight: 600;
            color: #1f2937;
        }
        
        .gpa-score {
            font-weight: 600;
            color: #1f2937;
        }
        
        .exam-count {
            font-size: 0.75rem;
            color: #6b7280;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .main-content {
                margin-left: 0;
            }
        }
        
        @media (max-width: 768px) {
            .modern-table th,
            .modern-table td {
                padding: 12px 16px;
                font-size: 0.8rem;
            }
            
            .grade-badge {
                padding: 4px 8px;
                font-size: 0.7rem;
                min-width: 60px;
            }
            
            .metric-card {
                padding: 16px;
            }
            
            .metric-card .metric-number {
                font-size: 1.5rem;
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
                        <a href="my_transcript.php" class="nav-link active">
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
                            <h1 class="mb-2">My Transcript</h1>
                        <p class="text-muted mb-0">View your academic transcript and performance summary</p>
                        </div>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                </div>

                <!-- Transcript Statistics -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="metric-number"><?php echo $overall_average; ?>%</div>
                            <div class="metric-label">OVERALL AVERAGE</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="metric-number"><?php echo $gpa; ?></div>
                            <div class="metric-label">GPA</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <div class="metric-number"><?php echo $total_subjects; ?></div>
                            <div class="metric-label">SUBJECTS</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-trophy"></i>
                            </div>
                            <div class="metric-number"><?php echo $total_marks_obtained; ?></div>
                            <div class="metric-label">TOTAL MARKS</div>
                        </div>
                    </div>
                </div>

                <!-- Transcript Records -->
                <div class="data-table">
                    <div class="table-header">
                        <h5><i class="fas fa-list"></i> Academic Transcript</h5>
                    </div>
                    
                    <?php if (empty($transcript_data)): ?>
                        <div class="empty-state">
                            <i class="fas fa-file-alt"></i>
                            <h4>No Transcript Data</h4>
                            <p>Your transcript data will appear here once you have marks records.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Subject</th>
                                        <th>Class</th>
                                        <th>Average</th>
                                        <th>Grade</th>
                                        <th>GPA</th>
                                        <th>Exams</th>
                                        <th>Total Marks</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($transcript_data as $subject): ?>
                                    <?php 
                                    $percentage = round($subject['average_percentage'], 1);
                                    $grade = '';
                                    $grade_class = '';
                                    $gpa_points = 0;
                                    
                                    if ($percentage >= 90) {
                                        $grade = 'A';
                                        $grade_class = 'grade-a';
                                        $gpa_points = 4.0;
                                    } elseif ($percentage >= 80) {
                                        $grade = 'B';
                                        $grade_class = 'grade-b';
                                        $gpa_points = 3.0;
                                    } elseif ($percentage >= 70) {
                                        $grade = 'C';
                                        $grade_class = 'grade-c';
                                        $gpa_points = 2.0;
                                    } elseif ($percentage >= 60) {
                                        $grade = 'D';
                                        $grade_class = 'grade-d';
                                        $gpa_points = 1.0;
                                    } else {
                                        $grade = 'F';
                                        $grade_class = 'grade-f';
                                        $gpa_points = 0.0;
                                    }
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="subject-name"><?php echo htmlspecialchars($subject['subject_name']); ?></div>
                                        </td>
                                        <td>
                                            <span class="class-name"><?php echo htmlspecialchars($subject['class_name']); ?></span>
                                        </td>
                                        <td>
                                            <span class="percentage"><?php echo $percentage; ?>%</span>
                                        </td>
                                        <td>
                                            <span class="grade-badge <?php echo $grade_class; ?>">
                                                <?php echo $grade; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="gpa-score"><?php echo $gpa_points; ?></span>
                                        </td>
                                        <td>
                                            <span class="exam-count"><?php echo $subject['total_exams']; ?> exams</span>
                                        </td>
                                        <td>
                                            <span style="color: #6b7280;"><?php echo $subject['total_marks_obtained']; ?>/<?php echo $subject['total_possible_marks']; ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
                </div>
            </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 