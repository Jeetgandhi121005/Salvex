<?php
session_start();
// if (isset($_SESSION['doctor_id'])) {
//     header("Location: dashboard.php");
//     exit();
// }
$error = isset($_GET['error']) ? $_GET['error'] : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Login | Salvex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-page">
        <div class="auth-left">
            <div class="auth-left-content">
                <h2>Welcome back, Doctor</h2>
                <p>Access your appointments, patients, and clinical notes from one unified dashboard.</p>
                <div class="auth-features">
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> View today's appointments</div>
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> Manage patient details</div>
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> Add clinical notes</div>
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> Update appointment status</div>
                </div>
            </div>
        </div>
        <div class="auth-right">
            <div class="auth-card">
                <div class="auth-card-header">
                    <div class="auth-icon"><i class="fa-solid fa-user-doctor"></i></div>
                    <h2>Doctor Login</h2>
                    <p>Sign in to your Salvex account</p>
                </div>

                <?php if($error === 'invalid'): ?>
                <div class="auth-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Invalid email or password. Please try again.
                </div>
                <?php elseif($error === 'notfound'): ?>
                <div class="auth-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    No account found with this email.
                </div>
                <?php elseif($error === 'inactive'): ?>
                <div class="auth-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    This doctor account is disabled by admin. Please contact support.
                </div>
                <?php endif; ?>

                <form action="signin_process.php" method="POST" class="auth-form">
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" placeholder="doctor@salvex.com" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-lock"></i>
                            <input type="password" name="password" id="loginPass" placeholder="Enter password" required>
                            <button type="button" class="eye-btn" onclick="togglePass('loginPass','eyeIcon')">
                                <i class="fa-solid fa-eye" id="eyeIcon"></i>
                            </button>
                        </div>
                    </div>
                    <button type="submit" class="btn-auth-submit">
                        <i class="fa-solid fa-right-to-bracket"></i> Sign In
                    </button>
                </form>
                <p class="auth-switch">Don't have an account? <a href="signup.php">Register here</a></p>
                <p class="auth-switch" style="margin-top:8px;">
                    <a href="../patient/index.php" style="color:#64748b;font-size:13px;">
                        <i class="fa-solid fa-arrow-left"></i> Back to Patient Portal
                    </a>
                </p>
            </div>
        </div>
    </div>
    <script>
        function togglePass(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon  = document.getElementById(iconId);
            input.type  = input.type === 'password' ? 'text' : 'password';
            icon.className = input.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }
    </script>
</body>
</html>
