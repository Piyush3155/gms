# Gym Management System

A comprehensive web-based Gym Management System built with PHP, MySQL, Bootstrap 5, and Chart.js. This system provides role-based access for administrators, trainers, and members with complete CRUD operations and advanced reporting features.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-purple)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

## 📋 Table of Contents

- [Features](#features)
  - [Administrator Features](#-administrator-features)
  - [Trainer Features](#-trainer-features)
  - [Member Features](#-member-features)
  - [System & Advanced Modules](#-system--advanced-modules)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
  - [Prerequisites](#prerequisites)
  - [Quick Installation (5 Minutes)](#quick-installation-5-minutes)
  - [Post-Installation Configuration](#post-installation-configuration)
  - [Troubleshooting Common Issues](#troubleshooting-common-issues)
  - [Advanced Installation Options](#advanced-installation-options)
  - [Verification Checklist](#verification-checklist)
- [Project Structure](#project-structure)
- [Code Guide & Architecture](#code-guide--architecture)
  - [Core Architecture](#core-architecture)
  - [Database Operations Pattern](#3-database-operations-pattern)
  - [Security Best Practices](#7-security-best-practices)
  - [Key Functions Reference](#key-functions-reference)
  - [Customization Guide](#customization-guide)
  - [Performance Optimization Tips](#performance-optimization-tips)
- [Database Schema](#database-schema)
  - [Core Tables](#core-tables)
  - [Advanced Feature Tables](#advanced-feature-tables)
  - [Database Relationships](#database-relationships-diagram)
  - [Sample Queries](#sample-queries)
- [Usage](#usage)
  - [Administrator Guide](#administrator-guide)
  - [Trainer Guide](#trainer-guide)
  - [Member Guide](#member-guide)
  - [API Documentation](#api-documentation)
- [Testing](#testing)
- [Deployment Guide](#deployment-guide)
  - [Production Deployment Steps](#production-deployment-steps)
  - [Docker Deployment](#docker-deployment-alternative)
- [Frequently Asked Questions (FAQ)](#frequently-asked-questions-faq)
- [Contributing](#contributing)
- [Roadmap](#roadmap)
- [Version History](#version-history)
- [License](#license)
- [Support](#support)

## Features

### 🏢 Administrator Features
- **Dashboard**: Overview of total members, trainers, income, and attendance summary
- **Member Management**: Add/edit members, manage renewals, and view active/expired status
- **Trainer Management**: Manage trainers, assign members, view profiles and workload
- **Membership Plans**: Create and manage membership packages (duration, cost, benefits)
- **Attendance Tracking**: Mark or bulk update attendance for members and trainers
- **Payment Management**: Record membership payments, renewals, and print invoices
- **Expense Tracking**: Track gym expenses by category and generate monthly summaries
- **Equipment Management**: Track gym equipment inventory, maintenance, and availability
- **Member Progress Tracking**: Record and visualize member measurements (weight, body fat, etc.)
- **Group Classes**: Schedule group fitness sessions, manage bookings, and set class capacity
- **Notification System (Email)**: Send bulk emails for membership expiry, updates, or promotions
- **Reports & Analytics**: Generate visual and PDF-based reports using Chart.js and FPDF
- **Settings**: Update gym name, tagline, logo, contact info, and other configurations
- **Profile Management**: Admin can edit personal info and change password

### 👨‍🏫 Trainer Features
- **Dashboard**: View assigned members, class schedules, and recent activity
- **Member Management**: View/manage assigned members and track their attendance
- **Workout Plans**: Create and assign workout plans (sets, reps, exercises)
- **Diet Plans**: Create and assign meal plans with calorie/nutrition details
- **Attendance Management**: Mark attendance for assigned members
- **Profile Management**: Update trainer's own profile and photo

### 👤 Member Features
- **Dashboard**: Personal stats overview (attendance, progress, plans)
- **Profile Management**: Update member profile, photo, and contact details
- **Attendance View**: View personal attendance history
- **Workout Plans**: View assigned workouts and exercises
- **Diet Plans**: View assigned diet plans and goals
- **Class Booking**: Browse and book available group fitness classes

### 🧾 System & Advanced Modules
- **Role & Permission Management (RBAC Enhancements)**: Manage multiple roles beyond admin/trainer/member, Assign permissions for each module (view, add, edit, delete)
- **Front Desk / Reception Module**: Quick member registration and renewals, Attendance check-in/out, Simple POS-style payment interface
- **Inventory / Supplement Store Management**: Manage stock of gym supplements and accessories, Record sales, purchases, and supplier details
- **HR & Payroll Management**: Track working hours, generate salaries for trainers/staff, Export salary slips to PDF
- **Feedback & Complaint System**: Members can submit feedback or complaints, Admin can respond and rate trainers
- **Access Control via QR / RFID**: Generate QR code or RFID tag for each member, Scan to auto-mark attendance
- **Admin Activity Log / Audit Trail**: Track all user activities (add/edit/delete), Store who did what and when
- **Backup & Restore Module**: Export and download database backups, Option to restore from backup files
- **Multi-Branch / Franchise Support**: Manage multiple gym branches under one system, Branch-level admin and centralized reporting
- **API Module (for future app integration)**: RESTful API endpoints for mobile app or third-party integration
- **Notification Dashboard (Centralized Alerts)**: Show expiring memberships, pending payments, and equipment maintenance alerts

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, Font Awesome 6, Chart.js
- **PDF Generation**: FPDF library for report exports
- **Authentication**: Session-based with role-based access control
- **File Upload**: Image upload for profiles and gym logo
- **Email**: PHP mail() function for notifications

## Installation

### Prerequisites

Before installing the Gym Management System, ensure you have the following:

- **PHP**: Version 7.4 or higher
  - Required extensions: `mysqli`, `gd`, `mbstring`, `json`
  - Check with: `php -v` and `php -m`
- **MySQL**: Version 5.7 or higher (or MariaDB 10.2+)
- **Apache Web Server**: 
  - WAMP (Windows): [Download here](http://www.wampserver.com/en/)
  - XAMPP (Cross-platform): [Download here](https://www.apachefriends.org/)
- **Web Browser**: Chrome, Firefox, Safari, or Edge (latest version)
- **Text Editor**: VS Code, Sublime Text, or any code editor

### Quick Installation (5 Minutes)

#### Step 1: Download and Place Files

1. **Download the project**
   ```bash
   git clone https://github.com/yourusername/gms.git
   # OR download ZIP and extract
   ```

2. **Move to web server directory**
   - **For WAMP (Windows)**: 
     ```
     C:\wamp64\www\gms\
     ```
   - **For XAMPP (Windows)**:
     ```
     C:\xampp\htdocs\gms\
     ```
   - **For XAMPP (Linux/Mac)**:
     ```
     /opt/lampp/htdocs/gms/
     ```

3. **Verify file structure**
   - Ensure all folders (admin, member, trainer, includes, assets, database) are present
   - Check that `index.php` and `login.php` exist in root directory

#### Step 2: Start Your Web Server

**For WAMP:**
1. Launch WAMP Server
2. Wait until icon turns green
3. Verify Apache and MySQL are running (left-click WAMP icon)

**For XAMPP:**
1. Open XAMPP Control Panel
2. Start Apache and MySQL modules
3. Check that both show "Running" status

#### Step 3: Create Database

**Method 1: Using phpMyAdmin (Recommended)**

1. Open phpMyAdmin in browser:
   ```
   http://localhost/phpmyadmin
   ```

2. Create new database:
   - Click "New" in left sidebar
   - Database name: `gym_management`
   - Collation: `utf8_general_ci`
   - Click "Create"

3. Import schema:
   - Select `gym_management` database
   - Click "Import" tab
   - Choose file: `database/schema.sql` (from project folder)
   - Scroll down and click "Go"
   - Wait for success message: "Import has been successfully finished"

**Method 2: Using Command Line**

```bash
# Navigate to project directory
cd C:\wamp64\www\gms

# Login to MySQL (enter password when prompted, default is blank)
mysql -u root -p

# Create database and import
CREATE DATABASE gym_management;
USE gym_management;
SOURCE database/schema.sql;
EXIT;
```

**Method 3: Using MySQL Workbench**

1. Open MySQL Workbench
2. Connect to local instance
3. Run: `CREATE DATABASE gym_management;`
4. Go to Server → Data Import
5. Select `database/schema.sql`
6. Click "Start Import"

#### Step 4: Configure Database Connection

1. **Open configuration file**:
   ```
   includes/db.php
   ```

2. **Update database credentials** (if needed):
   ```php
   <?php
   // Default configuration for WAMP/XAMPP
   define('DB_HOST', 'localhost');     // Usually 'localhost'
   define('DB_USER', 'root');          // Default MySQL user
   define('DB_PASS', '');              // Default is empty for WAMP/XAMPP
   define('DB_NAME', 'gym_management'); // Database name
   ?>
   ```

3. **Custom MySQL Configuration** (if you changed defaults):
   ```php
   // Example for custom setup
   define('DB_HOST', 'localhost');
   define('DB_USER', 'your_mysql_username');
   define('DB_PASS', 'your_mysql_password');
   define('DB_NAME', 'gym_management');
   ```

4. **Save the file**

#### Step 5: Configure Application Settings

1. **Open application config**:
   ```
   includes/config.php
   ```

2. **Update SITE_URL** (if different):
   ```php
   // For WAMP
   define('SITE_URL', 'http://localhost/gms/');
   
   // For XAMPP or custom port
   define('SITE_URL', 'http://localhost:8080/gms/');
   
   // For custom domain (if using virtual host)
   define('SITE_URL', 'http://gym.local/');
   ```

3. **Verify upload directory permissions**:
   - Folder: `assets/images/`
   - Should be writable (for profile photos and logo uploads)
   - Windows: Right-click folder → Properties → Security
   - Linux/Mac: `chmod 755 assets/images/`

#### Step 6: Test Database Connection

Run the system test script to verify installation:

```bash
# Navigate to project directory
cd C:\wamp64\www\gms

# Run test script
php test_system.php
```

**Expected output:**
```
✓ Database connection successful
✓ All required tables exist
✓ Configuration loaded correctly
✓ Sample data present
✓ File structure intact
```

If you see errors, check:
- Database credentials in `includes/db.php`
- MySQL service is running
- Database `gym_management` exists
- Schema was imported correctly

#### Step 7: Access the Application

1. **Open your web browser**

2. **Navigate to**:
   ```
   http://localhost/gms/
   ```
   or
   ```
   http://localhost/gms/login.php
   ```

3. **Login with default credentials**:

   | Role | Email | Password |
   |------|-------|----------|
   | **Admin** | admin@gym.com | password |
   | **Trainer** | trainer1@gym.com | password |
   | **Member** | member1@gym.com | password |

4. **First-time setup**:
   - Login as admin
   - Go to **Settings** (sidebar)
   - Update gym name, logo, and contact information
   - Change default passwords immediately!

### Post-Installation Configuration

#### Change Default Passwords (CRITICAL!)

1. Login as admin: `admin@gym.com`
2. Go to **Profile** → **Change Password**
3. Update to strong password (min 8 characters, mixed case, numbers, symbols)
4. Repeat for trainer and member test accounts

#### Configure Email Settings (Optional)

For email notifications to work:

1. Open `includes/config.php`
2. Configure SMTP settings:
   ```php
   // Email configuration
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your_email@gmail.com');
   define('SMTP_PASS', 'your_app_password');
   define('FROM_EMAIL', 'noreply@yourgym.com');
   define('FROM_NAME', 'Your Gym Name');
   ```

#### Set Up File Permissions (Linux/Mac)

```bash
cd /path/to/gms
chmod 755 admin/ trainer/ member/ api/ includes/
chmod 777 assets/images/
chmod 644 *.php
```

#### Configure PHP Settings

Edit `php.ini` for optimal performance:

```ini
; File uploads
upload_max_filesize = 10M
post_max_size = 10M

; Session configuration
session.gc_maxlifetime = 3600

; Error reporting (disable in production)
display_errors = Off
error_reporting = E_ALL & ~E_NOTICE
```

### Troubleshooting Common Issues

#### Issue 1: "Connection failed" Error

**Cause**: Database connection problem

**Solution**:
1. Verify MySQL is running (WAMP/XAMPP Control Panel)
2. Check credentials in `includes/db.php`
3. Test MySQL connection:
   ```bash
   mysql -u root -p
   SHOW DATABASES;
   ```
4. Verify database `gym_management` exists

#### Issue 2: Page Shows PHP Code

**Cause**: PHP not processing files

**Solution**:
1. Verify Apache is running
2. Check file extension is `.php` not `.html`
3. Access via `http://localhost/` not `file:///`
4. Restart Apache service

#### Issue 3: "Table doesn't exist" Error

**Cause**: Database schema not imported

**Solution**:
1. Re-import `database/schema.sql`
2. Verify all tables exist:
   ```sql
   USE gym_management;
   SHOW TABLES;
   ```
3. Should see 20+ tables including: users, members, trainers, plans, etc.

#### Issue 4: Cannot Upload Images

**Cause**: Permission issues

**Solution**:
1. Check `assets/images/` folder exists
2. Set write permissions:
   - Windows: Properties → Security → Edit → Add write permission
   - Linux/Mac: `chmod 777 assets/images/`
3. Verify `upload_max_filesize` in php.ini

#### Issue 5: Login Fails with Correct Credentials

**Cause**: Session or database issue

**Solution**:
1. Clear browser cookies and cache
2. Verify users table has data:
   ```sql
   SELECT * FROM users;
   ```
3. Check session configuration in `includes/config.php`
4. Ensure cookies are enabled in browser

#### Issue 6: 404 Not Found Errors

**Cause**: Incorrect SITE_URL configuration

**Solution**:
1. Edit `includes/config.php`
2. Update SITE_URL to match your setup:
   ```php
   define('SITE_URL', 'http://localhost/gms/');
   ```
3. Clear browser cache
4. Restart Apache

### Advanced Installation Options

#### Virtual Host Setup (Optional)

Create a custom domain like `http://gym.local`

**1. Edit Apache config** (`httpd-vhosts.conf`):
```apache
<VirtualHost *:80>
    ServerName gym.local
    DocumentRoot "C:/wamp64/www/gms"
    <Directory "C:/wamp64/www/gms">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

**2. Edit hosts file**:
- Windows: `C:\Windows\System32\drivers\etc\hosts`
- Linux/Mac: `/etc/hosts`

Add:
```
127.0.0.1 gym.local
```

**3. Restart Apache and access**: `http://gym.local`

#### SSL/HTTPS Setup (Optional)

For secure connections:

1. Generate SSL certificate (self-signed for local):
   ```bash
   openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
   -keyout gym.key -out gym.crt
   ```

2. Configure Apache SSL VirtualHost
3. Update SITE_URL to `https://`

#### Docker Installation (Advanced)

Create `docker-compose.yml`:

```yaml
version: '3.8'
services:
  web:
    image: php:7.4-apache
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www/html
  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root
      MYSQL_DATABASE: gym_management
    volumes:
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql
```

Run: `docker-compose up -d`

### Verification Checklist

After installation, verify:

- [ ] Can access `http://localhost/gms/`
- [ ] Login page loads correctly
- [ ] Can login as admin
- [ ] Dashboard displays without errors
- [ ] Can view members, trainers, plans
- [ ] Database tables populated with sample data
- [ ] Images folder is writable
- [ ] Test script passes all checks
- [ ] No PHP errors in browser console
- [ ] Changed default passwords

### Next Steps

1. **Customize Settings**: Update gym name, logo, contact info
2. **Add Real Data**: Remove sample data, add actual members/trainers
3. **Configure Backups**: Set up automated database backups
4. **Security Hardening**: Implement HTTPS, change passwords, set file permissions
5. **Email Setup**: Configure SMTP for notifications
6. **Documentation**: Review user manual and feature guides

## Project Structure

```
gms/
├── includes/           # Common PHP files
│   ├── config.php     # Configuration and helper functions
│   ├── db.php         # Database connection
│   ├── header.php     # Role-based navigation header
│   └── footer.php     # Footer template
├── admin/             # Admin panel pages
│   ├── index.php      # Admin dashboard
│   ├── members.php    # Member management
│   ├── trainers.php   # Trainer management
│   ├── plans.php      # Membership plans
│   ├── attendance.php # Attendance tracking
│   ├── payments.php   # Payment management
│   ├── expenses.php   # Expense tracking
│   ├── equipment.php  # Equipment management
│   ├── member_progress.php # Member progress tracking
│   ├── group_classes.php   # Group class scheduling
│   ├── notifications.php   # Bulk email notifications
│   ├── reports.php    # Reports & analytics
│   ├── settings.php   # System settings
│   ├── profile.php    # Admin profile management
│   ├── renew_membership.php # Membership renewal
│   ├── rbac.php       # Role-based access control
│   ├── reception.php  # Front desk module
│   ├── inventory.php  # Inventory management
│   ├── payroll.php    # HR & Payroll
│   ├── feedback.php   # Feedback system
│   ├── qr_scanner.php # QR code attendance
│   ├── activity_log.php # Audit trail
│   ├── backup.php     # Backup & restore
│   ├── branches.php   # Multi-branch support
│   └── api.php        # API management
├── trainer/           # Trainer panel pages
│   ├── index.php      # Trainer dashboard
│   ├── plans.php      # Workout & diet plan creation
│   ├── attendance.php # Attendance management
│   └── profile.php    # Trainer profile management
├── member/            # Member panel pages
│   ├── index.php      # Member dashboard
│   ├── attendance.php # Attendance history
│   ├── workouts.php   # View workout plans
│   ├── diets.php      # View diet plans
│   ├── classes.php    # Group class booking
│   ├── feedback.php   # Submit feedback
│   └── profile.php    # Member profile management
├── api/               # RESTful API endpoints
│   ├── config.php     # API configuration
│   ├── index.php      # API documentation
│   ├── members.php    # Member API endpoints
│   ├── attendance.php # Attendance API
│   ├── payments.php   # Payment API
│   └── search.php     # Search API
├── assets/            # Static files
│   ├── css/
│   │   ├── style.css       # Main stylesheet
│   │   ├── custom.css      # Custom styles
│   │   ├── components.css  # Component styles
│   │   ├── animations.css  # Animation effects
│   │   └── responsive.css  # Mobile responsive
│   ├── js/
│   │   ├── main.js         # Main JavaScript
│   │   ├── enhanced.js     # Enhanced features
│   │   ├── sidebar.js      # Sidebar functionality
│   │   └── chartConfig.js  # Chart.js configuration
│   └── images/        # Uploaded images (profiles, logos)
├── database/          # Database files
│   └── schema.sql     # Database schema with sample data
├── backups/           # Automated database backups
├── fpdf/              # PDF generation library
│   ├── fpdf.php       # Main FPDF class
│   ├── font/          # PDF fonts
│   └── doc/           # FPDF documentation
├── login.php          # Login page
├── logout.php         # Logout functionality
├── index.php          # Landing/redirect page
├── dashboard.php      # Role-based dashboard redirect
├── test_system.php    # System testing script
└── README.md          # This file
```

## Code Guide & Architecture

### Core Architecture

The Gym Management System follows an **MVC-inspired architecture** with clear separation of concerns:

#### 1. **Configuration Layer** (`includes/`)

**`includes/db.php`** - Database Connection
```php
<?php
// Establishes MySQL connection using mysqli
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'gym_management');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
// Connection available globally via $conn variable
?>
```

**`includes/config.php`** - Application Configuration
```php
<?php
// Core configuration and helper functions
define('SITE_URL', 'http://localhost/gms/');
define('BASE_URL', rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/');
define('UPLOAD_PATH', 'assets/images/');

// Security functions
function sanitize($data) {
    global $conn;
    return $conn->real_escape_string(trim($data));
}

function escape_output($data) {
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

// CSRF Protection
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && 
           hash_equals($_SESSION['csrf_token'], $token);
}
?>
```

**`includes/header.php`** - Dynamic Navigation
```php
<?php
// Role-based navigation header
// Automatically includes correct sidebar based on user role
if ($_SESSION['role'] == 'admin') {
    include 'admin_sidebar.php';
} elseif ($_SESSION['role'] == 'trainer') {
    include 'trainer_sidebar.php';
} elseif ($_SESSION['role'] == 'member') {
    include 'member_sidebar.php';
}
?>
```

#### 2. **Authentication & Authorization**

**Login Flow** (`login.php`):
```php
<?php
// 1. Validate input
$email = sanitize($_POST['email']);
$password = $_POST['password'];

// 2. Check credentials (using prepared statements)
$stmt = $conn->prepare("SELECT u.*, r.role_name FROM users u 
                        JOIN roles r ON u.role_id = r.id 
                        WHERE u.email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// 3. Verify password
if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
    if (password_verify($password, $user['password'])) {
        // 4. Set session variables
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role_name'];
        
        // 5. Redirect based on role
        header("Location: " . SITE_URL . $user['role_name'] . "/index.php");
    }
}
?>
```

**Access Control Pattern**:
```php
<?php
// At top of every protected page
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}
?>
```

#### 3. **Database Operations Pattern**

**CRUD Operations** (Create, Read, Update, Delete):

**CREATE - Adding New Record**:
```php
// Example: Add new member
if (isset($_POST['add_member'])) {
    // 1. Sanitize and validate input
    $name = sanitize($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $contact = sanitize($_POST['contact']);
    $plan_id = (int)$_POST['plan_id'];
    
    // 2. Prepare statement (prevents SQL injection)
    $stmt = $conn->prepare("INSERT INTO members 
        (name, email, contact, plan_id, join_date, expiry_date, status) 
        VALUES (?, ?, ?, ?, CURDATE(), DATE_ADD(CURDATE(), INTERVAL ? MONTH), 'active')");
    
    // 3. Get plan duration
    $plan_query = $conn->prepare("SELECT duration_months FROM plans WHERE id = ?");
    $plan_query->bind_param("i", $plan_id);
    $plan_query->execute();
    $duration = $plan_query->get_result()->fetch_assoc()['duration_months'];
    
    // 4. Execute insert
    $stmt->bind_param("sssii", $name, $email, $contact, $plan_id, $duration);
    
    if ($stmt->execute()) {
        $member_id = $stmt->insert_id;
        // 5. Log activity
        log_activity($_SESSION['user_id'], 'create', 'members', $member_id, 
                     "Added new member: $name");
        $_SESSION['success'] = "Member added successfully!";
    }
}
```

**READ - Fetching Records**:
```php
// Simple query
$result = $conn->query("SELECT * FROM members WHERE status = 'active' 
                        ORDER BY join_date DESC");

// With JOIN (get related data)
$query = "SELECT m.*, p.name as plan_name, t.name as trainer_name
          FROM members m
          LEFT JOIN plans p ON m.plan_id = p.id
          LEFT JOIN trainers t ON m.trainer_id = t.id
          WHERE m.status = 'active'";
$result = $conn->query($query);

// Fetch and display
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . escape_output($row['name']) . "</td>";
    echo "<td>" . escape_output($row['email']) . "</td>";
    echo "<td>" . escape_output($row['plan_name']) . "</td>";
    echo "</tr>";
}
```

**UPDATE - Modifying Records**:
```php
// Example: Update member profile
if (isset($_POST['update_member'])) {
    $id = (int)$_POST['id'];
    $name = sanitize($_POST['name']);
    $contact = sanitize($_POST['contact']);
    $address = sanitize($_POST['address']);
    
    $stmt = $conn->prepare("UPDATE members 
                           SET name = ?, contact = ?, address = ? 
                           WHERE id = ?");
    $stmt->bind_param("sssi", $name, $contact, $address, $id);
    
    if ($stmt->execute()) {
        log_activity($_SESSION['user_id'], 'update', 'members', $id, 
                     "Updated member: $name");
        $_SESSION['success'] = "Member updated successfully!";
    }
}
```

**DELETE - Removing Records**:
```php
// Soft delete (preferred - maintains data integrity)
if (isset($_GET['delete_id'])) {
    $id = (int)$_GET['delete_id'];
    
    $stmt = $conn->prepare("UPDATE members SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}

// Hard delete (use cautiously)
if (isset($_GET['hard_delete_id'])) {
    $id = (int)$_GET['hard_delete_id'];
    
    // Delete from child tables first (foreign key constraints)
    $conn->query("DELETE FROM attendance WHERE user_id = $id");
    $conn->query("DELETE FROM payments WHERE member_id = $id");
    
    // Then delete main record
    $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
}
```

#### 4. **Frontend Patterns**

**Bootstrap 5 Components**:
```html
<!-- Card Component -->
<div class="card">
    <div class="card-header">
        <h4>Members List</h4>
    </div>
    <div class="card-body">
        <table class="table table-striped">
            <!-- Table content -->
        </table>
    </div>
</div>

<!-- Modal Component -->
<div class="modal fade" id="addMemberModal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Member</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Form here -->
            </div>
        </div>
    </div>
</div>
```

**AJAX Requests** (`assets/js/main.js`):
```javascript
// Example: Search members dynamically
function searchMembers(query) {
    fetch(`../api/search.php?type=members&q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            displayResults(data);
        })
        .catch(error => console.error('Error:', error));
}

// Form submission with AJAX
document.getElementById('memberForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('members.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert('Success!', 'Member added successfully', 'success');
            location.reload();
        }
    });
});
```

**Chart.js Integration** (`assets/js/chartConfig.js`):
```javascript
// Example: Revenue chart on dashboard
function initRevenueChart(data) {
    const ctx = document.getElementById('revenueChart').getContext('2d');
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: data.months,
            datasets: [{
                label: 'Monthly Revenue',
                data: data.revenue,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'top' },
                title: {
                    display: true,
                    text: 'Revenue Trend'
                }
            }
        }
    });
}
```

#### 5. **API Module** (`api/`)

**RESTful API Structure**:
```php
<?php
// api/members.php - RESTful endpoint

header('Content-Type: application/json');
require_once 'config.php'; // API authentication

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Retrieve member(s)
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $conn->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            echo json_encode($result->fetch_assoc());
        } else {
            // List all members
            $result = $conn->query("SELECT * FROM members LIMIT 100");
            $members = $result->fetch_all(MYSQLI_ASSOC);
            echo json_encode($members);
        }
        break;
    
    case 'POST':
        // Create new member
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("INSERT INTO members (name, email, contact) 
                               VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $data['name'], $data['email'], $data['contact']);
        $stmt->execute();
        echo json_encode(['id' => $stmt->insert_id, 'success' => true]);
        break;
    
    case 'PUT':
        // Update member
        $data = json_decode(file_get_contents('php://input'), true);
        $stmt = $conn->prepare("UPDATE members SET name = ?, contact = ? 
                               WHERE id = ?");
        $stmt->bind_param("ssi", $data['name'], $data['contact'], $data['id']);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;
    
    case 'DELETE':
        // Delete member
        $id = (int)$_GET['id'];
        $stmt = $conn->prepare("DELETE FROM members WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        echo json_encode(['success' => true]);
        break;
}
?>
```

#### 6. **PDF Generation** (`fpdf/`)

**Generate Invoice/Receipt**:
```php
<?php
require_once '../fpdf/fpdf.php';

class InvoicePDF extends FPDF {
    function Header() {
        $this->Image('assets/images/logo.png', 10, 6, 30);
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, get_gym_name(), 0, 1, 'C');
        $this->Ln(10);
    }
    
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Page ' . $this->PageNo(), 0, 0, 'C');
    }
}

// Generate invoice
$pdf = new InvoicePDF();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 12);

// Fetch payment details
$payment_id = (int)$_GET['id'];
$result = $conn->query("SELECT p.*, m.name as member_name, pl.name as plan_name
                        FROM payments p
                        JOIN members m ON p.member_id = m.id
                        JOIN plans pl ON p.plan_id = pl.id
                        WHERE p.id = $payment_id");
$payment = $result->fetch_assoc();

// Add content
$pdf->Cell(0, 10, 'Invoice #' . $payment['invoice_no'], 0, 1);
$pdf->Cell(0, 10, 'Date: ' . $payment['payment_date'], 0, 1);
$pdf->Cell(0, 10, 'Member: ' . $payment['member_name'], 0, 1);
$pdf->Cell(0, 10, 'Plan: ' . $payment['plan_name'], 0, 1);
$pdf->Cell(0, 10, 'Amount: Rs. ' . number_format($payment['amount'], 2), 0, 1);

// Output PDF
$pdf->Output('I', 'invoice_' . $payment['invoice_no'] . '.pdf');
?>
```

#### 7. **Security Best Practices**

**Implemented Security Measures**:

1. **SQL Injection Prevention**:
```php
// BAD - Vulnerable to SQL injection
$query = "SELECT * FROM users WHERE email = '$email'";

// GOOD - Using prepared statements
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
```

2. **XSS (Cross-Site Scripting) Prevention**:
```php
// BAD - Outputs raw user input
echo $_POST['name'];

// GOOD - Escapes HTML entities
echo htmlspecialchars($_POST['name'], ENT_QUOTES, 'UTF-8');
// OR use helper function
echo escape_output($_POST['name']);
```

3. **CSRF Protection**:
```php
// In form
<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

// On form submission
if (!verify_csrf_token($_POST['csrf_token'])) {
    die("CSRF token validation failed!");
}
```

4. **Password Hashing**:
```php
// Registration - Hash password
$password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

// Login - Verify password
if (password_verify($_POST['password'], $user['password'])) {
    // Password correct
}
```

5. **Session Security**:
```php
// config.php
ini_set('session.cookie_httponly', 1);  // Prevent JavaScript access
ini_set('session.use_only_cookies', 1);  // No session ID in URL
ini_set('session.cookie_secure', 1);     // HTTPS only (in production)
```

#### 8. **File Upload Handling**

**Secure File Upload**:
```php
if (isset($_FILES['photo'])) {
    $file = $_FILES['photo'];
    
    // 1. Validate file type
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    if (!in_array($file['type'], $allowed)) {
        die("Invalid file type!");
    }
    
    // 2. Validate file size (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        die("File too large!");
    }
    
    // 3. Generate unique filename
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $ext;
    
    // 4. Move to upload directory
    $upload_path = UPLOAD_PATH . $filename;
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // 5. Save filename to database
        $stmt = $conn->prepare("UPDATE members SET photo = ? WHERE id = ?");
        $stmt->bind_param("si", $filename, $member_id);
        $stmt->execute();
    }
}
```

### Key Functions Reference

**`includes/config.php` Helper Functions**:

| Function | Purpose | Usage |
|----------|---------|-------|
| `sanitize($data)` | Sanitize user input | `$name = sanitize($_POST['name']);` |
| `escape_output($data)` | Escape HTML output | `echo escape_output($user['name']);` |
| `generate_csrf_token()` | Generate CSRF token | `<input value="<?php echo generate_csrf_token(); ?>">` |
| `verify_csrf_token($token)` | Verify CSRF token | `if (!verify_csrf_token($_POST['token'])) { ... }` |
| `get_gym_settings()` | Get gym configuration | `$settings = get_gym_settings();` |
| `log_activity($user, $action, $table, $record_id, $details)` | Log user activity | `log_activity($_SESSION['user_id'], 'create', 'members', 123, 'Added member');` |

### Customization Guide

#### Adding a New Feature Module

**Example: Adding a "Notifications" module**

**Step 1: Create database table**
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'warning', 'success', 'danger'),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Step 2: Create PHP file** (`admin/notifications.php`)
```php
<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/header.php';

// Check admin access
if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit();
}

// Add notification
if (isset($_POST['add_notification'])) {
    $user_id = (int)$_POST['user_id'];
    $title = sanitize($_POST['title']);
    $message = sanitize($_POST['message']);
    $type = sanitize($_POST['type']);
    
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, title, message, type) 
                           VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $message, $type);
    $stmt->execute();
}

// Fetch notifications
$result = $conn->query("SELECT n.*, u.name as user_name 
                        FROM notifications n 
                        JOIN users u ON n.user_id = u.id 
                        ORDER BY n.created_at DESC");
?>

<!-- HTML for displaying notifications -->
```

**Step 3: Add to navigation** (`includes/header.php`)
```php
<li class="nav-item">
    <a class="nav-link" href="notifications.php">
        <i class="fas fa-bell"></i> Notifications
    </a>
</li>
```

**Step 4: Add permissions** (update `rbac.php`)

#### Modifying Existing Features

**Example: Add new field to members table**

```sql
-- 1. Alter table
ALTER TABLE members ADD COLUMN blood_group VARCHAR(10) AFTER contact;

-- 2. Update admin/members.php form
<div class="form-group">
    <label>Blood Group</label>
    <select name="blood_group" class="form-control">
        <option value="A+">A+</option>
        <option value="B+">B+</option>
        <option value="O+">O+</option>
        <!-- etc -->
    </select>
</div>

-- 3. Update INSERT query
$stmt = $conn->prepare("INSERT INTO members (..., blood_group) VALUES (..., ?)");
```

### Performance Optimization Tips

1. **Use Indexes**:
```sql
CREATE INDEX idx_member_email ON members(email);
CREATE INDEX idx_attendance_date ON attendance(date);
```

2. **Optimize Queries**:
```php
// BAD - N+1 query problem
$members = $conn->query("SELECT * FROM members");
while ($member = $members->fetch_assoc()) {
    $plan = $conn->query("SELECT * FROM plans WHERE id = {$member['plan_id']}");
}

// GOOD - Single JOIN query
$members = $conn->query("SELECT m.*, p.name as plan_name 
                        FROM members m 
                        LEFT JOIN plans p ON m.plan_id = p.id");
```

3. **Cache Frequent Queries**:
```php
// Cache gym settings
if (!isset($_SESSION['gym_settings'])) {
    $_SESSION['gym_settings'] = get_gym_settings();
}
$settings = $_SESSION['gym_settings'];
```

4. **Pagination**:
```php
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$result = $conn->query("SELECT * FROM members LIMIT $per_page OFFSET $offset");
```

## Database Schema

The system uses a relational database with the following main tables and relationships:

### Core Tables

#### 1. **users** - User Authentication
```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,      -- Hashed with password_hash()
    role_id INT DEFAULT 3,               -- Links to roles table
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
);
```
**Purpose**: Central authentication table for all system users
**Key Fields**: 
- `password`: Stored as bcrypt hash for security
- `role_id`: Determines user permissions (1=Admin, 2=Trainer, 3=Member)

#### 2. **roles** - Role-Based Access Control
```sql
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) UNIQUE NOT NULL,  -- 'admin', 'trainer', 'member'
    description TEXT,
    permissions TEXT                         -- JSON string of permissions
);
```
**Purpose**: Define user roles and their permissions

#### 3. **members** - Member Information
```sql
CREATE TABLE members (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    gender ENUM('male', 'female', 'other'),
    dob DATE,
    contact VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    address TEXT,
    join_date DATE,
    expiry_date DATE,                    -- Auto-calculated based on plan
    plan_id INT,                         -- Current membership plan
    trainer_id INT,                      -- Assigned trainer
    status ENUM('active', 'expired', 'inactive') DEFAULT 'active',
    photo VARCHAR(255),                  -- Profile photo filename
    emergency_contact VARCHAR(20),
    blood_group VARCHAR(10),
    health_issues TEXT,
    FOREIGN KEY (plan_id) REFERENCES plans(id),
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);
```
**Purpose**: Store member profiles and membership status
**Key Relationships**: Links to plans (membership type) and trainers (assigned trainer)

#### 4. **trainers** - Trainer Information
```sql
CREATE TABLE trainers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    specialization VARCHAR(100),         -- e.g., "Yoga", "CrossFit"
    contact VARCHAR(20),
    email VARCHAR(100) UNIQUE,
    experience INT,                      -- Years of experience
    salary DECIMAL(10,2),
    join_date DATE,
    photo VARCHAR(255),
    certification TEXT,                  -- Trainer certifications
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```
**Purpose**: Store trainer profiles and specializations

#### 5. **plans** - Membership Plans
```sql
CREATE TABLE plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,          -- e.g., "Monthly", "Quarterly"
    duration_months INT NOT NULL,        -- 1, 3, 6, 12
    amount DECIMAL(10,2) NOT NULL,       -- Membership fee
    description TEXT,                    -- Plan benefits
    features TEXT,                       -- JSON array of features
    status ENUM('active', 'inactive') DEFAULT 'active'
);
```
**Purpose**: Define membership packages and pricing
**Usage**: Used to calculate expiry_date and payment amounts

#### 6. **attendance** - Attendance Tracking
```sql
CREATE TABLE attendance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('trainer', 'member') NOT NULL,
    date DATE NOT NULL,
    check_in TIME,
    check_out TIME,
    status ENUM('present', 'absent') DEFAULT 'present',
    marked_by INT,                       -- Who marked attendance (admin/trainer)
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_attendance (user_id, date)
);
```
**Purpose**: Track daily attendance of members and trainers
**Key Features**: 
- Prevents duplicate entries per day (unique constraint)
- Tracks check-in/out times
- Cascade delete when user is removed

#### 7. **payments** - Payment Transactions
```sql
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    method ENUM('cash', 'card', 'upi', 'bank_transfer') NOT NULL,
    invoice_no VARCHAR(50) UNIQUE,       -- Auto-generated: INV-20250101-001
    transaction_id VARCHAR(100),         -- External payment gateway ID
    status ENUM('paid', 'pending', 'failed') DEFAULT 'paid',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);
```
**Purpose**: Record all membership payments and renewals
**Key Features**: Unique invoice numbers, multiple payment methods

#### 8. **expenses** - Gym Expenses
```sql
CREATE TABLE expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category VARCHAR(50) NOT NULL,       -- 'equipment', 'maintenance', 'salary', etc.
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method VARCHAR(50),
    receipt_no VARCHAR(50),
    added_by INT,                        -- Admin user ID
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id)
);
```
**Purpose**: Track gym operational expenses

#### 9. **equipment** - Equipment Inventory
```sql
CREATE TABLE equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    category VARCHAR(50),                -- 'cardio', 'strength', 'functional'
    purchase_date DATE,
    cost DECIMAL(10,2),
    location VARCHAR(100),               -- Where in gym
    status ENUM('available', 'maintenance', 'out_of_service') DEFAULT 'available',
    last_maintenance DATE,
    next_maintenance DATE,
    condition_notes TEXT,
    warranty_expiry DATE
);
```
**Purpose**: Manage gym equipment inventory and maintenance schedules

### Advanced Feature Tables

#### 10. **workout_plans** - Workout Plans
```sql
CREATE TABLE workout_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    plan_name VARCHAR(100),
    description TEXT,
    exercises TEXT,                      -- JSON array of exercises
    duration_weeks INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);
```
**Exercise JSON Format**:
```json
[
    {
        "name": "Bench Press",
        "sets": 3,
        "reps": 12,
        "weight": "60kg",
        "rest": "90s"
    }
]
```

#### 11. **diet_plans** - Diet Plans
```sql
CREATE TABLE diet_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id INT NOT NULL,
    member_id INT NOT NULL,
    plan_name VARCHAR(100),
    description TEXT,
    meals TEXT,                          -- JSON array of meals
    total_calories INT,
    duration_days INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id),
    FOREIGN KEY (member_id) REFERENCES members(id)
);
```

#### 12. **member_progress** - Progress Tracking
```sql
CREATE TABLE member_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT NOT NULL,
    measurement_date DATE NOT NULL,
    weight DECIMAL(5,2),                 -- in kg
    body_fat_percentage DECIMAL(4,2),
    chest DECIMAL(5,2),                  -- measurements in cm
    waist DECIMAL(5,2),
    hips DECIMAL(5,2),
    arms DECIMAL(5,2),
    thighs DECIMAL(5,2),
    notes TEXT,
    recorded_by INT,                     -- Trainer/Admin ID
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (recorded_by) REFERENCES users(id)
);
```
**Purpose**: Track member fitness progress over time
**Usage**: Used to generate progress charts and trend analysis

#### 13. **group_classes** - Group Fitness Classes
```sql
CREATE TABLE group_classes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_name VARCHAR(100) NOT NULL,
    trainer_id INT NOT NULL,
    description TEXT,
    schedule_day VARCHAR(20),            -- 'Monday', 'Tuesday', etc.
    start_time TIME,
    end_time TIME,
    capacity INT DEFAULT 20,
    current_bookings INT DEFAULT 0,
    status ENUM('active', 'cancelled', 'completed') DEFAULT 'active',
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);
```

#### 14. **class_bookings** - Class Bookings
```sql
CREATE TABLE class_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    class_id INT NOT NULL,
    member_id INT NOT NULL,
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('booked', 'attended', 'missed', 'cancelled') DEFAULT 'booked',
    FOREIGN KEY (class_id) REFERENCES group_classes(id),
    FOREIGN KEY (member_id) REFERENCES members(id),
    UNIQUE KEY unique_booking (class_id, member_id)
);
```

#### 15. **activity_log** - Audit Trail
```sql
CREATE TABLE activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,         -- 'create', 'update', 'delete', 'login'
    table_name VARCHAR(50),
    record_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```
**Purpose**: Track all user activities for security and accountability
**Usage**: Admin can view who did what and when

#### 16. **settings** - System Configuration
```sql
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    gym_name VARCHAR(100) DEFAULT 'Gym Management System',
    tagline VARCHAR(255),
    contact VARCHAR(20),
    email VARCHAR(100),
    address TEXT,
    logo VARCHAR(255),
    currency VARCHAR(10) DEFAULT 'INR',
    timezone VARCHAR(50) DEFAULT 'Asia/Kolkata',
    email_notifications BOOLEAN DEFAULT TRUE,
    sms_notifications BOOLEAN DEFAULT FALSE
);
```
**Purpose**: Store gym-specific configuration
**Usage**: Retrieved by `get_gym_settings()` function

#### 17. **notifications** - System Notifications
```sql
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,                         -- NULL for broadcast to all
    title VARCHAR(255) NOT NULL,
    message TEXT,
    type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

#### 18. **inventory** - Supplement/Product Inventory
```sql
CREATE TABLE inventory (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(100) NOT NULL,
    category VARCHAR(50),                -- 'supplement', 'accessory', 'apparel'
    quantity INT DEFAULT 0,
    unit_price DECIMAL(10,2),
    supplier_id INT,
    reorder_level INT DEFAULT 10,        -- Alert when stock below this
    status ENUM('in_stock', 'low_stock', 'out_of_stock') DEFAULT 'in_stock',
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);
```

#### 19. **sales** - Product Sales
```sql
CREATE TABLE sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    member_id INT,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2),
    total_amount DECIMAL(10,2),
    sale_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    FOREIGN KEY (member_id) REFERENCES members(id),
    FOREIGN KEY (product_id) REFERENCES inventory(id)
);
```

#### 20. **payroll** - Staff Payroll
```sql
CREATE TABLE payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,            -- Trainer or staff user ID
    month VARCHAR(7) NOT NULL,           -- 'YYYY-MM' format
    basic_salary DECIMAL(10,2),
    bonus DECIMAL(10,2) DEFAULT 0,
    deductions DECIMAL(10,2) DEFAULT 0,
    net_salary DECIMAL(10,2),
    payment_date DATE,
    status ENUM('pending', 'paid') DEFAULT 'pending',
    FOREIGN KEY (employee_id) REFERENCES users(id)
);
```

### Database Relationships Diagram

```
users (1) ----< (M) activity_log
  |
  | (role_id)
  v
roles (1)

members (1) ----< (M) attendance
members (1) ----< (M) payments
members (1) ----< (M) workout_plans
members (1) ----< (M) diet_plans
members (1) ----< (M) member_progress
members (1) ----< (M) class_bookings
members (M) >---- (1) plans
members (M) >---- (1) trainers

trainers (1) ----< (M) members (assigned)
trainers (1) ----< (M) workout_plans
trainers (1) ----< (M) diet_plans
trainers (1) ----< (M) group_classes

group_classes (1) ----< (M) class_bookings

inventory (1) ----< (M) sales
```

### Sample Queries

**Get active members with their plans**:
```sql
SELECT m.*, p.name as plan_name, p.amount, t.name as trainer_name
FROM members m
LEFT JOIN plans p ON m.plan_id = p.id
LEFT JOIN trainers t ON m.trainer_id = t.id
WHERE m.status = 'active'
ORDER BY m.join_date DESC;
```

**Calculate monthly revenue**:
```sql
SELECT 
    DATE_FORMAT(payment_date, '%Y-%m') as month,
    SUM(amount) as total_revenue,
    COUNT(*) as transaction_count
FROM payments
WHERE status = 'paid'
GROUP BY month
ORDER BY month DESC;
```

**Get members with expiring memberships (next 7 days)**:
```sql
SELECT m.*, p.name as plan_name
FROM members m
JOIN plans p ON m.plan_id = p.id
WHERE m.expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    AND m.status = 'active';
```

**Trainer workload (assigned members count)**:
```sql
SELECT 
    t.name,
    COUNT(m.id) as member_count,
    t.specialization
FROM trainers t
LEFT JOIN members m ON t.id = m.trainer_id AND m.status = 'active'
GROUP BY t.id
ORDER BY member_count DESC;
```

**Member attendance percentage**:
```sql
SELECT 
    m.name,
    COUNT(a.id) as days_attended,
    DATEDIFF(CURDATE(), m.join_date) as total_days,
    ROUND((COUNT(a.id) / DATEDIFF(CURDATE(), m.join_date)) * 100, 2) as attendance_percentage
FROM members m
LEFT JOIN attendance a ON m.id = a.user_id AND a.status = 'present'
WHERE m.status = 'active'
GROUP BY m.id
ORDER BY attendance_percentage DESC;
```

## Configuration

After setting up the database, you can configure the gym information through the admin settings page:

1. Login as admin (username: `admin`, password: `admin123`)
2. Navigate to Settings in the sidebar
3. Update gym name, tagline, contact information, and upload logo
4. The gym name and tagline will be displayed throughout the application

## Usage

### Administrator Guide

#### Dashboard Overview
- **Total Members**: View active, expired, and inactive member counts
- **Revenue Summary**: Current month revenue and trends
- **Attendance Stats**: Daily/weekly/monthly attendance graphs
- **Quick Actions**: Add member, record payment, view reports

#### Member Management
1. **Add New Member**:
   - Go to Members → Add New
   - Fill in personal details (name, contact, email, address)
   - Select membership plan (duration auto-calculates expiry date)
   - Assign trainer (optional)
   - Upload profile photo
   - Submit to create member account

2. **Edit Member**:
   - Click Edit icon next to member
   - Update any information
   - Save changes (activity logged automatically)

3. **Renew Membership**:
   - Find member with expired/expiring status
   - Click "Renew" button
   - Select new plan
   - Record payment
   - Expiry date updates automatically

4. **View Member Details**:
   - Click member name to view full profile
   - See attendance history, payment records
   - View assigned workout/diet plans
   - Check progress tracking charts

#### Trainer Management
1. **Add Trainer**:
   - Go to Trainers → Add New
   - Enter name, contact, specialization
   - Set experience level and salary
   - Upload photo and certifications
   - Submit to create trainer account

2. **Assign Members**:
   - Edit member profile
   - Select trainer from dropdown
   - Members appear in trainer's dashboard

#### Payment Management
1. **Record Payment**:
   - Go to Payments → Add Payment
   - Select member and plan
   - Enter amount and payment method
   - Auto-generates invoice number (INV-YYYYMMDD-XXX)
   - Print/download invoice PDF

2. **View Payment History**:
   - Filter by date range, member, or payment method
   - Export to Excel/PDF
   - View total revenue analytics

#### Attendance Tracking
1. **Mark Attendance**:
   - Daily view: Check members present/absent
   - Bulk update: Select multiple members
   - Record check-in and check-out times
   - Add notes if needed

2. **View Attendance Reports**:
   - Filter by date range or member
   - Export attendance sheets
   - View attendance percentage

#### Equipment Management
1. **Add Equipment**:
   - Name, category, purchase date, cost
   - Set location in gym
   - Schedule maintenance dates
   - Track warranty information

2. **Maintenance Tracking**:
   - View equipment due for maintenance
   - Update maintenance status
   - Mark as out of service if needed

#### Reports & Analytics
1. **Revenue Reports**:
   - Monthly/quarterly/annual revenue
   - Payment method breakdown
   - Revenue trends (Chart.js graphs)

2. **Member Reports**:
   - Active vs expired members
   - New member registrations
   - Member retention rate

3. **Attendance Reports**:
   - Daily/monthly attendance stats
   - Peak hours analysis
   - Member attendance ranking

4. **Export Options**:
   - PDF reports with gym branding
   - Excel spreadsheets
   - Print-friendly formats

#### System Settings
1. **Gym Information**:
   - Update gym name and tagline
   - Upload logo (displayed on all pages)
   - Set contact information
   - Configure email settings

2. **User Management**:
   - View all system users
   - Reset passwords
   - Manage user roles and permissions

### Trainer Guide

#### Dashboard
- View assigned members count
- Today's attendance status
- Upcoming group classes
- Recent member progress updates

#### Managing Assigned Members
1. **View Members**:
   - See all assigned members
   - Check membership status
   - View contact information

2. **Mark Attendance**:
   - Daily attendance marking
   - Track check-in times
   - Add attendance notes

#### Creating Workout Plans
1. **Add Workout Plan**:
   - Select member
   - Enter plan name and duration
   - Add exercises with sets/reps/weight
   - Include rest periods and instructions
   - Save plan (member can view in their dashboard)

2. **Edit Workout Plan**:
   - Update exercises
   - Modify sets/reps based on progress
   - Add notes and modifications

#### Creating Diet Plans
1. **Add Diet Plan**:
   - Select member
   - Enter plan name and duration
   - Add meals (breakfast, lunch, dinner, snacks)
   - Include calorie counts and macros
   - Provide meal timing and portions
   - Save plan

2. **Track Results**:
   - View member progress charts
   - Adjust plans based on results

#### Profile Management
- Update personal information
- Upload/change profile photo
- Change password

### Member Guide

#### Dashboard
- Personal stats: Attendance, active plan, expiry date
- Assigned trainer information
- Upcoming group classes
- Recent progress updates

#### Viewing Profile
- Personal information display
- Membership plan details
- Expiry date and renewal status
- Upload/update profile photo

#### Attendance History
- Calendar view of attendance
- Check-in/out times
- Monthly attendance percentage
- Streak tracking

#### Workout Plans
1. **View Plans**:
   - See trainer-assigned workouts
   - Exercise details (sets, reps, weight)
   - Video links or instructions (if provided)

2. **Track Progress**:
   - Mark exercises as completed
   - Add personal notes
   - View workout history

#### Diet Plans
1. **View Plans**:
   - Daily meal schedule
   - Calorie and macro breakdown
   - Meal preparation tips

2. **Follow Plan**:
   - Check off completed meals
   - Track calorie intake
   - Note dietary adjustments

#### Group Classes
1. **Browse Classes**:
   - View schedule by day
   - See class details (time, trainer, capacity)
   - Check available slots

2. **Book Classes**:
   - Click "Book" on desired class
   - Receive confirmation
   - View booked classes in dashboard

3. **Manage Bookings**:
   - Cancel booking (if allowed)
   - View attendance history

#### Submit Feedback
- Rate trainer and facilities
- Submit suggestions or complaints
- View admin responses

### API Documentation

The system provides RESTful API endpoints for mobile app or third-party integration.

#### Authentication
All API requests require authentication token in header:
```
Authorization: Bearer YOUR_API_TOKEN
```

Get token via login endpoint:
```bash
POST /api/index.php?action=login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}

Response:
{
    "success": true,
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
        "id": 1,
        "name": "John Doe",
        "role": "member"
    }
}
```

#### Member Endpoints

**Get All Members**:
```bash
GET /api/members.php
Authorization: Bearer TOKEN

Response:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "status": "active",
            "expiry_date": "2025-12-31"
        }
    ]
}
```

**Get Single Member**:
```bash
GET /api/members.php?id=1
Authorization: Bearer TOKEN

Response:
{
    "success": true,
    "data": {
        "id": 1,
        "name": "John Doe",
        "email": "john@example.com",
        "phone": "1234567890",
        "plan": {
            "name": "Quarterly",
            "amount": 3000
        },
        "trainer": {
            "name": "Jane Smith",
            "specialization": "Yoga"
        }
    }
}
```

**Create Member**:
```bash
POST /api/members.php
Authorization: Bearer TOKEN
Content-Type: application/json

{
    "name": "John Doe",
    "email": "john@example.com",
    "contact": "1234567890",
    "plan_id": 1,
    "trainer_id": 2
}

Response:
{
    "success": true,
    "member_id": 123,
    "message": "Member created successfully"
}
```

**Update Member**:
```bash
PUT /api/members.php
Authorization: Bearer TOKEN
Content-Type: application/json

{
    "id": 1,
    "name": "John Updated",
    "contact": "9876543210"
}

Response:
{
    "success": true,
    "message": "Member updated successfully"
}
```

**Delete Member**:
```bash
DELETE /api/members.php?id=1
Authorization: Bearer TOKEN

Response:
{
    "success": true,
    "message": "Member deleted successfully"
}
```

#### Attendance Endpoints

**Mark Attendance**:
```bash
POST /api/attendance.php
Authorization: Bearer TOKEN
Content-Type: application/json

{
    "user_id": 1,
    "date": "2025-01-15",
    "status": "present",
    "check_in": "06:30:00"
}

Response:
{
    "success": true,
    "message": "Attendance marked successfully"
}
```

**Get Attendance History**:
```bash
GET /api/attendance.php?user_id=1&start_date=2025-01-01&end_date=2025-01-31
Authorization: Bearer TOKEN

Response:
{
    "success": true,
    "data": [
        {
            "date": "2025-01-15",
            "status": "present",
            "check_in": "06:30:00",
            "check_out": "08:00:00"
        }
    ],
    "attendance_percentage": 85.5
}
```

#### Payment Endpoints

**Get Payments**:
```bash
GET /api/payments.php?member_id=1
Authorization: Bearer TOKEN

Response:
{
    "success": true,
    "data": [
        {
            "id": 1,
            "invoice_no": "INV-20250115-001",
            "amount": 3000,
            "payment_date": "2025-01-15",
            "method": "upi",
            "status": "paid"
        }
    ]
}
```

**Record Payment**:
```bash
POST /api/payments.php
Authorization: Bearer TOKEN
Content-Type: application/json

{
    "member_id": 1,
    "plan_id": 2,
    "amount": 3000,
    "method": "card"
}

Response:
{
    "success": true,
    "payment_id": 456,
    "invoice_no": "INV-20250115-002"
}
```

#### Search Endpoint

**Global Search**:
```bash
GET /api/search.php?q=john&type=members
Authorization: Bearer TOKEN

Response:
{
    "success": true,
    "results": [
        {
            "id": 1,
            "name": "John Doe",
            "email": "john@example.com",
            "type": "member"
        }
    ]
}
```

#### Error Responses

All endpoints return consistent error format:
```json
{
    "success": false,
    "error": "Error message description",
    "code": "ERROR_CODE"
}
```

Common error codes:
- `AUTH_FAILED`: Authentication failed
- `INVALID_INPUT`: Validation error
- `NOT_FOUND`: Resource not found
- `PERMISSION_DENIED`: Insufficient permissions
- `SERVER_ERROR`: Internal server error

#### Rate Limiting

API requests are limited to:
- 100 requests per minute per IP
- 1000 requests per hour per user

Exceeding limits returns:
```json
{
    "success": false,
    "error": "Rate limit exceeded",
    "retry_after": 60
}
```

## New Features Guide

### 🆕 Membership Renewal System
- **Automatic Expiry Tracking**: Members table now includes `expiry_date` field
- **Renewal Interface**: Admin can renew memberships through the member management page
- **Payment Recording**: Renewal payments are automatically recorded
- **Expiry Alerts**: System tracks membership expiry dates

### 🆕 Equipment Management
- **Inventory Tracking**: Add, edit, and monitor gym equipment
- **Maintenance Scheduling**: Track equipment maintenance and status
- **Status Management**: Mark equipment as available, under maintenance, or out of service
- **Location Tracking**: Organize equipment by location in the gym

### 🆕 Member Progress Tracking
- **Measurement Recording**: Track weight, body fat, measurements over time
- **Progress History**: View historical progress data with charts
- **Goal Setting**: Set fitness goals and track progress towards them
- **Visual Analytics**: Progress charts and trend analysis

### 🆕 Group Classes System
- **Class Scheduling**: Create and manage group fitness classes
- **Capacity Management**: Set class capacity and track bookings
- **Member Booking**: Members can browse and book available classes
- **Booking Management**: View class bookings and manage attendance

### 🆕 Notification System
- **Bulk Email Sending**: Send emails to all members or filtered groups
- **Expiry Alerts**: Automated notifications for membership expiry
- **Custom Messages**: Send announcements and updates to members
- **Email Templates**: Pre-configured templates for common notifications

### 🆕 Enhanced Reports
- **Advanced Analytics**: Comprehensive reporting with Chart.js visualizations
- **Export Capabilities**: Export reports to PDF using FPDF library
- **Real-time Data**: Live dashboard with current gym statistics
- **Custom Date Ranges**: Filter reports by date ranges

## Testing

The system includes a comprehensive testing script to verify installation and functionality:

```bash
php test_system.php
```

This script checks:
- Database connectivity
- Configuration loading
- Table existence and sample data
- File structure integrity
- PHP syntax validation
- Authentication functions
- Gym settings configuration
- File permissions
- PHP extensions

Run this script after installation to ensure everything is working correctly.

### Manual Testing Checklist

After installation, verify these functionalities:

**Authentication & Access Control**:
- [ ] Can login as admin with correct credentials
- [ ] Cannot login with wrong password
- [ ] Cannot access admin pages as member/trainer
- [ ] Session persists across pages
- [ ] Logout works correctly

**Member Management**:
- [ ] Can add new member with all fields
- [ ] Email validation works
- [ ] Duplicate email prevented
- [ ] Can edit member information
- [ ] Can delete/deactivate member
- [ ] Profile photo upload works
- [ ] Expiry date calculates correctly

**Payments & Renewals**:
- [ ] Can record payment with invoice generation
- [ ] Invoice number is unique
- [ ] Can renew membership
- [ ] Expiry date extends correctly
- [ ] Payment history displays
- [ ] Can download invoice PDF

**Attendance**:
- [ ] Can mark attendance for today
- [ ] Cannot mark duplicate attendance
- [ ] Check-in/out times save correctly
- [ ] Attendance percentage calculates
- [ ] Can view attendance history

**Reports**:
- [ ] Dashboard shows correct statistics
- [ ] Charts render properly
- [ ] PDF export works
- [ ] Date filters work correctly

**Settings**:
- [ ] Can update gym name
- [ ] Logo upload and display works
- [ ] Settings persist across sessions
- [ ] Password change works

## Deployment Guide

### Production Deployment Steps

#### 1. Prepare Production Environment

**Server Requirements**:
- Linux server (Ubuntu 20.04+ or CentOS 7+ recommended)
- PHP 7.4 or 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ or Nginx
- SSL certificate (Let's Encrypt recommended)
- Minimum 2GB RAM, 10GB disk space

#### 2. Secure the Application

**Update Configuration**:
```php
// includes/config.php

// Production URL
define('SITE_URL', 'https://yourdomain.com/');

// Disable error display
ini_set('display_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

// Enable secure session cookies
ini_set('session.cookie_secure', 1);    // HTTPS only
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_samesite', 'Strict');
```

**Update Database Configuration**:
```php
// includes/db.php

// Use environment variables for credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'gms_user');
define('DB_PASS', getenv('DB_PASS') ?: 'strong_password_here');
define('DB_NAME', getenv('DB_NAME') ?: 'gym_management');
```

**Create .env file** (keep outside web root):
```bash
DB_HOST=localhost
DB_USER=gms_user
DB_PASS=your_secure_password
DB_NAME=gym_management
```

#### 3. Set File Permissions

```bash
# Navigate to project directory
cd /var/www/html/gms

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make uploads directory writable
chmod 777 assets/images/
chmod 777 backups/

# Protect sensitive files
chmod 600 includes/db.php
chmod 600 includes/config.php

# Set ownership
chown -R www-data:www-data .
```

#### 4. Apache Configuration

**Create VirtualHost** (`/etc/apache2/sites-available/gym.conf`):
```apache
<VirtualHost *:80>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    Redirect permanent / https://yourdomain.com/
</VirtualHost>

<VirtualHost *:443>
    ServerName yourdomain.com
    ServerAlias www.yourdomain.com
    
    DocumentRoot /var/www/html/gms
    
    <Directory /var/www/html/gms>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Strict-Transport-Security "max-age=31536000"
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /etc/letsencrypt/live/yourdomain.com/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/yourdomain.com/privkey.pem
    
    # Logging
    ErrorLog ${APACHE_LOG_DIR}/gym_error.log
    CustomLog ${APACHE_LOG_DIR}/gym_access.log combined
</VirtualHost>
```

**Enable site and modules**:
```bash
sudo a2enmod ssl
sudo a2enmod headers
sudo a2enmod rewrite
sudo a2ensite gym.conf
sudo systemctl reload apache2
```

#### 5. Create .htaccess for Security

Create `/var/www/html/gms/.htaccess`:
```apache
# Enable rewrite engine
RewriteEngine On

# Force HTTPS
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<FilesMatch "^(db\.php|config\.php|\.env)$">
    Require all denied
</FilesMatch>

# Prevent directory listing
Options -Indexes

# Prevent access to .git files
RedirectMatch 404 /\.git

# Security headers
<IfModule mod_headers.c>
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-Content-Type-Options "nosniff"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>

# PHP security
php_flag display_errors Off
php_flag log_errors On
php_value max_execution_time 300
php_value upload_max_filesize 10M
php_value post_max_size 10M
```

#### 6. SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt update
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

#### 7. Database Security

```sql
-- Create dedicated database user
CREATE USER 'gms_user'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Grant only necessary privileges
GRANT SELECT, INSERT, UPDATE, DELETE ON gym_management.* TO 'gms_user'@'localhost';

-- Remove root remote access
DELETE FROM mysql.user WHERE User='root' AND Host NOT IN ('localhost', '127.0.0.1', '::1');

FLUSH PRIVILEGES;
```

#### 8. Backup Strategy

**Automated Daily Backups**:

Create backup script (`/usr/local/bin/gym_backup.sh`):
```bash
#!/bin/bash

# Configuration
BACKUP_DIR="/var/backups/gym"
DB_NAME="gym_management"
DB_USER="gms_user"
DB_PASS="your_password"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u $DB_USER -p$DB_PASS $DB_NAME > $BACKUP_DIR/db_backup_$DATE.sql

# Compress
gzip $BACKUP_DIR/db_backup_$DATE.sql

# Delete backups older than 30 days
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

# Upload to remote storage (optional)
# rsync -avz $BACKUP_DIR/ user@remote:/backups/gym/
```

**Schedule with cron**:
```bash
# Edit crontab
sudo crontab -e

# Add daily backup at 2 AM
0 2 * * * /usr/local/bin/gym_backup.sh
```

#### 9. Monitoring & Logging

**Setup log rotation** (`/etc/logrotate.d/gym`):
```
/var/www/html/gms/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

**Monitor disk usage**:
```bash
# Check disk space
df -h

# Monitor logs
tail -f /var/log/apache2/gym_error.log
tail -f /var/log/php_errors.log
```

#### 10. Final Security Checklist

- [ ] Changed all default passwords
- [ ] Database user has minimal privileges
- [ ] SSL certificate installed and auto-renewing
- [ ] File permissions set correctly
- [ ] Error display disabled in production
- [ ] Security headers configured
- [ ] Automated backups running
- [ ] Firewall configured (allow only 80, 443, 22)
- [ ] SSH key authentication enabled
- [ ] Regular security updates scheduled
- [ ] Removed test accounts and sample data
- [ ] .env file outside web root
- [ ] CSRF protection enabled
- [ ] Input validation implemented

### Docker Deployment (Alternative)

**Dockerfile**:
```dockerfile
FROM php:7.4-apache

# Install extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite headers

# Copy application
COPY . /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html/

EXPOSE 80
```

**docker-compose.yml**:
```yaml
version: '3.8'

services:
  web:
    build: .
    ports:
      - "80:80"
    volumes:
      - ./:/var/www/html
    environment:
      - DB_HOST=db
      - DB_USER=gms_user
      - DB_PASS=secure_password
      - DB_NAME=gym_management
    depends_on:
      - db

  db:
    image: mysql:5.7
    environment:
      MYSQL_ROOT_PASSWORD: root_password
      MYSQL_DATABASE: gym_management
      MYSQL_USER: gms_user
      MYSQL_PASSWORD: secure_password
    volumes:
      - db_data:/var/lib/mysql
      - ./database/schema.sql:/docker-entrypoint-initdb.d/schema.sql

volumes:
  db_data:
```

**Deploy with Docker**:
```bash
docker-compose up -d
```

## Frequently Asked Questions (FAQ)

### Installation & Setup

**Q: I get "Connection failed" error. What should I do?**

A: Check these:
1. Verify MySQL service is running
2. Check database credentials in `includes/db.php`
3. Ensure database `gym_management` exists
4. Test MySQL connection: `mysql -u root -p`

**Q: Pages show PHP code instead of executing it. Why?**

A: Apache is not processing PHP files. Solutions:
1. Verify Apache is running
2. Install PHP: `sudo apt install php libapache2-mod-php`
3. Restart Apache: `sudo systemctl restart apache2`
4. Access via `http://localhost/` not `file:///`

**Q: How do I reset admin password?**

A: Run this SQL query in phpMyAdmin:
```sql
UPDATE users 
SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE email = 'admin@gym.com';
```
New password will be: `password`

**Q: Can I change the database name?**

A: Yes, update these places:
1. Create database with new name
2. Update `includes/db.php`: `define('DB_NAME', 'your_new_name');`
3. Import schema to new database

### Features & Functionality

**Q: How do I add custom membership plans?**

A: Admin panel → Plans → Add New Plan
- Enter plan name (e.g., "Yearly")
- Set duration in months (e.g., 12)
- Set amount (e.g., 10000)
- Add description and features

**Q: Can members have multiple trainers?**

A: Currently, each member is assigned one trainer. To allow multiple trainers, you'd need to modify the database schema to create a many-to-many relationship.

**Q: How do I send bulk emails to members?**

A: Admin panel → Notifications → Send Bulk Email
- Select recipient group (all members, active only, expiring soon)
- Compose message
- Click Send

**Q: Can I export member data to Excel?**

A: Yes, most list views have an "Export" button that generates CSV/Excel files.

**Q: How do I backup the database?**

A: Three methods:
1. Admin panel → Backup & Restore → Create Backup
2. phpMyAdmin → Export
3. Command line: `mysqldump -u root -p gym_management > backup.sql`

### Customization

**Q: How do I change the gym logo?**

A: Admin panel → Settings → Upload Logo
- Click "Choose File"
- Select your logo image (PNG/JPG, max 2MB)
- Logo appears on all pages

**Q: Can I change the color scheme?**

A: Yes, edit `assets/css/custom.css`:
```css
:root {
    --primary-color: #007bff;  /* Change this */
    --secondary-color: #6c757d;
}
```

**Q: How do I add new user roles?**

A: 
1. Add role to database: `INSERT INTO roles (role_name) VALUES ('receptionist');`
2. Create folder: `receptionist/`
3. Copy pages from `admin/` or `trainer/`
4. Update navigation in `includes/header.php`

**Q: Can I integrate payment gateway?**

A: Yes, add payment gateway in `admin/payments.php`:
```php
if ($_POST['method'] == 'online') {
    // Integrate Razorpay, PayPal, Stripe, etc.
    $payment_response = initiate_payment_gateway($_POST['amount']);
    // Store transaction ID
}
```

### Troubleshooting

**Q: Images not uploading. What's wrong?**

A: Check:
1. `assets/images/` folder exists
2. Folder has write permissions: `chmod 777 assets/images/`
3. PHP `upload_max_filesize` is sufficient (check `phpinfo()`)
4. File type is allowed (JPG, PNG only)

**Q: Charts not displaying on dashboard. Why?**

A: Check:
1. Chart.js library loaded: View page source, search for "chart.js"
2. Browser console for JavaScript errors (F12 → Console)
3. Data exists in database for chart queries

**Q: Email notifications not working. How to fix?**

A: Configure SMTP in `includes/config.php`:
```php
ini_set('SMTP', 'smtp.gmail.com');
ini_set('smtp_port', '587');
```
Or use PHPMailer library for better email delivery.

**Q: Getting "CSRF token validation failed" error. What to do?**

A: This is a security feature. Solutions:
1. Don't open form in multiple tabs
2. Clear browser cache and cookies
3. Ensure form has: `<input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">`

**Q: Slow performance with many members. How to optimize?**

A: Optimization tips:
1. Add database indexes: `CREATE INDEX idx_member_status ON members(status);`
2. Enable query caching in MySQL
3. Implement pagination (limit records per page)
4. Use CDN for Bootstrap/jQuery
5. Enable PHP opcode caching (OPcache)

### Security

**Q: Is this system secure for production?**

A: Version 2.1.0 includes:
- SQL injection protection (prepared statements)
- XSS prevention (output escaping)
- CSRF protection
- Password hashing (bcrypt)
- Session security

Always keep updated and follow security best practices.

**Q: How do I enable two-factor authentication?**

A: This feature is not built-in. You can integrate:
1. Google Authenticator library
2. SMS OTP service (Twilio, etc.)
3. Email verification codes

**Q: Can I restrict IP access?**

A: Yes, add to `.htaccess`:
```apache
<RequireAll>
    Require ip 192.168.1.0/24
    Require ip 10.0.0.0/8
</RequireAll>
```

### Mobile & API

**Q: Is there a mobile app?**

A: Not included, but RESTful API is available in `api/` folder for building mobile apps.

**Q: How do I use the API?**

A: See API Documentation section above. Basic usage:
1. Get auth token via login endpoint
2. Include token in header: `Authorization: Bearer TOKEN`
3. Make HTTP requests to endpoints

**Q: Can members access via mobile browser?**

A: Yes, the interface is responsive and works on mobile browsers.

### Licensing & Support

**Q: Is this open source?**

A: Yes, MIT License - free to use and modify.

**Q: Can I use this commercially?**

A: Yes, you can use it for commercial gyms without restrictions.

**Q: Where do I get support?**

A: 
1. Check this README first
2. Search existing issues on GitHub
3. Create new issue with details
4. Community forum (if available)

**Q: Can I hire someone to customize it?**

A: Yes, you can hire PHP developers to add custom features. The code is well-documented and follows standard practices.

### Upgrades & Maintenance

**Q: How do I update to a new version?**

A: 
1. Backup database and files
2. Download new version
3. Replace files (keep `includes/db.php` and uploads)
4. Run database migrations if provided
5. Clear cache and test

**Q: Will my data be lost on update?**

A: No, database data persists. Always backup before updates.

**Q: How often should I backup?**

A: Recommendations:
- Daily automated backups (production)
- Weekly backups (small gyms)
- Before any major changes
- Keep backups for 30 days minimum

## Contributing

We welcome contributions! Here's how you can help:

### Reporting Bugs

1. Check existing issues first
2. Create detailed bug report:
   - Steps to reproduce
   - Expected vs actual behavior
   - Screenshots if applicable
   - PHP/MySQL versions
   - Error messages

### Suggesting Features

1. Open feature request issue
2. Describe use case
3. Explain expected behavior
4. Provide mockups if possible

### Pull Requests

1. Fork the repository
2. Create feature branch: `git checkout -b feature/amazing-feature`
3. Commit changes: `git commit -m 'Add amazing feature'`
4. Push to branch: `git push origin feature/amazing-feature`
5. Open Pull Request

### Code Standards

- Follow PSR-12 coding standards
- Comment complex logic
- Write meaningful commit messages
- Test before submitting
- Update documentation

## Roadmap

### Upcoming Features (v3.0)

- [ ] Mobile app (React Native)
- [ ] Video workout library
- [ ] Online class streaming
- [ ] Member mobile app
- [ ] WhatsApp integration
- [ ] Biometric attendance
- [ ] Advanced analytics dashboard
- [ ] Multi-language support
- [ ] Dark mode
- [ ] Progressive Web App (PWA)

### Under Consideration

- AI-powered workout recommendations
- Nutrition tracking integration
- Wearable device sync (Fitbit, Apple Watch)
- Social features (member community)
- Gamification (achievements, leaderboards)
- Marketplace for gym merchandise

## Version History

### v2.1.0 - Security & Performance Update (November 11, 2025)
- 🔒 **Critical Security Fixes**: Eliminated SQL injection vulnerabilities across 8+ files
- 🔒 **XSS Protection**: Added output escaping functions and security framework
- 🔒 **CSRF Protection**: Implemented CSRF token generation and verification system
- 🔒 **Input Validation**: Added comprehensive validation helper functions
- 📊 **Activity Logging**: Enhanced audit trail for all CRUD operations
- ⚡ **Performance**: Optimized database queries with prepared statements
- 🔧 **Code Quality**: Improved type casting, NULL handling, and error management
- 📝 **Documentation**: Added comprehensive security improvements documentation (see IMPROVEMENTS.md)

### v2.0.0 - Enhanced Gym Management System
- ✅ **Membership Renewal System**: Added expiry date tracking and renewal functionality
- ✅ **Equipment Management**: Complete CRUD operations for gym equipment inventory
- ✅ **Member Progress Tracking**: Measurement recording and progress visualization
- ✅ **Group Classes**: Class scheduling and member booking system
- ✅ **Notification System**: Bulk email notifications for members
- ✅ **Enhanced Reports**: Advanced analytics with PDF export capabilities
- ✅ **Testing Suite**: Comprehensive system testing script
- ✅ **Database Schema Updates**: New tables and relationships for all features

### v1.0.0 - Initial Release
- Basic gym management functionality
- Role-based access control
- Member, trainer, and admin management
- Attendance tracking and reporting

## License

This project is open source and available under the MIT License.

## Support

For support or questions, please create an issue in the repository or contact the development team.