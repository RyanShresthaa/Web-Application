<?php
session_start();
require_once 'includes/functions.php';
require_once 'includes/Subject.php';

// Check if user is logged in and has admin permission
requireAuth();
if (!hasPermission('admin')) {
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
                try {
                    $subject = new Subject();
                    $subject->setName(trim($_POST['name']));
                    $subject->setCode(trim($_POST['code']));
                    $subject->setDescription(trim($_POST['description']));
                    $subject->setTeacherId($_POST['teacher_id'] ?: null);
                    $subject->setIsActive(true);
                    if ($subject->save()) {
                        header('Location: subjects.php?success=Subject added successfully');
                        exit();
                    } else {
                        throw new Exception('Failed to add subject');
                    }
                } catch (Exception $e) {
                    header('Location: subjects.php?error=' . urlencode($e->getMessage()));
                    exit();
                }
                break;
            case 'edit':
                try {
                    $subject = new Subject();
                    if (!$subject->loadById($_POST['id'])) {
                        throw new Exception('Subject not found');
                    }
                    $subject->setName(trim($_POST['name']));
                    $subject->setCode(trim($_POST['code']));
                    $subject->setDescription(trim($_POST['description']));
                    $subject->setTeacherId($_POST['teacher_id'] ?: null);
                    $subject->setIsActive(true);
                    if ($subject->update()) {
                        header('Location: subjects.php?success=Subject updated successfully');
                        exit();
                    } else {
                        throw new Exception('Failed to update subject');
                    }
                } catch (Exception $e) {
                    header('Location: subjects.php?error=' . urlencode($e->getMessage()));
                    exit();
                }
                break;
                
            case 'delete':
                $id = $_POST['id'];
                try {
                    $stmt = $pdo->prepare("DELETE FROM subjects WHERE id = ?");
                    $stmt->execute([$id]);
                    header('Location: subjects.php?success=Subject deleted successfully');
                    exit();
                } catch (PDOException $e) {
                    header('Location: subjects.php?error=Failed to delete subject');
                    exit();
                }
                break;
        }
    }
}

// Get all subjects with teacher information
$stmt = $pdo->prepare("
    SELECT s.*, u.full_name as teacher_name 
    FROM subjects s 
    LEFT JOIN users u ON s.teacher_id = u.id 
    ORDER BY s.name
");
$stmt->execute();
$subjects = $stmt->fetchAll();

// Get all teachers for dropdown
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE user_type = 'teacher' AND is_active = 1 ORDER BY full_name");
$stmt->execute();
$teachers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - Student Management System</title>
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
                        <i class="fas fa-user-shield"></i>
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                        <span class="badge bg-danger">Admin</span>
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
                            <a class="nav-link" href="users.php">
                                <i class="fas fa-users"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="students.php">
                                <i class="fas fa-user-graduate"></i> Manage Students
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="classes.php">
                                <i class="fas fa-chalkboard"></i> Manage Classes
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="subjects.php">
                                <i class="fas fa-book"></i> Manage Subjects
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="activity.php">
                                <i class="fas fa-chart-line"></i> Activity Log
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
                        <h2><i class="fas fa-book"></i> Manage Subjects</h2>
                        <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                            <i class="fas fa-plus"></i> Add New Subject
                        </button>
                    </div>

                    <!-- Subjects Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-list"></i> All Subjects</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Code</th>
                                            <th>Description</th>
                                            <th>Teacher</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($subjects as $subject): ?>
                                        <tr>
                                            <td><?php echo $subject['id']; ?></td>
                                            <td><?php echo htmlspecialchars($subject['name']); ?></td>
                                            <td><span class="badge bg-info"><?php echo htmlspecialchars($subject['code']); ?></span></td>
                                            <td><?php echo htmlspecialchars($subject['description'] ?? 'No description'); ?></td>
                                            <td><?php echo htmlspecialchars($subject['teacher_name'] ?? 'Not Assigned'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $subject['is_active'] ? 'success' : 'danger'; ?>">
                                                    <?php echo $subject['is_active'] ? 'Active' : 'Inactive'; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" onclick="editSubject(<?php echo htmlspecialchars(json_encode($subject)); ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteSubject(<?php echo $subject['id']; ?>, '<?php echo htmlspecialchars($subject['name']); ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal fade" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-plus"></i> Add New Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add">
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="code" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="code" name="code" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="teacher_id" class="form-label">Assigned Teacher (Optional)</label>
                            <select class="form-control" id="teacher_id" name="teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Subject Modal -->
    <div class="modal fade" id="editSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit"></i> Edit Subject</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" id="edit_id">
                        
                        <div class="mb-3">
                            <label for="edit_name" class="form-label">Subject Name</label>
                            <input type="text" class="form-control" id="edit_name" name="name" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_code" class="form-label">Subject Code</label>
                            <input type="text" class="form-control" id="edit_code" name="code" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_description" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_teacher_id" class="form-label">Assigned Teacher (Optional)</label>
                            <select class="form-control" id="edit_teacher_id" name="teacher_id">
                                <option value="">Select Teacher</option>
                                <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>"><?php echo htmlspecialchars($teacher['full_name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the subject "<span id="deleteSubjectName"></span>"?</p>
                    <p class="text-danger"><strong>This action cannot be undone!</strong></p>
                </div>
                <form method="POST">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="deleteSubjectId">
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Delete Subject</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editSubject(subjectData) {
            document.getElementById('edit_id').value = subjectData.id;
            document.getElementById('edit_name').value = subjectData.name;
            document.getElementById('edit_code').value = subjectData.code;
            document.getElementById('edit_description').value = subjectData.description || '';
            document.getElementById('edit_teacher_id').value = subjectData.teacher_id || '';
            
            new bootstrap.Modal(document.getElementById('editSubjectModal')).show();
        }
        
        function deleteSubject(id, name) {
            document.getElementById('deleteSubjectId').value = id;
            document.getElementById('deleteSubjectName').textContent = name;
            new bootstrap.Modal(document.getElementById('deleteSubjectModal')).show();
        }
    </script>
</body>
</html> 