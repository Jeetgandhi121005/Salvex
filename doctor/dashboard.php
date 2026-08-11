<?php
session_start();
include 'includes/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header("Location: signin.php");
    exit();
}

function sanitiseScheduleTime(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return '09:00 AM - 05:00 PM';
    }

    if (!preg_match('/^(\d{1,2}:\d{2}\s*[APap][Mm])\s*-\s*(\d{1,2}:\d{2}\s*[APap][Mm])$/', $raw, $matches)) {
        return $raw;
    }

    $start = strtoupper(trim($matches[1]));
    $end = strtoupper(trim($matches[2]));

    return $start . ' - ' . $end;
}

function patientInitials(string $name): string
{
    $initials = '';
    foreach (preg_split('/\s+/', trim($name)) as $part) {
        if ($part !== '') {
            $initials .= strtoupper($part[0]);
        }
    }

    return substr($initials ?: 'PA', 0, 2);
}

$doctorId = (int) $_SESSION['doctor_id'];
$doctorStmt = $conn->prepare("SELECT * FROM doctors WHERE id = ? AND is_active = 1 LIMIT 1");
$doctorStmt->bind_param('i', $doctorId);
$doctorStmt->execute();
$doctor = $doctorStmt->get_result()->fetch_assoc();
$doctorStmt->close();

if (!$doctor) {
    session_destroy();
    header("Location: signin.php?error=inactive");
    exit();
}

$_SESSION['doctor_name'] = $doctor['full_name'];
$_SESSION['doctor_email'] = $doctor['email'];
$_SESSION['doctor_specialty'] = $doctor['specialty'];
$_SESSION['doctor_hospital'] = $doctor['hospital'];
$_SESSION['doctor_exp'] = $doctor['experience'];
$_SESSION['doctor_time'] = $doctor['available_time'];
$_SESSION['doctor_status'] = $doctor['status'];

$doctorName = $doctor['full_name'];
$specialty = $doctor['specialty'] ?: 'General';
$hospital = $doctor['hospital'] ?: 'Salvex Hospital';
$experience = $doctor['experience'] ?: 'N/A';
$availableTime = sanitiseScheduleTime((string) $doctor['available_time']);
$doctorStatus = $doctor['status'] ?: 'available';
$doctorEmail = $doctor['email'];
$consultationFee = (int) ($doctor['consultation_fee'] ?? 0);
$today = date('Y-m-d');

$appointments = [];
$apptStmt = $conn->prepare(
    "SELECT a.*, u.full_name AS booked_by_name, u.email AS booked_by_email
     FROM appointments a
     LEFT JOIN users u ON a.user_id = u.id
     WHERE a.doctor_id = ?
     ORDER BY a.appointment_date ASC, STR_TO_DATE(a.appointment_time, '%h:%i %p') ASC, a.id ASC"
);
$apptStmt->bind_param('i', $doctorId);
$apptStmt->execute();
$apptResult = $apptStmt->get_result();
while ($row = $apptResult->fetch_assoc()) {
    $appointments[] = $row;
}
$apptStmt->close();

$todayAppointments = [];
$upcomingAppointments = [];
$allPatients = [];

foreach ($appointments as $appointment) {
    if (!empty($appointment['patient_name'])) {
        $allPatients[] = strtolower($appointment['patient_name']);
    }

    if ($appointment['appointment_date'] === $today) {
        $todayAppointments[] = $appointment;
    } elseif ($appointment['appointment_date'] > $today && $appointment['status'] !== 'Cancelled') {
        $upcomingAppointments[] = $appointment;
    }
}

$totalToday = count($todayAppointments);
$completed = count(array_filter($todayAppointments, fn($item) => $item['status'] === 'Completed'));
$pending = count(array_filter($todayAppointments, fn($item) => $item['status'] === 'Pending'));
$confirmed = count(array_filter($todayAppointments, fn($item) => $item['status'] === 'Confirmed'));
$cancelled = count(array_filter($todayAppointments, fn($item) => $item['status'] === 'Cancelled'));
$totalPatients = count(array_unique($allPatients));

$hour = (int) date('H');
$greeting = $hour < 12 ? 'Good Morning' : ($hour < 17 ? 'Good Afternoon' : 'Good Evening');
$doctorShortName = trim((string) preg_replace('/^Dr\.?\s*/i', '', $doctorName));
$doctorShortName = explode(' ', $doctorShortName)[0] ?: $doctorName;
$doctorInitials = patientInitials($doctorName);

$specColors = [
    'Cardiology' => '#ef4444',
    'Neurology' => '#7c3aed',
    'Orthopedics' => '#f97316',
    'Dermatology' => '#ec4899',
    'Pediatrics' => '#06b6d4',
    'Oncology' => '#f43f5e',
    'Gastroenterology' => '#10b981',
    'Ophthalmology' => '#3b82f6',
    'Nephrology' => '#6366f1',
    'Pulmonology' => '#0ea5e9',
    'Endocrinology' => '#f59e0b',
    'Psychiatry' => '#a855f7',
    'ENT' => '#14b8a6',
    'Urology' => '#64748b',
    'Dentistry' => '#22c55e',
];
$specColor = $specColors[$specialty] ?? '#2563eb';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Salvex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body class="<?php echo isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true' ? 'dark-mode' : ''; ?>">
<header class="topbar">
    <div class="topbar-left">
        <button class="sidebar-toggle-btn" onclick="toggleSidebar()">
            <i class="fa-solid fa-bars"></i>
        </button>
        <a href="javascript:void(0)" class="topbar-logo" onclick="showSection('overview'); return false;">
            <img src="assets/images/Salvex_Logo.png" alt="Salvex">
            <span>Salvex</span>
        </a>
    </div>
    <div class="topbar-right">
        <button class="topbar-icon-btn" onclick="toggleDarkMode()" title="Toggle Dark Mode">
            <i class="fa-solid fa-moon" id="darkModeIcon"></i>
        </button>
        <div class="notif-wrap">
            <button class="topbar-icon-btn has-badge" onclick="toggleNotifPanel()" title="Notifications">
                <i class="fa-solid fa-bell"></i>
                <span class="badge" id="notifBadge"><?php echo $pending; ?></span>
            </button>
            <div class="notif-panel" id="notifPanel">
                <div class="notif-header">
                    <h4>Notifications</h4>
                    <button onclick="clearNotifs()">Clear all</button>
                </div>
                <div class="notif-list" id="notifList">
                    <?php if ($pending > 0): ?>
                    <div class="notif-item unread">
                        <div class="notif-icon orange"><i class="fa-solid fa-hourglass-half"></i></div>
                        <div class="notif-content">
                            <p><strong><?php echo $pending; ?> pending</strong> appointment<?php echo $pending !== 1 ? 's' : ''; ?> for today</p>
                            <span>Today</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if (count($upcomingAppointments) > 0): ?>
                    <div class="notif-item">
                        <div class="notif-icon blue"><i class="fa-solid fa-calendar-days"></i></div>
                        <div class="notif-content">
                            <p><strong><?php echo count($upcomingAppointments); ?> upcoming</strong> appointments scheduled</p>
                            <span>Next few days</span>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($pending === 0 && count($upcomingAppointments) === 0): ?>
                    <div class="notif-item">
                        <div class="notif-icon blue"><i class="fa-solid fa-circle-info"></i></div>
                        <div class="notif-content">
                            <p>No new notifications right now.</p>
                            <span>All caught up</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="profile-wrap">
            <button class="topbar-profile-btn" onclick="toggleProfileDrop()">
                <div class="topbar-avatar" style="background:<?php echo $specColor; ?>;"><?php echo $doctorInitials; ?></div>
                <div class="topbar-profile-info">
                    <span class="topbar-name"><?php echo htmlspecialchars($doctorName); ?></span>
                    <span class="topbar-spec"><?php echo htmlspecialchars($specialty); ?></span>
                </div>
                <i class="fa-solid fa-chevron-down"></i>
            </button>
            <div class="profile-dropdown" id="profileDrop">
                <div class="profile-drop-header">
                    <div class="profile-drop-avatar" style="background:<?php echo $specColor; ?>;"><?php echo $doctorInitials; ?></div>
                    <div>
                        <strong><?php echo htmlspecialchars($doctorName); ?></strong>
                        <span><?php echo htmlspecialchars($specialty); ?></span>
                    </div>
                </div>
                <a href="javascript:void(0)" onclick="showSection('profile');toggleProfileDrop();return false;" class="profile-drop-item">
                    <i class="fa-solid fa-user"></i> My Profile
                </a>
                <a href="logout.php" class="profile-drop-item danger">
                    <i class="fa-solid fa-right-from-bracket"></i> Logout
                </a>
            </div>
        </div>
    </div>
</header>

<div class="app-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-status-card">
            <div class="sidebar-status-top">
                <div class="sidebar-avatar" style="background:<?php echo $specColor; ?>;"><?php echo $doctorInitials; ?></div>
                <div class="sidebar-doc-info">
                    <strong title="<?php echo htmlspecialchars($doctorName); ?>"><?php echo htmlspecialchars($doctorName); ?></strong>
                    <span><?php echo htmlspecialchars($specialty); ?></span>
                </div>
            </div>
            <select class="status-select" id="statusSelect" onchange="updateDoctorStatus(this.value)">
                <option value="available" <?php echo $doctorStatus === 'available' ? 'selected' : ''; ?>>Available</option>
                <option value="busy" <?php echo $doctorStatus === 'busy' ? 'selected' : ''; ?>>Busy</option>
                <option value="offline" <?php echo $doctorStatus === 'offline' ? 'selected' : ''; ?>>Offline</option>
            </select>
        </div>

        <nav class="sidebar-nav">
            <a href="javascript:void(0)" class="nav-item active" onclick="showSection('overview');return false;">
                <i class="fa-solid fa-gauge-high"></i><span>Overview</span>
            </a>
            <a href="javascript:void(0)" class="nav-item" onclick="showSection('appointments');return false;">
                <i class="fa-solid fa-calendar-check"></i><span>Appointments</span>
                <?php if ($pending > 0): ?><span class="nav-badge"><?php echo $pending; ?></span><?php endif; ?>
            </a>
            <a href="javascript:void(0)" class="nav-item" onclick="showSection('upcoming');return false;">
                <i class="fa-solid fa-calendar-days"></i><span>Upcoming</span>
                <?php if (count($upcomingAppointments) > 0): ?><span class="nav-badge" style="background:#7c3aed"><?php echo count($upcomingAppointments); ?></span><?php endif; ?>
            </a>
            <a href="javascript:void(0)" class="nav-item" onclick="showSection('schedule');return false;">
                <i class="fa-solid fa-clock"></i><span>My Schedule</span>
            </a>
            <a href="javascript:void(0)" class="nav-item" onclick="showSection('profile');return false;">
                <i class="fa-solid fa-user-doctor"></i><span>My Profile</span>
            </a>
        </nav>

        <a href="logout.php" class="sidebar-logout">
            <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
        </a>
    </aside>

    <main class="main-content">
        <div class="section-panel active" id="section-overview">
            <div class="greeting-bar">
                <div>
                    <h1 class="greeting-title"><?php echo $greeting; ?>, Dr. <?php echo htmlspecialchars($doctorShortName); ?></h1>
                    <p class="greeting-sub">
                        You have <strong><?php echo $totalToday; ?></strong> appointment<?php echo $totalToday !== 1 ? 's' : ''; ?> today.
                        <?php if ($pending > 0): ?>
                        <span class="pending-alert"><?php echo $pending; ?> pending confirmation</span>
                        <?php endif; ?>
                    </p>
                </div>
                <div class="greeting-date">
                    <i class="fa-regular fa-calendar"></i>
                    <?php echo date('l, d M Y'); ?>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card" style="--accent:#2563eb;" onclick="goToAppointments()">
                    <div class="stat-icon" style="background:#eff6ff;color:#2563eb;"><i class="fa-solid fa-calendar-check"></i></div>
                    <div class="stat-info"><span class="stat-num"><?php echo $totalToday; ?></span><span class="stat-label">Today's Appointments</span></div>
                    <div class="stat-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                </div>
                <div class="stat-card" style="--accent:#22c55e;" onclick="statCardClick('appointments','completed')">
                    <div class="stat-icon" style="background:#f0fdf4;color:#22c55e;"><i class="fa-solid fa-circle-check"></i></div>
                    <div class="stat-info"><span class="stat-num"><?php echo $completed; ?></span><span class="stat-label">Completed</span></div>
                    <div class="stat-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                </div>
                <div class="stat-card" style="--accent:#f97316;" onclick="statCardClick('appointments','pending')">
                    <div class="stat-icon" style="background:#fff7ed;color:#f97316;"><i class="fa-solid fa-hourglass-half"></i></div>
                    <div class="stat-info"><span class="stat-num"><?php echo $pending; ?></span><span class="stat-label">Pending</span></div>
                    <div class="stat-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                </div>
                <div class="stat-card" style="--accent:#7c3aed;" onclick="showSection('profile')">
                    <div class="stat-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fa-solid fa-users"></i></div>
                    <div class="stat-info"><span class="stat-num"><?php echo $totalPatients; ?></span><span class="stat-label">Total Patients</span></div>
                    <div class="stat-arrow"><i class="fa-solid fa-arrow-up-right-from-square"></i></div>
                </div>
            </div>

            <div class="section-panel-header">
                <h2><i class="fa-solid fa-calendar-check" style="color:#2563eb;"></i> Today at a glance</h2>
                <p><?php echo date('l, d M Y'); ?> • Real patient bookings from the Salvex portal</p>
            </div>

            <?php if ($totalToday > 0): ?>
            <div class="appt-list">
                <?php foreach ($todayAppointments as $appointment): ?>
                <div class="appt-card" id="appt-<?php echo (int) $appointment['id']; ?>">
                    <div class="appt-avatar"><?php echo patientInitials((string) ($appointment['patient_name'] ?: 'Patient')); ?></div>
                    <div class="appt-main-info">
                        <h4><?php echo htmlspecialchars($appointment['patient_name'] ?: 'Patient'); ?></h4>
                        <p>
                            <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($appointment['appointment_time']); ?>
                            &nbsp;|&nbsp;
                            <i class="fa-solid fa-hospital"></i> <?php echo htmlspecialchars($appointment['hospital_name'] ?: $hospital); ?>
                        </p>
                    </div>
                    <span class="appt-status-badge status-<?php echo strtolower($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span>
                    <div style="display:flex;gap:10px;flex-wrap:wrap;">
                        <button class="btn-view-detail" onclick='openPatientModal(<?php echo json_encode($appointment, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                            <i class="fa-solid fa-eye"></i> View
                        </button>
                        <a class="btn-view-detail" style="text-decoration:none;" href="consultation.php?appointment_id=<?php echo (int) $appointment['id']; ?>">
                            <i class="fa-solid fa-notes-medical"></i> Consult
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-calendar-xmark"></i>
                <h3>No appointments today</h3>
                <p>Patient bookings will appear here once appointments are made.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-panel" id="section-appointments">
            <div class="section-panel-header">
                <h2><i class="fa-solid fa-calendar-check" style="color:#2563eb;"></i> Today's Appointments</h2>
                <p><?php echo date('l, d M Y'); ?> • <?php echo $totalToday; ?> total appointments today</p>
            </div>

            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;margin-bottom:18px;">
                <div onclick="filterAppts('all', null)" style="background:var(--surface);border:1px solid var(--border);border-radius:10px;padding:12px 16px;cursor:pointer;text-align:center;">
                    <div style="font-size:20px;font-weight:800;color:#0f172a"><?php echo $totalToday; ?></div>
                    <div style="font-size:11px;color:#64748b;font-weight:600">Total</div>
                </div>
                <div onclick="filterAppts('confirmed', null)" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:12px 16px;text-align:center;cursor:pointer;">
                    <div style="font-size:20px;font-weight:800;color:#1d4ed8"><?php echo $confirmed; ?></div>
                    <div style="font-size:11px;color:#1d4ed8;font-weight:600">Confirmed</div>
                </div>
                <div onclick="filterAppts('pending', null)" style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:12px 16px;text-align:center;cursor:pointer;">
                    <div style="font-size:20px;font-weight:800;color:#c2410c"><?php echo $pending; ?></div>
                    <div style="font-size:11px;color:#c2410c;font-weight:600">Pending</div>
                </div>
                <div onclick="filterAppts('completed', null)" style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:10px;padding:12px 16px;text-align:center;cursor:pointer;">
                    <div style="font-size:20px;font-weight:800;color:#166534"><?php echo $completed; ?></div>
                    <div style="font-size:11px;color:#166534;font-weight:600">Completed</div>
                </div>
            </div>

            <div class="filter-tabs" id="apptFilterTabs">
                <button class="filter-tab active" onclick="filterAppts('all',this)">All <span><?php echo $totalToday; ?></span></button>
                <button class="filter-tab" onclick="filterAppts('pending',this)">Pending <span><?php echo $pending; ?></span></button>
                <button class="filter-tab" onclick="filterAppts('confirmed',this)">Confirmed <span><?php echo $confirmed; ?></span></button>
                <button class="filter-tab" onclick="filterAppts('completed',this)">Completed <span><?php echo $completed; ?></span></button>
                <button class="filter-tab" onclick="filterAppts('cancelled',this)">Cancelled <span><?php echo $cancelled; ?></span></button>
            </div>

            <?php if ($totalToday > 0): ?>
            <div class="appt-list full-list" id="fullApptList">
                <?php foreach ($todayAppointments as $appointment): ?>
                <div class="appt-card-full" data-status="<?php echo strtolower($appointment['status']); ?>" id="full-appt-<?php echo (int) $appointment['id']; ?>">
                    <div class="appt-avatar lg"><?php echo patientInitials((string) ($appointment['patient_name'] ?: 'Patient')); ?></div>
                    <div class="appt-detail-block">
                        <h4><?php echo htmlspecialchars($appointment['patient_name'] ?: 'Patient'); ?></h4>
                        <div class="appt-meta-row">
                            <span><i class="fa-solid fa-stethoscope"></i> <?php echo htmlspecialchars($appointment['specialty'] ?: $specialty); ?></span>
                            <span><i class="fa-solid fa-hospital"></i> <?php echo htmlspecialchars($appointment['hospital_name'] ?: $hospital); ?></span>
                            <?php if (!empty($appointment['patient_age'])): ?><span><i class="fa-solid fa-user"></i> <?php echo (int) $appointment['patient_age']; ?> yrs</span><?php endif; ?>
                            <span><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?></span>
                            <span><i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($appointment['appointment_time']); ?></span>
                        </div>
                        <?php if (!empty($appointment['notes'])): ?>
                        <div class="appt-note-preview">
                            <i class="fa-solid fa-note-sticky"></i> <?php echo htmlspecialchars(strlen($appointment['notes']) > 80 ? substr($appointment['notes'], 0, 80) . '...' : $appointment['notes']); ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="appt-right-block">
                        <span class="appt-status-badge status-<?php echo strtolower($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span>
                        <div class="appt-btn-row">
                            <button class="btn-view-detail" onclick='openPatientModal(<?php echo json_encode($appointment, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                                <i class="fa-solid fa-eye"></i> Details
                            </button>
                            <a class="btn-view-detail" style="text-decoration:none;" href="consultation.php?appointment_id=<?php echo (int) $appointment['id']; ?>">
                                <i class="fa-solid fa-notes-medical"></i> Consultation
                            </a>
                            <?php if ($appointment['status'] === 'Pending'): ?>
                            <button class="btn-confirm-appt" onclick="updateApptStatus(<?php echo (int) $appointment['id']; ?>,'Confirmed')">
                                <i class="fa-solid fa-circle-check"></i> Confirm
                            </button>
                            <?php endif; ?>
                            <?php if (!in_array($appointment['status'], ['Completed', 'Cancelled'], true)): ?>
                            <button class="btn-complete" onclick="updateApptStatus(<?php echo (int) $appointment['id']; ?>,'Completed')" title="Mark as done">
                                <i class="fa-solid fa-check"></i>
                            </button>
                            <button class="btn-cancel-appt" onclick="updateApptStatus(<?php echo (int) $appointment['id']; ?>,'Cancelled')" title="Cancel appointment">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="apptEmptyFiltered" style="display:none;text-align:center;padding:40px;color:#94a3b8;">
                <i class="fa-solid fa-filter-circle-xmark" style="font-size:36px;display:block;margin-bottom:12px;"></i>
                No appointments match this filter.
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-calendar-xmark"></i>
                <h3>No appointments today</h3>
                <p>Patients will appear here once they book with you through the patient portal.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-panel" id="section-upcoming">
            <div class="section-panel-header">
                <h2><i class="fa-solid fa-calendar-days" style="color:#7c3aed;"></i> Upcoming Appointments</h2>
                <p>Future patient bookings scheduled for you — <?php echo count($upcomingAppointments); ?> upcoming</p>
            </div>
            <?php if (count($upcomingAppointments) > 0): ?>
            <div class="appt-list">
                <?php foreach ($upcomingAppointments as $appointment): ?>
                <div class="appt-card">
                    <div class="appt-avatar" style="background:linear-gradient(135deg,#7c3aed,#a855f7);"><?php echo patientInitials((string) ($appointment['patient_name'] ?: 'Patient')); ?></div>
                    <div class="appt-main-info">
                        <h4><?php echo htmlspecialchars($appointment['patient_name'] ?: 'Patient'); ?></h4>
                        <p>
                            <i class="fa-regular fa-calendar"></i> <?php echo date('d M Y', strtotime($appointment['appointment_date'])); ?>
                            &nbsp;|&nbsp;
                            <i class="fa-regular fa-clock"></i> <?php echo htmlspecialchars($appointment['appointment_time']); ?>
                            &nbsp;|&nbsp;
                            <i class="fa-solid fa-stethoscope"></i> <?php echo htmlspecialchars($appointment['specialty'] ?: $specialty); ?>
                        </p>
                    </div>
                    <span class="appt-status-badge status-<?php echo strtolower($appointment['status']); ?>"><?php echo htmlspecialchars($appointment['status']); ?></span>
                    <button class="btn-view-detail" onclick='openPatientModal(<?php echo json_encode($appointment, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)'>
                        <i class="fa-solid fa-eye"></i> View
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fa-solid fa-calendar-days"></i>
                <h3>No upcoming appointments</h3>
                <p>Future patient bookings will appear here.</p>
            </div>
            <?php endif; ?>
        </div>

        <div class="section-panel" id="section-schedule">
            <div class="section-panel-header">
                <h2><i class="fa-solid fa-clock" style="color:#f97316;"></i> My Schedule</h2>
                <p>Available time slots for today — Working hours: <strong><?php echo htmlspecialchars($availableTime); ?></strong></p>
            </div>
            <div style="display:flex;gap:14px;margin-bottom:16px;flex-wrap:wrap;">
                <span style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#166534;"><span style="width:12px;height:12px;border-radius:3px;background:#f0fdf4;border:1px solid #bbf7d0;display:inline-block;"></span>Available</span>
                <span style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#1d4ed8;"><span style="width:12px;height:12px;border-radius:3px;background:#eff6ff;border:1px solid #bfdbfe;display:inline-block;"></span>Booked</span>
                <span style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:600;color:#64748b;"><span style="width:12px;height:12px;border-radius:3px;background:#f1f5f9;border:1px solid #e2e8f0;display:inline-block;"></span>Past</span>
            </div>
            <div class="schedule-grid" id="scheduleGrid"></div>
        </div>

        <div class="section-panel" id="section-profile">
            <div class="section-panel-header">
                <h2><i class="fa-solid fa-user-doctor" style="color:#22c55e;"></i> My Profile</h2>
                <p>Your doctor information on Salvex</p>
            </div>
            <div class="profile-card-big">
                <div class="profile-card-top" style="background:linear-gradient(135deg,<?php echo $specColor; ?>22,<?php echo $specColor; ?>08);">
                    <div class="profile-avatar-xl" style="background:<?php echo $specColor; ?>;"><?php echo $doctorInitials; ?></div>
                    <div class="profile-main-info">
                        <h2><?php echo htmlspecialchars($doctorName); ?></h2>
                        <span class="profile-spec-badge" style="background:<?php echo $specColor; ?>18;color:<?php echo $specColor; ?>;"><?php echo htmlspecialchars($specialty); ?></span>
                    </div>
                </div>
                <div class="profile-details-grid">
                    <div class="profile-detail-item">
                        <div class="profile-detail-icon" style="background:#eff6ff;color:#2563eb;"><i class="fa-solid fa-hospital"></i></div>
                        <div><span class="pd-label">Hospital</span><span class="pd-val"><?php echo htmlspecialchars($hospital); ?></span></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-icon" style="background:#f0fdf4;color:#16a34a;"><i class="fa-solid fa-briefcase-medical"></i></div>
                        <div><span class="pd-label">Experience</span><span class="pd-val"><?php echo htmlspecialchars($experience); ?></span></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-icon" style="background:#fff7ed;color:#ea580c;"><i class="fa-regular fa-clock"></i></div>
                        <div><span class="pd-label">Available Hours</span><span class="pd-val"><?php echo htmlspecialchars($availableTime); ?></span></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-icon" style="background:#fdf2f8;color:#db2777;"><i class="fa-solid fa-envelope"></i></div>
                        <div><span class="pd-label">Email</span><span class="pd-val"><?php echo htmlspecialchars($doctorEmail); ?></span></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-icon" style="background:#f5f3ff;color:#7c3aed;"><i class="fa-solid fa-circle-dot"></i></div>
                        <div><span class="pd-label">Current Status</span><span class="pd-val status-text-<?php echo htmlspecialchars($doctorStatus); ?>" data-field="status"><?php echo ucfirst(htmlspecialchars($doctorStatus)); ?></span></div>
                    </div>
                    <div class="profile-detail-item">
                        <div class="profile-detail-icon" style="background:#f0fdf4;color:#0891b2;"><i class="fa-solid fa-calendar-check"></i></div>
                        <div><span class="pd-label">Consultation Fee</span><span class="pd-val">₹<?php echo number_format($consultationFee); ?></span></div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<div class="modal-overlay" id="patientModal" onclick="closePatientModal(event)">
    <div class="patient-modal">
        <div class="patient-modal-header">
            <div class="patient-modal-avatar" id="modalAvatar">PA</div>
            <div>
                <h3 id="modalPatientName">Patient Name</h3>
                <p id="modalPatientMeta">Specialty | Time</p>
            </div>
            <button class="modal-close" onclick="closePatientModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="patient-modal-body">
            <div class="modal-detail-grid">
                <div class="modal-detail-item"><span class="modal-label">Date</span><span class="modal-val" id="modalDate">—</span></div>
                <div class="modal-detail-item"><span class="modal-label">Time</span><span class="modal-val" id="modalTime">—</span></div>
                <div class="modal-detail-item"><span class="modal-label">Age</span><span class="modal-val" id="modalAge">—</span></div>
                <div class="modal-detail-item"><span class="modal-label">Hospital</span><span class="modal-val" id="modalHospital">—</span></div>
                <div class="modal-detail-item"><span class="modal-label">Status</span><span class="modal-val" id="modalStatus">—</span></div>
                <div class="modal-detail-item"><span class="modal-label">Specialty</span><span class="modal-val" id="modalSpecialty">—</span></div>
            </div>
            <div class="notes-section">
                <h4><i class="fa-solid fa-note-sticky"></i> Clinical Notes</h4>
                <textarea id="modalNotes" class="notes-textarea" placeholder="Write your clinical notes here..."></textarea>
                <button class="btn-save-notes" onclick="saveNotes()">
                    <i class="fa-solid fa-floppy-disk"></i> Save Notes
                </button>
            </div>
            <div class="modal-action-row" id="modalActionRow">
                <a class="btn-modal-confirm" id="modalConsultationLink" href="#">
                    <i class="fa-solid fa-notes-medical"></i> Consultation
                </a>
                <button class="btn-modal-confirm" onclick="updateApptStatus(currentApptId,'Confirmed')">
                    <i class="fa-solid fa-circle-check"></i> Confirm
                </button>
                <button class="btn-modal-complete" onclick="updateApptStatus(currentApptId,'Completed')">
                    <i class="fa-solid fa-check"></i> Mark as Done
                </button>
                <button class="btn-modal-cancel" onclick="updateApptStatus(currentApptId,'Cancelled')">
                    <i class="fa-solid fa-xmark"></i> Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<div id="doctorToast"></div>

<script>
window._doctorStatus = <?php echo json_encode($doctorStatus); ?>;
window._docTime = <?php echo json_encode($availableTime); ?>;
window._todayAppts = <?php echo json_encode(array_values(array_map(fn($appointment) => $appointment['appointment_time'], $todayAppointments))); ?>;
</script>
<script src="assets/js/dashboard.js?v=2"></script>
</body>
</html>
