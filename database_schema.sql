-- Database Schema for Multi-Role Tech Support & Community Platform

-- 1. Users Table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL, -- Store hashed password
    firebase_uid VARCHAR(255) UNIQUE DEFAULT NULL, -- Firebase UID for OAuth users
    role ENUM('super_admin', 'admin', 'user') NOT NULL DEFAULT 'user',
    is_active BOOLEAN NOT NULL DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    profile_pic VARCHAR(255) DEFAULT NULL -- Path to the user's profile picture
);

-- 2. Media Table (Stores metadata for all uploaded files)
CREATE TABLE media (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    file_path VARCHAR(255) NOT NULL, -- Path relative to the 'uploads' directory
    file_type ENUM('image', 'video', 'audio') NOT NULL,
    file_size INT NOT NULL, -- Size in bytes
    uploaded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 3. Problems Table (User-shared tech problems)
CREATE TABLE problems (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    category ENUM('Software', 'Hardware', 'Networking', 'Mobile systems', 'Laptop/Desktop systems') NOT NULL,
    status ENUM('open', 'in_progress', 'resolved') NOT NULL DEFAULT 'open',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 4. Junction Table for Problems and Media (Many-to-Many)
CREATE TABLE problem_media (
    problem_id INT NOT NULL,
    media_id INT NOT NULL,
    PRIMARY KEY (problem_id, media_id),
    FOREIGN KEY (problem_id) REFERENCES problems(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- 5. Products Table (User-showcased tech products)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    demo_video_path VARCHAR(255), -- Can be a path to a video in 'media' or an external link
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- 6. Junction Table for Products and Media (Many-to-Many)
CREATE TABLE product_media (
    product_id INT NOT NULL,
    media_id INT NOT NULL,
    PRIMARY KEY (product_id, media_id),
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- 7. Tutorials Table (User-uploaded tutorials)
CREATE TABLE tutorials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    category ENUM('Software', 'Hardware', 'Networking', 'Mobile systems', 'Laptop/Desktop systems') NOT NULL,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT NULL,
    approved_at DATETIME NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
);

-- 8. Junction Table for Tutorials and Media (Many-to-Many)
CREATE TABLE tutorial_media (
    tutorial_id INT NOT NULL,
    media_id INT NOT NULL,
    PRIMARY KEY (tutorial_id, media_id),
    FOREIGN KEY (tutorial_id) REFERENCES tutorials(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE CASCADE
);

-- 9. Chat Messages Table (Central real-time group chat)
CREATE TABLE chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'audio', 'call') DEFAULT 'text',
    media_id INT NULL,
    call_duration INT NULL, -- Duration in seconds for calls
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE SET NULL
);

-- 9.1. Admin Chat Messages Table (Admin-only chat)
CREATE TABLE admin_chat_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    message TEXT NOT NULL,
    message_type ENUM('text', 'audio', 'call') DEFAULT 'text',
    media_id INT NULL,
    call_duration INT NULL, -- Duration in seconds for calls
    sent_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (media_id) REFERENCES media(id) ON DELETE SET NULL
);

-- 10. System Settings Table (For super admin configurations)
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT NOT NULL,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 11. Admin Activity Log Table (For monitoring admin activities)
CREATE TABLE admin_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

**Database Configuration and Class:**

1.  **`config.php`**: Define constants for database connection.
2.  **`Database.php`**: Create a simple PDO-based class for database connection and basic query execution.

I will use placeholder values for the database credentials, as I cannot set up a live database in this environment. The user will need to replace these with their actual credentials.

**Placeholder Credentials:**
*   DB\_HOST: `localhost`
*   DB\_USER: `root`
*   DB\_PASS: `password`
*   DB\_NAME: `tech_support_db`

I will also create a basic `index.php` and `login.php` to start the application flow.

**Next Steps:**

1.  Write `config.php`.
2.  Write `Database.php`.
3.  Write a basic `index.php` to redirect to the splash screen.
4.  Write a basic `login.php`.
5.  Advance to the next phase.
