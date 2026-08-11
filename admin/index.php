<?php
include __DIR__ . '/includes/db.php';
$doctorCount = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM doctors"))['total'] ?? 0);
$patientCount = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM users"))['total'] ?? 0);
$appointmentCount = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM appointments"))['total'] ?? 0);
$hospitalCount = (int) (mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) AS total FROM hospitals"))['total'] ?? 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvex Admin Portal – Less Paperwork. More Patience-Work.</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/index.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
    <div class="nav-container">
        <a href="index.php" class="nav-logo">
            <img src="assets/images/Salvex_Logo.png" alt="Salvex Logo" class="logo-img">            
            <span class="logo-text">Salvex <span class="logo-badge">Admin</span></span>
        </a>
        <div class="nav-right">
            <span class="nav-tagline">Less Paperwork. More Patience-Work.</span>
            <a href="login.php" class="btn-login">
                <i class="fas fa-shield-halved"></i> Admin Login
            </a>
        </div>
    </div>
</nav>

<!-- HERO SECTION -->
<section class="hero-section">
    <div class="hero-bg-grid"></div>
    <div class="hero-blob hero-blob-1"></div>
    <div class="hero-blob hero-blob-2"></div>
    <div class="hero-container">
        <div class="hero-badge">
            <i class="fas fa-circle-dot pulse-dot"></i> System Online &amp; Operational
        </div>
        <h1 class="hero-title">
            The <span class="gradient-text">Command Center</span><br>of Salvex Healthcare
        </h1>
        <p class="hero-subtitle">
            Monitor, manage, and control every aspect of the Salvex platform —<br>
            from doctors and patients to appointments and hospital operations.
        </p>
        <div class="hero-actions">
            <a href="login.php" class="btn-primary-hero">
                <i class="fas fa-lock-open"></i> Access Admin Portal
            </a>
            <a href="#overview" class="btn-secondary-hero">
                <i class="fas fa-chart-line"></i> View System Overview
            </a>
        </div>
        <div class="hero-stats">
            <div class="hero-stat"><span class="stat-num" data-target="<?php echo $hospitalCount; ?>">0</span>+<span class="stat-label">Hospitals</span></div>
            <div class="stat-divider"></div>
            <div class="hero-stat"><span class="stat-num" data-target="<?php echo $doctorCount; ?>">0</span>+<span class="stat-label">Doctors</span></div>
            <div class="stat-divider"></div>
            <div class="hero-stat"><span class="stat-num" data-target="<?php echo $patientCount; ?>">0</span>+<span class="stat-label">Patients</span></div>
            <div class="stat-divider"></div>
            <div class="hero-stat"><span class="stat-num" data-target="98">0</span>%<span class="stat-label">Uptime</span></div>
        </div>
    </div>
    <div class="hero-scroll-hint"><span>Scroll to explore</span><i class="fas fa-chevron-down bounce"></i></div>
</section>

<!-- SYSTEM OVERVIEW -->
<section class="overview-section" id="overview">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag"><i class="fas fa-gauge-high"></i> Live System Overview</span>
            <h2 class="section-title">Platform at a Glance</h2>
            <p class="section-subtitle">Real-time insights into the entire Salvex healthcare ecosystem</p>
        </div>
        <div class="overview-grid">
            <div class="overview-card card-blue" onclick="window.location.href='login.php'">
                <div class="card-icon-wrap"><i class="fas fa-hospital"></i></div>
                <div class="card-data"><span class="card-number"><?php echo number_format($hospitalCount); ?></span><span class="card-unit">Hospitals</span></div>
                <div class="card-trend trend-up"><i class="fas fa-arrow-trend-up"></i> Live data</div>
                <div class="card-glow"></div>
            </div>
            <div class="overview-card card-violet" onclick="window.location.href='login.php'">
                <div class="card-icon-wrap"><i class="fas fa-user-doctor"></i></div>
                <div class="card-data"><span class="card-number"><?php echo number_format($doctorCount); ?></span><span class="card-unit">Doctors</span></div>
                <div class="card-trend trend-up"><i class="fas fa-arrow-trend-up"></i> Live data</div>
                <div class="card-glow"></div>
            </div>
            <div class="overview-card card-green" onclick="window.location.href='login.php'">
                <div class="card-icon-wrap"><i class="fas fa-users"></i></div>
                <div class="card-data"><span class="card-number"><?php echo number_format($patientCount); ?></span><span class="card-unit">Patients</span></div>
                <div class="card-trend trend-up"><i class="fas fa-arrow-trend-up"></i> Live data</div>
                <div class="card-glow"></div>
            </div>
            <div class="overview-card card-rose" onclick="window.location.href='login.php'">
                <div class="card-icon-wrap"><i class="fas fa-calendar-check"></i></div>
                <div class="card-data"><span class="card-number"><?php echo number_format($appointmentCount); ?></span><span class="card-unit">Appointments</span></div>
                <div class="card-trend trend-up"><i class="fas fa-arrow-trend-up"></i> Live data</div>
                <div class="card-glow"></div>
            </div>
        </div>
    </div>
</section>

<!-- FEATURES SECTION -->
<section class="features-section" id="features">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag"><i class="fas fa-bolt"></i> Admin Capabilities</span>
            <h2 class="section-title">Everything Under One Control Panel</h2>
            <p class="section-subtitle">Powerful tools designed to give you complete authority over the platform</p>
        </div>
        <div class="features-grid">
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon-box" style="background:linear-gradient(135deg,#0080FF20,#0080FF10)"><i class="fas fa-user-doctor" style="color:#0080FF"></i></div>
                <h3 class="feature-title">Doctor Management</h3>
                <p class="feature-desc">View, enable, or disable doctor accounts. Monitor specializations, hospital assignments, and activity status across the entire network.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Enable / Disable accounts</li>
                    <li><i class="fas fa-check-circle"></i> View specialization &amp; hospital</li>
                    <li><i class="fas fa-check-circle"></i> Monitor activity status</li>
                </ul>
                <span class="feature-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon-box" style="background:linear-gradient(135deg,#818CF820,#818CF810)"><i class="fas fa-users" style="color:#818CF8"></i></div>
                <h3 class="feature-title">Patient Management</h3>
                <p class="feature-desc">Track registered patients, monitor their activity, and access their profile information. Ensure every patient is handled efficiently.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> View patient profiles</li>
                    <li><i class="fas fa-check-circle"></i> Track activity levels</li>
                    <li><i class="fas fa-check-circle"></i> Monitor registrations</li>
                </ul>
                <span class="feature-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon-box" style="background:linear-gradient(135deg,#10B98120,#10B98110)"><i class="fas fa-calendar-check" style="color:#10B981"></i></div>
                <h3 class="feature-title">Appointment Tracking</h3>
                <p class="feature-desc">Monitor all scheduled appointments across hospitals and doctors. Filter by date, status, or hospital for precise control.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Full appointment list</li>
                    <li><i class="fas fa-check-circle"></i> Filter by date &amp; status</li>
                    <li><i class="fas fa-check-circle"></i> Conflict detection</li>
                </ul>
                <span class="feature-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon-box" style="background:linear-gradient(135deg,#F43F5E20,#F43F5E10)"><i class="fas fa-hospital" style="color:#F43F5E"></i></div>
                <h3 class="feature-title">Hospital Oversight</h3>
                <p class="feature-desc">Oversee registered hospitals, their operational status, and the doctors assigned to each institution across the platform.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Hospital status control</li>
                    <li><i class="fas fa-check-circle"></i> Doctor assignments</li>
                    <li><i class="fas fa-check-circle"></i> Operational monitoring</li>
                </ul>
                <span class="feature-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon-box" style="background:linear-gradient(135deg,#F59E0B20,#F59E0B10)"><i class="fas fa-chart-line" style="color:#F59E0B"></i></div>
                <h3 class="feature-title">Analytics &amp; Reports</h3>
                <p class="feature-desc">Access appointment trends, doctor performance, and platform usage analytics to make informed operational decisions.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Trend analysis charts</li>
                    <li><i class="fas fa-check-circle"></i> Usage reports</li>
                    <li><i class="fas fa-check-circle"></i> Performance metrics</li>
                </ul>
                <span class="feature-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
            <div class="feature-card" onclick="window.location.href='login.php'">
                <div class="feature-icon-box" style="background:linear-gradient(135deg,#06B6D420,#06B6D410)"><i class="fas fa-bell" style="color:#06B6D4"></i></div>
                <h3 class="feature-title">Alerts &amp; Notifications</h3>
                <p class="feature-desc">Stay informed on system activity with real-time alerts for cancellations, emergency flags, and high-priority system events.</p>
                <ul class="feature-list">
                    <li><i class="fas fa-check-circle"></i> Real-time alert feed</li>
                    <li><i class="fas fa-check-circle"></i> Emergency flags</li>
                    <li><i class="fas fa-check-circle"></i> Cancellation tracking</li>
                </ul>
                <span class="feature-arrow"><i class="fas fa-arrow-right"></i></span>
            </div>
        </div>
    </div>
</section>

<!-- HOW IT WORKS -->
<section class="how-section">
    <div class="section-container">
        <div class="section-header">
            <span class="section-tag"><i class="fas fa-map"></i> Workflow</span>
            <h2 class="section-title">How the Admin System Works</h2>
            <p class="section-subtitle">A streamlined process designed for efficiency and control</p>
        </div>
        <div class="steps-container">
            <div class="step-item" onclick="window.location.href='login.php'">
                <div class="step-number">01</div>
                <div class="step-icon"><i class="fas fa-key"></i></div>
                <h3 class="step-title">Secure Login</h3>
                <p class="step-desc">Admin authenticates with secure credentials, gaining authorized access to the full control panel.</p>
            </div>
            <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item" onclick="window.location.href='login.php'">
                <div class="step-number">02</div>
                <div class="step-icon"><i class="fas fa-gauge-high"></i></div>
                <h3 class="step-title">Dashboard Overview</h3>
                <p class="step-desc">Instantly view real-time platform statistics, alerts, and system health at a glance.</p>
            </div>
            <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item" onclick="window.location.href='login.php'">
                <div class="step-number">03</div>
                <div class="step-icon"><i class="fas fa-sliders"></i></div>
                <h3 class="step-title">Manage Operations</h3>
                <p class="step-desc">Control doctors, patients, appointments, and hospitals through intuitive management panels.</p>
            </div>
            <div class="step-connector"><i class="fas fa-arrow-right"></i></div>
            <div class="step-item" onclick="window.location.href='login.php'">
                <div class="step-number">04</div>
                <div class="step-icon"><i class="fas fa-shield-check"></i></div>
                <h3 class="step-title">Maintain Control</h3>
                <p class="step-desc">Monitor alerts, review analytics, and ensure the platform runs smoothly and efficiently.</p>
            </div>
        </div>
    </div>
</section>

<!-- CTA SECTION -->
<section class="cta-section">
    <div class="cta-blob cta-blob-1"></div>
    <div class="cta-blob cta-blob-2"></div>
    <div class="cta-container">
        <div class="cta-badge"><i class="fas fa-lock"></i> Restricted Access</div>
        <h2 class="cta-title">Ready to Take Control?</h2>
        <p class="cta-subtitle">Log in to the Salvex Admin Portal and manage the entire healthcare platform from one powerful dashboard.</p>
        <a href="login.php" class="btn-cta"><i class="fas fa-right-to-bracket"></i> Enter Admin Portal</a>
    </div>
</section>

<!-- FOOTER -->
<footer class="footer">
    <div class="footer-container">
        <div class="footer-brand">
            <div class="footer-logo">
                <img src="assets/images/Salvex_Logo.png" alt="Salvex" class="footer-logo-img">
                <span class="footer-logo-text">Salvex</span>
            </div>
            <p class="footer-tagline">Less Paperwork. More Patience-Work.</p>
            <p class="footer-desc">Salvex is a comprehensive Hospital Management System designed to digitize and streamline healthcare operations — from patient registration to doctor management and appointment scheduling.</p>
        </div>
        <div class="footer-links">
            <h4>Admin Portal</h4>
            <ul>
                <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Admin Login</a></li>
                <li><a href="dashboard.php"><i class="fas fa-gauge"></i> Dashboard</a></li>
                <li><a href="dashboard.php#doctors"><i class="fas fa-user-doctor"></i> Manage Doctors</a></li>
                <li><a href="dashboard.php#patients"><i class="fas fa-users"></i> Manage Patients</a></li>
                <li><a href="dashboard.php#appointments"><i class="fas fa-calendar"></i> Appointments</a></li>
            </ul>
        </div>
        <div class="footer-links">
            <h4>System</h4>
            <ul>
                <li><a href="#"><i class="fas fa-circle-check" style="color:#10B981"></i> System Status: Online</a></li>
                <li><a href="#"><i class="fas fa-shield-halved"></i> Security Policies</a></li>
                <li><a href="#"><i class="fas fa-file-contract"></i> Admin Guidelines</a></li>
                <li><a href="#"><i class="fas fa-headset"></i> Support</a></li>
            </ul>
        </div>
    </div>
    <div class="footer-bottom">
        <p>&copy; 2024 Salvex Hospital Management System. All rights reserved.</p>
        <p>Built with <i class="fas fa-heart" style="color:#F43F5E"></i> for better healthcare</p>
    </div>
</footer>

<script src="assets/js/index.js"></script>
</body>
</html>
