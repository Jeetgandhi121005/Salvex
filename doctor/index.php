<?php
session_start();
// if (isset($_SESSION['doctor_id'])) {
//     header("Location: dashboard.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvex | Doctor Portal</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<!-- ===== NAVBAR ===== -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="assets/images/Salvex_Logo.png" alt="Salvex">
            <span>Salvex</span>
            <span class="portal-badge">Doctor</span>
        </a>
        <div class="nav-links">
            <a href="#features" class="nav-link">Features</a>
            <a href="#how-it-works" class="nav-link">How It Works</a>
            <a href="#hospitals" class="nav-link">Hospitals</a>
            <a href="signin.php" class="btn-nav-login">
                <i class="fa-solid fa-right-to-bracket"></i> Doctor Login
            </a>
            <a href="signup.php" class="btn-nav-register">
                Register
            </a>
        </div>
        <button class="nav-hamburger" onclick="toggleMobileNav()">
            <i class="fa-solid fa-bars"></i>
        </button>
    </div>
    <div class="mobile-nav" id="mobileNav">
        <a href="#features">Features</a>
        <a href="#how-it-works">How It Works</a>
        <a href="#hospitals">Hospitals</a>
        <a href="signin.php">Doctor Login</a>
        <a href="signup.php">Register</a>
    </div>
</nav>

<!-- ===== HERO ===== -->
<section class="hero">
    <div class="hero-bg-circles">
        <div class="circle c1"></div>
        <div class="circle c2"></div>
        <div class="circle c3"></div>
    </div>
    <div class="hero-content">
        <div class="hero-badge">
            <i class="fa-solid fa-shield-heart"></i>
            Trusted by Top Ahmedabad Hospitals
        </div>
        <h1 class="hero-title">
            Your Patients.<br>
            <span class="gradient-text">Your Schedule.</span><br>
            Your Control.
        </h1>
        <p class="hero-subtitle">
            Salvex Doctor Portal gives you everything you need to manage appointments, track patients, and streamline your daily workflow — all in one place.
        </p>
        <div class="hero-actions">
            <a href="signin.php" class="btn-primary-lg">
                <i class="fa-solid fa-right-to-bracket"></i>
                Login to Portal
            </a>
            <a href="signup.php" class="btn-secondary-lg">
                Register as Doctor
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat">
                <span class="stat-num">90+</span>
                <span class="stat-label">Doctors</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <span class="stat-num">6</span>
                <span class="stat-label">Hospitals</span>
            </div>
            <div class="hero-stat-divider"></div>
            <div class="hero-stat">
                <span class="stat-num">15+</span>
                <span class="stat-label">Specialties</span>
            </div>
        </div>
    </div>
    <div class="hero-visual">
        <div class="dashboard-preview">
            <div class="preview-header">
                <div class="preview-dots">
                    <span></span><span></span><span></span>
                </div>
                <span class="preview-title">Doctor Dashboard</span>
            </div>
            <div class="preview-body">
                <div class="preview-greeting">Good Morning, Dr. Prajapati 👋</div>
                <div class="preview-stats-row">
                    <div class="preview-stat-card blue">
                        <i class="fa-solid fa-calendar-check"></i>
                        <div><span>12</span><p>Today's Appts</p></div>
                    </div>
                    <div class="preview-stat-card green">
                        <i class="fa-solid fa-circle-check"></i>
                        <div><span>5</span><p>Completed</p></div>
                    </div>
                    <div class="preview-stat-card orange">
                        <i class="fa-solid fa-hourglass-half"></i>
                        <div><span>7</span><p>Pending</p></div>
                    </div>
                </div>
                <div class="preview-appt-card">
                    <div class="preview-appt-avatar">RK</div>
                    <div class="preview-appt-info">
                        <strong>Rahul Kumar</strong>
                        <span>Cardiology • 10:30 AM</span>
                    </div>
                    <span class="preview-status pending">Pending</span>
                </div>
                <div class="preview-appt-card">
                    <div class="preview-appt-avatar">PS</div>
                    <div class="preview-appt-info">
                        <strong>Priya Shah</strong>
                        <span>Cardiology • 11:00 AM</span>
                    </div>
                    <span class="preview-status completed">Done</span>
                </div>
                <div class="preview-appt-card">
                    <div class="preview-appt-avatar">AM</div>
                    <div class="preview-appt-info">
                        <strong>Amit Mehta</strong>
                        <span>Cardiology • 11:30 AM</span>
                    </div>
                    <span class="preview-status upcoming">Upcoming</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== FEATURES ===== -->
<section class="features-section" id="features">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Core Features</span>
            <h2>Everything a Doctor Needs</h2>
            <p>Designed for real clinical workflows — not just management theory</p>
        </div>
        <div class="features-grid">
            <div class="feature-card" data-delay="0">
                <div class="feature-icon" style="background:#eff6ff;color:#2563eb;">
                    <i class="fa-solid fa-calendar-days"></i>
                </div>
                <h3>Appointment Management</h3>
                <p>View all your daily, weekly, and upcoming appointments in a clean structured layout with status tracking.</p>
                <div class="feature-tag">Real-time</div>
            </div>
            <div class="feature-card" data-delay="100">
                <div class="feature-icon" style="background:#f5f3ff;color:#7c3aed;">
                    <i class="fa-solid fa-user-injured"></i>
                </div>
                <h3>Patient Details</h3>
                <p>Access patient name, age, reason for visit, and appointment history — all from a single view.</p>
                <div class="feature-tag">Detailed</div>
            </div>
            <div class="feature-card" data-delay="200">
                <div class="feature-icon" style="background:#f0fdf4;color:#16a34a;">
                    <i class="fa-solid fa-notes-medical"></i>
                </div>
                <h3>Clinical Notes</h3>
                <p>Write and save appointment notes for each patient to keep track of observations and recommendations.</p>
                <div class="feature-tag">Organized</div>
            </div>
            <div class="feature-card" data-delay="300">
                <div class="feature-icon" style="background:#fff7ed;color:#ea580c;">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                </div>
                <h3>Schedule Overview</h3>
                <p>See your full day's time slots at a glance — booked, available, and completed — in a visual format.</p>
                <div class="feature-tag">Visual</div>
            </div>
            <div class="feature-card" data-delay="400">
                <div class="feature-icon" style="background:#fdf2f8;color:#db2777;">
                    <i class="fa-solid fa-bell"></i>
                </div>
                <h3>Smart Notifications</h3>
                <p>Instant alerts for new bookings, cancellations, and rescheduled appointments from patients.</p>
                <div class="feature-tag">Instant</div>
            </div>
            <div class="feature-card" data-delay="500">
                <div class="feature-icon" style="background:#f0fdf4;color:#0891b2;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h3>Stats Dashboard</h3>
                <p>Get a quick summary of your performance — total appointments, completion rates, and daily activity.</p>
                <div class="feature-tag">Analytics</div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOW IT WORKS ===== -->
<section class="how-section" id="how-it-works">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Process</span>
            <h2>How It Works</h2>
            <p>Four simple steps to a fully managed clinical day</p>
        </div>
        <div class="steps-wrapper">
            <div class="step-line"></div>
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-num">01</div>
                    <div class="step-icon"><i class="fa-solid fa-right-to-bracket"></i></div>
                    <h3>Login</h3>
                    <p>Sign in with your registered credentials to access your personal doctor dashboard.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">02</div>
                    <div class="step-icon"><i class="fa-solid fa-calendar-check"></i></div>
                    <h3>View Appointments</h3>
                    <p>See all your appointments for today, upcoming days, and review pending requests.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">03</div>
                    <div class="step-icon"><i class="fa-solid fa-stethoscope"></i></div>
                    <h3>Treat Patients</h3>
                    <p>View patient details, add clinical notes, and manage each consultation efficiently.</p>
                </div>
                <div class="step-card">
                    <div class="step-num">04</div>
                    <div class="step-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h3>Update Status</h3>
                    <p>Mark appointments as completed, confirmed or cancelled and keep your schedule accurate.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ===== HOSPITALS ===== -->
<section class="hospitals-section" id="hospitals">
    <div class="container">
        <div class="section-header">
            <span class="section-badge">Our Network</span>
            <h2>Trusted by Leading Hospitals</h2>
            <p>Salvex powers doctor workflows across top hospitals in Ahmedabad</p>
        </div>
        <div class="hospitals-grid">
            <?php
            $hospitals = [
                ['name'=>'Apollo Hospital',       'loc'=>'GIDC, Ahmedabad',          'icon'=>'fa-hospital',      'color'=>'#2563eb', 'bg'=>'#eff6ff', 'docs'=>15],
                ['name'=>'Narayana Hospital',     'loc'=>'Rakhial, Ahmedabad',        'icon'=>'fa-heart-pulse',   'color'=>'#dc2626', 'bg'=>'#fef2f2', 'docs'=>15],
                ['name'=>'Zydus Hospital',        'loc'=>'Sola, Ahmedabad',           'icon'=>'fa-briefcase-medical','color'=>'#7c3aed','bg'=>'#f5f3ff','docs'=>15],
                ['name'=>'Marengo CIMS Hospital', 'loc'=>'Sola, Ahmedabad',           'icon'=>'fa-shield-heart',  'color'=>'#0891b2', 'bg'=>'#f0f9ff', 'docs'=>15],
                ['name'=>'Sterling Hospital',     'loc'=>'Sindhubhavan, Ahmedabad',   'icon'=>'fa-star-of-life',  'color'=>'#16a34a', 'bg'=>'#f0fdf4', 'docs'=>15],
                ['name'=>'HCG Hospital',          'loc'=>'Ellisbridge, Ahmedabad',    'icon'=>'fa-microscope',    'color'=>'#ea580c', 'bg'=>'#fff7ed', 'docs'=>15],
            ];
            foreach($hospitals as $h): ?>
            <div class="hospital-card">
                <div class="hospital-icon" style="background:<?php echo $h['bg']; ?>;color:<?php echo $h['color']; ?>;">
                    <i class="fa-solid <?php echo $h['icon']; ?>"></i>
                </div>
                <div class="hospital-info">
                    <h3><?php echo $h['name']; ?></h3>
                    <p><i class="fa-solid fa-location-dot"></i> <?php echo $h['loc']; ?></p>
                    <span class="hospital-doc-count"><?php echo $h['docs']; ?> Specialists</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ===== CTA ===== -->
<section class="cta-section">
    <div class="cta-content">
        <h2>Ready to simplify your clinical day?</h2>
        <p>Join Salvex and experience a smarter way to manage your patients and appointments.</p>
        <div class="cta-actions">
            <a href="signin.php" class="btn-primary-lg">
                <i class="fa-solid fa-right-to-bracket"></i> Login Now
            </a>
            <a href="signup.php" class="btn-white-lg">Register Your Account</a>
        </div>
    </div>
</section>

<!-- ===== FOOTER ===== -->
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <div style="width:44px;height:44px;background:#fff;border-radius:12px;display:flex;align-items:center;justify-content:center;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.15);">
                <img src="assets/images/Salvex_Logo.png" alt="Salvex" style="width:38px;height:38px;object-fit:contain;">
            </div>
            <span>Salvex Doctor Portal</span>
        </div>
        <p class="footer-tagline">Less Paperwork. More Patience-Work.</p>
        <p class="footer-copy">&copy; <?php echo date('Y'); ?> Salvex HMS. All rights reserved.</p>
    </div>
</footer>

<script src="assets/js/main.js"></script>
</body>
</html>
