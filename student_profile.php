<?php
session_start();
require_once 'includes/functions.php';

// Require authentication
requireAuth();

// Check if user has permission to view students
if (!hasPermission('teacher')) {
    header("Location: dashboard.php?error=insufficient_permissions");
    exit();
}

$student_id = $_GET['id'] ?? 0;
if (!$student_id) {
    header("Location: students.php?error=invalid_student");
    exit();
}

$pdo = getDBConnection();

// Get student information
$stmt = $pdo->prepare("
    SELECT u.*, sp.* 
    FROM users u 
    LEFT JOIN student_profiles sp ON u.id = sp.student_id 
    WHERE u.id = ? AND u.user_type = 'student'
");
$stmt->execute([$student_id]);
$student = $stmt->fetch();

if (!$student) {
    header("Location: students.php?error=student_not_found");
    exit();
}

// Get student enrollments
$stmt = $pdo->prepare("
    SELECT se.*, c.name as class_name, c.grade_level, c.academic_year
    FROM student_enrollments se
    JOIN classes c ON se.class_id = c.id
    WHERE se.student_id = ?
    ORDER BY se.enrollment_date DESC
");
$stmt->execute([$student_id]);
$enrollments = $stmt->fetchAll();

// Get recent attendance
$stmt = $pdo->prepare("
    SELECT a.*, s.name as subject_name, c.name as class_name
    FROM attendance a
    JOIN subjects s ON a.subject_id = s.id
    JOIN classes c ON a.class_id = c.id
    WHERE a.student_id = ?
    ORDER BY a.date DESC
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_attendance = $stmt->fetchAll();

// Get recent marks
$stmt = $pdo->prepare("
    SELECT m.*, s.name as subject_name, c.name as class_name
    FROM marks m
    JOIN subjects s ON m.subject_id = s.id
    JOIN classes c ON m.class_id = c.id
    WHERE m.student_id = ?
    ORDER BY m.exam_date DESC
    LIMIT 10
");
$stmt->execute([$student_id]);
$recent_marks = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Profile - Student Management System</title>
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
        .student-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 2rem;
            font-weight: bold;
        }
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-active { background: #28a745; color: white; }
        .status-inactive { background: #6c757d; color: white; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-graduation-cap"></i> Student Management System
            </a>
            
            <div class="navbar-nav ms-auto">
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="<?php echo getUserTypeIcon($_SESSION['user_type']); ?>"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="badge bg-warning">Teacher</span>
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a></li>
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
                            <a class="nav-link active" href="students.php">
                                <i class="fas fa-user-graduate"></i> Students
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
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-user-graduate"></i> Student Profile</h2>
                        <a href="students.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Students
                        </a>
                    </div>

                    <!-- Student Information -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user"></i> Student Information</h5>
                                </div>
                                <div class="card-body text-center">
                                    <div class="student-avatar mx-auto mb-3">
                                        <?php echo strtoupper(substr($student['full_name'], 0, 1)); ?>
                                    </div>
                                    <h4><?php echo htmlspecialchars($student['full_name']); ?></h4>
                                    <p class="text-muted"><?php echo htmlspecialchars($student['email']); ?></p>
                                    <span class="status-badge status-<?php echo $student['is_active'] ? 'active' : 'inactive'; ?>">
                                        <?php echo $student['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Personal Details</h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p><strong>Roll Number:</strong> <?php echo htmlspecialchars($student['roll_number'] ?? 'Not assigned'); ?></p>
                                            <p><strong>Date of Birth:</strong> <?php echo $student['date_of_birth'] ? date('M d, Y', strtotime($student['date_of_birth'])) : 'Not provided'; ?></p>
                                            <p><strong>Gender:</strong> <?php echo ucfirst($student['gender'] ?? 'Not specified'); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p><strong>Blood Group:</strong> <?php echo htmlspecialchars($student['blood_group'] ?? 'Not specified'); ?></p>
                                            <p><strong>Admission Date:</strong> <?php echo $student['admission_date'] ? date('M d, Y', strtotime($student['admission_date'])) : 'Not provided'; ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($student['address'] ?? 'Not provided'); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Parent Information -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-users"></i> Parent Information</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Parent Name:</strong> <?php echo htmlspecialchars($student['parent_name'] ?? 'Not provided'); ?></p>
                                    <p><strong>Parent Phone:</strong> <?php echo htmlspecialchars($student['parent_phone'] ?? 'Not provided'); ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Parent Email:</strong> <?php echo htmlspecialchars($student['parent_email'] ?? 'Not provided'); ?></p>
                                    <p><strong>Emergency Contact:</strong> <?php echo htmlspecialchars($student['emergency_contact'] ?? 'Not provided'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Enrollments -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Class Enrollments</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($enrollments)): ?>
                                <p class="text-muted">No class enrollments found.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Class</th>
                                                <th>Grade Level</th>
                                                <th>Academic Year</th>
                                                <th>Enrollment Date</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($enrollments as $enrollment): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($enrollment['class_name']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['grade_level']); ?></td>
                                                <td><?php echo htmlspecialchars($enrollment['academic_year']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $enrollment['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                        <?php echo ucfirst($enrollment['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Activity -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-calendar-check"></i> Recent Attendance</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_attendance)): ?>
                                        <p class="text-muted">No recent attendance records.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Subject</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_attendance as $attendance): ?>
                                                    <tr>
                                                        <td><?php echo date('M d', strtotime($attendance['date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($attendance['subject_name']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $attendance['status'] === 'present' ? 'success' : 
                                                                    ($attendance['status'] === 'absent' ? 'danger' : 
                                                                    ($attendance['status'] === 'late' ? 'warning' : 'secondary')); 
                                                            ?>">
                                                                <?php echo ucfirst($attendance['status']); ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Recent Marks</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_marks)): ?>
                                        <p class="text-muted">No recent marks records.</p>
                                    <?php else: ?>
                                        <div class="table-responsive">
                                            <table class="table table-sm">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Subject</th>
                                                        <th>Marks</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_marks as $mark): ?>
                                                    <tr>
                                                        <td><?php echo date('M d', strtotime($mark['exam_date'])); ?></td>
                                                        <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                                        <td>
                                                            <span class="fw-bold">
                                                                <?php echo $mark['marks_obtained']; ?>/<?php echo $mark['total_marks']; ?>
                                                                (<?php echo number_format($mark['percentage'], 1); ?>%)
                                                            </span>
                                                        </td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
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

