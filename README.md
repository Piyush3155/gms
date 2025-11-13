# Gym Management System

A comprehensive, production-ready web-based Gym Management System built with PHP, MySQL, Bootstrap 5, and modern web technologies.

[![PHP Version](https://img.shields.io/badge/PHP-7.4%2B-blue)](https://php.net)
[![MySQL Version](https://img.shields.io/badge/MySQL-5.7%2B-orange)](https://www.mysql.com)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5-purple)](https://getbootstrap.com)
[![License](https://img.shields.io/badge/License-MIT-green)](LICENSE)
[![PWA Ready](https://img.shields.io/badge/PWA-Ready-success)](manifest.json)
[![Production Ready](https://img.shields.io/badge/Production-Ready-brightgreen)](PRODUCTION_DEPLOYMENT.md)

## 🚀 Production Ready Features

This system includes enterprise-level features and is optimized for production deployment:

### 🔒 Security
- Advanced authentication with session management
- CSRF protection and XSS prevention
- SQL injection protection with prepared statements
- Rate limiting on sensitive endpoints
- Secure password hashing (bcrypt)
- Role-based access control (RBAC)
- Security headers (.htaccess)

### 📧 Communication
- **Email System**: Automated SMTP email notifications with templates
- **SMS Integration**: Twilio integration for SMS alerts
- Membership expiry reminders (7, 30 days)
- Payment confirmations and receipts
- Bulk email campaigns

### 💳 Payment Processing
- **Razorpay** integration (Indian market)
- **Stripe** integration (International)
- **PayPal** support
- Secure webhook handling
- Automated receipt generation
- Refund management

### 📊 Analytics & Reporting
- Real-time dashboard analytics
- Member growth trends
- Revenue analytics with projections
- Attendance patterns
- Equipment utilization tracking
- Exportable reports (PDF, Excel)

### 📱 Progressive Web App (PWA)
- Install on mobile devices
- Offline functionality
- Fast loading with service workers
- Push notifications support
- Responsive design

### 🔄 Automation
- Automated database backups (daily)
- Scheduled membership expiry emails
- Automatic status updates
- Backup retention management
- Cron job ready scripts

### ✅ Form Validation
- Real-time client-side validation
- Server-side verification
- Visual feedback with error messages
- Secure input sanitization

### 🎨 Modern UI/UX
- Bootstrap 5 responsive design
- Chart.js data visualizations
- DataTables with export capabilities
- Font Awesome 6 icons
- Smooth animations and transitions

## Features by Role

### 👨‍💼 Administrator
- **Dashboard**: Real-time statistics, charts, and insights
- **Member Management**: Complete lifecycle management
- **Trainer Management**: Assignments and performance tracking
- **Plans & Pricing**: Flexible membership configuration
- **Attendance**: Bulk updates and tracking
- **Payments**: Online/offline payment processing
- **Expenses**: Category-wise expense tracking
- **Equipment**: Inventory and maintenance logs
- **Progress Tracking**: Member fitness journey
- **Group Classes**: Scheduling and capacity management
- **Email Campaigns**: Bulk communications
- **Backups**: Automated and manual database backups
- **Reports**: Comprehensive analytics and exports
- **Settings**: System-wide configuration

### 👨‍🏫 Trainer
- Personal dashboard with assignments
- Member management for assigned clients
- Workout plan creation and management
- Diet plan customization
- Attendance marking
- Progress tracking
- Profile management

### 👤 Member
- Personal dashboard with statistics
- Profile management with photo upload
- Attendance history and calendar
- Workout plan access
- Diet plan viewing
- Group class booking
- Feedback submission
- QR code for check-in

## Technology Stack

| Component | Technology |
|-----------|------------|
| **Backend** | PHP 7.4+ |
| **Database** | MySQL 5.7+ / MariaDB 10.3+ |
| **Frontend** | Bootstrap 5, HTML5, CSS3, JavaScript ES6+ |
| **Charts** | Chart.js 3.x |
| **PDF Generation** | FPDF |
| **QR Codes** | PHP QR Code Library |
| **Email** | PHPMailer / Native Mail |
| **SMS** | Twilio API |
| **Payments** | Razorpay, Stripe, PayPal APIs |
| **PWA** | Service Workers, Web Manifest |
| **Icons** | Font Awesome 6 |
| **Data Tables** | Custom DataTable implementation |

## Quick Start

### Prerequisites
- PHP 7.4+ with required extensions (mysqli, gd, curl, mbstring, zip, xml)
- MySQL 5.7+ or MariaDB 10.3+
- Apache 2.4+ or Nginx 1.18+
- SSL Certificate (for production)

### Development Installation

1. **Clone/Download** the project
   ```bash
   git clone <repository-url>
   cd gms
   ```

2. **Database Setup**
   ```bash
   mysql -u root -p
   CREATE DATABASE gym_management;
   USE gym_management;
   SOURCE database/schema.sql;
   ```

3. **Configuration**
   ```bash
   cp includes/config.example.php includes/config.php
   # Edit config.php with your database credentials
   ```

4. **File Permissions** (Linux/Mac)
   ```bash
   chmod 775 backups/ uploads/ logs/ phpqrcode/cache/
   chmod 600 includes/config.php
   ```

5. **Access Application**
   - Development: `http://localhost/gms/`
   - Default credentials:
     - Admin: `admin@gym.com` / `password`
     - Trainer: `trainer@gym.com` / `password`
     - Member: `member@gym.com` / `password`

### Production Deployment

For production deployment, follow the comprehensive guide:
📖 **[PRODUCTION_DEPLOYMENT.md](PRODUCTION_DEPLOYMENT.md)**

Key production steps:
1. Configure `includes/config.php` with production settings
2. Set `DEBUG_MODE = false`
3. Enable HTTPS with SSL certificate
4. Configure `.htaccess` security rules
5. Set proper file permissions
6. Configure email/SMS/payment gateways
7. Set up automated backups (cron jobs)
8. Enable monitoring and logging

## Project Structure

```
gms/
├── includes/                   # Core system files
│   ├── config.example.php     # Configuration template
│   ├── config.php             # Configuration (create from example)
│   ├── db.php                 # Database connection
│   ├── security.php           # Security functions
│   ├── email.php              # Email service
│   ├── sms_service.php        # SMS integration
│   ├── payment_gateway.php    # Payment processing
│   ├── qrcode_service.php     # QR code generation
│   ├── analytics.php          # Analytics engine
│   ├── backup_service.php     # Backup management
│   ├── header.php             # Header template
│   ├── footer.php             # Footer template
│   └── email_templates/       # Email templates
├── admin/                      # Administrator panel
│   ├── backup_enhanced.php    # Advanced backup system
│   ├── send_expiry_emails.php # Email automation
│   ├── online_payments.php    # Payment management
│   ├── send_payment_reminders.php
│   ├── send_sms.php           # SMS campaigns
│   ├── cron_backup.php        # Automated backup cron
│   ├── cron_expiry_emails.php # Email automation cron
│   ├── cron_cleanup_backups.php
│   └── ... (other admin files)
├── trainer/                    # Trainer panel
├── member/                     # Member panel
├── api/                        # REST API endpoints
│   ├── payment_webhook.php    # Payment gateway webhooks
│   └── ...
├── assets/                     # Static assets
│   ├── css/
│   │   ├── style.css
│   │   ├── validation.css
│   │   └── ...
│   ├── js/
│   │   ├── form-validator.js
│   │   ├── enhanced.js
│   │   └── ...
│   └── images/
├── database/
│   └── schema.sql             # Database schema
├── backups/                    # Database backups (auto-generated)
├── logs/                       # Application logs (create manually)
├── uploads/                    # File uploads (create manually)
├── fpdf/                       # PDF library
├── phpqrcode/                  # QR code library
├── .htaccess                   # Apache security rules
├── .gitignore                  # Git ignore rules
├── manifest.json               # PWA manifest
├── service-worker.js           # PWA service worker
├── offline.html                # PWA offline page
├── login.php                   # Authentication
├── logout.php
├── index.php                   # Landing page
├── dashboard.php               # Role-based routing
├── README.md                   # This file
├── DEPLOYMENT_GUIDE.md         # Deployment instructions
└── PRODUCTION_DEPLOYMENT.md    # Production checklist
```

## Configuration

### Database Connection

Edit `includes/config.php`:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'gym_management');
```

### Email Configuration

```php
define('SMTP_ENABLED', true);
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');
```

### Payment Gateways

```php
// Razorpay
define('RAZORPAY_ENABLED', true);
define('RAZORPAY_KEY_ID', 'rzp_...');
define('RAZORPAY_KEY_SECRET', '...');

// Stripe
define('STRIPE_ENABLED', true);
define('STRIPE_PUBLISHABLE_KEY', 'pk_...');
define('STRIPE_SECRET_KEY', 'sk_...');
```

## Automation (Cron Jobs)

Set up these cron jobs for automation:

```bash
# Daily backup at 2 AM
0 2 * * * /usr/bin/php /path/to/gms/admin/cron_backup.php

# Daily expiry emails at 9 AM
0 9 * * * /usr/bin/php /path/to/gms/admin/cron_expiry_emails.php

# Weekly backup cleanup (Sunday 3 AM)
0 3 * * 0 /usr/bin/php /path/to/gms/admin/cron_cleanup_backups.php
```

## Security Best Practices

1. **Never commit** `includes/config.php` to version control
2. **Change default passwords** immediately after installation
3. **Enable HTTPS** in production (SSL certificate required)
4. **Set proper file permissions** (see deployment guide)
5. **Keep backups** in a secure, separate location
6. **Update regularly** and monitor security advisories
7. **Use strong passwords** for admin accounts
8. **Enable rate limiting** on login and sensitive endpoints
9. **Review logs** regularly for suspicious activity
10. **Test backups** periodically to ensure they work

## Browser Support

- Chrome/Edge (latest)
- Firefox (latest)
- Safari (latest)
- Mobile browsers (iOS Safari, Chrome Mobile)

## Performance

- Optimized database queries with indexing
- Lazy loading for images
- Minified CSS/JS (production)
- Browser caching enabled
- GZIP compression
- Service worker caching (PWA)

## API Documentation

API endpoints are available under `/api/` directory:

- `GET/POST /api/members.php` - Member management
- `POST /api/attendance.php` - Attendance tracking
- `GET/POST /api/payments.php` - Payment processing
- `POST /api/payment_webhook.php` - Payment gateway callbacks
- `GET /api/search.php` - Global search

## Troubleshooting

### Database Connection Failed
- Verify database credentials in `config.php`
- Ensure MySQL service is running
- Check firewall settings

### Emails Not Sending
- Verify SMTP credentials
- Check spam folder
- Enable "Less secure apps" for Gmail (or use App Password)
- Review error logs in `logs/` directory

### Payment Gateway Errors
- Verify API keys are correct
- Ensure webhook URLs are configured
- Check if gateway is in live/test mode
- Review payment gateway dashboard logs

### File Upload Issues
- Check directory permissions (775 for uploads/)
- Verify PHP upload limits in php.ini
- Ensure disk space available

## Support

For issues, questions, or contributions:

- **Documentation**: See DEPLOYMENT_GUIDE.md
- **Email**: support@yourgym.com
- **Issues**: Create an issue in the repository

## License

MIT License - feel free to use for personal or commercial projects.

## Credits

Developed with ❤️ for modern gym management

## Changelog

### v2.0.0 (Production Release)
- ✅ Enhanced security implementation
- ✅ Email automation system
- ✅ SMS integration (Twilio)
- ✅ Payment gateway integration (Razorpay, Stripe, PayPal)
- ✅ Advanced analytics and reporting
- ✅ PWA support with offline capabilities
- ✅ Form validation system
- ✅ QR code generation for members
- ✅ Automated backup system
- ✅ Production deployment optimization
- ✅ Removed test and redundant files
- ✅ Added comprehensive documentation

### v1.0.0 (Initial Release)
- Basic gym management features
- Member, trainer, admin panels
- Attendance tracking
- Payment management
- Reports generation

---

**🎯 Production Ready** | **🔒 Secure** | **📱 Mobile First** | **⚡ Fast** | **📊 Analytics**
