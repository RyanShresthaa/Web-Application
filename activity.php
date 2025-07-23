<?php
session_start();
require_once 'includes/functions.php';

// Require admin permissions
requireUserType('admin');

$login_activity = getLoginActivity(100);
$total_logins = count($login_activity);
$successful_logins = 0;
$failed_logins = 0;

foreach ($login_activity as $activity) {
    if ($activity['status'] === 'success') {
        $successful_logins++;
    } else {
        $failed_logins++;
    }
}

$success_rate = $total_logins > 0 ? round(($successful_logins / $total_logins) * 100, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - Multi-Login System</title>
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
        .user-type-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .user-type-admin {
            background: #dc3545;
            color: white;
        }
        .user-type-moderator {
            background: #fd7e14;
            color: white;
        }
        .user-type-user {
            background: #28a745;
            color: white;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .btn-custom {
            border-radius: 10px;
            padding: 8px 20px;
            font-weight: 600;
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
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .status-success {
            background: #28a745;
            color: white;
        }
        .status-failed {
            background: #dc3545;
            color: white;
        }
        .activity-time {
            font-size: 0.9rem;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <i class="fas fa-users"></i> Multi-Login System
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
                        <li><a class="dropdown-item" href="users.php"><i class="fas fa-users"></i> Manage Users</a></li>
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
                            <a class="nav-link active" href="activity.php">
                                <i class="fas fa-chart-line"></i> Activity Log
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
                    <h2 class="mb-4">
                        <i class="fas fa-chart-line"></i> Activity Log
                    </h2>

                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-sign-in-alt fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $total_logins; ?></div>
                                    <div>Total Logins</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-check-circle fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $successful_logins; ?></div>
                                    <div>Successful</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-times-circle fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $failed_logins; ?></div>
                                    <div>Failed</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <i class="fas fa-percentage fa-2x mb-2"></i>
                                    <div class="stats-number"><?php echo $success_rate; ?>%</div>
                                    <div>Success Rate</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Activity Table -->
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list"></i> Recent Login Activity</h5>
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="exportActivity()">
                                    <i class="fas fa-download"></i> Export
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-light" onclick="refreshActivity()">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="activityTable">
                                    <thead>
                                        <tr>
                                            <th>User</th>
                                            <th>Type</th>
                                            <th>Login Time</th>
                                            <th>IP Address</th>
                                            <th>User Agent</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($login_activity as $activity): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($activity['full_name']); ?></strong>
                                                <br><small class="text-muted">@<?php echo htmlspecialchars($activity['username']); ?></small>
                                            </td>
                                            <td>
                                                <span class="user-type-badge user-type-<?php echo $activity['user_type']; ?>">
                                                    <?php echo getUserTypeDisplayName($activity['user_type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div><?php echo date('M j, Y', strtotime($activity['login_time'])); ?></div>
                                                <div class="activity-time"><?php echo date('g:i A', strtotime($activity['login_time'])); ?></div>
                                            </td>
                                            <td>
                                                <code><?php echo htmlspecialchars($activity['ip_address']); ?></code>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php 
                                                    $user_agent = $activity['user_agent'];
                                                    if (strlen($user_agent) > 50) {
                                                        echo htmlspecialchars(substr($user_agent, 0, 50)) . '...';
                                                    } else {
                                                        echo htmlspecialchars($user_agent);
                                                    }
                                                    ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="status-badge <?php echo $activity['status'] === 'success' ? 'status-success' : 'status-failed'; ?>">
                                                    <?php echo ucfirst($activity['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <?php if (empty($login_activity)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No activity found</h5>
                                <p class="text-muted">Login activity will appear here once users start logging in.</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Activity Chart -->
                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Login Status Distribution</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="statusChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-chart-bar"></i> User Type Activity</h5>
                                </div>
                                <div class="card-body">
                                    <canvas id="userTypeChart" width="400" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Status Distribution Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Successful', 'Failed'],
                datasets: [{
                    data: [<?php echo $successful_logins; ?>, <?php echo $failed_logins; ?>],
                    backgroundColor: ['#28a745', '#dc3545'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });

        // User Type Activity Chart
        const userTypeCtx = document.getElementById('userTypeChart').getContext('2d');
        
        // Count user types
        const userTypeCounts = {};
        <?php foreach ($login_activity as $activity): ?>
        userTypeCounts['<?php echo $activity['user_type']; ?>'] = (userTypeCounts['<?php echo $activity['user_type']; ?>'] || 0) + 1;
        <?php endforeach; ?>
        
        new Chart(userTypeCtx, {
            type: 'bar',
            data: {
                labels: Object.keys(userTypeCounts).map(type => {
                    const labels = {admin: 'Admin', moderator: 'Moderator', user: 'User'};
                    return labels[type] || type;
                }),
                datasets: [{
                    label: 'Login Count',
                    data: Object.values(userTypeCounts),
                    backgroundColor: ['#dc3545', '#fd7e14', '#28a745'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        function exportActivity() {
            // Create CSV content
            let csv = 'User,Type,Login Time,IP Address,Status\n';
            const rows = document.querySelectorAll('#activityTable tbody tr');
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const user = cells[0].textContent.trim();
                const type = cells[1].textContent.trim();
                const time = cells[2].textContent.trim();
                const ip = cells[3].textContent.trim();
                const status = cells[5].textContent.trim();
                
                csv += `"${user}","${type}","${time}","${ip}","${status}"\n`;
            });
            
            // Download CSV
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'login_activity.csv';
            a.click();
            window.URL.revokeObjectURL(url);
        }

        function refreshActivity() {
            location.reload();
        }
    </script>
</body>
</html> 