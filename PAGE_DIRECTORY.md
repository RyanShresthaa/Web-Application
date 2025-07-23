# Multi-Login System - Page Directory

## 📋 Main Application Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **index.php** | Login Page | Public | Main login interface for all user types (Admin, Teacher, Student) |
| **register.php** | Registration/Add Student | Public/Admin | User registration (public) or Add New Student (admin only) |
| **dashboard.php** | Dashboard | All Users | Main dashboard after login - shows different content based on user type |
| **logout.php** | Logout | All Users | Logs out user and destroys session |

## 👨‍💼 Admin Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **users.php** | Manage Users | Admin Only | View, edit, delete all system users (admins, teachers, students) |
| **students.php** | Manage Students | Admin/Teacher | View all students, access profiles, attendance, marks |
| **classes.php** | Manage Classes | Admin Only | Create and manage class groups (10A, 10B, etc.) |
| **subjects.php** | Manage Subjects | Admin Only | Create and manage subjects (Math, English, etc.) |
| **activity.php** | Activity Log | Admin Only | View system login activity and user actions |

## 👩‍🏫 Teacher Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **attendance.php** | Take Attendance | Teacher/Admin | Record student attendance for classes |
| **marks.php** | Manage Marks | Teacher/Admin | Enter and manage student exam marks/grades |

## 👨‍🎓 Student Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **my_attendance.php** | My Attendance | Student | View own attendance records |
| **my_marks.php** | My Marks | Student | View own marks and grades |

## 👤 Profile & Account Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **profile.php** | User Profile | All Users | View and edit own profile information |
| **student_profile.php** | Student Profile | Admin/Teacher | View detailed student profile (called from students.php) |
| **change_password.php** | Change Password | All Users | Change account password |

## 🔧 System & Utility Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **debug.php** | Debug Info | Dev/Admin | Development debugging information |
| **debug_students.php** | Student Debug | Dev/Admin | Debug student-specific issues |
| **fix_students.php** | Fix Students | Admin | Utility to fix student data issues |
| **check_student.php** | Check Student | Admin | Verify student data integrity |

## 🧪 Test & Maintenance Pages

| Page File | Purpose | Access Level | Description |
|-----------|---------|--------------|-------------|
| **test_add_student.php** | Test Add Student | Dev/Admin | Test student addition functionality |
| **test_all_users.php** | Test All Users | Dev/Admin | Test user retrieval functionality |
| **test_new_student.php** | Test New Student | Dev/Admin | Test new student creation |
| **test_students.php** | Test Students | Dev/Admin | Test student management features |
| **fix_student_profiles.php** | Fix Student Profiles | Admin | Fix students missing profiles |
| **fix_user_types.php** | Fix User Types | Admin | Fix incorrect user type assignments |

## 📂 Backend Files (Not Direct Pages)

| File | Purpose | Description |
|------|---------|-------------|
| **config/database.php** | Database Config | Database connection and table creation |
| **includes/functions.php** | Core Functions | Main system functions (auth, students, etc.) |
| **includes/User.php** | User Class | User object management |
| **includes/Subject.php** | Subject Class | Subject management |
| **includes/ClassEntity.php** | Class Entity | Class management |

## 🚀 Access Control Summary

### **Public Access**
- `index.php` (Login)
- `register.php` (Public registration - if enabled)

### **Student Access**
- `dashboard.php`, `profile.php`, `change_password.php`
- `my_attendance.php`, `my_marks.php`
- `logout.php`

### **Teacher Access**
- All Student Access pages, plus:
- `students.php`, `attendance.php`, `marks.php`
- `student_profile.php`

### **Admin Access**
- All Teacher Access pages, plus:
- `users.php`, `classes.php`, `subjects.php`, `activity.php`
- `register.php` (Add Students)
- All utility and maintenance pages

## 🔗 Navigation Flow

```
index.php (Login) 
    ↓
dashboard.php (Main Hub)
    ↓
├── Admin → users.php, students.php, classes.php, subjects.php, activity.php
├── Teacher → students.php, attendance.php, marks.php  
└── Student → my_attendance.php, my_marks.php, profile.php
```

## 📱 Key Features by User Type

| Feature | Admin | Teacher | Student |
|---------|-------|---------|---------|
| Add/Manage Users | ✅ | ❌ | ❌ |
| Add Students | ✅ | ❌ | ❌ |
| View All Students | ✅ | ✅ | ❌ |
| Take Attendance | ✅ | ✅ | ❌ |
| Enter Marks | ✅ | ✅ | ❌ |
| View Own Attendance | ✅ | ✅ | ✅ |
| View Own Marks | ✅ | ✅ | ✅ |
| Manage Classes/Subjects | ✅ | ❌ | ❌ |
| View Activity Logs | ✅ | ❌ | ❌ |
