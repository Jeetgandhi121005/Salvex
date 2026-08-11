<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In | Salvex HMS</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .auth-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f0f7ff 0%, #ffffff 100%);
            padding: 20px;
        }
        .auth-card {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.05);
            width: 100%;
            max-width: 400px;
            border: 1px solid #f1f5f9;
        }
        .auth-header { text-align: center; margin-bottom: 30px; }
        .auth-header img { height: 60px; margin-bottom: 10px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #475569; }
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            outline: none;
            transition: 0.3s;
        }
        .form-group input:focus { border-color: var(--primary); }
        .auth-btn {
            width: 100%;
            padding: 14px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
        }
        .auth-btn:hover { background: #0066cc; transform: translateY(-2px); }
        .auth-footer { text-align: center; margin-top: 20px; color: #64748b; font-size: 14px; }
        .auth-footer a { color: var(--primary); text-decoration: none; font-weight: 600; }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <img src="assets/images/Salvex_Logo.png" alt="Salvex Logo">
                <h2>Welcome Back</h2>
                <p>Login to Dashboard</p>
            </div>
            <form action="signin_process.php" method="POST">
                    <input type="hidden" name="redirect_to" value="<?php echo isset($_GET['redirect']) ? $_GET['redirect'] : 'index'; ?>">                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="username@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="login_pass" placeholder="Password" required>
                        <i class="fas fa-eye toggle-eye" id="eye_login" onclick="toggleVisibility('login_pass', 'eye_login')"></i>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Sign In</button>
            </form>
            <div class="auth-footer">
                Don't have an account? <a href="signup.php">Sign Up</a>
            </div>
        </div>
    </div>

    <script>
        function toggleVisibility(inputId, iconId) {
            const passInput = document.getElementById(inputId);
            const eyeIcon = document.getElementById(iconId);

            if (passInput.type === "password") {
                passInput.type = "text";
                eyeIcon.classList.replace("fa-eye", "fa-eye-slash");
            } else {
                passInput.type = "password";
                eyeIcon.classList.replace("fa-eye-slash", "fa-eye");
            }
        }
    </script>    
</body>
</html>
