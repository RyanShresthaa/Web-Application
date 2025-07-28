<?php
// includes/sidebar.php
if (!isset($_SESSION)) session_start();
require_once __DIR__ . '/functions.php';

// Get current page name for active state
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$user_type = $_SESSION['user_type'] ?? 'user';
?>

<aside class="sidebar">
    <div class="sidebar-header">
        <div class="sidebar-brand">
            <i class="fas fa-graduation-cap"></i>
            <span>Student Management System</span>
        </div>
    </div>
    
    <nav class="sidebar-nav">
        <ul class="nav-list">
            <!-- Dashboard - All users -->
            <li class="nav-item">
                <a href="dashboard.php" class="nav-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            
            <!-- Admin Only Pages -->
            <?php if ($user_type === 'admin'): ?>
            <li class="nav-item">
                <a href="users.php" class="nav-link <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Users</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="classes.php" class="nav-link <?php echo $current_page === 'classes' ? 'active' : ''; ?>">
                    <i class="fas fa-chalkboard"></i>
                    <span>Classes</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="subjects.php" class="nav-link <?php echo $current_page === 'subjects' ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i>
                    <span>Subjects</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="activity.php" class="nav-link <?php echo $current_page === 'activity' ? 'active' : ''; ?>">
                    <i class="fas fa-history"></i>
                    <span>Activity</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Admin and Teacher Pages -->
            <?php if (in_array($user_type, ['admin', 'teacher'])): ?>
            <li class="nav-item">
                <a href="students.php" class="nav-link <?php echo $current_page === 'students' ? 'active' : ''; ?>">
                    <i class="fas fa-user-graduate"></i>
                    <span>Students</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="attendance.php" class="nav-link <?php echo $current_page === 'attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Attendance</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="marks.php" class="nav-link <?php echo $current_page === 'marks' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>Marks</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Student Only Pages -->
            <?php if ($user_type === 'student'): ?>
            <li class="nav-item">
                <a href="my_attendance.php" class="nav-link <?php echo $current_page === 'my_attendance' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>My Attendance</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="my_marks.php" class="nav-link <?php echo $current_page === 'my_marks' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-line"></i>
                    <span>My Marks</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="my_transcript.php" class="nav-link <?php echo $current_page === 'my_transcript' ? 'active' : ''; ?>">
                    <i class="fas fa-file-alt"></i>
                    <span>My Transcript</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="my_notifications.php" class="nav-link <?php echo $current_page === 'my_notifications' ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i>
                    <span>Notifications</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="my_schedule.php" class="nav-link <?php echo $current_page === 'my_schedule' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-alt"></i>
                    <span>My Schedule</span>
                </a>
            </li>
            
            <li class="nav-item">
                <a href="my_profile.php" class="nav-link <?php echo $current_page === 'my_profile' ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i>
                    <span>My Profile</span>
                </a>
            </li>
            <?php endif; ?>
            
            <!-- Analytics - All Users -->
            <li class="nav-item">
                <a href="analytics.php" class="nav-link <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
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