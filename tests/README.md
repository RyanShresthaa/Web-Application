# Testing Files Directory

This directory contains all testing, debugging, and utility files for the Student Management System.

## Test Files

### User Management Tests
- **test_user_type.php** - Test user type functionality
- **test_create_student.php** - Test student creation process
- **test_new_student.php** - Test new student registration
- **test_students.php** - Test student listing and management
- **test_add_student.php** - Test adding students to the system
- **test_all_users.php** - Test user listing functionality
- **test_student_login.php** - Test student login functionality
- **test_student_profiles.php** - Test student profile creation

### Debug Files
- **debug.php** - General debugging utilities
- **debug_issue.php** - Issue-specific debugging
- **debug_students.php** - Student-related debugging
- **debug_student_login.php** - Student login debugging

### Database and Schema Files
- **check_schema.php** - Database schema validation
- **check_student.php** - Student data validation

### Fix Scripts
- **fix_students.php** - Fix student-related issues
- **fix_student_profiles.php** - Fix student profile issues
- **fix_user_types.php** - Fix user type issues

### Other Tests
- **test_final.php** - Final system testing
- **simple_student_test.php** - Simple student functionality test

## Usage

These files are for development and testing purposes only. They should not be used in production.

To run a test file:
```bash
php tests/filename.php
```

## Notes

- All test files are kept separate from the main application files
- These files may contain debugging information and should not be exposed to end users
- Some files may modify database data - use with caution
- Always backup your database before running test files 