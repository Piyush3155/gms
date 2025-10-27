<?php
require_once 'includes/config.php';

if (is_logged_in()) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = sanitize($_POST['email']);
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
</head>
<body class="login-page">
    <div class="login-container">
            <div class="content-box text-center text-white p-4 mt-4 align-items-center d-flex flex-column justify-content-center">
                <div class="icon"><i class="fas fa-dumbbell"></i></div>
                <h1>Welcome Back</h1>
                <p>Your journey to strength and fitness continues here. Let's get to work.</p>
            </div>
            <div class="login-form">
            <h2><i class="fas fa-user-circle me-2"></i>Secure Login</h2>

            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
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
                    <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    <label for="email"><i class="fas fa-envelope me-2"></i>Email Address</label>
                </div>
                <div class="form-floating mb-4">
                    <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                    <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                </div>

                <button type="submit" class="btn btn-login">
                    <i class="fas fa-sign-in-alt me-2"></i>Login
                </button>
            </form>

            <div class="text-center mt-4">
                <small class="text-muted">
                    Default Admin: admin@gym.com / password
                </small>
            </div>
        </div>
    </div>
</body>
</html>