# Gym Management System

A comprehensive web-based Gym Management System built with PHP, MySQL, Bootstrap 5, and Chart.js. This system provides role-based access for administrators, trainers, and members with complete CRUD operations and advanced reporting features.

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

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache web server (WAMP/XAMPP recommended)
- Web browser

### Setup Steps

1. **Clone/Download the project**
   ```
   Place the 'gms' folder in your web server's root directory
   For WAMP: C:\wamp64\www\
   For XAMPP: C:\xampp\htdocs\
   ```

2. **Create Database**
   - Open phpMyAdmin (usually at http://localhost/phpmyadmin)
   - Create a new database named `gym_management`
   - Import the `database/schema.sql` file

3. **Configure Database Connection**
   - Open `includes/db.php`
   - Update database credentials if needed (default: localhost, root, no password)

4. **Access the Application**
   - Open your browser and go to: `http://localhost/gms/`
   - Default login credentials:
     - **Admin**: email: `admin@gym.com`, password: `password`
     - **Trainer**: email: `trainer1@gym.com`, password: `password`
     - **Member**: email: `member1@gym.com`, password: `password`

## Project Structure

```
gms/
├── includes/           # Common PHP files
│   ├── config.php     # Configuration and helper functions
│   ├── db.php         # Database connection
│   └── header.php     # Role-based navigation header
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
│   └── renew_membership.php # Membership renewal
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
│   └── profile.php    # Member profile management
├── assets/            # Static files
│   ├── css/
│   │   └── style.css
│   └── images/        # Uploaded images
├── database/          # Database files
│   └── schema.sql     # Database schema with sample data
├── fpdf/              # PDF generation library
├── login.php          # Login page
├── dashboard.php      # Role-based redirect
├── logout.php         # Logout functionality
├── test_system.php    # System testing script
└── README.md
```

## Database Schema

The system uses the following main tables:

- `users` - User authentication and roles
- `members` - Member information with expiry_date for renewal tracking
- `trainers` - Trainer information
- `plans` - Membership plans
- `attendance` - Attendance records
- `payments` - Payment transactions
- `workout_plans` - Workout plans
- `diet_plans` - Diet plans
- `expenses` - Gym expenses
- `equipment` - Equipment inventory and maintenance tracking
- `member_progress` - Member fitness progress measurements
- `group_classes` - Group class schedules and details
- `class_bookings` - Member class booking records
- `settings` - System settings (gym name, tagline, contact info, logo)

## Configuration

After setting up the database, you can configure the gym information through the admin settings page:

1. Login as admin (username: `admin`, password: `admin123`)
2. Navigate to Settings in the sidebar
3. Update gym name, tagline, contact information, and upload logo
4. The gym name and tagline will be displayed throughout the application

## Usage

### For Admin
- Manage all aspects of the gym
- View comprehensive reports
- Configure system settings

### For Trainers
- View assigned members
- Mark attendance
- Create workout and diet plans

### For Members
- View personal profile
- Check attendance history
- View assigned plans

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

Run this script after installation to ensure everything is working correctly.

## Future Enhancements

- Payment gateway integration (Stripe, PayPal)
- Mobile app development
- Advanced analytics with machine learning
- Online booking system for personal training
- Integration with fitness wearables
- Automated social media posting

## Version History

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