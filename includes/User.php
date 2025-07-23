<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $id;
    private $username;
    private $password;
    private $full_name;
    private $email;
    private $user_type;
    private $is_active;
    private $created_at;
    private $updated_at;

    // Accessors (getters)
    public function getId() { return $this->id; }
    public function getUsername() { return $this->username; }
    public function getPassword() { return $this->password; }
    public function getFullName() { return $this->full_name; }
    public function getEmail() { return $this->email; }
    public function getUserType() { return $this->user_type; }
    public function isActive() { return $this->is_active; }
    public function getCreatedAt() { return $this->created_at; }
    public function getUpdatedAt() { return $this->updated_at; }

    // Mutators (setters) with validation
    public function setUsername($username) {
        if (strlen($username) < 3) {
            throw new Exception('Username must be at least 3 characters long.');
        }
        $this->username = $username;
    }
    public function setPassword($password) {
        if (strlen($password) < 6) {
            throw new Exception('Password must be at least 6 characters long.');
        }
        $this->password = password_hash($password, PASSWORD_DEFAULT);
    }
    public function setFullName($full_name) {
        if (empty($full_name)) {
            throw new Exception('Full name is required.');
        }
        $this->full_name = $full_name;
    }
    public function setEmail($email) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email address.');
        }
        $this->email = strtolower($email);
    }
    public function setUserType($user_type) {
        $valid_types = ['admin', 'teacher', 'student'];
        if (!in_array($user_type, $valid_types)) {
            throw new Exception('Invalid user type.');
        }
        $this->user_type = $user_type;
    }
    public function setIsActive($is_active) {
        $this->is_active = (bool)$is_active;
    }

    // Load user from DB by ID
    public function loadById($id) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        if ($user) {
            $this->fillFromArray($user);
            return true;
        }
        return false;
    }

    // Load user from DB by username
    public function loadByUsername($username) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user) {
            $this->fillFromArray($user);
            return true;
        }
        return false;
    }

    // Save new user to DB
    public function save() {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('INSERT INTO users (username, password, full_name, email, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?)');
        $result = $stmt->execute([
            $this->username,
            $this->password,
            $this->full_name,
            $this->email,
            $this->user_type,
            $this->is_active ? 1 : 0
        ]);
        if ($result) {
            $this->id = $pdo->lastInsertId();
            
            // Auto-create student profile if this is a student
            if ($this->user_type === 'student') {
                require_once __DIR__ . '/functions.php';
                createStudentProfile($this->id);
            }
        }
        return $result;
    }

    // Update existing user in DB
    public function update() {
        if (!$this->id) throw new Exception('User ID is required for update.');
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('UPDATE users SET username=?, full_name=?, email=?, user_type=?, is_active=? WHERE id=?');
        return $stmt->execute([
            $this->username,
            $this->full_name,
            $this->email,
            $this->user_type,
            $this->is_active ? 1 : 0,
            $this->id
        ]);
    }

    // Fill object from DB array
    private function fillFromArray($arr) {
        $this->id = $arr['id'];
        $this->username = $arr['username'];
        $this->password = $arr['password'];
        $this->full_name = $arr['full_name'];
        $this->email = $arr['email'];
        $this->user_type = $arr['user_type'];
        $this->is_active = $arr['is_active'];
        $this->created_at = $arr['created_at'] ?? null;
        $this->updated_at = $arr['updated_at'] ?? null;
    }
} 