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

// Get student's marks records
$stmt = $pdo->prepare("
    SELECT m.*, s.name as subject_name, c.name as class_name, u.full_name as recorded_by_name
    FROM marks m
    JOIN subjects s ON m.subject_id = s.id
    JOIN classes c ON m.class_id = c.id
    JOIN users u ON m.recorded_by = u.id
    WHERE m.student_id = ?
    ORDER BY m.exam_date DESC, s.name
");
$stmt->execute([$student_id]);
$marks_records = $stmt->fetchAll();

// Calculate marks statistics
$total_exams = count($marks_records);
$total_marks_obtained = 0;
$total_possible_marks = 0;
$subject_averages = [];

foreach ($marks_records as $record) {
    $total_marks_obtained += $record['marks_obtained'];
    $total_possible_marks += $record['total_marks'];
    
    $subject = $record['subject_name'];
    if (!isset($subject_averages[$subject])) {
        $subject_averages[$subject] = ['total_obtained' => 0, 'total_possible' => 0, 'count' => 0];
    }
    $subject_averages[$subject]['total_obtained'] += $record['marks_obtained'];
    $subject_averages[$subject]['total_possible'] += $record['total_marks'];
    $subject_averages[$subject]['count']++;
}

$overall_average = $total_possible_marks > 0 ? round(($total_marks_obtained / $total_possible_marks) * 100, 1) : 0;

// Calculate subject averages
foreach ($subject_averages as $subject => &$data) {
    $data['average'] = $data['total_possible'] > 0 ? round(($data['total_obtained'] / $data['total_possible']) * 100, 1) : 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Marks - Student Management System</title>
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
        .percentage-badge {
            font-weight: bold;
        }
        .percentage-excellent { color: #28a745; }
        .percentage-good { color: #17a2b8; }
        .percentage-average { color: #ffc107; }
        .percentage-poor { color: #dc3545; }
        .exam-type-badge {
            font-size: 0.8rem;
        }
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
                            <a class="nav-link" href="my_attendance.php">
                                <i class="fas fa-calendar-check"></i> My Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my_marks.php">
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
                        <i class="fas fa-chart-bar"></i> My Marks
                        <small class="text-muted">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</small>
                    </h2>

                    <!-- Marks Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-percentage fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $overall_average; ?>%</div>
                                    <div>Overall Average</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-chart-bar fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $total_exams; ?></div>
                                    <div>Total Exams</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-star fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $total_marks_obtained; ?></div>
                                    <div>Marks Obtained</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-trophy fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $total_possible_marks; ?></div>
                                    <div>Total Possible</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Subject Averages -->
                    <?php if (!empty($subject_averages)): ?>
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Subject Averages</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php foreach ($subject_averages as $subject => $data): ?>
                                <div class="col-md-4 mb-3">
                                    <div class="card">
                                        <div class="card-body text-center">
                                            <h6><?php echo htmlspecialchars($subject); ?></h6>
                                            <?php 
                                            $average = $data['average'];
                                            $class = 'percentage-average';
                                            if ($average >= 90) $class = 'percentage-excellent';
                                            elseif ($average >= 80) $class = 'percentage-good';
                                            elseif ($average >= 70) $class = 'percentage-average';
                                            else $class = 'percentage-poor';
                                            ?>
                                            <div class="percentage-badge <?php echo $class; ?> fs-4">
                                                <?php echo $average; ?>%
                                            </div>
                                            <small class="text-muted"><?php echo $data['count']; ?> exam(s)</small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Marks Records -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> All Marks</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($marks_records)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No marks found</h5>
                                    <p class="text-muted">Your marks will appear here once teachers start recording them.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Date</th>
                                                <th>Subject</th>
                                                <th>Class</th>
                                                <th>Exam Type</th>
                                                <th>Marks</th>
                                                <th>Percentage</th>
                                                <th>Grade</th>
                                                <th>Remarks</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($marks_records as $record): ?>
                                            <tr>
                                                <td><?php echo date('M d, Y', strtotime($record['exam_date'])); ?></td>
                                                <td><?php echo htmlspecialchars($record['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($record['class_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-info exam-type-badge">
                                                        <?php echo ucfirst($record['exam_type']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $record['marks_obtained']; ?>/<?php echo $record['total_marks']; ?></td>
                                                <td>
                                                    <?php 
                                                    $percentage = $record['percentage'];
                                                    $class = 'percentage-average';
                                                    if ($percentage >= 90) $class = 'percentage-excellent';
                                                    elseif ($percentage >= 80) $class = 'percentage-good';
                                                    elseif ($percentage >= 70) $class = 'percentage-average';
                                                    else $class = 'percentage-poor';
                                                    ?>
                                                    <span class="percentage-badge <?php echo $class; ?>">
                                                        <?php echo number_format($percentage, 1); ?>%
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $grade = '';
                                                    if ($percentage >= 90) $grade = 'A+';
                                                    elseif ($percentage >= 85) $grade = 'A';
                                                    elseif ($percentage >= 80) $grade = 'A-';
                                                    elseif ($percentage >= 75) $grade = 'B+';
                                                    elseif ($percentage >= 70) $grade = 'B';
                                                    elseif ($percentage >= 65) $grade = 'B-';
                                                    elseif ($percentage >= 60) $grade = 'C+';
                                                    elseif ($percentage >= 55) $grade = 'C';
                                                    elseif ($percentage >= 50) $grade = 'C-';
                                                    else $grade = 'F';
                                                    
                                                    $gradeClass = 'text-success';
                                                    if ($grade === 'F') $gradeClass = 'text-danger';
                                                    elseif (in_array($grade, ['C+', 'C', 'C-'])) $gradeClass = 'text-warning';
                                                    ?>
                                                    <span class="fw-bold <?php echo $gradeClass; ?>"><?php echo $grade; ?></span>
                                                </td>
                                                <td><?php echo htmlspecialchars($record['remarks'] ?? 'No remarks'); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Performance Summary -->
                    <?php if (!empty($marks_records)): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-line"></i> Performance Summary</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Overall Performance:</h6>
                                    <div class="progress mb-3" style="height: 30px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $overall_average; ?>%" 
                                             aria-valuenow="<?php echo $overall_average; ?>" 
                                             aria-valuemin="0" aria-valuemax="100">
                                            <?php echo $overall_average; ?>%
                                        </div>
                                    </div>
                                    <p class="text-muted">
                                        <?php if ($overall_average >= 90): ?>
                                            <i class="fas fa-star text-warning"></i> Excellent performance!
                                        <?php elseif ($overall_average >= 80): ?>
                                            <i class="fas fa-thumbs-up text-success"></i> Good performance
                                        <?php elseif ($overall_average >= 70): ?>
                                            <i class="fas fa-exclamation-triangle text-warning"></i> Average performance
                                        <?php else: ?>
                                            <i class="fas fa-exclamation-circle text-danger"></i> Needs improvement
                                        <?php endif; ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <h6>Grade Distribution:</h6>
                                    <ul class="list-group list-group-flush">
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            A Grades (90%+)
                                            <span class="badge bg-success rounded-pill">
                                                <?php echo count(array_filter($marks_records, function($r) { return $r['percentage'] >= 90; })); ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            B Grades (80-89%)
                                            <span class="badge bg-info rounded-pill">
                                                <?php echo count(array_filter($marks_records, function($r) { return $r['percentage'] >= 80 && $r['percentage'] < 90; })); ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            C Grades (70-79%)
                                            <span class="badge bg-warning rounded-pill">
                                                <?php echo count(array_filter($marks_records, function($r) { return $r['percentage'] >= 70 && $r['percentage'] < 80; })); ?>
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            F Grades (<70%)
                                            <span class="badge bg-danger rounded-pill">
                                                <?php echo count(array_filter($marks_records, function($r) { return $r['percentage'] < 70; })); ?>
                                            </span>
                                        </li>
                                    </ul>
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