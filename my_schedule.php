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

// Get subjects for enrolled classes (assuming all subjects are available to all classes for demo)
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as teacher_name
    FROM subjects s
    JOIN users u ON s.teacher_id = u.id
    WHERE s.is_active = 1
    ORDER BY s.name
");
$stmt->execute();
$subjects = $stmt->fetchAll();

// Create a sample schedule (in a real system, this would come from a schedule table)
$schedule = [];
$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
$time_slots = [
    '08:00 - 09:00' => 'Period 1',
    '09:00 - 10:00' => 'Period 2', 
    '10:00 - 11:00' => 'Period 3',
    '11:00 - 12:00' => 'Period 4',
    '12:00 - 13:00' => 'Lunch Break',
    '13:00 - 14:00' => 'Period 5',
    '14:00 - 15:00' => 'Period 6',
    '15:00 - 16:00' => 'Period 7'
];

// Generate sample schedule data
foreach ($days as $day) {
    $schedule[$day] = [];
    foreach ($time_slots as $time => $period) {
        if ($period === 'Lunch Break') {
            $schedule[$day][$time] = [
                'type' => 'break',
                'name' => 'Lunch Break',
                'teacher' => '',
                'room' => 'Cafeteria'
            ];
        } else {
            // Randomly assign subjects to time slots
            $subject = $subjects[array_rand($subjects)];
            $schedule[$day][$time] = [
                'type' => 'class',
                'name' => $subject['name'],
                'teacher' => $subject['teacher_name'],
                'room' => 'Room ' . rand(101, 305)
            ];
        }
    }
}

$total_classes = count($enrolled_classes);
$total_subjects = count($subjects);
$total_periods = count($time_slots);
$total_days = count($days);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedule - Student Management System</title>
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
        
        /* Schedule Table */
        .schedule-table {
            background: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .schedule-header {
            background: var(--gray-50);
            padding: 24px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .schedule-header h5 {
            margin: 0;
            color: var(--gray-900);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .schedule-header h5 i {
            color: var(--primary-blue);
        }
        
        /* Schedule Grid */
        .schedule-grid {
            overflow-x: auto;
            width: 100%;
        }
        
        .schedule-table-inner {
            width: 100%;
            border-collapse: collapse;
            margin: 0;
            font-size: 0.875rem;
            background: white;
        }
        
        .schedule-table-inner th {
            padding: 16px 12px;
            text-align: center;
            font-weight: 600;
            color: #374151;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.75rem;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            white-space: nowrap;
            min-width: 120px;
        }
        
        .schedule-table-inner td {
            padding: 12px;
            color: #1f2937;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
            text-align: center;
            min-width: 120px;
        }
        
        .schedule-table-inner td:hover {
            background: #f8fafc;
            transition: background-color 0.2s ease;
        }
        
        /* Schedule Cell Styling */
        .schedule-cell {
            padding: 8px;
            border-radius: 8px;
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        .schedule-cell.class {
            background: #dbeafe;
            color: #1e40af;
            border: 1px solid #bfdbfe;
        }
        
        .schedule-cell.break {
            background: #fef3c7;
            color: #92400e;
            border: 1px solid #fde68a;
        }
        
        .subject-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .teacher-name {
            font-size: 0.7rem;
            opacity: 0.8;
        }
        
        .room-info {
            font-size: 0.7rem;
            opacity: 0.7;
            margin-top: 2px;
        }
        
        .time-slot {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            font-size: 0.7rem;
            padding: 8px 4px;
            border-radius: 4px;
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
            .schedule-table-inner th,
            .schedule-table-inner td {
                padding: 8px 6px;
                font-size: 0.7rem;
                min-width: 80px;
            }
            
            .schedule-cell {
                padding: 4px;
                font-size: 0.65rem;
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
                        <a href="my_schedule.php" class="nav-link active">
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
                            <h1 class="mb-2">My Schedule</h1>
                        <p class="text-muted mb-0">View your weekly class schedule</p>
                        </div>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                </div>

                <!-- Schedule Statistics -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-chalkboard"></i>
                            </div>
                            <div class="metric-number"><?php echo $total_classes; ?></div>
                            <div class="metric-label">ENROLLED CLASSES</div>
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
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="metric-number"><?php echo $total_periods; ?></div>
                            <div class="metric-label">PERIODS PER DAY</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-calendar-week"></i>
                            </div>
                            <div class="metric-number"><?php echo $total_days; ?></div>
                            <div class="metric-label">SCHOOL DAYS</div>
                                    </div>
                                </div>
                            </div>
                            
                <!-- Weekly Schedule -->
                <div class="schedule-table">
                    <div class="schedule-header">
                        <h5><i class="fas fa-calendar-alt"></i> Weekly Schedule</h5>
                    </div>
                    
                    <?php if (empty($enrolled_classes)): ?>
                        <div class="empty-state">
                            <i class="fas fa-calendar-times"></i>
                            <h4>No Schedule Available</h4>
                            <p>You are not enrolled in any classes yet.</p>
                                        </div>
                    <?php else: ?>
                        <div class="schedule-grid">
                            <table class="schedule-table-inner">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <?php foreach ($days as $day): ?>
                                            <th><?php echo $day; ?></th>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($time_slots as $time => $period): ?>
                                    <tr>
                                        <td class="time-slot"><?php echo $time; ?></td>
                                        <?php foreach ($days as $day): ?>
                                            <td>
                                                <?php 
                                                $slot = $schedule[$day][$time];
                                                $cell_class = $slot['type'] === 'class' ? 'class' : 'break';
                                                ?>
                                                <div class="schedule-cell <?php echo $cell_class; ?>">
                                                    <?php if ($slot['type'] === 'class'): ?>
                                                        <div class="subject-name"><?php echo htmlspecialchars($slot['name']); ?></div>
                                                        <div class="teacher-name"><?php echo htmlspecialchars($slot['teacher']); ?></div>
                                                        <div class="room-info"><?php echo htmlspecialchars($slot['room']); ?></div>
                                                    <?php else: ?>
                                                        <div class="subject-name"><?php echo htmlspecialchars($slot['name']); ?></div>
                                                        <div class="room-info"><?php echo htmlspecialchars($slot['room']); ?></div>
                                            <?php endif; ?>
                                        </div>
                                            </td>
                                        <?php endforeach; ?>
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