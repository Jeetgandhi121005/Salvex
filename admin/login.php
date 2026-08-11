<?php
session_start();
include __DIR__ . '/includes/db.php';

if (isset($_SESSION['admin_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['admin_email'] ?? ''));
    $password = $_POST['admin_password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Please enter both email and password.';
    } else {
        $stmt = $conn->prepare("SELECT id, full_name, email, password FROM admin_users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = (int) $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            header("Location: dashboard.php");
            exit();
        }

        $error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvex Admin - Secure Login</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/login.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="login-body">
<div class="login-page">
    <div class="login-left">
        <div class="login-left-blur"></div>
        <div class="login-left-content">
            <a href="index.php" class="login-back-link">
                <i class="fas fa-arrow-left"></i> Back to Home
            </a>
            <h2 class="login-left-title">Admin Control Panel</h2>
            <p class="login-left-sub">Your live command center for doctors, patients, appointments, hospitals, and billing across Salvex.</p>
            <div class="login-features">
                <div class="login-feature-item"><i class="fas fa-shield-halved"></i><span>Secure admin authentication</span></div>
                <div class="login-feature-item"><i class="fas fa-chart-pie"></i><span>Live platform analytics</span></div>
                <div class="login-feature-item"><i class="fas fa-user-doctor"></i><span>Doctor and hospital control</span></div>
                <div class="login-feature-item"><i class="fas fa-bell"></i><span>Appointment and billing monitoring</span></div>
            </div>
            <div class="login-left-stats">
                <div class="ls-stat"><span class="ls-num">Live</span><span class="ls-label">Database</span></div>
                <div class="ls-stat"><span class="ls-num">Admin</span><span class="ls-label">Protected</span></div>
                <div class="ls-stat"><span class="ls-num">24/7</span><span class="ls-label">Control</span></div>
            </div>
        </div>
    </div>

    <div class="login-right">
        <div class="login-card">
            <div class="login-card-header">
                <div class="login-shield-icon"><i class="fas fa-shield-halved"></i></div>
                <h1 class="login-title">Admin Login</h1>
                <p class="login-subtitle">Enter your credentials to access the admin portal</p>
            </div>

            <?php if ($error !== ''): ?>
            <div class="alert alert-error" style="display:flex;">
                <i class="fas fa-circle-exclamation"></i>
                <span><?php echo htmlspecialchars($error); ?></span>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="login-form" id="loginForm" novalidate>
                <div class="form-group">
                    <label for="admin_email" class="form-label">
                        <i class="fas fa-envelope"></i> Admin Email
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="email"
                            id="admin_email"
                            name="admin_email"
                            class="form-input"
                            placeholder="admin@salvex.com"
                            autocomplete="email"
                            value="<?php echo htmlspecialchars((string) ($_POST['admin_email'] ?? '')); ?>"
                            required
                        >
                        <span class="input-icon"><i class="fas fa-envelope"></i></span>
                    </div>
                    <span class="field-error" id="emailError"></span>
                </div>

                <div class="form-group">
                    <label for="admin_password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <div class="input-wrapper">
                        <input
                            type="password"
                            id="admin_password"
                            name="admin_password"
                            class="form-input"
                            placeholder="Enter your password"
                            autocomplete="current-password"
                            required
                        >
                        <span class="input-icon toggle-password" onclick="togglePassword()">
                            <i class="fas fa-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                    <span class="field-error" id="passError"></span>
                </div>

                <div class="form-extras">
                    <label class="remember-label">
                        <input type="checkbox" name="remember_me" id="rememberMe">
                        <span class="checkmark"></span>
                        Remember me
                    </label>
                    <a href="#" class="forgot-link" onclick="showForgotModal()">Forgot password?</a>
                </div>

                <button type="submit" class="btn-login-submit" id="loginBtn">
                    <span class="btn-text"><i class="fas fa-right-to-bracket"></i> Login to Dashboard</span>
                    <span class="btn-loader" style="display:none;"><i class="fas fa-spinner fa-spin"></i> Authenticating...</span>
                </button>
            </form>

            <div class="login-card-footer">
                <p><i class="fas fa-lock" style="color:#0080FF"></i> Restricted admin area. Authorized users only.</p>
                <a href="index.php" class="back-home-link"><i class="fas fa-home"></i> Return to Salvex Home</a>
            </div>
        </div>
    </div>
</div>

<div class="modal-overlay" id="forgotModal" onclick="closeForgotModal(event)">
    <div class="modal-box">
        <button class="modal-close" onclick="hideForgotModal()"><i class="fas fa-xmark"></i></button>
        <div class="modal-icon"><i class="fas fa-key"></i></div>
        <h3 class="modal-title">Reset Password</h3>
        <p class="modal-sub">Use the default admin account or contact the project owner for password recovery.</p>
        <button class="btn-login-submit" onclick="hideForgotModal()">
            <i class="fas fa-check"></i> Understood
        </button>
    </div>
</div>

<script src="assets/js/login.js?v=2"></script>
</body>
</html>
