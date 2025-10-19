# Gym Management System

A comprehensive web-based Gym Management System built with PHP, MySQL, Bootstrap 5, and Chart.js. This system provides role-based access for administrators, trainers, and members with complete CRUD operations and advanced reporting features.

## Features

### 🏢 Administrator Features
- **Dashboard**: Overview of gym statistics and recent activities
- **Member Management**: Add, edit, view, and manage member profiles
- **Trainer Management**: Manage trainer profiles and assignments
- **Membership Plans**: Create and manage different membership plans
- **Attendance Tracking**: Bulk attendance marking and reporting
- **Payment Management**: Record payments and generate invoices
- **Expense Tracking**: Track gym expenses by category
- **Reports & Analytics**: Comprehensive reports with charts and graphs
- **Settings**: Configure gym information, logo, and branding (name, tagline, contact, address)
- **Profile Management**: Update admin profile and change password

### 👨‍🏫 Trainer Features
- **Dashboard**: View assigned members and recent activities
- **Member Management**: View and manage assigned members
- **Workout Plans**: Create personalized workout plans for members
- **Diet Plans**: Design nutrition plans with meal breakdowns
- **Attendance Management**: Mark attendance for assigned members
- **Profile Management**: Update trainer profile with photo upload

### 👤 Member Features
- **Dashboard**: Personal fitness overview and quick stats
- **Profile Management**: Update personal information and photo
- **Attendance View**: Check personal attendance history
- **Workout Plans**: View assigned workout routines
- **Diet Plans**: Access personalized nutrition plans

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: Bootstrap 5, Font Awesome 6, Chart.js
- **Authentication**: Session-based with role-based access control
- **File Upload**: Image upload for profiles and gym logo

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
     - **Admin**: username: `admin`, password: `admin123`
     - **Trainer**: username: `trainer1`, password: `trainer123`
     - **Member**: username: `member1`, password: `member123`

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
│   ├── reports.php    # Reports & analytics
│   ├── settings.php   # System settings
│   └── profile.php    # Admin profile management
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
│   └── profile.php    # Member profile management
├── assets/            # Static files
│   ├── css/
│   │   └── style.css
│   └── images/        # Uploaded images
├── login.php          # Login page
├── dashboard.php      # Role-based redirect
├── logout.php         # Logout functionality
├── schema.sql         # Database schema
└── README.md
```

## Database Schema

The system uses the following main tables:

- `users` - User authentication and roles
- `members` - Member information
- `trainers` - Trainer information
- `plans` - Membership plans
- `attendance` - Attendance records
- `payments` - Payment transactions
- `workout_plans` - Workout plans
- `diet_plans` - Diet plans
- `expenses` - Gym expenses
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

## Security Features

- Password hashing using bcrypt
- Session-based authentication
- Role-based access control
- SQL injection prevention
- XSS protection

## Future Enhancements

- Email/SMS notifications
- Payment gateway integration
- Mobile app
- Advanced reporting with charts
- Inventory management
- Online booking system

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## License

This project is open source and available under the MIT License.

## Support

For support or questions, please create an issue in the repository or contact the development team.