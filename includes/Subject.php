<?php
require_once __DIR__ . '/../config/database.php';

class Subject {
    private $id;
    private $name;
    private $code;
    private $description;
    private $teacher_id;
    private $is_active;
    private $created_at;

    // Accessors
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getCode() { return $this->code; }
    public function getDescription() { return $this->description; }
    public function getTeacherId() { return $this->teacher_id; }
    public function isActive() { return $this->is_active; }
    public function getCreatedAt() { return $this->created_at; }

    // Mutators
    public function setName($name) {
        if (empty($name)) throw new Exception('Subject name is required.');
        $this->name = $name;
    }
    public function setCode($code) {
        if (empty($code)) throw new Exception('Subject code is required.');
        $this->code = strtoupper($code);
    }
    public function setDescription($description) {
        $this->description = $description;
    }
    public function setTeacherId($teacher_id) {
        $this->teacher_id = $teacher_id;
    }
    public function setIsActive($is_active) {
        $this->is_active = (bool)$is_active;
    }

    // Load by ID
    public function loadById($id) {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('SELECT * FROM subjects WHERE id = ?');
        $stmt->execute([$id]);
        $subject = $stmt->fetch();
        if ($subject) {
            $this->fillFromArray($subject);
            return true;
        }
        return false;
    }

    // Save new subject
    public function save() {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('INSERT INTO subjects (name, code, description, teacher_id, is_active) VALUES (?, ?, ?, ?, ?)');
        $result = $stmt->execute([
            $this->name,
            $this->code,
            $this->description,
            $this->teacher_id,
            $this->is_active ? 1 : 0
        ]);
        if ($result) {
            $this->id = $pdo->lastInsertId();
        }
        return $result;
    }

    // Update subject
    public function update() {
        if (!$this->id) throw new Exception('Subject ID is required for update.');
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('UPDATE subjects SET name=?, code=?, description=?, teacher_id=?, is_active=? WHERE id=?');
        return $stmt->execute([
            $this->name,
            $this->code,
            $this->description,
            $this->teacher_id,
            $this->is_active ? 1 : 0,
            $this->id
        ]);
    }

    // Fill from array
    private function fillFromArray($arr) {
        $this->id = $arr['id'];
        $this->name = $arr['name'];
        $this->code = $arr['code'];
        $this->description = $arr['description'];
        $this->teacher_id = $arr['teacher_id'];
        $this->is_active = $arr['is_active'];
        $this->created_at = $arr['created_at'] ?? null;
    }
} 