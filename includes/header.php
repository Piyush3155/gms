<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="../dashboard.php"><?php echo SITE_NAME; ?></a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../dashboard.php">Dashboard</a>
                </li>

                <?php if ($_SESSION['user_role'] == 'admin'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                            Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="members.php">Members</a></li>
                            <li><a class="dropdown-item" href="trainers.php">Trainers</a></li>
                            <li><a class="dropdown-item" href="plans.php">Plans</a></li>
                            <li><a class="dropdown-item" href="attendance.php">Attendance</a></li>
                            <li><a class="dropdown-item" href="payments.php">Payments</a></li>
                            <li><a class="dropdown-item" href="expenses.php">Expenses</a></li>
                            <li><a class="dropdown-item" href="reports.php">Reports</a></li>
                            <li><a class="dropdown-item" href="settings.php">Settings</a></li>
                        </ul>
                    </li>
                <?php elseif ($_SESSION['user_role'] == 'trainer'): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="trainerDropdown" role="button" data-bs-toggle="dropdown">
                            Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="index.php">My Members</a></li>
                            <li><a class="dropdown-item" href="plans.php">Workout & Diet Plans</a></li>
                            <li><a class="dropdown-item" href="attendance.php">Attendance</a></li>
                        </ul>
                    </li>
                <?php elseif ($_SESSION['user_role'] == 'member'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="attendance.php">My Attendance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="workouts.php">My Workouts</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="diets.php">My Diets</a>
                    </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                        <?php echo $_SESSION['user_name']; ?> (<?php echo ucfirst($_SESSION['user_role']); ?>)
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="../<?php echo $_SESSION['user_role']; ?>/profile.php">Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>