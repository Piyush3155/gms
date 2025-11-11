# Gym Management System

A comprehensive web-based Gym Management System built with PHP, MySQL, Bootstrap 5, and Chart.js.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-purple)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)

## Features

### Administrator Features
- Dashboard with member, trainer, and revenue statistics
- Member management (add, edit, renew memberships)
- Trainer management and assignment
- Membership plans and pricing
- Attendance tracking and bulk updates
- Payment management with invoice generation
- Expense tracking by category
- Equipment inventory and maintenance
- Member progress tracking
- Group classes scheduling
- Bulk email notifications
- Reports and analytics with charts
- System settings and configuration

### Trainer Features
- Dashboard with assigned members and schedules
- Member management for assigned clients
- Workout and diet plan creation
- Attendance marking
- Profile management

### Member Features
- Personal dashboard with stats
- Profile management
- Attendance history
- Workout and diet plan viewing
- Group class booking
- Feedback submission

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, Font Awesome 6, Chart.js
- **PDF Generation**: FPDF library
- **Authentication**: Session-based with role-based access control

## Setup

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server (WAMP/XAMPP)
- Web browser

### Installation Steps
1. Download and extract the project files
2. Place in web server directory (e.g., C:\wamp64\www\gms for WAMP)
3. Start Apache and MySQL services
4. Create database named gym_management
5. Import database/schema.sql file
6. Update database credentials in includes/db.php if needed
7. Access the application at http://localhost/gms/
8. Login with default credentials:
   - Admin: admin@gym.com / password
   - Trainer: 	rainer@gym.com / password
   - Member: member@gym.com / password

### Post-Installation
- Change default passwords immediately
- Configure gym settings through admin panel
- Set up file permissions for uploads

## Folder Structure

```
gms/
├── includes/           # Common files
│   ├── config.php     # Configuration
│   ├── db.php         # Database connection
│   ├── header.php     # Navigation header
│   └── footer.php     # Footer
├── admin/             # Admin panel
│   ├── index.php      # Dashboard
│   ├── members.php    # Member management
│   ├── trainers.php   # Trainer management
│   ├── plans.php      # Membership plans
│   ├── attendance.php # Attendance tracking
│   ├── payments.php   # Payments
│   ├── expenses.php   # Expenses
│   ├── equipment.php  # Equipment
│   ├── reports.php    # Reports
│   ├── settings.php   # Settings
│   └── profile.php    # Profile
├── trainer/           # Trainer panel
│   ├── index.php      # Dashboard
│   ├── plans.php      # Workout/diet plans
│   ├── attendance.php # Attendance
│   └── profile.php    # Profile
├── member/            # Member panel
│   ├── index.php      # Dashboard
│   ├── attendance.php # Attendance history
│   ├── workouts.php   # Workout plans
│   ├── diets.php      # Diet plans
│   ├── classes.php    # Group classes
│   └── profile.php    # Profile
├── api/               # API endpoints
│   ├── members.php    # Member API
│   ├── attendance.php # Attendance API
│   └── payments.php   # Payment API
├── assets/            # Static files
│   ├── css/           # Stylesheets
│   ├── js/            # JavaScript files
│   └── images/        # Uploaded images
├── database/          # Database files
│   └── schema.sql     # Database schema
├── fpdf/              # PDF library
├── login.php          # Login page
├── logout.php         # Logout
├── index.php          # Landing page
├── dashboard.php      # Role-based redirect
└── README.md          # This file
```

## Database Connection

The database connection is configured in includes/db.php:

- **Host**: localhost
- **Database**: gym_management
- **Username**: root (default)
- **Password**: (blank by default)

Update these values if your MySQL setup differs. The connection uses mysqli extension with prepared statements for security.

## License

MIT License
