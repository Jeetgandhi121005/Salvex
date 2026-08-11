<?php 
session_start(); // Zaroori: Navbar mein login status check karne ke liye
include 'includes/data.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvex | Less Paperwork. More Patience-Work.</title>
    <link rel="stylesheet" href="assets/css/style.css?v=1.2"> <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <nav class="navbar">
        <div class="nav-content">
            <a href="index.php" class="logo">
                <img src="assets/images/Salvex_Logo.png" alt="Salvex Logo">
                <span class="logo-text">Salvex</span>
            </a>
            
            <div class="search-container header-search">
                <div class="search-box">
                    <i class="fa-solid fa-magnifying-glass"></i>
                    <input type="text" id="mainSearch" placeholder="Search Hospital / Doctor...">
                </div>
            </div>

            <div class="nav-right">
                <?php if(isset($_SESSION['user_name'])): ?>
                    <span class="auth-link" style="color: var(--text); cursor: default;">Hi, <?php echo explode(' ', $_SESSION['user_name'])[0]; ?></span>
                    <div class="user-profile-container">
                        <div class="profile-trigger" onclick="toggleDropdown()">
                            <i class="fas fa-user-circle"></i>
                        </div>
                
                        <div class="modern-dropdown" id="profileDropdown">
                            <div class="dropdown-header">
                                <h4 class="user-display-name">
                                    <?php echo isset($_SESSION['user_name']) ? htmlspecialchars($_SESSION['user_name']) : 'Guest User'; ?>
                                </h4>

                                <p class="user-display-email">
                                    <?php echo isset($_SESSION['user_email']) ? htmlspecialchars($_SESSION['user_email']) : 'No Email Found'; ?>
                                </p>
                            </div>

                            <div class="dropdown-body">
                                <a href="dashboard.php" class="dropdown-item">
                                    <i class="fa-solid fa-stethoscope"></i> Book Appointment
                                </a>
                                <a href="dashboard.php?view=family" class="dropdown-item">
                                    <i class="fa-solid fa-users"></i> Manage Family Members
                                </a>
                                <a href="dashboard.php?view=appointments" class="dropdown-item">
                                    <i class="fa-solid fa-calendar-check"></i> My Appointments
                                </a>
                                <a href="dashboard.php?view=billing" class="dropdown-item">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> Billing
                                </a>
                                <a href="dashboard.php?view=reports" class="dropdown-item">
                                    <i class="fa-solid fa-chart-column"></i> Reports
                                </a>
                                <a href="dashboard.php?view=help" class="dropdown-item">
                                    <i class="fa-solid fa-headset"></i> Need Help
                                </a>
                                <div class="dropdown-divider"></div>
                                <a href="logout.php" class="dropdown-item logout-item">
                                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="signin.php" class="nav-link">Sign In</a>
                    <span class="divider">|</span>
                    <a href="signup.php" class="nav-link">Sign Up</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main id="main-content">
        <section class="hero-section">
            <h1>Less Paperwork. <span class="highlight">More Patience-Work.</span></h1>
            <p class="hero-subtitle">
                Find the right specialist, check availability, and <strong>book your appointment</strong> in just 3 simple steps.
            </p>
            <div class="hero-cta">
                <a href="#categories" class="cta-btn">
                    <i class="fa-solid fa-calendar-check"></i> Book Appointment Now
                </a>
            </div>
        </section>

        <section class="steps-section container">
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                    <h4>1. Find Doctor</h4>
                    <p>Search by disease or hospital facility.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-user-check"></i></div>
                    <h4>2. Login Securely</h4>
                    <p>Quick sign-in for your data safety.</p>
                </div>
                <div class="step-card">
                    <div class="step-icon"><i class="fa-solid fa-circle-check"></i></div>
                    <h4>3. Book Slot</h4>
                    <p>Pick a time and you're done!</p>
                </div>
            </div>
        </section>

        <section id="categories" class="disease-section container">
            <h2 class="section-title">Browse by Category</h2>
            <div class="disease-grid">
                <?php foreach($diseases as $d): ?>
                <div class="disease-card" onclick="filterByDisease('<?php echo $d['name']; ?>', '<?php echo $d['desc']; ?>')">
                    <div class="d-icon"><i class="fa-solid <?php echo $d['icon']; ?>"></i></div>
                    <h3><?php echo $d['name']; ?></h3>
                    <p><?php echo $d['part']; ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </section>

        <footer class="footer">
            <h2 class="middle-heading">About Us</h2>
            <div class="container">
                <div class="footer-section about-section">
                    <div class="info-bordered-box">
                        <h3 class="info-title">What is Salvex?</h3>
                        <p class="info-text">
                            Salvex is a modern, web-based Hospital Management System designed to simplify how patients discover hospitals, connect with 
                            doctors, and manage their healthcare journey. Built around the core philosophy of <strong>“Less Paperwork. More Patience-Work,”</strong> 
                            Salvex focuses on reducing administrative friction so that both patients and medical professionals can concentrate on quality care. 
                        </p>
                        <p class="info-text" style="margin-top: 15px;">
                            At its core, Salvex acts as a centralized digital bridge between patients and selected private hospitals in Ahmedabad. Instead of 
                            navigating scattered websites or physically visiting facilities to gather information, patients can access hospital listings, 
                            specialist details, disease-based filtering, and doctor availability through a single, streamlined, and interactive platform.
                        </p>
                    </div>

                                
                    <div class="info-bordered-box purpose-gap">
                        <h3 class="info-title">The Purpose Behind Salvex</h3>
                        <p class="info-text">
                            Healthcare systems often struggle with inefficiencies such as manual paperwork, delayed communication, and fragmented information.
                            Salvex addresses these gaps by organizing hospital and doctor data in a clear, searchable format, allowing patients to quickly 
                            find relevant specialists, and presenting structured medical information without confusion. By reducing the time spent 
                            navigating administrative processes, the platform ensures a smoother transition from discovery to consultation. 
                            The goal is not just digitization, but the comprehensive optimization of the patient experience.
                        </p>
                    </div>
                </div>
                <hr class="footer-divider">

                <div class="footer-section">
                    <div class="section-header">
                        <h3>Quick Navigation</h3>
                        <p>Click on the arrows to learn more about our services.</p>
                    </div>

                    <div class="vertical-links-grid">
                        <div class="link-group">
                            <h4>Discovery</h4>
                            <div class="interactive-link" onclick="toggleIdea(this)">
                                <div class="link-header">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <span>Search Specialists</span>
                                </div>
                                <div class="link-idea">Browse through our verified network of top-rated specialists in Ahmedabad, filtered by expertise and availability.</div>
                            </div>
                            <div class="interactive-link" onclick="toggleIdea(this)">
                                <div class="link-header">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <span>Partner Hospitals</span>
                                </div>
                                <div class="link-idea">Get direct access to world-class medical facilities and multi-specialty hospitals integrated with the Salvex ecosystem.</div>
                            </div>
                        </div>

                        <div class="link-group">
                            <h4>Assistance</h4>
                            <div class="interactive-link" onclick="toggleIdea(this)">
                                <div class="link-header">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <span>Emergency 24/7</span>
                                </div>
                                <div class="link-idea">Round-the-clock emergency support that connects you to the nearest trauma center and alerts doctors before you arrive.</div>
                            </div>
                            <div class="interactive-link" onclick="toggleIdea(this)">
                                <div class="link-header">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <span>Smart Patient Navigator</span>
                                </div>
                                <div class="link-idea">Turns hospital stress into a seamless experience with instant doctor matching that ensures no patient ever feels lost or ignored.</div>
                            </div>
                        </div>

                        <div class="link-group">
                            <h4>Company</h4>
                            <div class="interactive-link" onclick="toggleIdea(this)">
                                <div class="link-header">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <span>Privacy Policy</span>
                                </div>
                                <div class="link-idea">Your health data is encrypted with bank-grade security. We ensure total confidentiality of your medical records.</div>
                            </div>
                            <div class="interactive-link" onclick="toggleIdea(this)">
                                <div class="link-header">
                                    <i class="fa-solid fa-chevron-right"></i>
                                    <span>Terms of Use</span>
                                </div>
                                <div class="link-idea">A clear guide on the rules and regulations for using the Salvex platform as a patient or a healthcare provider.</div>
                            </div>
                        </div>
                    </div>
                </div>

                <hr class="footer-divider">
                <div class="footer-section">
                    <div class="section-header">
                        <h3>Contact Information</h3>
                        <p>Reach out to our support team or visit our Ahmedabad office.</p>
                    </div>
                    <div class="contact-grid-detailed">
                        <div class="contact-card">
                            <div class="c-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div class="c-text">
                                <strong>Our Workplace</strong>
                                <p>SGI Tower, Near Sindhu Bhavan, Ahmedabad, 380054</p>
                            </div>
                        </div>
                        <div class="contact-card">
                            <div class="c-icon"><i class="fa-solid fa-phone"></i></div>
                            <div class="c-text">
                                <strong>Support Line</strong>
                                <p>+91 79 1234 5678</p>
                            </div>
                        </div>
                        <div class="contact-card">
                            <div class="c-icon"><i class="fa-solid fa-envelope"></i></div>
                            <div class="c-text">
                                <strong>Email Address</strong>
                                <p>support@salvex.com</p>
                            </div>
                        </div>
                    </div>
                </div>
                <hr class="footer-divider">
                <div class="footer-bottom-centered">
                    <p class="copy-text">© 2026 Salvex HMS | All Rights Reserved.</p>
                </div>
            </div>
        </footer>
    </main>

    <div id="overlay" class="overlay">
        <div class="overlay-content">
            <button class="close-btn" onclick="closeOverlay()">&times;</button>
            <div id="result-header"></div>
            <div id="results-list" class="results-grid"></div>
        </div>
    </div>

    <script>
        const allDoctors = <?php echo json_encode($doctors); ?>;
        const allHospitals = <?php echo json_encode($hospitals); ?>;
    </script>

    <script src="assets/js/main.js?v=2"></script>
    <script src="assets/js/auth_handler.js"></script>
</body>
</html>
