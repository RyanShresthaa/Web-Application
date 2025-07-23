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

// Get student's attendance records
$stmt = $pdo->prepare("
    SELECT a.*, s.name as subject_name, c.name as class_name, u.full_name as recorded_by_name
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    JOIN users u ON a.recorded_by = u.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC, s.name
");
$stmt->execute([$student_id]);
$attendance_records = $stmt->fetchAll();

// Calculate attendance statistics
$total_days = count($attendance_records);
$present_days = 0;
$absent_days = 0;
$late_days = 0;
$excused_days = 0;

foreach ($attendance_records as $record) {
    switch ($record['status']) {
        case 'present':
            $present_days++;
            break;
        case 'absent':
            $absent_days++;
            break;
        case 'late':
            $late_days++;
            break;
        case 'excused':
            $excused_days++;
            break;
    }
}

$attendance_percentage = $total_days > 0 ? round((($present_days + $late_days) / $total_days) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - Student Management System</title>
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
        .main-content {
            padding: 20px;
        }
        @media (max-width: 991.98px) {
            .main-content {
                padding: 10px;
            }
        }
        .navbar-brand { font-weight: 600; }
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
        .status-badge {
            font-weight: 600;
        }
        .status-present { background-color: #28a745; }
        .status-absent { background-color: #dc3545; }
        .status-late { background-color: #ffc107; color: #000; }
        .status-excused { background-color: #6c757d; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users"></i> Student Management System
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user-graduate"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="badge bg-success">Student</span>
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
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my_attendance.php">
                                <i class="fas fa-calendar-check"></i> My Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="my_marks.php">
                                <i class="fas fa-chart-bar"></i> My Marks
                            </a>
                        </li>
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
                <div class="p-4">
                    <h2 class="mb-4">
                        <i class="fas fa-calendar-check"></i> My Attendance
                        <small class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small>
                    </h2>

                    <!-- Attendance Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-percentage fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $attendance_percentage; ?>%</div>
                                    <div>Attendance Rate</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-check fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $present_days; ?></div>
                                    <div>Present Days</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $absent_days; ?></div>
                                    <div>Absent Days</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-clock fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $late_days; ?></div>
                                    <div>Late Days</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Attendance Records -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Attendance Records</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attendance_records)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No attendance records found</h5>
                                    <p class="text-muted">Your attendance records will appear here once teachers start recording them.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Subject</th>
                                                <th>Class</th>
                                                <th>Status</th>
                                                <th>Remarks</th>
                                                <th>Recorded By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($attendance_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                                <td>
                                                    <span class="badge status-badge status-<?php echo $record['status']; ?>">
                                                        <?php echo ucfirst($record['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['remarks'] ?? 'No remarks'); ?></td>
                                                <td><?php echo htmlspecialchars($record['recorded_by_name']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Attendance Summary -->
                    <?php if (!empty($attendance_records)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Attendance Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Attendance Breakdown:</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Present
                                            <span class="badge bg-success rounded-pill"><?php echo $present_days; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Late
                                            <span class="badge bg-warning rounded-pill"><?php echo $late_days; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Absent
                                            <span class="badge bg-danger rounded-pill"><?php echo $absent_days; ?></span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            Excused
                                            <span class="badge bg-secondary rounded-pill"><?php echo $excused_days; ?></span>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <h6>Attendance Rate:</h6>
                                    <div class="progress mb-3" style="height: 30px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $attendance_percentage; ?>%" 
                                             aria-valuenow="<?php echo $attendance_percentage; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $attendance_percentage; ?>%
                                        </div>
                                    </div>
                                    <p class="text-muted">
                                        <?php if ($attendance_percentage >= 90): ?>
                                            <i class="fas fa-star text-warning"></i> Excellent attendance!
                                        <?php elseif ($attendance_percentage >= 80): ?>
                                            <i class="fas fa-thumbs-up text-success"></i> Good attendance
                                        <?php elseif ($attendance_percentage >= 70): ?>
                                            <i class="fas fa-exclamation-triangle text-warning"></i> Average attendance
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-circle text-danger"></i> Poor attendance - needs improvement
                                        <?php endif; ?>
                                    </p>
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