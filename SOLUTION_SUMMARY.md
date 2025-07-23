# Solution: Students Not Showing in Table

## Problem
When admins added new students via the registration system, they were not appearing in the students table on the students.php page.

## Root Cause
The issue had two main components:

1. **Database Schema Mismatch**: The database ENUM for `user_type` only allowed `'admin', 'user', 'moderator'` but the application expected `'admin', 'teacher', 'student'`.

2. **Missing Student Profiles**: The `getAllStudents()` function uses a LEFT JOIN with the `student_profiles` table, but new students weren't getting profiles created automatically.

## Solution

### 1. Fixed Database Schema
- Updated the `users` table ENUM to include the correct user types: `'admin', 'teacher', 'student'`
- Recreated the database with the correct schema in `config/database.php`

### 2. Auto-Create Student Profiles
- Modified the `User` class (`includes/User.php`) to automatically create a student profile when a student is saved
- Added `createStudentProfile()` function in `includes/functions.php` to handle profile creation with auto-generated roll numbers

### 3. Auto-Fix Existing Students
- Added `fixStudentsWithoutProfiles()` function to fix any existing students without profiles
- Updated `students.php` to automatically fix missing profiles when the page loads (admin only)

### 4. Added Utility Scripts
- Created `fix_student_profiles.php` for manually checking and fixing student profiles
- Created test scripts to verify the fix works correctly

## Files Modified

1. **config/database.php**: Fixed ENUM values and database schema
2. **includes/User.php**: Added auto-creation of student profiles
3. **includes/functions.php**: Added helper functions for profile management
4. **students.php**: Added auto-fix functionality

## Testing
The solution was tested with:
- Creating new students through the registration system
- Verifying students appear correctly in the students table
- Checking that profiles are created automatically with proper roll numbers

## Result
✅ **FIXED**: New students added by admins now appear correctly in the students table with proper roll numbers and profiles.

## Usage
- Admins can now add students via "Add New Student" and they will appear immediately
- Existing students without profiles are automatically fixed when viewing the students page
- Manual fixes can be performed using `fix_student_profiles.php`
