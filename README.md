# Multi-Role Tech Support & Community Platform

A comprehensive PHP-based web application designed to facilitate tech support and community interaction among users, admins, and super admins. This platform enables users to share tech problems, upload tutorials, showcase products, and engage in real-time chat discussions.

## Table of Contents
- [Features](#features)
- [Architecture](#architecture)
- [Installation & Setup](#installation--setup)
- [Database Schema](#database-schema)
- [User Roles & Permissions](#user-roles--permissions)
- [Usage Guide](#usage-guide)
- [Configuration](#configuration)
- [Security](#security)
- [API/Endpoints](#apiendpoints)
- [Contributing](#contributing)
- [License](#license)

## Features

### Core Functionality
- **User Management**: Registration, authentication, and profile management with role-based access control
- **Problem Sharing**: Users can post tech problems categorized by type (Software, Hardware, Networking, Mobile systems, Laptop/Desktop systems)
- **Tutorial System**: User-uploaded tutorials with admin approval workflow
- **Product Showcase**: Users can showcase tech products with descriptions and demo videos
- **Real-time Chat**: Central group chat for community discussions with voice calls, video calls, and voicemails
- **Voice & Video Calls**: Peer-to-peer communication using WebRTC for real-time audio/video calls
- **Voicemail System**: Record and send audio messages in chat
- **Media Uploads**: Support for images, videos, and audio files with size and type validation
- **Admin Dashboard**: Comprehensive admin interface for user management and content moderation
- **Activity Logging**: Detailed logging of admin actions for audit purposes
- **System Settings**: Configurable system-wide settings managed by super admins

### Technical Features
- MVC Architecture with separation of concerns
- PDO-based database abstraction with prepared statements
- Secure password hashing using PHP's password_hash()
- Session-based authentication with role validation
- File upload handling with validation and security checks
- Responsive design with CSS styling
- AJAX-powered chat functionality
- RESTful API endpoints for chat operations

## Architecture

### Technology Stack
- **Backend**: PHP 7.4+ with PDO for database operations
- **Database**: MySQL 5.7+ with UTF8MB4 character set
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Architecture Pattern**: Model-View-Controller (MVC)

### Directory Structure
```
tech_support_platform/
├── app/
│   ├── config.php              # Application configuration and constants
│   ├── controllers/            # Business logic controllers
│   │   ├── AuthController.php  # Authentication handling
│   │   └── ProfileController.php # Profile management
│   ├── models/                 # Data models and database operations
│   │   ├── Database.php        # Database connection singleton
│   │   ├── User.php            # User management operations
│   │   ├── AdminActivityLog.php # Admin activity logging
│   │   ├── Chat.php            # Chat message operations
│   │   ├── Media.php           # File upload handling
│   │   ├── Problem.php         # Problem management
│   │   ├── Product.php         # Product showcase operations
│   │   ├── SystemSettings.php  # System configuration
│   │   └── Tutorial.php        # Tutorial management
│   └── views/                  # View templates and includes
│       ├── includes/           # Reusable view components
│       │   ├── header.php      # Page header
│       │   ├── footer.php      # Page footer
│       │   ├── admin_sidebar.php # Admin navigation
│       │   └── user_sidebar.php  # User navigation
├── public/                     # Public web-accessible files
│   ├── index.php               # Application entry point
│   ├── login.php               # Login page
│   ├── register.php            # Registration page
│   ├── admin_dashboard.php     # Admin dashboard
│   ├── user_dashboard.php      # User dashboard
│   ├── user_dashboard_update_profile.php # Profile update
│   ├── chat_api.php            # Chat API endpoint
│   ├── create_superadmin.php   # Super admin creation utility
│   ├── splash.php              # Landing page
│   ├── logout.php              # Logout handler
│   └── assets/                 # Static assets
│       ├── css/                # Stylesheets
│       ├── js/                 # JavaScript files
│       └── img/                # Images
├── uploads/                    # User-uploaded files
├── database_schema.sql         # Database schema definition
├── setup.php                   # Initial setup script
└── README.md                   # This file
```

## Installation & Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- Composer (optional, for dependency management)
- Git

### Installation Steps

1. **Clone the Repository**
   ```bash
   git clone <repository-url>
   cd tech_support_platform
   ```

2. **Configure Database**
   - Create a new MySQL database
   - Update `app/config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');
   define('DB_NAME', 'your_db_name');
   ```

3. **Run Setup Script**
   - Access `setup.php` in your web browser or via command line:
   ```bash
   php setup.php
   ```
   - This will create database tables and the initial super admin user

4. **Configure Web Server**
   - Point your web server document root to the `public/` directory
   - Ensure proper permissions for `uploads/` directory (755)
   - Configure URL rewriting if using clean URLs

5. **Initial Login**
   - Default super admin credentials:
     - Email: superadmin@platform.com
     - Password: password123
   - **Important**: Change the password immediately after first login

### Post-Installation
- Verify file permissions for uploads directory
- Test user registration and login functionality
- Configure email settings if implementing notifications
- Set up SSL certificate for production deployment

## Database Schema

The application uses 11 interconnected tables to manage all data:

### Core Tables

1. **users**
   - Stores user accounts with role-based permissions
   - Fields: id, username, email, password (hashed), role, is_active, created_at, profile_pic

2. **media**
   - Manages uploaded files metadata
   - Fields: id, user_id, file_path, file_type, file_size, uploaded_at
   - Supports images, videos, and audio files

3. **problems**
   - User-submitted tech problems
   - Fields: id, user_id, title, description, category, status, created_at

4. **products**
   - User-showcased tech products
   - Fields: id, user_id, name, description, demo_video_path, created_at

5. **tutorials**
   - Educational content with approval workflow
   - Fields: id, user_id, title, content, category, status, approved_by, approved_at, created_at

6. **chat_messages**
   - Real-time community chat messages with support for text, audio, and call messages
   - Fields: id, user_id, message, message_type, media_id, call_duration, sent_at

7. **system_settings**
   - Configurable system-wide settings
   - Fields: id, setting_key, setting_value, updated_at

8. **admin_activity_log**
   - Audit trail for admin actions
   - Fields: id, admin_id, action, details, ip_address, created_at

### Junction Tables (Many-to-Many Relationships)

9. **problem_media** - Links problems to uploaded media
10. **product_media** - Links products to uploaded media
11. **tutorial_media** - Links tutorials to uploaded media

### Relationships
- Users can create multiple problems, products, tutorials, and chat messages
- Media files can be attached to problems, products, or tutorials
- Admins approve tutorials and their actions are logged
- System settings control platform behavior

## User Roles & Permissions

### Super Admin (ROLE_SUPER_ADMIN)
- Full system access and control
- Create and manage admin accounts
- Configure system-wide settings
- Access all admin functions
- View all activity logs
- Block/unblock the entire system

### Admin (ROLE_ADMIN)
- Moderate user-generated content
- Approve/reject tutorials
- Manage user accounts (activate/suspend/delete)
- View activity logs for their actions
- Access admin dashboard
- Limited to 5 admins maximum (configurable)

### User (ROLE_USER)
- Register and manage personal profile
- Post tech problems and solutions
- Upload tutorials (pending admin approval)
- Showcase products with media
- Participate in community chat
- Upload and manage personal media files

### Permission Matrix

| Feature | Super Admin | Admin | User |
|---------|-------------|-------|------|
| User Management | ✓ | ✓ | ✗ |
| Content Moderation | ✓ | ✓ | ✗ |
| System Settings | ✓ | ✗ | ✗ |
| View All Logs | ✓ | ✗ | ✗ |
| Post Problems | ✓ | ✓ | ✓ |
| Upload Tutorials | ✓ | ✓ | ✓ |
| Showcase Products | ✓ | ✓ | ✓ |
| Community Chat | ✓ | ✓ | ✓ |
| Profile Management | ✓ | ✓ | ✓ |

## Usage Guide

### For Users

1. **Registration & Login**
   - Visit the login page and select "Register"
   - Provide username, email, and password
   - Login with your credentials

2. **Profile Management**
   - Access profile settings from dashboard
   - Update personal information and profile picture
   - Profile pictures are validated for type and size

3. **Sharing Problems**
   - Navigate to "Problems" section
   - Create new problem with title, description, and category
   - Attach relevant media files (images/videos)

4. **Uploading Tutorials**
   - Go to "Tutorials" section
   - Submit tutorial with title, content, and category
   - Wait for admin approval before it becomes visible

5. **Showcasing Products**
   - Access "Products" section
   - Add product with name, description, and demo video
   - Attach additional media files

6. **Community Chat**
   - Join the real-time chat from any dashboard
   - Messages appear instantly for all online users
   - Chat history is preserved

### For Admins

1. **Dashboard Overview**
   - View system statistics and recent activity
   - Access user management and content moderation

2. **User Management**
   - View all registered users
   - Activate/suspend user accounts
   - Delete users if necessary

3. **Content Moderation**
   - Review pending tutorials
   - Approve or reject submissions
   - All moderation actions are logged

4. **Activity Monitoring**
   - View logs of admin actions
   - Track system usage and moderation activities

### For Super Admins

1. **System Configuration**
   - Access system settings panel
   - Configure platform-wide parameters
   - Set limits and enable/disable features

2. **Admin Management**
   - Create new admin accounts
   - Monitor admin activity logs
   - Manage admin permissions

3. **System Control**
   - Block/unblock entire platform
   - Configure maximum admin limits
   - Set system-wide policies

## Configuration

### config.php Settings

The `app/config.php` file contains all application constants:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'tech_support_db');

// Application Settings
define('APP_NAME', 'Multi-Role Tech Support & Community Platform');
define('BASE_URL', '/'); // Adjust for subdirectory installations
define('ROOT_PATH', dirname(__DIR__) . '/');

// User Roles
define('ROLE_SUPER_ADMIN', 'super_admin');
define('ROLE_ADMIN', 'admin');
define('ROLE_USER', 'user');

// Media Settings
define('UPLOAD_DIR', ROOT_PATH . 'uploads/');
define('MAX_FILE_SIZE', 1024 * 1024 * 50); // 50MB
define('MAX_VIDEO_DURATION_SECONDS', 60); // 1 minute

// System Limits
define('MAX_ADMINS', 5);

// System Setting Keys
define('SETTING_MAX_ADMINS', 'max_admins');
define('SETTING_SYSTEM_BLOCKED', 'system_blocked');
define('SETTING_BLOCK_REASON', 'block_reason');
```

### System Settings

Configurable through the admin interface:
- **max_admins**: Maximum number of admin accounts (default: 5)
- **system_blocked**: Boolean to block entire platform access
- **block_reason**: Message displayed when system is blocked

## Security

### Authentication & Authorization
- Passwords hashed using PHP's `password_hash()` with PASSWORD_DEFAULT algorithm
- Session-based authentication with automatic timeout
- Role-based access control for all operations
- Account suspension mechanism for inactive/misbehaving users

### File Upload Security
- File type validation using MIME type checking
- Size limits enforced (50MB max, 5MB for profile pictures)
- Secure filename generation to prevent path traversal
- Files stored outside web root in `uploads/` directory

### Data Protection
- PDO prepared statements prevent SQL injection
- Input sanitization and validation
- CSRF protection through session validation
- Error logging without exposing sensitive information

### Session Management
- Secure session configuration
- Automatic logout on role changes or account suspension
- Session regeneration on privilege escalation

## API/Endpoints

### Public Endpoints

| Endpoint | Method | Description | Access |
|----------|--------|-------------|---------|
| `/index.php` | GET | Application entry point | Public |
| `/login.php` | GET/POST | User authentication | Public |
| `/register.php` | GET/POST | User registration | Public |
| `/splash.php` | GET | Landing page | Public |
| `/logout.php` | GET | Session termination | Authenticated |
| `/admin_dashboard.php` | GET | Admin control panel | Admin+ |
| `/user_dashboard.php` | GET | User dashboard | Users |
| `/user_dashboard_update_profile.php` | GET/POST | Profile management | Authenticated |
| `/chat_api.php` | GET/POST | Chat operations | Authenticated |

### Chat API

The chat system uses AJAX for real-time messaging with support for text messages, voice calls, video calls, and voicemails:

**Send Text Message** (POST)
```javascript
fetch('chat_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ message: 'Hello world' })
});
```

**Send Audio/Voicemail Message** (POST)
```javascript
fetch('chat_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        message: 'Voicemail',
        message_type: 'audio',
        media_id: 123
    })
});
```

**Send Call Message** (POST)
```javascript
fetch('chat_api.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        message: 'Call started',
        message_type: 'call',
        call_duration: 120
    })
});
```

**Get Messages** (GET)
```javascript
fetch('chat_api.php?last_id=' + lastMessageId)
    .then(response => response.json())
    .then(data => {
        data.messages.forEach(message => {
            if (message.message_type === 'audio' && message.media_path) {
                // Display audio player
            } else if (message.message_type === 'call') {
                // Display call information
            }
        });
    });
```

**Upload Voicemail** (POST)
```javascript
const formData = new FormData();
formData.append('audio', audioBlob, 'voicemail.mp3');

fetch('upload_voicemail.php', {
    method: 'POST',
    body: formData
})
.then(response => response.json())
.then(data => {
    // Use data.media_id to send chat message
});
```

### Response Formats
- Authentication endpoints return redirects
- API endpoints return JSON responses
- Error states redirect to appropriate pages with messages

## Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes following the existing code style
4. Test thoroughly across different user roles
5. Submit a pull request with detailed description

### Code Standards
- Follow PSR-12 coding standards for PHP
- Use meaningful variable and function names
- Include PHPDoc comments for all classes and methods
- Validate all user inputs and handle errors gracefully
- Maintain separation of concerns in MVC pattern

### Testing
- Test user registration and authentication
- Verify role-based access controls
- Test file upload functionality with various file types
- Validate admin moderation workflows
- Check database relationships and constraints

## License

This project is licensed under the MIT License - see the LICENSE file for details.

---

**Note**: This platform is designed for educational and community purposes. For production deployment, additional security measures such as SSL certificates, regular security audits, and backup strategies should be implemented.
