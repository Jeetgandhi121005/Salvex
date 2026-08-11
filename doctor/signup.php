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
    <title>Doctor Register | Salvex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="auth-body">
    <div class="auth-page">
        <div class="auth-left">
            <div class="auth-left-content">
                <h2>Join Salvex as a Doctor</h2>
                <p>Register your account and start managing your appointments and patients digitally.</p>
                <div class="auth-features">
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> Free to register</div>
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> Instant dashboard access</div>
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> All specialties supported</div>
                    <div class="auth-feat"><i class="fa-solid fa-circle-check"></i> Secure & private</div>
                </div>
            </div>
        </div>
        <div class="auth-right">
            <div class="auth-card" style="max-width:480px;">
                <div class="auth-card-header">
                    <div class="auth-icon"><i class="fa-solid fa-user-plus"></i></div>
                    <h2>Create Account</h2>
                    <p>Register as a Salvex Doctor</p>
                </div>

                <?php if($error === 'exists'): ?>
                <div class="auth-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    This email is already registered.
                </div>
                <?php elseif($error === 'passmatch'): ?>
                <div class="auth-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Passwords do not match.
                </div>
                <?php elseif($error === 'passstrength'): ?>
                <div class="auth-error">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    Password must be at least 8 characters with one uppercase letter.
                </div>
                <?php endif; ?>

                <form action="signup_process.php" method="POST" class="auth-form">
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Full Name</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-user-doctor"></i>
                                <input type="text" name="full_name" placeholder="Dr. Full Name" required>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Phone</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-phone"></i>
                                <input type="tel" name="phone" placeholder="+91 XXXXX XXXXX">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Email Address</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-envelope"></i>
                            <input type="email" name="email" placeholder="doctor@hospital.com" required>
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Specialty</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-stethoscope"></i>
                                <select name="specialty" required>
                                    <option value="">Select</option>
                                    <?php
                                    $specs = ['Cardiology','Neurology','Orthopedics','Dermatology','Pediatrics','Oncology','Gastroenterology','Ophthalmology','Nephrology','Pulmonology','Endocrinology','Psychiatry','ENT','Urology','Dentistry'];
                                    foreach($specs as $s) echo "<option value='$s'>$s</option>";
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Hospital</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-hospital"></i>
                                <select name="hospital" required>
                                    <option value="">Select</option>
                                    <?php
                                    $hospitals = ['Apollo Hospital','Narayana Hospital','Zydus Hospital','Marengo CIMS Hospital','Sterling Hospital','HCG Hospital'];
                                    foreach($hospitals as $h) echo "<option value='$h'>$h</option>";
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Experience</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-briefcase-medical"></i>
                                <input type="text" name="experience" placeholder="e.g. 10 Yrs">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Available Time</label>
                            <div class="input-wrap">
                                <i class="fa-regular fa-clock"></i>
                                <input type="text" name="available_time" placeholder="09:00 AM - 05:00 PM">
                            </div>
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="form-group">
                            <label>Password</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="password" id="regPass" placeholder="Min 8 chars" required>
                                <button type="button" class="eye-btn" onclick="togglePass('regPass','eye1')">
                                    <i class="fa-solid fa-eye" id="eye1"></i>
                                </button>
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Confirm Password</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" name="confirm_password" id="regPass2" placeholder="Repeat password" required>
                                <button type="button" class="eye-btn" onclick="togglePass('regPass2','eye2')">
                                    <i class="fa-solid fa-eye" id="eye2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn-auth-submit">
                        <i class="fa-solid fa-user-plus"></i> Create Account
                    </button>
                </form>
                <p class="auth-switch">Already registered? <a href="signin.php">Login here</a></p>
            </div>
        </div>
    </div>
    <script>
        function togglePass(id, iconId) {
            const input = document.getElementById(id);
            const icon  = document.getElementById(iconId);
            input.type  = input.type === 'password' ? 'text' : 'password';
            icon.className = input.type === 'password' ? 'fa-solid fa-eye' : 'fa-solid fa-eye-slash';
        }
    </script>
</body>
</html>
