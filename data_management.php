<?php
session_start();
require_once 'includes/functions.php';

// Require admin permissions
requireUserType('admin');

$message = '';
$error = '';

// Handle data export
if (isset($_POST['export'])) {
    $type = $_POST['export_type'];
    $format = $_POST['export_format'];
    
    $url = "api_export.php?type=$type&format=$format";
    header("Location: $url");
    exit();
}

// Handle data import
if (isset($_POST['import'])) {
    $type = $_POST['import_type'];
    $format = $_POST['import_format'];
    $data = $_POST['import_data'];
    
    if (empty($data)) {
        $error = 'Please provide data to import.';
    } else {
        // Send to import API
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'api_import.php');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
            'type' => $type,
            'format' => $format,
            'data' => $data
        ]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        
        if ($result && isset($result['success'])) {
            $message = "Import completed: {$result['imported']} items imported successfully.";
            if ($result['errors'] > 0) {
                $message .= " {$result['errors']} errors occurred.";
            }
        } else {
            $error = 'Import failed. Please check your data format.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Management - Student Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="admin-theme.css" rel="stylesheet">
    <style>
        .export-import-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
            margin-bottom: 2rem;
        }
        
        .card-header {
            background: #f8fafc;
            padding: 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            border-radius: 12px 12px 0 0;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .format-badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .format-json {
            background: #dbeafe;
            color: #1e40af;
        }
        
        .format-xml {
            background: #fef3c7;
            color: #d97706;
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
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="main-content">
            <div class="container-fluid">
                <!-- Page Header -->
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h1 class="mb-2">Data Management</h1>
                        <p class="text-muted mb-0">Export and import data in XML/JSON formats</p>
                    </div>
                    <a href="dashboard.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if ($message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <!-- Export Section -->
                <div class="export-import-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-download text-primary"></i> Export Data
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Data Type</label>
                                        <select name="export_type" class="form-select" required>
                                            <option value="">Select data type</option>
                                            <option value="students">Students</option>
                                            <option value="classes">Classes</option>
                                            <option value="subjects">Subjects</option>
                                            <option value="attendance">Attendance Records</option>
                                            <option value="marks">Marks Records</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">Format</label>
                                        <select name="export_format" class="form-select" required>
                                            <option value="json">
                                                <span class="format-badge format-json">JSON</span> JSON Format
                                            </option>
                                            <option value="xml">
                                                <span class="format-badge format-xml">XML</span> XML Format
                                            </option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label class="form-label">&nbsp;</label>
                                        <button type="submit" name="export" class="btn btn-primary w-100">
                                            <i class="fas fa-download"></i> Export Data
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Import Section -->
                <div class="export-import-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-upload text-success"></i> Import Data
                        </h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Data Type</label>
                                        <select name="import_type" class="form-select" required>
                                            <option value="">Select data type</option>
                                            <option value="students">Students</option>
                                            <option value="classes">Classes</option>
                                            <option value="subjects">Subjects</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-label">Format</label>
                                        <select name="import_format" class="form-select" required>
                                            <option value="json">JSON Format</option>
                                            <option value="xml">XML Format</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Data</label>
                                        <textarea name="import_data" class="form-control" rows="4" placeholder="Paste your JSON or XML data here..." required></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12">
                                    <button type="submit" name="import" class="btn btn-success">
                                        <i class="fas fa-upload"></i> Import Data
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sample Data Formats -->
                <div class="export-import-card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle text-info"></i> Sample Data Formats
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>JSON Format (Students)</h6>
                                <pre class="bg-light p-3 rounded"><code>[
  {
    "username": "student1",
    "password": "student123",
    "full_name": "John Doe",
    "email": "john@example.com",
    "roll_number": "STU001",
    "phone": "1234567890",
    "date_of_birth": "2005-01-15"
  }
]</code></pre>
                            </div>
                            <div class="col-md-6">
                                <h6>XML Format (Students)</h6>
                                <pre class="bg-light p-3 rounded"><code>&lt;?xml version="1.0" encoding="UTF-8"?&gt;
&lt;students&gt;
  &lt;item&gt;
    &lt;username&gt;student1&lt;/username&gt;
    &lt;password&gt;student123&lt;/password&gt;
    &lt;full_name&gt;John Doe&lt;/full_name&gt;
    &lt;email&gt;john@example.com&lt;/email&gt;
    &lt;roll_number&gt;STU001&lt;/roll_number&gt;
    &lt;phone&gt;1234567890&lt;/phone&gt;
    &lt;date_of_birth&gt;2005-01-15&lt;/date_of_birth&gt;
  &lt;/item&gt;
&lt;/students&gt;</code></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 