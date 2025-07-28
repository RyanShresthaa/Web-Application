# 🔐 Multi-Login System - Login Credentials

## 📋 Default System Accounts

| User Type | Username | Password | Full Name | Email | Access Level |
|-----------|----------|----------|-----------|--------|--------------|
| **👨‍💼 Admin** | `admin` | `admin123` | System Administrator | admin@school.com | **FULL ACCESS** |

## 👩‍🏫 Teacher Accounts

| Username | Password | Full Name | Email | Access Level |
|----------|----------|-----------|--------|--------------|
| `teacher1` | `teacher123` | Sarah Johnson | sarah.johnson@school.com | **Teacher Access** |
| `teacher2` | `teacher123` | Michael Brown | michael.brown@school.com | **Teacher Access** |

## 👨‍🎓 Student Accounts

| Username | Password | Full Name | Email | Roll Number | Access Level |
|----------|----------|-----------|--------|-------------|--------------|
| `student1` | `student123` | Alex Smith | alex.smith@student.com | STU001 | **Student Access** |
| `student2` | `student123` | Emma Wilson | emma.wilson@student.com | STU002 | **Student Access** |
| `student3` | `student123` | David Lee | david.lee@student.com | STU003 | **Student Access** |

---

## 🚀 Quick Access Summary

### **🔥 ADMIN LOGIN (Full Control)**
```
Username: admin
Password: admin123
```
**Can Access:** Everything - manage users, students, classes, subjects, view logs

### **📚 TEACHER LOGIN**
```
Username: teacher1 (or teacher2)
Password: teacher123
```
**Can Access:** Students, attendance, marks, own profile

### **🎒 STUDENT LOGIN**
```
Username: student1 (or student2 or student3)
Password: student123
```
**Can Access:** Own attendance, own marks, own profile

---

## 🌟 Testing Different User Types

### **Admin Test:**
1. Login with `admin` / `admin123`
2. You'll see: Users, Students, Classes, Subjects, Activity Log
3. Can add new students via "Add New Student"

### **Teacher Test:**
1. Login with `teacher1` / `teacher123`
2. You'll see: Students, Attendance, Marks
3. Can view all students and manage their attendance/marks

### **Student Test:**
1. Login with `student1` / `student123`
2. You'll see: My Attendance, My Marks
3. Can only see own information

---

## 🔒 Security Notes

### **Production Deployment:**
⚠️ **IMPORTANT:** Before going live, you MUST:

1. **Change Admin Password:**
   ```sql
   UPDATE users SET password = '[NEW_HASHED_PASSWORD]' WHERE username = 'admin';
   ```

2. **Change Default Passwords:**
   - All teacher and student accounts should have unique passwords
   - Remove or change default accounts

3. **Remove Test Accounts:**
   - Delete sample accounts if not needed
   - Keep only necessary users

### **Password Security:**
- All passwords are hashed using PHP's `password_hash()` function
- Default passwords are for testing only
- In production, enforce strong password policies

---

## 📱 Mobile/Tablet Access

These same credentials work on any device:
- **Desktop:** http://localhost/Multi-Login/
- **Mobile:** http://[your-ip]/Multi-Login/
- **Network:** http://[server-ip]/Multi-Login/

---

## 🛠️ Adding New Users

### **As Admin:**
1. Login as admin
2. Go to "Manage Users" or "Add New Student"
3. Create new accounts with secure passwords

### **Bulk User Import:**
For multiple users, you can modify the database initialization file or use SQL scripts.

---

## 🔄 Password Reset

Currently, password changes can be done via:
1. **Users themselves:** Through "Change Password" page
2. **Admin:** Through "Manage Users" page
3. **Database direct:** Update password hash in database

---

## 📊 System Statistics

**Default Setup Includes:**
- 1 Administrator
- 2 Teachers  
- 3 Students
- 6 Subjects (Math, English, Physics, Chemistry, CS, History)
- 4 Classes (10A, 10B, 11A, 11B)

**Total Accounts:** 6 users ready to test all functionality!

---

## 🎯 Recommended Testing Order

1. **Start with Admin** (`admin`/`admin123`) - See everything
2. **Try Teacher** (`teacher1`/`teacher123`) - See teacher view
3. **Test Student** (`student1`/`student123`) - See student view
4. **Create New Student** as admin - Test the fixed functionality!

This gives you complete access to test all features and user roles! 🚀
