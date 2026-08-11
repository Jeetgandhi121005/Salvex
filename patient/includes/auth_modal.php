<div id="authModal" class="auth-modal">
    <div class="auth-modal-content single-view">
        <span class="auth-close">&times;</span>

        <div class="auth-toggle-container">
            <div id="toggle-bg" class="toggle-bg"></div>
            <button class="toggle-btn active" id="btn-login" onclick="switchAuth('login')">Signin</button>
            <button class="toggle-btn" id="btn-signup" onclick="switchAuth('signup')">Signup</button>
        </div>
        
        <div id="loginSection" class="auth-box">
            <div class="auth-header text-center">
                <h2>Welcome Back</h2>
                <p>Login to book your appointment</p>
            </div>
            <form id="loginForm" action="signin_process.php" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Password" required>
                </div>
                <button type="submit" class="auth-btn">Sign In</button>
            </form>
        </div>

        <div id="signupSection" class="auth-box" style="display: none;">
            <div class="auth-header text-center">
                <h2>Create Account</h2>
                <p>Join Salvex in just a few seconds</p>
            </div>
            <form id="registerForm" action="signup_process.php" method="POST">
                <div class="input-group">
                    <i class="fa-solid fa-user"></i>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-phone"></i>
                    <input type="tel" name="phone" placeholder="Phone Number" required>
                </div>
                <div class="input-group" style="grid-column: span 2;">
                    <i class="fa-solid fa-envelope"></i>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="password" placeholder="Min 8 chars & 1 Capital" pattern="(?=.*[A-Z]).{8,}" title="Must be at least 8 characters long and contain at least one uppercase letter" required>
                </div>
                <div class="input-group">
                    <i class="fa-solid fa-lock"></i>
                    <input type="password" name="confirm_password" placeholder="Confirm Password" required>
                </div>
                <button type="submit" class="auth-btn">Create Account</button>
            </form>
        </div>
    </div>
</div>