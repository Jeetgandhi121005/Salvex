<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up | Salvex HMS</title>
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
                <h2>Create Account</h2>
                <p>Join Salvex Health Network</p>
            </div>
            <form action="signup_process.php" method="POST">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" 
                        name="full_name" 
                        placeholder="Enter your name" 
                        pattern="^[A-Za-z\s]+$" 
                        title="Name mein sirf letters aur spaces allow hain (Symbols/Numbers nahi)" 
                        minlength="3"
                        required>
                </div>
                <div class="form-group">
                    <label>Phone Number</label>
                    <input type="tel" 
                        name="phone" 
                        placeholder="987XXXXXXX" 
                        pattern="[0-9]{10}" 
                        title="Please enter a valid 10-digit phone number" 
                        maxlength="10" 
                        required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="email" placeholder="username@gmail.com" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="password" id="reg_pass" placeholder="At least 8 chars & 1 capital letter" pattern="(?=.*[A-Z]).{8,}" required>
                        <i class="fas fa-eye toggle-eye" id="eye_reg" onclick="toggleVisibility('reg_pass', 'eye_reg')"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Confirm Password</label>
                    <div class="password-wrapper">
                        <input type="password" name="confirm_password" id="confirm_pass" placeholder="Confirm your password" required>
                        <i class="fas fa-eye toggle-eye" id="eye_confirm" onclick="toggleVisibility('confirm_pass', 'eye_confirm')"></i>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>

            <div class="auth-footer">
                Already have an account? <a href="signin.php">Sign In</a>
            </div>
        </div>
    </div>

    <script>
        function redirectToDashboard(e) {
            e.preventDefault();
            window.location.href = 'dashboard.php'; 
        }

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

