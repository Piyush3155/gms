<!-- Modern Sidebar -->
<div class="modern-sidebar" id="sidebar">
    <div class="sidebar-brand">
        <div class="brand-logo">
            <?php 
            $settings = get_gym_settings();
            if (!empty($settings['logo'])): ?>
                <img src="<?php echo SITE_URL . UPLOAD_PATH . $settings['logo']; ?>" alt="Logo" class="sidebar-logo-img">
            <?php else: ?>
                <div class="sidebar-logo-icon">
                    <i class="fas fa-dumbbell"></i>
                </div>
            <?php endif; ?>
        </div>
        <div class="brand-text">
            <h4 class="brand-name"><?php echo get_gym_name(); ?></h4>
            <p class="brand-tagline"><?php echo get_gym_tagline(); ?></p>
        </div>
        <button class="sidebar-close-btn" id="sidebarClose">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="sidebar-profile">
        <div class="profile-avatar">
            <i class="fas fa-user-circle"></i>
        </div>
        <div class="profile-info">
            <h6 class="profile-name"><?php echo $_SESSION['user_name']; ?></h6>
            <span class="profile-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
        </div>
    </div>

    <div class="sidebar-menu">
        <ul class="menu-list">
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?>dashboard.php" class="menu-link">
                    <span class="menu-icon"><i class="fas fa-home"></i></span>
                    <span class="menu-text">Dashboard</span>
                </a>
            </li>

            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                <li class="menu-section">
                    <span class="section-title">Management</span>
                </li>
                <li class="menu-item has-submenu">
                    <a href="#" class="menu-link" data-bs-toggle="collapse" data-bs-target="#adminMenu">
                        <span class="menu-icon"><i class="fas fa-users-cog"></i></span>
                        <span class="menu-text">Members & Staff</span>
                        <span class="menu-arrow"><i class="fas fa-chevron-down"></i></span>
                    </a>
                    <ul class="submenu collapse" id="adminMenu">
                        <li><a href="<?php echo SITE_URL; ?>admin/members.php" class="submenu-link"><i class="fas fa-users"></i>Members</a></li>
                        <li><a href="<?php echo SITE_URL; ?>admin/trainers.php" class="submenu-link"><i class="fas fa-user-tie"></i>Trainers</a></li>
                        <li><a href="<?php echo SITE_URL; ?>admin/plans.php" class="submenu-link"><i class="fas fa-id-card"></i>Membership Plans</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/attendance.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-calendar-check"></i></span>
                        <span class="menu-text">Attendance</span>
                    </a>
                </li>
                <li class="menu-item has-submenu">
                    <a href="#" class="menu-link" data-bs-toggle="collapse" data-bs-target="#financeMenu">
                        <span class="menu-icon"><i class="fas fa-wallet"></i></span>
                        <span class="menu-text">Finance</span>
                        <span class="menu-arrow"><i class="fas fa-chevron-down"></i></span>
                    </a>
                    <ul class="submenu collapse" id="financeMenu">
                        <li><a href="<?php echo SITE_URL; ?>admin/payments.php" class="submenu-link"><i class="fas fa-credit-card"></i>Payments</a></li>
                        <li><a href="<?php echo SITE_URL; ?>admin/expenses.php" class="submenu-link"><i class="fas fa-receipt"></i>Expenses</a></li>
                    </ul>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/reports.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-chart-line"></i></span>
                        <span class="menu-text">Reports & Analytics</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>admin/settings.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-cog"></i></span>
                        <span class="menu-text">Settings</span>
                    </a>
                </li>
            <?php elseif ($_SESSION['user_role'] == 'trainer'): ?>
                <li class="menu-section">
                    <span class="section-title">My Workspace</span>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>trainer/index.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-users"></i></span>
                        <span class="menu-text">My Members</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>trainer/plans.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-clipboard-list"></i></span>
                        <span class="menu-text">Training Plans</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>trainer/attendance.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-calendar-check"></i></span>
                        <span class="menu-text">Attendance</span>
                    </a>
                </li>
            <?php elseif ($_SESSION['user_role'] == 'member'): ?>
                <li class="menu-section">
                    <span class="section-title">My Fitness</span>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/attendance.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-calendar-alt"></i></span>
                        <span class="menu-text">My Attendance</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/workouts.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-dumbbell"></i></span>
                        <span class="menu-text">Workout Plans</span>
                    </a>
                </li>
                <li class="menu-item">
                    <a href="<?php echo SITE_URL; ?>member/diets.php" class="menu-link">
                        <span class="menu-icon"><i class="fas fa-utensils"></i></span>
                        <span class="menu-text">Diet Plans</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <div class="sidebar-footer">
        <ul class="footer-menu">
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?><?php echo $_SESSION['user_role']; ?>/profile.php" class="menu-link">
                    <span class="menu-icon"><i class="fas fa-user-circle"></i></span>
                    <span class="menu-text">My Profile</span>
                </a>
            </li>
            <li class="menu-item">
                <a href="<?php echo SITE_URL; ?>logout.php" class="menu-link logout-link">
                    <span class="menu-icon"><i class="fas fa-sign-out-alt"></i></span>
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
            <div class="header-brand d-none d-lg-block">
                <h5 class="mb-0"><?php echo get_gym_name(); ?></h5>
            </div>
        </div>

        <div class="header-right">
            <div class="header-search d-none d-md-block">
                <div class="search-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" class="search-input" placeholder="Search...">
                </div>
            </div>

            <div class="header-actions">
                <div class="header-action-item notification-item">
                    <button class="action-btn">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge">3</span>
                    </button>
                </div>

                <div class="header-action-item user-menu">
                    <div class="dropdown">
                        <button class="user-menu-btn" type="button" id="userDropdown" data-bs-toggle="dropdown">
                            <div class="user-avatar">
                                <i class="fas fa-user"></i>
                            </div>
                            <div class="user-details d-none d-md-block">
                                <span class="user-name"><?php echo $_SESSION['user_name']; ?></span>
                                <span class="user-role"><?php echo ucfirst($_SESSION['user_role']); ?></span>
                            </div>
                            <i class="fas fa-chevron-down ms-2"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end user-dropdown">
                            <li class="dropdown-header">
                                <div class="dropdown-user-info">
                                    <div class="dropdown-avatar">
                                        <i class="fas fa-user-circle"></i>
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
                                    <i class="fas fa-user me-2"></i>My Profile
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>dashboard.php">
                                    <i class="fas fa-home me-2"></i>Dashboard
                                </a>
                            </li>
                            <?php if ($_SESSION['user_role'] == 'admin'): ?>
                            <li>
                                <a class="dropdown-item" href="<?php echo SITE_URL; ?>admin/settings.php">
                                    <i class="fas fa-cog me-2"></i>Settings
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
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