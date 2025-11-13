<?php
// Get settings for the header
$header_settings_query = $conn->query("SELECT gym_name, tagline, logo FROM settings WHERE id = 1");
$header_settings = $header_settings_query->fetch_assoc();
?>
<body class="sidebar-open">
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <?php if (!empty($header_settings['logo'])): ?>
                <img src="<?php echo SITE_URL . $header_settings['logo']; ?>" alt="Logo" class="sidebar-logo-img">
            <?php else: ?>
                <div class="sidebar-logo-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="brand-text">
            <h4 class="brand-name"><?php echo htmlspecialchars($header_settings['gym_name'] ?? 'GymName'); ?></h4>
            <p class="brand-tagline"><?php echo htmlspecialchars($header_settings['tagline'] ?? 'Stay Fit'); ?></p>
        </div>
        <button class="sidebar-close-btn" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- <div class="sidebar-profile">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h6 class="profile-name"><?php echo $_SESSION['user_name']; ?></h6>
            <span class="profile-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
        </div>
    </div> -->

    <div class="sidebar-menu">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/index.php" class="menu-link active">
                    <i class="fas fa-home menu-icon"></i>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <li class="menu-section">
                    <span class="section-title">Management</span>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/members.php" class="menu-link">
                        <i class="fas fa-users menu-icon"></i>
                        <span class="menu-text">Members</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/trainers.php" class="menu-link">
                        <i class="fas fa-user-tie menu-icon"></i>
                        <span class="menu-text">Trainers</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/plans.php" class="menu-link">
                        <i class="fas fa-id-card menu-icon"></i>
                        <span class="menu-text">Plans</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/attendance.php" class="menu-link">
                        <i class="fas fa-calendar-check menu-icon"></i>
                        <span class="menu-text">Attendance</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/qr_scanner.php" class="menu-link">
                        <i class="fas fa-qrcode menu-icon"></i>
                        <span class="menu-text">QR Scanner</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/expiry_alerts.php" class="menu-link">
                        <i class="fas fa-exclamation-triangle menu-icon"></i>
                        <span class="menu-text">Expiry Alerts</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/equipment.php" class="menu-link">
                        <i class="fas fa-tools menu-icon"></i>
                        <span class="menu-text">Equipment</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/member_progress.php" class="menu-link">
                        <i class="fas fa-chart-line menu-icon"></i>
                        <span class="menu-text">Progress Tracking</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/group_classes.php" class="menu-link">
                        <i class="fas fa-calendar-alt menu-icon"></i>
                        <span class="menu-text">Group Classes</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/notifications.php" class="menu-link">
                        <i class="fas fa-bell menu-icon"></i>
                        <span class="menu-text">Notifications</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/inventory.php" class="menu-link">
                        <i class="fas fa-boxes menu-icon"></i>
                        <span class="menu-text">Inventory</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/suppliers.php" class="menu-link">
                        <i class="fas fa-truck menu-icon"></i>
                        <span class="menu-text">Suppliers</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/sales.php" class="menu-link">
                        <i class="fas fa-shopping-cart menu-icon"></i>
                        <span class="menu-text">Sales</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/payroll.php" class="menu-link">
                        <i class="fas fa-money-check menu-icon"></i>
                        <span class="menu-text">Payroll</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/feedback.php" class="menu-link">
                        <i class="fas fa-comments menu-icon"></i>
                        <span class="menu-text">Feedback</span>
                    </a>
                </li>
                <li class="menu-item has-submenu">
                    <a href="#" class="menu-link" data-bs-toggle="collapse" data-bs-target="#financeMenu">
                        <i class="fas fa-wallet menu-icon"></i>
                        <span class="menu-text">Finance</span>
                        <span class="menu-arrow"><i class="fas fa-chevron-down"></i></span>
                    </a>
                    <ul class="submenu collapse" id="financeMenu">
                        <li><a href="<?php echo SITE_URL; ?>admin/payments.php" class="submenu-link">Payments</a></li>
                        <li><a href="<?php echo SITE_URL; ?>admin/expenses.php" class="submenu-link">Expenses</a></li>
                    </ul>
                </li>
                <li class="menu-section">
                    <span class="section-title">System</span>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/reports.php" class="menu-link">
                        <i class="fas fa-chart-line menu-icon"></i>
                        <span class="menu-text">Reports</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/backup.php" class="menu-link">
                        <i class="fas fa-database menu-icon"></i>
                        <span class="menu-text">Backup & Restore</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/branches.php" class="menu-link">
                        <i class="fas fa-building menu-icon"></i>
                        <span class="menu-text">Branches</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/settings.php" class="menu-link">
                        <i class="fas fa-cog menu-icon"></i>
                        <span class="menu-text">Settings</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/activity_log.php" class="menu-link">
                        <i class="fas fa-history menu-icon"></i>
                        <span class="menu-text">Activity Log</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/rbac.php" class="menu-link">
                        <i class="fas fa-user-shield menu-icon"></i>
                        <span class="menu-text">RBAC</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/reception.php" class="menu-link">
                        <i class="fas fa-concierge-bell menu-icon"></i>
                        <span class="menu-text">Reception</span>
                    </a>
                </li>
            <?php elseif ($_SESSION['user_role'] == 'trainer'): ?>
                <li class="menu-section">
                    <span class="section-title">My Workspace</span>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>trainer/index.php" class="menu-link">
                        <i class="fas fa-users menu-icon"></i>
                        <span class="menu-text">My Members</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>trainer/plans.php" class="menu-link">
                        <i class="fas fa-clipboard-list menu-icon"></i>
                        <span class="menu-text">Training Plans</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>trainer/attendance.php" class="menu-link">
                        <i class="fas fa-calendar-check menu-icon"></i>
                        <span class="menu-text">Attendance</span>
                    </a>
                </li>
            <?php elseif ($_SESSION['user_role'] == 'member'): ?>
                <li class="menu-section">
                    <span class="section-title">My Fitness</span>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/attendance.php" class="menu-link">
                        <i class="fas fa-calendar-alt menu-icon"></i>
                        <span class="menu-text">My Attendance</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/workouts.php" class="menu-link">
                        <i class="fas fa-dumbbell menu-icon"></i>
                        <span class="menu-text">Workout Plans</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/diets.php" class="menu-link">
                        <i class="fas fa-utensils menu-icon"></i>
                        <span class="menu-text">Diet Plans</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/classes.php" class="menu-link">
                        <i class="fas fa-calendar-check menu-icon"></i>
                        <span class="menu-text">Group Classes</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sidebar-footer">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/profile.php" class="menu-link">
                    <i class="fas fa-user-circle menu-icon"></i>
                    <span class="menu-text">My Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?>logout.php" class="menu-link logout-link">
                    <i class="fas fa-sign-out-alt menu-icon"></i>
                    <span class="menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </div>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Modern Top Header -->
<header class="modern-header">
    <div class="header-container">
        <div class="header-left">
            <button class="sidebar-toggle-btn" id="sidebarToggle">
                <i class="fas fa-bars"></i>
            </button>
            <div class="header-search-container">
                <input type="text" id="headerSearch" class="form-control" placeholder="Search for members, plans, etc.">
                <div id="searchResults" class="search-results-dropdown"></div>
            </div>
        </div>

        <div class="header-right">
            <div class="header-actions">
                <div class="header-action-item notification-item">
                    <button class="action-btn" onclick="window.location.href='<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/notifications.php'">
                        <i class="fas fa-bell"></i>
                        <?php
                        // Quick notification count for header
                        if ($_SESSION['user_role'] == 'admin') {
                            $notification_count = 0;

                            // Expiring memberships
                            $expiry_date = date('Y-m-d', strtotime('+7 days'));
                            $stmt = $conn->prepare("SELECT COUNT(*) as total FROM members WHERE expiry_date <= ? AND status = 'active'");
                            $stmt->bind_param("s", $expiry_date);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $notification_count += $result->fetch_assoc()['total'];
                            $stmt->close();

                            // Pending feedback
                            $result = $conn->query("SELECT COUNT(*) as total FROM feedback WHERE status = 'pending'");
                            $notification_count += $result->fetch_assoc()['total'];

                            // Equipment maintenance due
                            $result = $conn->query("SELECT COUNT(*) as total FROM equipment WHERE next_maintenance <= CURDATE() AND status != 'maintenance'");
                            $notification_count += $result->fetch_assoc()['total'];

                            if ($notification_count > 0) {
                                echo '<span class="notification-badge">' . $notification_count . '</span>';
                            }
                        }
                        ?>
                    </button>
                </div>

                <div class="header-action-item">
                    <div class="dropdown">
                        <!-- <button class="user-menu-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                            <div class="user-details d-none d-lg-block">
                                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                                <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                            </div>
                            <i class="fas fa-chevron-down ms-2 d-none d-lg-block"></i>
                        </button> -->
                        <div class="user-details d-none d-lg-block" >
                            <!--display in dropdown signout button and profile link-->
                            <span class="user-name "><?php echo $_SESSION['user_name']; ?></span>
                        </div>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
                            <!-- <li class="dropdown-header">
                                <div class="dropdown-user-info">
                                    <div>
                                        <h6 class="mb-0"><?php echo $_SESSION['user_name']; ?></h6>
                                    </div>
                                </div>
                            </li> -->
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/profile.php">
                                    <i class="fas fa-user"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/index.php">
                                    <i class="fas fa-home"></i>Dashboard
                                </a>
                            </li>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/settings.php">
                                    <i class="fas fa-cog"></i>Settings
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>

<link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/custom.css?v=1.0">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/animations.css?v=1.0">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/components.css?v=1.0">
<link rel="stylesheet" href="<?php echo SITE_URL; ?>assets/css/responsive.css?v=1.0">

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>

<?php
if (isset($_GET['msg'])) {
    $msg = $_GET['msg'];
    $message = '';
    $type = 'success'; // Default type

    switch ($msg) {
        // Member messages
        case '1': $message = "Member added successfully."; $type = 'success'; break;
        case '2': $message = "Member updated successfully."; $type = 'success'; break;
        case '3': $message = "Member deleted successfully."; $type = 'success'; break;
        
        // Trainer messages
        case '4': $message = "Trainer added successfully."; $type = 'success'; break;
        case '5': $message = "Trainer updated successfully."; $type = 'success'; break;
        case '6': $message = "Trainer deleted successfully."; $type = 'success'; break;

        // Plan messages
        case '7': $message = "Plan added successfully."; $type = 'success'; break;
        case '8': $message = "Plan updated successfully."; $type = 'success'; break;
        case '9': $message = "Plan deleted successfully."; $type = 'success'; break;

        // Branch messages
        case '10': $message = "Branch added successfully."; $type = 'success'; break;
        case '11': $message = "Branch updated successfully."; $type = 'success'; break;
        case '12': $message = "Branch deleted successfully."; $type = 'success'; break;

        // Expense messages
        case '13': $message = "Expense added successfully."; $type = 'success'; break;
        case '14': $message = "Expense updated successfully."; $type = 'success'; break;
        case '15': $message = "Expense deleted successfully."; $type = 'success'; break;

        // Payment messages
        case '16': $message = "Payment recorded successfully."; $type = 'success'; break;
        case '17': $message = "Payment updated successfully."; $type = 'success'; break;
        case '18': $message = "Payment deleted successfully."; $type = 'success'; break;

        // General error
        case 'error': $message = "An error occurred."; $type = 'error'; break;
        
        default: $message = "Unknown operation."; $type = 'info'; break;
    }

    echo "<script type='text/javascript'>
        document.addEventListener('DOMContentLoaded', function() {
            toastr.options = {
                'closeButton': true,
                'debug': false,
                'newestOnTop': false,
                'progressBar': true,
                'positionClass': 'toast-top-right',
                'preventDuplicates': false,
                'onclick': null,
                'showDuration': '300',
                'hideDuration': '1000',
                'timeOut': '5000',
                'extendedTimeOut': '1000',
                'showEasing': 'swing',
                'hideEasing': 'linear',
                'showMethod': 'fadeIn',
                'hideMethod': 'fadeOut'
            };
            toastr['{$type}']('{$message}');
        });
    </script>";
}
?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Export Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.31/jspdf.plugin.autotable.min.js"></script>

<script src="<?php echo SITE_URL; ?>assets/js/sidebar.js"></script>
<script id="main-script" src="<?php echo SITE_URL; ?>assets/js/main.js" data-site-url="<?php echo SITE_URL; ?>"></script>
<script src="<?php echo SITE_URL; ?>assets/js/enhanced.js"></script>
<!-- Web icon -->
<link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/web.png">
<link rel="shortcut icon" href="<?php echo SITE_URL; ?>assets/images/web.png">
<script>
// Ensure favicon is present in <head> even though this include is loaded inside <body>
(function(){
    var href = '<?php echo SITE_URL; ?>assets/images/web.png';
    function setFavicon(h){
        var head = document.head || document.getElementsByTagName('head')[0];
        if(!head) return;
        // Check existing icons
        var existing = head.querySelectorAll('link[rel~="icon"], link[rel~="shortcut icon"]');
        var hasCorrect = false;
        existing.forEach(function(l){ if(l.getAttribute('href') === h) hasCorrect = true; });
        if(hasCorrect) return;
        // Add or update
        var link = document.createElement('link');
        link.rel = 'icon';
        link.type = 'image/x-icon';
        link.href = h;
        head.appendChild(link);
        var link2 = document.createElement('link');
        link2.rel = 'shortcut icon';
        link2.href = h;
        head.appendChild(link2);
    }
    if(document.readyState === 'loading'){
        document.addEventListener('DOMContentLoaded', function(){ setFavicon(href); });
    } else {
        setFavicon(href);
    }
})();
</script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Activate current page link in sidebar
    const currentUrl = window.location.href;
    const sidebarLinks = document.querySelectorAll('.modern-sidebar .menu-link, .modern-sidebar .submenu-link');

    sidebarLinks.forEach(link => {
        if (link.href === currentUrl) {
            link.classList.add('active');
            
            // If it's a submenu link, open the parent menu
            const parentSubmenu = link.closest('.collapse');
            if (parentSubmenu) {
                parentSubmenu.classList.add('show');
                const parentMenuLink = document.querySelector(`[data-bs-target="#${parentSubmenu.id}"]`);
                if(parentMenuLink) {
                    parentMenuLink.classList.add('active');
                }
            }
        }
    });
});
</script>