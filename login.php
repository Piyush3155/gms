<?php
require_once 'includes/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $errors[] = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT u.id, u.name, u.password, u.role_id, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = strtolower($user['role_name']);
                $_SESSION['user_role_id'] = $user['role_id'];

                // Role-based redirection
                if ($user['role_name'] == 'admin') {
                    redirect('admin/index.php');
                } elseif ($user['role_name'] == 'trainer') {
                    redirect('trainer/index.php');
                } elseif ($user['role_name'] == 'member') {
                    redirect('member/index.php');
                } else {
                    redirect('dashboard.php');
                }
            } else {
                $errors[] = "Invalid password.";
            }
        } else {
            $errors[] = "User not found.";
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="<?php echo SITE_URL; ?>assets/images/web.png">
<link rel="shortcut icon" href="<?php echo SITE_URL; ?>assets/images/web.png">
    <style>
        /* 1. Enhanced Background and Container */
        .login-page {
            /* Keep existing background, but adjust attachment for better mobile experience */
            background: url('assets/images/gym.jpg') no-repeat center center;
            background-size: cover;
            min-height: 100vh;
            /* Use flexbox to perfectly center the container vertically and horizontally */
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px; /* Add padding for small screens */
        }

        /* Remove .login-container and use a div for the full page overlay for a sleeker look */
        .login-overlay {
            background: rgba(0, 0, 0, 0.65); /* Slightly lighter overlay for better visibility */
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0; /* Behind the content */
        }

        /* Main container for the form and welcome box */
        .login-wrapper {
            z-index: 1; /* Above the overlay */
            display: flex; /* Flexbox for side-by-side content */
            max-width: 900px; /* Max width for desktop view */
            width: 100%;
            border-radius: 15px;
            overflow: hidden; /* Important for clean borders */
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.8);
        }

        /* 2. Welcome/Info Box (The left side) */
        .content-box {
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); /* Blue gradient background */
            color: #fff !important;
            padding: 3rem;
            flex: 1; /* Takes up equal space initially */
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
        }
        .content-box h1 {
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .content-box .icon i {
            font-size: 3.5rem;
            margin-bottom: 1rem;
        }

        /* 3. Login Form Enhancements (The right side) */
        .login-form {
            background: #fff; /* Solid white background for contrast */
            padding: 3rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-form h2 {
            color: #333;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-align: center;
        }

        /* 4. Input & Button Styles */
        .form-floating > .form-control {
            border-radius: 8px; /* Slightly softer corners */
            border: 1px solid #ddd;
        }
        .btn-login {
            background: linear-gradient(45deg, #007bff, #0056b3); /* Blue gradient */
            border: none;
            border-radius: 30px; /* More pill-shaped */
            padding: 0.85rem 2.5rem;
            font-weight: 700; /* Bolder text */
            letter-spacing: 0.5px;
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4); /* Adjusted shadow for blue */
            width: 100%; /* Full width button */
        }

        /* 5. Responsiveness: Stack columns on smaller screens */
        @media (max-width: 768px) {
            .login-wrapper {
                flex-direction: column; /* Stack the welcome box and form */
                max-width: 400px;
            }
            .content-box {
                padding: 2rem 1.5rem; /* Smaller padding on mobile */
                border-radius: 15px 15px 0 0; /* Rounded only at the top */
            }
            .login-form {
                padding: 2rem 1.5rem;
            }
        }
    </style>
</head>
<body class="login-page">
    <div class="login-overlay"></div>
    <div class="login-wrapper">
        <div class="content-box">
            <div class="icon mb-3"><i class="fas fa-dumbbell fa-fw"></i></div>
            <h1><?php echo SITE_NAME; ?></h1>
            <p class="lead">Your journey to strength and fitness continues here. Log in to access your personalized dashboard.</p>
        </div>

        <div class="login-form">
            <h2><i class="fas fa-sign-in-alt me-2"></i>Sign In</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <ul class="mb-0 ps-3">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" id="loginForm" class="mt-4">
                <div class="form-floating mb-3">
                    <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
                    <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                </div>

                <button type="submit" class="btn btn-login mt-2">
                    <i class="fas fa-bolt me-2"></i>LIFT OFF!
                </button>
            </form>

            <div class="text-center mt-4 pt-2 border-top">
                <small class="text-muted d-block">
                    *FOR DEMO PURPOSES*
                </small>
                <small class="text-muted d-block">
                    Default Admin: <strong>admin@gym.com</strong> / <strong>password</strong>
                </small>
            </div>
        </div>
    </div>
</body>
</html>