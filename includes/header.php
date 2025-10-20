<body class="">
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <?php 
            // Simulating settings for demonstration
            $settings = ['logo' => '']; 
            if (!empty($settings['logo'])): ?>
                <img src="<?php echo 'path/to/your/logo.png'; ?>" alt="Logo" class="sidebar-logo-img">
            <?php else: ?>
                <div class="sidebar-logo-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="brand-text">
            <h4 class="brand-name"><?php echo 'GymName'; ?></h4>
            <p class="brand-tagline"><?php echo 'Stay Fit'; ?></p>
        </div>
        <button class="sidebar-close-btn" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-profile">
        <div class="profile-avatar">
            <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
        </div>
        <div class="profile-info">
            <h6 class="profile-name"><?php echo $_SESSION['user_name']; ?></h6>
            <span class="profile-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
        </div>
    </div>

    <div class="sidebar-menu">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?>dashboard.php" class="menu-link active">
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
                    <a href="<?php echo SITE_URL; ?>admin/settings.php" class="menu-link">
                        <i class="fas fa-cog menu-icon"></i>
                        <span class="menu-text">Settings</span>
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
            <div class="header-search d-none d-md-block ms-4">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="Search members, plans...">
                </div>
            </div>
        </div>

        <div class="header-right">
            <div class="header-actions">
                <div class="header-action-item notification-item">
                    <button class="action-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                </div>

                <div class="header-action-item">
                    <div class="dropdown">
                        <button class="user-menu-btn" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <div class="user-avatar">
                                <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                            </div>
                            <div class="user-details d-none d-lg-block">
                                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                                <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                            </div>
                            <i class="fas fa-chevron-down ms-2 d-none d-lg-block"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown" aria-labelledby="userDropdown">
                            <li class="dropdown-header">
                                <div class="dropdown-user-info">
                                    <div class="dropdown-avatar">
                                        <?php echo strtoupper(substr($_SESSION['user_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <h6 class="mb-0"><?php echo $_SESSION['user_name']; ?></h6>
                                        <small class="text-muted"><?php echo ucfirst($_SESSION['user_role']); ?></small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/profile.php">
                                    <i class="fas fa-user"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo SITE_URL; ?>assets/js/sidebar.js"></script>
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