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

// Get recent attendance updates
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        s.name as subject_name,
        c.name as class_name,
        u.full_name as recorded_by_name
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    JOIN users u ON a.recorded_by = u.id
    WHERE a.student_id = ? AND a.date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    ORDER BY a.date DESC, a.created_at DESC
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_attendance = $stmt->fetchAll();

// Get recent marks updates
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        s.name as subject_name,
        c.name as class_name,
        u.full_name as recorded_by_name
    FROM marks m
    JOIN subjects s ON m.subject_id = s.id
    JOIN classes c ON m.class_id = c.id
    JOIN users u ON m.recorded_by = u.id
    WHERE m.student_id = ? AND m.exam_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ORDER BY m.exam_date DESC, m.created_at DESC
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_marks = $stmt->fetchAll();

// Combine and sort notifications
$notifications = [];
foreach ($recent_attendance as $attendance) {
    $notifications[] = [
        'type' => 'attendance',
        'title' => 'Attendance Recorded',
        'message' => "Your attendance for {$attendance['subject_name']} has been recorded as " . ucfirst($attendance['status']),
        'date' => $attendance['date'],
        'created_at' => $attendance['created_at'],
        'recorded_by' => $attendance['recorded_by_name'],
        'subject' => $attendance['subject_name'],
        'class' => $attendance['class_name'],
        'status' => $attendance['status'],
        'priority' => $attendance['status'] === 'absent' ? 'high' : 'medium',
        'is_read' => 0 // For demo purposes, all notifications are unread
    ];
}

foreach ($recent_marks as $mark) {
    $percentage = $mark['total_marks'] > 0 ? round(($mark['marks_obtained'] / $mark['total_marks']) * 100, 1) : 0;
    $notifications[] = [
        'type' => 'marks',
        'title' => 'Marks Recorded',
        'message' => "Your marks for {$mark['subject_name']} have been recorded: {$mark['marks_obtained']}/{$mark['total_marks']} ({$percentage}%)",
        'date' => $mark['exam_date'],
        'created_at' => $mark['created_at'],
        'recorded_by' => $mark['recorded_by_name'],
        'subject' => $mark['subject_name'],
        'class' => $mark['class_name'],
        'marks_obtained' => $mark['marks_obtained'],
        'total_marks' => $mark['total_marks'],
        'percentage' => $percentage,
        'priority' => $percentage < 60 ? 'high' : ($percentage < 80 ? 'medium' : 'low'),
        'is_read' => 0 // For demo purposes, all notifications are unread
    ];
}

// Sort by date (newest first)
usort($notifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});

// Calculate notification statistics
$total_notifications = count($notifications);
$unread_notifications = $total_notifications; // For demo purposes, all notifications are unread
$read_notifications = 0;
$important_notifications = 0;

foreach ($notifications as $notification) {
    if ($notification['priority'] == 'high') {
        $important_notifications++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - Student Management System</title>
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
            display: flex;
            justify-content: between;
            align-items: center;
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
        
        .table-actions {
            display: flex;
            gap: 0.5rem;
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
        
        /* Priority Badges */
        .priority-badge {
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
        
        .priority-badge.high {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .priority-badge.medium {
            background: #fef3c7;
            color: #d97706;
        }
        
        .priority-badge.low {
            background: #d1fae5;
            color: #059669;
        }
        
        /* Read/Unread Status */
        .notification-unread {
            background: #f0f9ff;
            border-left: 4px solid var(--primary-blue);
        }
        
        .notification-read {
            background: white;
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
        
        /* Notification styling */
        .notification-title {
            font-weight: 600;
            color: #1f2937;
            line-height: 1.2;
            margin-bottom: 0.25rem;
        }
        
        .notification-message {
            color: #6b7280;
            font-size: 0.875rem;
            line-height: 1.4;
        }
        
        .notification-meta {
            font-size: 0.75rem;
            color: #9ca3af;
            margin-top: 0.5rem;
        }
        
        .sender-name {
            font-weight: 500;
            color: #1f2937;
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
            
            .priority-badge {
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
                        <a href="my_transcript.php" class="nav-link">
                            <i class="fas fa-file-alt"></i>
                            <span>My Transcript</span>
                        </a>
                    </li>
                    
                    <li class="nav-item">
                        <a href="my_notifications.php" class="nav-link active">
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
                            <h1 class="mb-2">My Notifications</h1>
                        <p class="text-muted mb-0">View and manage your notifications</p>
                        </div>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Back to Dashboard
                            </a>
                </div>

                <!-- Notification Statistics -->
                <div class="row mb-5">
                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-bell"></i>
                            </div>
                            <div class="metric-number"><?php echo $total_notifications; ?></div>
                            <div class="metric-label">TOTAL NOTIFICATIONS</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-envelope-open"></i>
                            </div>
                            <div class="metric-number"><?php echo $unread_notifications; ?></div>
                            <div class="metric-label">UNREAD</div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="metric-number"><?php echo $read_notifications; ?></div>
                            <div class="metric-label">READ</div>
                        </div>
                    </div>

                    <div class="col-md-3 mb-4">
                        <div class="metric-card">
                            <div class="metric-icon">
                                <i class="fas fa-exclamation-triangle"></i>
                            </div>
                            <div class="metric-number"><?php echo $important_notifications; ?></div>
                            <div class="metric-label">IMPORTANT</div>
                        </div>
                                                </div>
                                            </div>

                <!-- Notifications List -->
                <div class="data-table">
                    <div class="table-header">
                        <h5><i class="fas fa-list"></i> Recent Activity</h5>
                                    </div>
                    
                    <?php if (empty($notifications)): ?>
                        <div class="empty-state">
                            <i class="fas fa-bell-slash"></i>
                            <h4>No Notifications</h4>
                            <p>You don't have any notifications at the moment.</p>
                                        </div>
                    <?php else: ?>
                        <div class="table-container">
                            <table class="modern-table">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Priority</th>
                                        <th>Sender</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                    <tr class="<?php echo $notification['is_read'] ? 'notification-read' : 'notification-unread'; ?>">
                                        <td>
                                            <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        </td>
                                        <td>
                                            <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                        </td>
                                        <td>
                                            <?php 
                                            $priority_class = '';
                                            switch ($notification['priority']) {
                                                case 'high': 
                                                    $priority_class = 'high'; 
                                                    break;
                                                case 'medium': 
                                                    $priority_class = 'medium'; 
                                                    break;
                                                case 'low': 
                                                    $priority_class = 'low'; 
                                                    break;
                                            }
                                            ?>
                                            <span class="priority-badge <?php echo $priority_class; ?>">
                                                <?php echo ucfirst($notification['priority']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="sender-name"><?php echo htmlspecialchars($notification['recorded_by']); ?></span>
                                        </td>
                                        <td>
                                            <div class="notification-meta">
                                                <?php echo date('M j, Y', strtotime($notification['created_at'])); ?>
                                                <br>
                                                <small><?php echo date('g:i A', strtotime($notification['created_at'])); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span style="color: var(--primary-blue);"><i class="fas fa-circle"></i> Unread</span>
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