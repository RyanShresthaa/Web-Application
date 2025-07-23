<?php
session_start();
require_once 'includes/functions.php';

// Check if user is logged in and has teacher permission
requireAuth();
if (!hasPermission('teacher')) {
    header('Location: dashboard.php?error=Access denied');
    exit();
}

$pdo = getDBConnection();
$error = $_GET['error'] ?? '';
$success = $_GET['success'] ?? '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $student_id = $_POST['student_id'];
                $subject_id = $_POST['subject_id'];
                $class_id = $_POST['class_id'];
                $exam_type = $_POST['exam_type'];
                $marks_obtained = $_POST['marks_obtained'];
                $total_marks = $_POST['total_marks'];
                $exam_date = $_POST['exam_date'];
                $remarks = trim($_POST['remarks']);
                
                if (empty($student_id) || empty($subject_id) || empty($class_id) || empty($exam_type) || empty($marks_obtained) || empty($total_marks) || empty($exam_date)) {
                    header('Location: marks.php?error=All required fields must be filled');
                    exit();
                }
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO marks (student_id, subject_id, class_id, exam_type, marks_obtained, total_marks, exam_date, remarks, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$student_id, $subject_id, $class_id, $exam_type, $marks_obtained, $total_marks, $exam_date, $remarks, $_SESSION['user_id']]);
                    header('Location: marks.php?success=Mark added successfully');
                    exit();
                } catch (PDOException $e) {
                    header('Location: marks.php?error=Failed to add mark');
                    exit();
                }
                break;
                
            case 'edit':
                $id = $_POST['id'];
                $marks_obtained = $_POST['marks_obtained'];
                $total_marks = $_POST['total_marks'];
                $exam_date = $_POST['exam_date'];
                $remarks = trim($_POST['remarks']);
                
                if (empty($marks_obtained) || empty($total_marks) || empty($exam_date)) {
                    header('Location: marks.php?error=All required fields must be filled');
                    exit();
                }
                
                try {
                    $stmt = $pdo->prepare("UPDATE marks SET marks_obtained = ?, total_marks = ?, exam_date = ?, remarks = ? WHERE id = ?");
                    $stmt->execute([$marks_obtained, $total_marks, $exam_date, $remarks, $id]);
                    header('Location: marks.php?success=Mark updated successfully');
                    exit();
                } catch (PDOException $e) {
                    header('Location: marks.php?error=Failed to update mark');
                    exit();
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM marks WHERE id = ?");
                    $stmt->execute([$id]);
                    header('Location: marks.php?success=Mark deleted successfully');
                    exit();
                } catch (PDOException $e) {
                    header('Location: marks.php?error=Failed to delete mark');
                    exit();
                }
                break;
        }
    }
}

// Get teacher's subjects and classes
$teacher_subjects = getTeacherSubjects($_SESSION['user_id']);
$teacher_classes = getTeacherClasses($_SESSION['user_id']);

// Get all students
$students = getAllStudents();

// Get marks for teacher's subjects
$marks = [];
if (!empty($teacher_subjects)) {
    $subject_ids = array_column($teacher_subjects, 'id');
    $placeholders = str_repeat('?,', count($subject_ids) - 1) . '?';
    $stmt = $pdo->prepare("
        SELECT m.*, s.name as subject_name, c.name as class_name, u.full_name as student_name, 
               u2.full_name as recorded_by_name
        FROM marks m
        JOIN subjects s ON m.subject_id = s.id
        JOIN classes c ON m.class_id = c.id
        JOIN users u ON m.student_id = u.id
        JOIN users u2 ON m.recorded_by = u2.id
        WHERE m.subject_id IN ($placeholders)
        ORDER BY m.exam_date DESC, s.name, u.full_name
    ");
    $stmt->execute($subject_ids);
    $marks = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Marks - Student Management System</title>
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
        .btn-custom {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
        }
        .percentage-badge {
            font-weight: bold;
        }
        .percentage-excellent { color: #28a745; }
        .percentage-good { color: #17a2b8; }
        .percentage-average { color: #ffc107; }
        .percentage-poor { color: #dc3545; }
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
                        <i class="fas fa-chalkboard-teacher"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="badge bg-warning">Teacher</span>
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
                            <a class="nav-link" href="attendance.php">
                                <i class="fas fa-calendar-check"></i> Attendance
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="marks.php">
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
                <div class="p-4">
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

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2><i class="fas fa-chart-bar"></i> Manage Marks</h2>
                        <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addMarkModal">
                            <i class="fas fa-plus"></i> Add New Mark
                        </button>
                    </div>

                    <!-- Marks Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> All Marks</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($marks)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No marks found</h5>
                                    <p class="text-muted">Start by adding marks for your students.</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Student</th>
                                                <th>Subject</th>
                                                <th>Class</th>
                                                <th>Exam Type</th>
                                                <th>Marks</th>
                                                <th>Percentage</th>
                                                <th>Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($marks as $mark): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($mark['student_name']); ?></td>
                                                <td><?php echo htmlspecialchars($mark['subject_name']); ?></td>
                                                <td><?php echo htmlspecialchars($mark['class_name']); ?></td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo ucfirst($mark['exam_type']); ?></span>
                                                </td>
                                                <td><?php echo $mark['marks_obtained']; ?>/<?php echo $mark['total_marks']; ?></td>
                                                <td>
                                                    <?php 
                                                    $percentage = $mark['percentage'];
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
                                                <td><?php echo date('M d, Y', strtotime($mark['exam_date'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" onclick="editMark(<?php echo htmlspecialchars(json_encode($mark)); ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteMark(<?php echo $mark['id']; ?>, '<?php echo htmlspecialchars($mark['student_name']); ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
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

    <!-- Add Mark Modal -->
    <div class="modal fade" id="addMarkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Mark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="student_id" class="form-label">Student</label>
                            <select class="form-control" id="student_id" name="student_id" required>
                                <option value="">Select Student</option>
                                <?php foreach ($students as $student): ?>
                                <option value="<?php echo $student['id']; ?>"><?php echo htmlspecialchars($student['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="subject_id" class="form-label">Subject</label>
                            <select class="form-control" id="subject_id" name="subject_id" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($teacher_subjects as $subject): ?>
                                <option value="<?php echo $subject['id']; ?>"><?php echo htmlspecialchars($subject['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="class_id" class="form-label">Class</label>
                            <select class="form-control" id="class_id" name="class_id" required>
                                <option value="">Select Class</option>
                                <?php foreach ($teacher_classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exam_type" class="form-label">Exam Type</label>
                            <select class="form-control" id="exam_type" name="exam_type" required>
                                <option value="">Select Exam Type</option>
                                <option value="quiz">Quiz</option>
                                <option value="midterm">Midterm</option>
                                <option value="final">Final</option>
                                <option value="assignment">Assignment</option>
                                <option value="project">Project</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="marks_obtained" class="form-label">Marks Obtained</label>
                                    <input type="number" class="form-control" id="marks_obtained" name="marks_obtained" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="total_marks" class="form-label">Total Marks</label>
                                    <input type="number" class="form-control" id="total_marks" name="total_marks" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exam_date" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="exam_date" name="exam_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Mark</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Mark Modal -->
    <div class="modal fade" id="editMarkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Mark</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_marks_obtained" class="form-label">Marks Obtained</label>
                                    <input type="number" class="form-control" id="edit_marks_obtained" name="marks_obtained" step="0.01" min="0" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_total_marks" class="form-label">Total Marks</label>
                                    <input type="number" class="form-control" id="edit_total_marks" name="total_marks" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_exam_date" class="form-label">Exam Date</label>
                            <input type="date" class="form-control" id="edit_exam_date" name="exam_date" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_remarks" class="form-label">Remarks (Optional)</label>
                            <textarea class="form-control" id="edit_remarks" name="remarks" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Mark</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteMarkModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the mark for "<span id="deleteMarkStudent"></span>"?</p>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteMarkId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Mark</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editMark(markData) {
            document.getElementById('edit_id').value = markData.id;
            document.getElementById('edit_marks_obtained').value = markData.marks_obtained;
            document.getElementById('edit_total_marks').value = markData.total_marks;
            document.getElementById('edit_exam_date').value = markData.exam_date;
            document.getElementById('edit_remarks').value = markData.remarks || '';
            
            new bootstrap.Modal(document.getElementById('editMarkModal')).show();
        }
        
        function deleteMark(id, studentName) {
            document.getElementById('deleteMarkId').value = id;
            document.getElementById('deleteMarkStudent').textContent = studentName;
            new bootstrap.Modal(document.getElementById('deleteMarkModal')).show();
        }
    </script>
</body>
</html> 