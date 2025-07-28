# Multi-Login System

A comprehensive PHP-based multi-user login system with role-based access control, session management, and activity logging.

## Features

- **Multi-User Authentication**: Support for different user types (Admin, Moderator, User)
- **Role-Based Access Control**: Different permissions and views based on user type
- **Session Management**: Secure session handling with database tracking
- **Activity Logging**: Track login attempts and user activities
- **User Management**: Admin panel for managing users
- **Modern UI**: Beautiful, responsive interface with Bootstrap 5
- **Security Features**: Password hashing, input sanitization, SQL injection protection

## User Types

### Administrator
- Full system access
- Manage all users
- View activity logs
- Create/edit/delete users
- Access to all features

### Moderator
- Limited administrative access
- User management capabilities
- Activity monitoring
- Cannot delete admin users

### Regular User
- Basic system access
- Profile management
- Password changes
- Limited dashboard features

## System Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- PDO MySQL extension
- Session support

## Installation

1. **Clone or download the project** to your web server directory
2. **Configure database settings** in `config/database.php`:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   define('DB_NAME', 'multi_login_system');
   ```

3. **Set up your web server** to point to the project directory

4. **Access the system** through your web browser:
   ```
   http://localhost/Multi-Login/
   ```

5. **Database will be automatically created** on first access

## Default Users

The system creates these default users on first run:

| Username | Password | Type | Description |
|----------|----------|------|-------------|
| admin | admin123 | Admin | System Administrator |
| john_doe | user123 | User | Sample User |
| jane_smith | user123 | User | Sample User |
| moderator1 | mod123 | Moderator | Sample Moderator |
| moderator2 | mod123 | Moderator | Sample Moderator |

## File Structure

```
Multi-Login/
├── index.php              # Main login page
├── dashboard.php          # User dashboard
├── register.php           # User registration
├── profile.php            # User profile management
├── change_password.php    # Password change
├── users.php              # User management (admin)
├── activity.php           # Activity logs (admin)
├── logout.php             # Logout functionality
├── config/
│   └── database.php       # Database configuration
├── includes/
│   └── functions.php      # Core functions
└── README.md              # This file
```

## Database Schema

### Users Table
- `id`: Primary key
- `username`: Unique username
- `password`: Hashed password
- `full_name`: User's full name
- `email`: Email address
- `user_type`: User role (admin/moderator/user)
- `is_active`: Account status
- `created_at`: Account creation date
- `updated_at`: Last update date

### Login Activity Table
- `id`: Primary key
- `user_id`: Foreign key to users
- `user_type`: User type at login
- `login_time`: Login timestamp
- `ip_address`: User's IP address
- `user_agent`: Browser information
- `status`: Login success/failure

### User Sessions Table
- `id`: Primary key
- `user_id`: Foreign key to users
- `session_id`: PHP session ID
- `ip_address`: Session IP
- `user_agent`: Session browser info
- `created_at`: Session creation
- `expires_at`: Session expiration
- `is_active`: Session status

## Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Protection**: Prepared statements throughout
- **XSS Protection**: Input sanitization and output escaping
- **Session Security**: Secure session handling with database tracking
- **Role-Based Access**: Strict permission checking
- **Input Validation**: Comprehensive form validation

## Customization

### Adding New User Types
1. Update the `user_type` ENUM in the database
2. Modify `getUserTypeDisplayName()` function
3. Update permission checks in `hasPermission()`
4. Add UI elements for the new type

### Styling
The system uses Bootstrap 5 with custom CSS. Main styling is in:
- `index.php` (login page styles)
- `dashboard.php` (dashboard styles)
- Other pages have inline styles for consistency

### Database Configuration
Edit `config/database.php` to change:
- Database connection settings
- Default users
- Table structure (if needed)

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database user permissions

2. **Session Issues**
   - Check PHP session configuration
   - Ensure write permissions for session directory
   - Verify session cookies are enabled

3. **Permission Denied**
   - Check file permissions (755 for directories, 644 for files)
   - Ensure web server can read/write to project directory

4. **Login Not Working**
   - Verify database tables exist
   - Check if default users were created
   - Ensure password hashing is working

### Debug Mode
To enable debug mode, add this to the top of any PHP file:
```php
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

## API Endpoints

The system provides these main endpoints:

- `GET /` - Login page
- `POST /` - Login authentication
- `GET /dashboard.php` - User dashboard
- `GET /users.php` - User management (admin)
- `GET /activity.php` - Activity logs (admin)
- `GET /profile.php` - User profile
- `POST /profile.php` - Update profile
- `GET /change_password.php` - Password change form
- `POST /change_password.php` - Change password
- `GET /logout.php` - Logout user

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support or questions:
1. Check the troubleshooting section
2. Review the code comments
3. Create an issue on the repository

## Changelog

### Version 1.0.0
- Initial release
- Multi-user authentication
- Role-based access control
- Session management
- Activity logging
- User management interface
- Modern responsive UI 