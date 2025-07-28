<?php
require_once 'config/database.php';

/**
 * Authenticate user with username, password and user type
 */
function authenticateUser($username, $password, $user_type) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND user_type = ? AND is_active = 1");
    $stmt->execute([$username, $user_type]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        return $user;
    }
    
    return false;
}

/**
 * Log login activity
 */
function logLoginActivity($user_id, $user_type, $status = 'success') {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("INSERT INTO login_activity (user_id, user_type, ip_address, user_agent, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $user_type,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $status
    ]);
}

/**
 * Create user session
 */
function createUserSession($user_id) {
    $pdo = getDBConnection();
    
    $session_id = session_id();
    $expires_at = date('Y-m-d H:i:s', strtotime('+24 hours'));
    
    $stmt = $pdo->prepare("INSERT INTO user_sessions (user_id, session_id, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $session_id,
        $_SERVER['REMOTE_ADDR'] ?? 'unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        $expires_at
    ]);
}

/**
 * Validate user session
 */
function validateUserSession() {
    $pdo = getDBConnection();
    
    $session_id = session_id();
    $stmt = $pdo->prepare("SELECT * FROM user_sessions WHERE session_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$session_id]);
    
    return $stmt->fetch() !== false;
}

/**
 * Destroy user session
 */
function destroyUserSession() {
    $pdo = getDBConnection();
    
    $session_id = session_id();
    $stmt = $pdo->prepare("UPDATE user_sessions SET is_active = 0 WHERE session_id = ?");
    $stmt->execute([$session_id]);
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

/**
 * Check user permissions
 */
function hasPermission($required_type) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $user_type = $_SESSION['user_type'];
    
    switch ($required_type) {
        case 'admin':
            return $user_type === 'admin';
        case 'teacher':
            return in_array($user_type, ['admin', 'teacher']);
        case 'student':
            return in_array($user_type, ['admin', 'teacher', 'student']);
        default:
            return false;
    }
}

/**
 * Require authentication
 */
function requireAuth() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

/**
 * Require specific user type
 */
function requireUserType($user_type) {
    requireAuth();
    
    if (!hasPermission($user_type)) {
        header("Location: dashboard.php?error=insufficient_permissions");
        exit();
    }
}

/**
 * Get user information
 */
function getUserInfo($user_id = null) {
    if ($user_id === null) {
        $user_id = $_SESSION['user_id'] ?? null;
    }
    
    if (!$user_id) {
        return null;
    }
    
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    
    return $stmt->fetch();
}

/**
 * Get all users (admin only)
 */
function getAllUsers() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, user_type, is_active, created_at FROM users ORDER BY created_at DESC");
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get login activity
 */
function getLoginActivity($limit = 50) {
    $pdo = getDBConnection();
    $limit = (int)$limit;
    $stmt = $pdo->prepare("
        SELECT la.*, u.username, u.full_name 
        FROM login_activity la 
        JOIN users u ON la.user_id = u.id 
        ORDER BY la.login_time DESC 
        LIMIT " . $limit
    );
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Create new user
 */
function createUser($username, $password, $full_name, $email, $user_type) {
    $pdo = getDBConnection();
    
    // Check if username or email already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $email]);
    
    if ($stmt->fetchColumn() > 0) {
        return ['success' => false, 'message' => 'Username or email already exists'];
    }
    
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, user_type) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt->execute([$username, $hashedPassword, $full_name, $email, $user_type])) {
        return ['success' => true, 'message' => 'User created successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to create user'];
    }
}

/**
 * Update user
 */
function updateUser($user_id, $full_name, $email, $user_type, $is_active) {
    $pdo = getDBConnection();
    
    $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, user_type = ?, is_active = ? WHERE id = ?");
    
    if ($stmt->execute([$full_name, $email, $user_type, $is_active, $user_id])) {
        return ['success' => true, 'message' => 'User updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update user'];
    }
}

/**
 * Delete user
 */
function deleteUser($user_id) {
    $pdo = getDBConnection();
    
    // Don't allow deletion of admin users
    $stmt = $pdo->prepare("SELECT user_type FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if ($user && $user['user_type'] === 'admin') {
        return ['success' => false, 'message' => 'Cannot delete admin users'];
    }
    
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
    
    if ($stmt->execute([$user_id])) {
        return ['success' => true, 'message' => 'User deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete user'];
    }
}

/**
 * Change user password
 */
function changePassword($user_id, $current_password, $new_password) {
    $pdo = getDBConnection();
    
    // Verify current password
    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user || !password_verify($current_password, $user['password'])) {
        return ['success' => false, 'message' => 'Current password is incorrect'];
    }
    
    $hashedPassword = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
    
    if ($stmt->execute([$hashedPassword, $user_id])) {
        return ['success' => true, 'message' => 'Password changed successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to change password'];
    }
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// ==================== STUDENT MANAGEMENT FUNCTIONS ====================

/**
 * Get all students
 */
function getAllStudents() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, sp.roll_number, sp.date_of_birth, sp.gender, sp.phone, sp.address
        FROM users u 
        LEFT JOIN student_profiles sp ON u.id = sp.student_id 
        WHERE u.user_type = 'student' AND u.is_active = 1
        ORDER BY u.full_name
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Create student profile automatically when student is created
 */
function createStudentProfile($student_id, $roll_number = null) {
    $pdo = getDBConnection();
    
    // Check if profile already exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_profiles WHERE student_id = ?");
    $stmt->execute([$student_id]);
    
    if ($stmt->fetchColumn() > 0) {
        return true; // Profile already exists
    }
    
    // Generate roll number if not provided
    if (!$roll_number) {
        // Get the highest existing roll number and increment
        $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(roll_number, 4) AS UNSIGNED)) FROM student_profiles WHERE roll_number LIKE 'STU%'");
        $stmt->execute();
        $maxNum = $stmt->fetchColumn() ?: 0;
        $roll_number = 'STU' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
    }
    
    try {
        $stmt = $pdo->prepare("INSERT INTO student_profiles (student_id, roll_number, admission_date) VALUES (?, ?, CURDATE())");
        return $stmt->execute([$student_id, $roll_number]);
    } catch (PDOException $e) {
        // Handle duplicate roll number
        if ($e->getCode() == 23000) {
            // Try with a different roll number
            $stmt = $pdo->prepare("SELECT MAX(CAST(SUBSTRING(roll_number, 4) AS UNSIGNED)) FROM student_profiles WHERE roll_number LIKE 'STU%'");
            $stmt->execute();
            $maxNum = $stmt->fetchColumn() ?: 0;
            $roll_number = 'STU' . str_pad($maxNum + 1, 3, '0', STR_PAD_LEFT);
            
            $stmt = $pdo->prepare("INSERT INTO student_profiles (student_id, roll_number, admission_date) VALUES (?, ?, CURDATE())");
            return $stmt->execute([$student_id, $roll_number]);
        }
        return false;
    }
}

/**
 * Fix existing students without profiles
 */
function fixStudentsWithoutProfiles() {
    $pdo = getDBConnection();
    
    // Find students without profiles
    $stmt = $pdo->prepare("
        SELECT u.id 
        FROM users u 
        LEFT JOIN student_profiles sp ON u.id = sp.student_id 
        WHERE u.user_type = 'student' AND u.is_active = 1 AND sp.student_id IS NULL
    ");
    $stmt->execute();
    $studentsWithoutProfiles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $fixed = 0;
    foreach ($studentsWithoutProfiles as $studentId) {
        if (createStudentProfile($studentId)) {
            $fixed++;
        }
    }
    
    return $fixed;
}

/**
 * Get all teachers
 */
function getAllTeachers() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM users 
        WHERE user_type = 'teacher' AND is_active = 1
        ORDER BY full_name
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all subjects
 */
function getAllSubjects() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT s.*, u.full_name as teacher_name
        FROM subjects s
        LEFT JOIN users u ON s.teacher_id = u.id
        WHERE s.is_active = 1
        ORDER BY s.name
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get all classes
 */
function getAllClasses() {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT c.*, u.full_name as teacher_name
        FROM classes c
        LEFT JOIN users u ON c.teacher_id = u.id
        WHERE c.is_active = 1
        ORDER BY c.grade_level, c.name
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get student profile
 */
function getStudentProfile($student_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, sp.*
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.student_id
        WHERE u.id = ? AND u.user_type = 'student'
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetch();
}

/**
 * Get student enrollments
 */
function getStudentEnrollments($student_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT se.*, c.name as class_name, c.grade_level, c.academic_year
        FROM student_enrollments se
        JOIN classes c ON se.class_id = c.id
        WHERE se.student_id = ? AND se.status = 'active'
        ORDER BY se.enrollment_date DESC
    ");
    $stmt->execute([$student_id]);
    return $stmt->fetchAll();
}

/**
 * Get student attendance
 */
function getStudentAttendance($student_id, $class_id = null, $subject_id = null, $date_from = null, $date_to = null) {
    $pdo = getDBConnection();
    
    $where_conditions = ["a.student_id = ?"];
    $params = [$student_id];
    
    if ($class_id) {
        $where_conditions[] = "a.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($subject_id) {
        $where_conditions[] = "a.subject_id = ?";
        $params[] = $subject_id;
    }
    
    if ($date_from) {
        $where_conditions[] = "a.date >= ?";
        $params[] = $date_from;
    }
    
    if ($date_to) {
        $where_conditions[] = "a.date <= ?";
        $params[] = $date_to;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT a.*, c.name as class_name, s.name as subject_name, u.full_name as recorded_by_name
        FROM attendance a
        JOIN classes c ON a.class_id = c.id
        JOIN subjects s ON a.subject_id = s.id
        JOIN users u ON a.recorded_by = u.id
        WHERE $where_clause
        ORDER BY a.date DESC, s.name
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Get student marks
 */
function getStudentMarks($student_id, $class_id = null, $subject_id = null) {
    $pdo = getDBConnection();
    
    $where_conditions = ["m.student_id = ?"];
    $params = [$student_id];
    
    if ($class_id) {
        $where_conditions[] = "m.class_id = ?";
        $params[] = $class_id;
    }
    
    if ($subject_id) {
        $where_conditions[] = "m.subject_id = ?";
        $params[] = $subject_id;
    }
    
    $where_clause = implode(" AND ", $where_conditions);
    
    $stmt = $pdo->prepare("
        SELECT m.*, c.name as class_name, s.name as subject_name, u.full_name as recorded_by_name
        FROM marks m
        JOIN classes c ON m.class_id = c.id
        JOIN subjects s ON m.subject_id = s.id
        JOIN users u ON m.recorded_by = u.id
        WHERE $where_clause
        ORDER BY m.exam_date DESC, s.name
    ");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

/**
 * Add attendance record
 */
function addAttendance($student_id, $class_id, $subject_id, $date, $status, $remarks, $recorded_by) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO attendance (student_id, class_id, subject_id, date, status, remarks, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            status = VALUES(status),
            remarks = VALUES(remarks),
            recorded_by = VALUES(recorded_by)
        ");
        $stmt->execute([$student_id, $class_id, $subject_id, $date, $status, $remarks, $recorded_by]);
        return ['success' => true, 'message' => 'Attendance recorded successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to record attendance: ' . $e->getMessage()];
    }
}

/**
 * Add marks record
 */
function addMarks($student_id, $subject_id, $class_id, $exam_type, $marks_obtained, $total_marks, $exam_date, $remarks, $recorded_by) {
    $pdo = getDBConnection();
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO marks (student_id, subject_id, class_id, exam_type, marks_obtained, total_marks, exam_date, remarks, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$student_id, $subject_id, $class_id, $exam_type, $marks_obtained, $total_marks, $exam_date, $remarks, $recorded_by]);
        return ['success' => true, 'message' => 'Marks recorded successfully'];
    } catch (PDOException $e) {
        return ['success' => false, 'message' => 'Failed to record marks: ' . $e->getMessage()];
    }
}

/**
 * Get class students
 */
function getClassStudents($class_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT u.*, sp.roll_number, sp.date_of_birth, sp.gender
        FROM users u
        LEFT JOIN student_profiles sp ON u.id = sp.student_id
        JOIN student_enrollments se ON u.id = se.student_id
        WHERE se.class_id = ? AND se.status = 'active' AND u.is_active = 1
        ORDER BY sp.roll_number, u.full_name
    ");
    $stmt->execute([$class_id]);
    return $stmt->fetchAll();
}

/**
 * Get teacher's classes
 */
function getTeacherClasses($teacher_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM classes 
        WHERE teacher_id = ? AND is_active = 1
        ORDER BY grade_level, name
    ");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll();
}

/**
 * Get teacher's subjects
 */
function getTeacherSubjects($teacher_id) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare("
        SELECT * FROM subjects 
        WHERE teacher_id = ? AND is_active = 1
        ORDER BY name
    ");
    $stmt->execute([$teacher_id]);
    return $stmt->fetchAll();
}

/**
 * Get user type display name
 */
function getUserTypeDisplayName($user_type) {
    switch ($user_type) {
        case 'admin':
            return 'Administrator';
        case 'teacher':
            return 'Teacher';
        case 'student':
            return 'Student';
        default:
            return ucfirst($user_type);
    }
}

/**
 * Get user type icon
 */
function getUserTypeIcon($user_type) {
    switch ($user_type) {
        case 'admin':
            return 'fas fa-user-shield';
        case 'teacher':
            return 'fas fa-chalkboard-teacher';
        case 'student':
            return 'fas fa-user-graduate';
        default:
            return 'fas fa-user';
    }
}
?> 