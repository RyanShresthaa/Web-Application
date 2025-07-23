<?php
session_start();
require_once 'includes/functions.php';

// Require teacher permissions
requireUserType('teacher');

$teacher_id = $_SESSION['user_id'];
$teacher_classes = getTeacherClasses($teacher_id);
$teacher_subjects = getTeacherSubjects($teacher_id);
$message = '';
$error = '';

// Handle attendance submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_id = $_POST['class_id'] ?? '';
    $subject_id = $_POST['subject_id'] ?? '';
    $date = $_POST['date'] ?? '';
    $attendance_data = $_POST['attendance'] ?? [];
    
    if (empty($class_id) || empty($subject_id) || empty($date)) {
        $error = 'Please fill in all required fields.';
    } else {
        $success_count = 0;
        foreach ($attendance_data as $student_id => $status) {
            $result = addAttendance($student_id, $class_id, $subject_id, $date, $status, '', $teacher_id);
            if ($result['success']) {
                $success_count++;
            }
        }
        
        if ($success_count > 0) {
            $message = "Attendance recorded successfully for $success_count students.";
        } else {
            $error = 'Failed to record attendance.';
        }
    }
}

// Get selected class and subject for filtering
$selected_class = $_GET['class_id'] ?? '';
$selected_subject = $_GET['subject_id'] ?? '';
$selected_date = $_GET['date'] ?? date('Y-m-d');

$class_students = [];
if ($selected_class && $selected_subject) {
    $class_students = getClassStudents($selected_class);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - Student Management System</title>
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
        @media (max-width: 991.98px) {
            .main-content {
                padding: 10px;
            }
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
        .btn-custom {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .attendance-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-present {
            background: #28a745;
            color: white;
        }
        .status-absent {
            background: #dc3545;
            color: white;
        }
        .status-late {
            background: #ffc107;
            color: black;
        }
        .status-excused {
            background: #6c757d;
            color: white;
        }
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
                        <span class="user-type-badge user-type-<?php echo $_SESSION['user_type']; ?>">
                            <?php echo getUserTypeDisplayName($_SESSION['user_type']); ?>
                        </span>
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
                            <a class="nav-link active" href="attendance.php">
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

                    <?php if ($message): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <h2 class="mb-4">
                        <i class="fas fa-calendar-check"></i> Attendance Management
                    </h2>

                    <!-- Filter Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-filter"></i> Select Class and Subject</h5>
                        </div>
                        <div class="card-body">
                            <form method="GET" action="">
                                <div class="row">
                                    <div class="col-md-4">
                                        <label for="class_id" class="form-label">Class</label>
                                        <select class="form-select" id="class_id" name="class_id" required>
                                            <option value="">Select Class</option>
                                            <?php foreach ($teacher_classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>" <?php echo $selected_class == $class['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($class['name']); ?> (<?php echo htmlspecialchars($class['grade_level']); ?>)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="subject_id" class="form-label">Subject</label>
                                        <select class="form-select" id="subject_id" name="subject_id" required>
                                            <option value="">Select Subject</option>
                                            <?php foreach ($teacher_subjects as $subject): ?>
                                            <option value="<?php echo $subject['id']; ?>" <?php echo $selected_subject == $subject['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($subject['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="date" class="form-label">Date</label>
                                        <input type="date" class="form-control" id="date" name="date" value="<?php echo $selected_date; ?>" required>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary btn-custom">
                                        <i class="fas fa-search"></i> Load Students
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <!-- Attendance Form -->
                    <?php if (!empty($class_students)): ?>
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-users"></i> Record Attendance
                                <small class="text-white-50">
                                    - <?php echo count($class_students); ?> students
                                </small>
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="">
                                <input type="hidden" name="class_id" value="<?php echo $selected_class; ?>">
                                <input type="hidden" name="subject_id" value="<?php echo $selected_subject; ?>">
                                <input type="hidden" name="date" value="<?php echo $selected_date; ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Student</th>
                                                <th>Roll Number</th>
                                                <th>Attendance Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($class_students as $student): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($student['full_name']); ?></strong>
                                                    <br><small class="text-muted">@<?php echo htmlspecialchars($student['username']); ?></small>
                                                </td>
                                                <td>
                                                    <code><?php echo htmlspecialchars($student['roll_number'] ?? 'N/A'); ?></code>
                                                </td>
                                                <td>
                                                    <select class="form-select" name="attendance[<?php echo $student['id']; ?>]" required>
                                                        <option value="present">Present</option>
                                                        <option value="absent">Absent</option>
                                                        <option value="late">Late</option>
                                                        <option value="excused">Excused</option>
                                                    </select>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <div class="mt-3">
                                    <button type="submit" class="btn btn-success btn-custom">
                                        <i class="fas fa-save"></i> Save Attendance
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Attendance Status Guide</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <span class="attendance-status status-present">Present</span>
                                            <small class="d-block text-muted">Student attended the class</small>
                                        </div>
                                        <div class="col-6">
                                            <span class="attendance-status status-absent">Absent</span>
                                            <small class="d-block text-muted">Student was not present</small>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <span class="attendance-status status-late">Late</span>
                                            <small class="d-block text-muted">Student arrived late</small>
                                        </div>
                                        <div class="col-6">
                                            <span class="attendance-status status-excused">Excused</span>
                                            <small class="d-block text-muted">Student had valid excuse</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-lightbulb"></i> Tips</h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li><i class="fas fa-check text-success"></i> Select class and subject first</li>
                                        <li><i class="fas fa-check text-success"></i> Choose the correct date</li>
                                        <li><i class="fas fa-check text-success"></i> Mark attendance for all students</li>
                                        <li><i class="fas fa-check text-success"></i> Save attendance before leaving</li>
                                    </ul>
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