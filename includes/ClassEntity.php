<?php
require_once __DIR__ . '/../config/database.php';

class ClassEntity {
    private $id;
    private $name;
    private $grade_level;
    private $academic_year;
    private $teacher_id;
    private $is_active;
    private $created_at;

    // Accessors
    public function getId() { return $this->id; }
    public function getName() { return $this->name; }
    public function getGradeLevel() { return $this->grade_level; }
    public function getAcademicYear() { return $this->academic_year; }
    public function getTeacherId() { return $this->teacher_id; }
    public function isActive() { return $this->is_active; }
    public function getCreatedAt() { return $this->created_at; }

    // Mutators
    public function setName($name) {
        if (empty($name)) throw new Exception('Class name is required.');
        $this->name = $name;
    }
    public function setGradeLevel($grade_level) {
        if (empty($grade_level)) throw new Exception('Grade level is required.');
        $this->grade_level = $grade_level;
    }
    public function setAcademicYear($academic_year) {
        if (empty($academic_year)) throw new Exception('Academic year is required.');
        $this->academic_year = $academic_year;
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
        $stmt = $pdo->prepare('SELECT * FROM classes WHERE id = ?');
        $stmt->execute([$id]);
        $class = $stmt->fetch();
        if ($class) {
            $this->fillFromArray($class);
            return true;
        }
        return false;
    }

    // Save new class
    public function save() {
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('INSERT INTO classes (name, grade_level, academic_year, teacher_id, is_active) VALUES (?, ?, ?, ?, ?)');
        $result = $stmt->execute([
            $this->name,
            $this->grade_level,
            $this->academic_year,
            $this->teacher_id,
            $this->is_active ? 1 : 0
        ]);
        if ($result) {
            $this->id = $pdo->lastInsertId();
        }
        return $result;
    }

    // Update class
    public function update() {
        if (!$this->id) throw new Exception('Class ID is required for update.');
        $pdo = getDBConnection();
        $stmt = $pdo->prepare('UPDATE classes SET name=?, grade_level=?, academic_year=?, teacher_id=?, is_active=? WHERE id=?');
        return $stmt->execute([
            $this->name,
            $this->grade_level,
            $this->academic_year,
            $this->teacher_id,
            $this->is_active ? 1 : 0,
            $this->id
        ]);
    }

    // Fill from array
    private function fillFromArray($arr) {
        $this->id = $arr['id'];
        $this->name = $arr['name'];
        $this->grade_level = $arr['grade_level'];
        $this->academic_year = $arr['academic_year'];
        $this->teacher_id = $arr['teacher_id'];
        $this->is_active = $arr['is_active'];
        $this->created_at = $arr['created_at'] ?? null;
    }
} 