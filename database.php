<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'multi_login_system');

// Create database connection
function getDBConnection() {
    try {
        $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }
}

// Initialize database and create tables
function initializeDatabase() {
    try {
        // Create database if it doesn't exist
        $pdo = new PDO("mysql:host=" . DB_HOST, DB_USER, DB_PASS);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $pdo->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        $pdo->exec("USE " . DB_NAME);
        
        // Create users table - first drop if exists to recreate with correct schema
        $pdo->exec("DROP TABLE IF EXISTS user_sessions");
        $pdo->exec("DROP TABLE IF EXISTS login_activity");
        $pdo->exec("DROP TABLE IF EXISTS attendance");
        $pdo->exec("DROP TABLE IF EXISTS marks");
        $pdo->exec("DROP TABLE IF EXISTS student_enrollments");
        $pdo->exec("DROP TABLE IF EXISTS student_profiles");
        $pdo->exec("DROP TABLE IF EXISTS subjects");
        $pdo->exec("DROP TABLE IF EXISTS classes");
        $pdo->exec("DROP TABLE IF EXISTS users");
        
        $pdo->exec("CREATE TABLE users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) UNIQUE NOT NULL,
            user_type ENUM('admin', 'teacher', 'student') NOT NULL DEFAULT 'student',
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create login_activity table
        $pdo->exec("CREATE TABLE IF NOT EXISTS login_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            user_type VARCHAR(20) NOT NULL,
            login_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            ip_address VARCHAR(45),
            user_agent TEXT,
            status ENUM('success', 'failed') DEFAULT 'success',
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create user_sessions table
        $pdo->exec("CREATE TABLE IF NOT EXISTS user_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            session_id VARCHAR(255) UNIQUE NOT NULL,
            ip_address VARCHAR(45),
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            is_active BOOLEAN DEFAULT TRUE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create subjects table
        $pdo->exec("CREATE TABLE IF NOT EXISTS subjects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            code VARCHAR(20) UNIQUE NOT NULL,
            description TEXT,
            teacher_id INT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create classes table
        $pdo->exec("CREATE TABLE IF NOT EXISTS classes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(50) NOT NULL,
            grade_level VARCHAR(20) NOT NULL,
            academic_year VARCHAR(20) NOT NULL,
            teacher_id INT,
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create student_enrollments table
        $pdo->exec("CREATE TABLE IF NOT EXISTS student_enrollments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            class_id INT NOT NULL,
            enrollment_date DATE NOT NULL,
            status ENUM('active', 'inactive', 'graduated') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            UNIQUE KEY unique_enrollment (student_id, class_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create attendance table
        $pdo->exec("CREATE TABLE IF NOT EXISTS attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            class_id INT NOT NULL,
            subject_id INT NOT NULL,
            date DATE NOT NULL,
            status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
            remarks TEXT,
            recorded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_attendance (student_id, class_id, subject_id, date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create marks table
        $pdo->exec("CREATE TABLE IF NOT EXISTS marks (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT NOT NULL,
            subject_id INT NOT NULL,
            class_id INT NOT NULL,
            exam_type ENUM('quiz', 'midterm', 'final', 'assignment', 'project') NOT NULL,
            marks_obtained DECIMAL(5,2) NOT NULL,
            total_marks DECIMAL(5,2) NOT NULL,
            percentage DECIMAL(5,2) GENERATED ALWAYS AS ((marks_obtained / total_marks) * 100) STORED,
            exam_date DATE NOT NULL,
            remarks TEXT,
            recorded_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
            FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
            FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Create student_profiles table
        $pdo->exec("CREATE TABLE IF NOT EXISTS student_profiles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            student_id INT UNIQUE NOT NULL,
            roll_number VARCHAR(20) UNIQUE,
            date_of_birth DATE,
            gender ENUM('male', 'female', 'other'),
            phone VARCHAR(20),
            address TEXT,
            parent_name VARCHAR(100),
            parent_phone VARCHAR(20),
            parent_email VARCHAR(100),
            emergency_contact VARCHAR(20),
            blood_group VARCHAR(5),
            admission_date DATE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        
        // Admin insert
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, user_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['admin', $adminPassword, 'System Administrator', 'admin@school.com', 'admin']);
        }
        
        // Sample User haru
        $sampleUsers = [
            ['teacher1', 'teacher123', 'Sarah Johnson', 'sarah.johnson@school.com', 'teacher'],
            ['teacher2', 'teacher123', 'Michael Brown', 'michael.brown@school.com', 'teacher'],
            ['student1', 'student123', 'Alex Smith', 'alex.smith@student.com', 'student'],
            ['student2', 'student123', 'Emma Wilson', 'emma.wilson@student.com', 'student'],
            ['student3', 'student123', 'David Lee', 'david.lee@student.com', 'student']
        ];
        
        foreach ($sampleUsers as $user) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            $stmt->execute([$user[0]]);
            
            if ($stmt->fetchColumn() == 0) {
                $hashedPassword = password_hash($user[1], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (username, password, full_name, email, user_type) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$user[0], $hashedPassword, $user[2], $user[3], $user[4]]);
            }
        }
        
        // Teacher ID
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username IN ('teacher1', 'teacher2') ORDER BY username");
        $stmt->execute();
        $teacherIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // sample subjects
        $sampleSubjects = [
            ['Mathematics', 'MATH101', 'Advanced Mathematics Course', $teacherIds[0] ?? 1],
            ['English Literature', 'ENG101', 'English Literature and Composition', $teacherIds[0] ?? 1],
            ['Physics', 'PHY101', 'Fundamental Physics', $teacherIds[1] ?? 2],
            ['Chemistry', 'CHEM101', 'General Chemistry', $teacherIds[1] ?? 2],
            ['Computer Science', 'CS101', 'Introduction to Programming', $teacherIds[0] ?? 1],
            ['History', 'HIST101', 'World History', $teacherIds[0] ?? 1]
        ];
        
        foreach ($sampleSubjects as $subject) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM subjects WHERE code = ?");
            $stmt->execute([$subject[1]]);
            
            if ($stmt->fetchColumn() == 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO subjects (name, code, description, teacher_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute($subject);
                } catch (PDOException $e) {
                    // Skip if there's a duplicate key error
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }
        }
        
        // Sample Classes
        $sampleClasses = [
            ['Class 10A', 'Grade 10', '2024-2025', $teacherIds[0] ?? 1],
            ['Class 10B', 'Grade 10', '2024-2025', $teacherIds[1] ?? 2],
            ['Class 11A', 'Grade 11', '2024-2025', $teacherIds[0] ?? 1],
            ['Class 11B', 'Grade 11', '2024-2025', $teacherIds[1] ?? 2]
        ];
        
        foreach ($sampleClasses as $class) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM classes WHERE name = ? AND academic_year = ?");
            $stmt->execute([$class[0], $class[2]]);
            
            if ($stmt->fetchColumn() == 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO classes (name, grade_level, academic_year, teacher_id) VALUES (?, ?, ?, ?)");
                    $stmt->execute($class);
                } catch (PDOException $e) {
                    // Skip if there's a duplicate key error
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }
        }
        
        // Student Detail pauna
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username IN ('student1', 'student2', 'student3') ORDER BY username");
        $stmt->execute();
        $studentIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Student ko sample
        $sampleProfiles = [
            [$studentIds[0] ?? 3, 'STU001', '2006-03-15', 'male', '+1234567890', '123 Student St, City', 'John Smith', '+1234567891', 'john.smith@email.com', '+1234567892', 'O+', '2023-09-01'],
            [$studentIds[1] ?? 4, 'STU002', '2006-07-22', 'female', '+1234567893', '456 Student Ave, City', 'Mary Wilson', '+1234567894', 'mary.wilson@email.com', '+1234567895', 'A+', '2023-09-01'],
            [$studentIds[2] ?? 5, 'STU003', '2005-11-08', 'male', '+1234567896', '789 Student Blvd, City', 'Robert Lee', '+1234567897', 'robert.lee@email.com', '+1234567898', 'B+', '2023-09-01']
        ];
        
        foreach ($sampleProfiles as $profile) {
            // Check if student profile already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_profiles WHERE student_id = ? OR roll_number = ?");
            $stmt->execute([$profile[0], $profile[1]]);
            
            if ($stmt->fetchColumn() == 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO student_profiles (student_id, roll_number, date_of_birth, gender, phone, address, parent_name, parent_phone, parent_email, emergency_contact, blood_group, admission_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute($profile);
                } catch (PDOException $e) {
                    // Skip if there's a duplicate key error
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }
        }
        
        // Insert sample enrollments
        $sampleEnrollments = [
            [$studentIds[0] ?? 3, 1, '2023-09-01'],
            [$studentIds[1] ?? 4, 1, '2023-09-01'],
            [$studentIds[2] ?? 5, 2, '2023-09-01']
        ];
        
        foreach ($sampleEnrollments as $enrollment) {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM student_enrollments WHERE student_id = ? AND class_id = ?");
            $stmt->execute([$enrollment[0], $enrollment[1]]);
            
            if ($stmt->fetchColumn() == 0) {
                try {
                    $stmt = $pdo->prepare("INSERT INTO student_enrollments (student_id, class_id, enrollment_date) VALUES (?, ?, ?)");
                    $stmt->execute($enrollment);
                } catch (PDOException $e) {
                    // Skip if there's a duplicate key error
                    if ($e->getCode() != 23000) {
                        throw $e;
                    }
                }
            }
        }
        
        return true;
    } catch (PDOException $e) {
        die("Database initialization failed: " . $e->getMessage());
    }
}

?> 