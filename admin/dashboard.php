<?php
session_start();
include __DIR__ . '/includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

salvex_sync_billing_status($conn);

function initials(string $value): string
{
    $result = '';
    foreach (preg_split('/\s+/', trim($value)) as $part) {
        if ($part !== '') {
            $result .= strtoupper($part[0]);
        }
    }
    return substr($result ?: 'NA', 0, 2);
}

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$adminName = $_SESSION['admin_name'] ?? 'Super Admin';
$adminEmail = $_SESSION['admin_email'] ?? 'admin@salvex.com';

$doctorRows = [];
$doctorResult = mysqli_query(
    $conn,
    "SELECT d.*, COUNT(a.id) AS appointment_count, COUNT(DISTINCT a.user_id) AS patient_count
     FROM doctors d
     LEFT JOIN appointments a ON a.doctor_id = d.id
     GROUP BY d.id
     ORDER BY d.created_at DESC, d.id DESC"
);
while ($doctorResult && ($row = mysqli_fetch_assoc($doctorResult))) {
    $row['ui_status'] = ((int) ($row['is_active'] ?? 1) === 1) ? 'Active' : 'Inactive';
    $row['initials'] = initials($row['full_name']);
    $doctorRows[] = $row;
}

$patientRows = [];
$patientSql = "
    SELECT
        u.*,
        COALESCE(f.member_age, NULL) AS self_age,
        COUNT(a.id) AS appointment_count,
        MAX(a.appointment_date) AS last_visit
    FROM users u
    LEFT JOIN (
        SELECT patient_id, MAX(CASE WHEN LOWER(relation) = 'self' THEN member_age END) AS member_age
        FROM family_members
        GROUP BY patient_id
    ) f ON f.patient_id = u.id
    LEFT JOIN appointments a ON a.user_id = u.id
    GROUP BY u.id
    ORDER BY u.created_at DESC, u.id DESC
";
$patientResult = mysqli_query($conn, $patientSql);
while ($patientResult && ($row = mysqli_fetch_assoc($patientResult))) {
    $row['initials'] = initials($row['full_name']);
    $patientRows[] = $row;
}

$appointmentRows = [];
$appointmentSql = "
    SELECT
        a.*,
        u.full_name AS user_full_name,
        u.email AS user_email,
        d.full_name AS doctor_full_name,
        d.specialty AS doctor_specialty,
        d.hospital AS doctor_hospital
    FROM appointments a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    ORDER BY a.appointment_date DESC, STR_TO_DATE(a.appointment_time, '%h:%i %p') DESC, a.id DESC
";
$appointmentResult = mysqli_query($conn, $appointmentSql);
while ($appointmentResult && ($row = mysqli_fetch_assoc($appointmentResult))) {
    $row['patient_display'] = $row['patient_name'] ?: ($row['user_full_name'] ?: 'Patient');
    $row['doctor_display'] = $row['doctor_name'] ?: ($row['doctor_full_name'] ?: 'Not assigned');
    $row['hospital_display'] = $row['hospital_name'] ?: ($row['doctor_hospital'] ?: 'Not assigned');
    $row['specialty_display'] = $row['specialty'] ?: ($row['doctor_specialty'] ?: 'General');
    $appointmentRows[] = $row;
}

$hospitalRows = [];
$hospitalResult = mysqli_query($conn, "SELECT * FROM hospitals ORDER BY name ASC");
while ($hospitalResult && ($row = mysqli_fetch_assoc($hospitalResult))) {
    $row['doctor_count'] = 0;
    $row['patient_count'] = 0;
    $row['appointment_count'] = 0;
    $row['ui_status'] = ((int) ($row['is_active'] ?? 1) === 1) ? 'Active' : 'Inactive';
    $hospitalRows[$row['name']] = $row;
}

foreach ($doctorRows as $doctor) {
    $hospitalName = $doctor['hospital'] ?: 'Unknown';
    if (isset($hospitalRows[$hospitalName])) {
        $hospitalRows[$hospitalName]['doctor_count']++;
    }
}

$hospitalPatients = [];
foreach ($appointmentRows as $appointment) {
    $hospitalName = $appointment['hospital_display'];
    if (!isset($hospitalRows[$hospitalName])) {
        continue;
    }

    $hospitalRows[$hospitalName]['appointment_count']++;
    $patientKey = $hospitalName . '|' . ($appointment['user_id'] ?: $appointment['patient_display']);
    if (!isset($hospitalPatients[$patientKey])) {
        $hospitalPatients[$patientKey] = true;
        $hospitalRows[$hospitalName]['patient_count']++;
    }
}

$hospitalRows = array_values($hospitalRows);

$doctorTotal = count($doctorRows);
$activeDoctors = count(array_filter($doctorRows, fn($row) => $row['ui_status'] === 'Active'));
$patientTotal = count($patientRows);
$appointmentTotal = count($appointmentRows);
$hospitalTotal = count($hospitalRows);
$activeHospitals = count(array_filter($hospitalRows, fn($row) => $row['ui_status'] === 'Active'));
$pendingAppointments = count(array_filter($appointmentRows, fn($row) => $row['status'] === 'Pending'));
$todayAppointments = count(array_filter($appointmentRows, fn($row) => $row['appointment_date'] === date('Y-m-d')));

$billingRows = [];
$billingResult = mysqli_query($conn, "SELECT * FROM billing ORDER BY billing_date DESC, id DESC");
while ($billingResult && ($row = mysqli_fetch_assoc($billingResult))) {
    $billingRows[] = $row;
}
$unpaidBills = count(array_filter($billingRows, fn($row) => $row['status'] === 'Unpaid'));

$activity = [];
foreach ($appointmentRows as $row) {
    $activity[] = [
        'time' => strtotime($row['created_at']),
        'date_label' => date('d M Y · h:i A', strtotime($row['created_at'])),
        'user' => $row['patient_display'],
        'role' => 'Patient',
        'action' => 'Booked appointment with ' . $row['doctor_display'],
        'status' => $row['status'],
    ];
}
foreach ($doctorRows as $row) {
    $activity[] = [
        'time' => strtotime($row['created_at']),
        'date_label' => date('d M Y · h:i A', strtotime($row['created_at'])),
        'user' => $row['full_name'],
        'role' => 'Doctor',
        'action' => 'Doctor account registered',
        'status' => $row['ui_status'],
    ];
}
foreach ($patientRows as $row) {
    $activity[] = [
        'time' => strtotime($row['created_at']),
        'date_label' => date('d M Y · h:i A', strtotime($row['created_at'])),
        'user' => $row['full_name'],
        'role' => 'Patient',
        'action' => 'Patient account registered',
        'status' => 'Active',
    ];
}
usort($activity, fn($a, $b) => $b['time'] <=> $a['time']);
$activity = array_slice($activity, 0, 12);

$alerts = [];
if ($pendingAppointments > 0) {
    $alerts[] = ['type' => 'warning', 'title' => 'Pending Appointments', 'desc' => $pendingAppointments . ' appointments are waiting for doctor confirmation.', 'action' => 'Review'];
}
if ($unpaidBills > 0) {
    $alerts[] = ['type' => 'critical', 'title' => 'Unpaid Bills', 'desc' => $unpaidBills . ' billing records are still marked as unpaid.', 'action' => 'Inspect'];
}
$inactiveDoctors = $doctorTotal - $activeDoctors;
if ($inactiveDoctors > 0) {
    $alerts[] = ['type' => 'info', 'title' => 'Doctor Accounts Disabled', 'desc' => $inactiveDoctors . ' doctor accounts are currently inactive.', 'action' => 'Manage'];
}
$inactiveHospitals = $hospitalTotal - $activeHospitals;
if ($inactiveHospitals > 0) {
    $alerts[] = ['type' => 'warning', 'title' => 'Inactive Hospitals', 'desc' => $inactiveHospitals . ' hospital records are currently inactive.', 'action' => 'Review'];
}
if (empty($alerts)) {
    $alerts[] = ['type' => 'info', 'title' => 'System Healthy', 'desc' => 'No urgent admin alerts right now.', 'action' => 'OK'];
}

$specLabels = [];
$specCounts = [];
foreach ($doctorRows as $row) {
    $key = $row['specialty'] ?: 'General';
    $specCounts[$key] = ($specCounts[$key] ?? 0) + 1;
}
$specLabels = array_keys($specCounts);
$specValues = array_values($specCounts);

$monthLabels = [];
$monthCounts = [];
for ($i = 5; $i >= 0; $i--) {
    $label = date('M Y', strtotime("-{$i} months"));
    $monthLabels[] = $label;
    $monthCounts[$label] = 0;
}
foreach ($appointmentRows as $row) {
    $label = date('M Y', strtotime($row['appointment_date']));
    if (isset($monthCounts[$label])) {
        $monthCounts[$label]++;
    }
}

$weeklyLabels = [];
$weeklyCounts = [];
for ($i = 6; $i >= 0; $i--) {
    $label = date('D', strtotime("-{$i} days"));
    $dateKey = date('Y-m-d', strtotime("-{$i} days"));
    $weeklyLabels[] = $label;
    $weeklyCounts[$dateKey] = 0;
}
foreach ($appointmentRows as $row) {
    if (isset($weeklyCounts[$row['appointment_date']])) {
        $weeklyCounts[$row['appointment_date']]++;
    }
}

$peakUsage = array_fill(0, 24, 0);
foreach ($appointmentRows as $row) {
    if (!empty($row['appointment_time'])) {
        $time = DateTime::createFromFormat('h:i A', $row['appointment_time']);
        if ($time) {
            $peakUsage[(int) $time->format('G')]++;
        }
    }
}

$searchData = [];
foreach ($doctorRows as $row) {
    $searchData[] = ['label' => $row['full_name'], 'icon' => 'fa-user-doctor', 'section' => 'doctors'];
}
foreach ($patientRows as $row) {
    $searchData[] = ['label' => $row['full_name'], 'icon' => 'fa-user', 'section' => 'patients'];
}
foreach ($appointmentRows as $row) {
    $searchData[] = ['label' => 'APT' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT) . ' - ' . $row['patient_display'], 'icon' => 'fa-calendar', 'section' => 'appointments'];
}
foreach ($hospitalRows as $row) {
    $searchData[] = ['label' => $row['name'], 'icon' => 'fa-hospital', 'section' => 'hospitals'];
}

$jsPayload = [
    'doctors' => array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['full_name'],
            'initials' => $row['initials'],
            'spec' => $row['specialty'] ?: 'General',
            'hosp' => $row['hospital'] ?: 'Not assigned',
            'patients' => (int) $row['patient_count'],
            'status' => $row['ui_status'],
            'email' => $row['email'],
            'exp' => $row['experience'] ?: 'N/A',
            'phone' => $row['phone'] ?: 'N/A',
            'schedule' => $row['available_time'] ?: 'Not set',
        ];
    }, $doctorRows),
    'patients' => array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['full_name'],
            'initials' => $row['initials'],
            'age' => $row['self_age'] ? (int) $row['self_age'] : null,
            'email' => $row['email'],
            'phone' => $row['phone'],
            'lastVisit' => $row['last_visit'] ? date('d M Y', strtotime($row['last_visit'])) : 'No visits yet',
            'appts' => (int) $row['appointment_count'],
        ];
    }, $patientRows),
    'appointments' => array_map(function ($row) {
        return [
            'id' => 'APT' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT),
            'db_id' => (int) $row['id'],
            'patient' => $row['patient_display'],
            'doctor' => $row['doctor_display'],
            'hosp' => $row['hospital_display'],
            'date' => date('d M Y', strtotime($row['appointment_date'])),
            'time' => $row['appointment_time'],
            'status' => $row['status'],
            'dept' => $row['specialty_display'],
            'notes' => $row['notes'] ?: ($row['reason_for_visit'] ?: 'No notes available'),
        ];
    }, $appointmentRows),
    'hospitals' => array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'name' => $row['name'],
            'city' => $row['location'],
            'doctors' => (int) $row['doctor_count'],
            'patients' => (int) $row['patient_count'],
            'appts' => (int) $row['appointment_count'],
            'status' => $row['ui_status'],
        ];
    }, $hospitalRows),
    'searchData' => $searchData,
    'charts' => [
        'months' => $monthLabels,
        'monthCounts' => array_values($monthCounts),
        'specialties' => $specLabels,
        'specialtyCounts' => $specValues,
        'weeklyLabels' => $weeklyLabels,
        'weeklyCounts' => array_values($weeklyCounts),
        'peakLabels' => array_map(fn($hour) => sprintf('%02d:00', $hour), range(0, 23)),
        'peakCounts' => $peakUsage,
    ],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Salvex Admin Dashboard</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
</head>
<body class="dashboard-body" id="dashBody">
<div class="dash-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle-btn" id="sidebarToggle" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div class="sidebar-admin-info">
            <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="admin-details">
                <span class="admin-name"><?php echo esc($adminName); ?></span>
                <span class="admin-role"><i class="fas fa-circle" style="color:#10B981;font-size:8px"></i> Online</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="#overview" class="nav-item active" onclick="switchSection('overview', this)">
                <i class="fas fa-gauge-high"></i><span>Dashboard</span>
                <span class="nav-badge">Live</span>
            </a>
            <a href="#doctors" class="nav-item" onclick="switchSection('doctors', this)">
                <i class="fas fa-user-doctor"></i><span>Doctors</span>
                <span class="nav-count"><?php echo $doctorTotal; ?></span>
            </a>
            <a href="#patients" class="nav-item" onclick="switchSection('patients', this)">
                <i class="fas fa-users"></i><span>Patients</span>
                <span class="nav-count"><?php echo $patientTotal; ?></span>
            </a>
            <a href="#appointments" class="nav-item" onclick="switchSection('appointments', this)">
                <i class="fas fa-calendar-check"></i><span>Appointments</span>
                <span class="nav-count"><?php echo $appointmentTotal; ?></span>
            </a>
            <div class="nav-section-label">Management</div>
            <a href="#hospitals" class="nav-item" onclick="switchSection('hospitals', this)">
                <i class="fas fa-hospital"></i><span>Hospitals</span>
                <span class="nav-count"><?php echo $hospitalTotal; ?></span>
            </a>
            <a href="#analytics" class="nav-item" onclick="switchSection('analytics', this)">
                <i class="fas fa-chart-line"></i><span>Analytics</span>
            </a>
            <div class="nav-section-label">Reports</div>
            <a href="reports.php" class="nav-item">
                <i class="fas fa-file-chart-column"></i><span>Reports</span>
            </a>
            <a href="medicine_report.php" class="nav-item">
                <i class="fas fa-pills"></i><span>Medicine Reports</span>
            </a>
            <a href="#alerts" class="nav-item" onclick="switchSection('alerts', this)">
                <i class="fas fa-bell"></i><span>Alerts</span>
                <span class="nav-badge nav-badge-red"><?php echo count($alerts); ?></span>
            </a>
            <div class="nav-section-label">System</div>
            <a href="#activity" class="nav-item" onclick="switchSection('activity', this)">
                <i class="fas fa-clock-rotate-left"></i><span>Activity Log</span>
            </a>
            <a href="#settings" class="nav-item" onclick="switchSection('settings', this)">
                <i class="fas fa-gear"></i><span>Settings</span>
            </a>
            <a href="logout.php" class="nav-item nav-logout">
                <i class="fas fa-right-from-bracket"></i><span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="dash-main" id="dashMain">
        <header class="topbar">
            <div class="topbar-left">
                <button class="mobile-menu-btn" onclick="toggleMobileSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="topbar-search">
                    <i class="fas fa-magnifying-glass"></i>
                    <input type="text" placeholder="Search doctors, patients, appointments..." id="globalSearch" onkeyup="handleSearch(this.value)">
                    <div class="search-dropdown" id="searchDropdown"></div>
                </div>
            </div>
            <div class="topbar-right">
                <div class="topbar-status">
                    <i class="fas fa-circle" style="color:#10B981;font-size:8px"></i>
                    <span>System Healthy</span>
                </div>
                <button class="topbar-btn" onclick="toggleDarkMode()" id="darkToggle" title="Toggle Dark Mode">
                    <i class="fas fa-moon"></i>
                </button>
                <button class="topbar-btn notif-btn" onclick="switchSection('alerts', document.querySelector('[href=\'#alerts\']'))">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge"><?php echo count($alerts); ?></span>
                </button>
                <div class="topbar-profile" onclick="toggleProfileMenu()">
                    <div class="profile-avatar"><i class="fas fa-user-shield"></i></div>
                    <div class="profile-info">
                        <span class="profile-name"><?php echo esc($adminName); ?></span>
                        <span class="profile-email"><?php echo esc($adminEmail); ?></span>
                    </div>
                    <i class="fas fa-chevron-down"></i>
                    <div class="profile-menu" id="profileMenu">
                        <a href="javascript:void(0)" onclick="switchSection('settings');toggleProfileMenu();return false;"><i class="fas fa-gear"></i> Settings</a>
                        <div class="profile-menu-divider"></div>
                        <a href="logout.php" class="logout-link"><i class="fas fa-right-from-bracket"></i> Logout</a>
                    </div>
                </div>
            </div>
        </header>

        <div class="dash-content">
            <section id="section-overview" class="dash-section active">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Dashboard Overview</h1>
                        <p class="dash-section-sub">Real-time view of the live Salvex database.</p>
                    </div>
                    <div class="section-title-actions">
                        <span class="date-badge"><i class="fas fa-calendar"></i> <span id="currentDate"></span></span>
                        <button class="btn-refresh" onclick="refreshData()"><i class="fas fa-rotate-right"></i> Refresh</button>
                    </div>
                </div>

                <div class="kpi-grid">
                    <div class="kpi-card kpi-blue" onclick="switchSection('doctors', document.querySelector('[href=\'#doctors\']'))">
                        <div class="kpi-left">
                            <span class="kpi-label">Total Doctors</span>
                            <span class="kpi-value"><?php echo number_format($doctorTotal); ?></span>
                            <span class="kpi-change positive"><i class="fas fa-arrow-up"></i> <?php echo $activeDoctors; ?> active</span>
                        </div>
                        <div class="kpi-icon"><i class="fas fa-user-doctor"></i></div>
                        <div class="kpi-progress"><div class="kpi-bar" style="width:<?php echo $doctorTotal > 0 ? round(($activeDoctors / $doctorTotal) * 100) : 0; ?>%"></div></div>
                    </div>
                    <div class="kpi-card kpi-violet" onclick="switchSection('patients', document.querySelector('[href=\'#patients\']'))">
                        <div class="kpi-left">
                            <span class="kpi-label">Total Patients</span>
                            <span class="kpi-value"><?php echo number_format($patientTotal); ?></span>
                            <span class="kpi-change positive"><i class="fas fa-arrow-up"></i> Registered users</span>
                        </div>
                        <div class="kpi-icon"><i class="fas fa-users"></i></div>
                        <div class="kpi-progress"><div class="kpi-bar" style="width:90%"></div></div>
                    </div>
                    <div class="kpi-card kpi-green" onclick="switchSection('appointments', document.querySelector('[href=\'#appointments\']'))">
                        <div class="kpi-left">
                            <span class="kpi-label">Total Appointments</span>
                            <span class="kpi-value"><?php echo number_format($appointmentTotal); ?></span>
                            <span class="kpi-change positive"><i class="fas fa-arrow-up"></i> <?php echo $todayAppointments; ?> today</span>
                        </div>
                        <div class="kpi-icon"><i class="fas fa-calendar-check"></i></div>
                        <div class="kpi-progress"><div class="kpi-bar" style="width:75%"></div></div>
                    </div>
                    <div class="kpi-card kpi-orange" onclick="switchSection('hospitals', document.querySelector('[href=\'#hospitals\']'))">
                        <div class="kpi-left">
                            <span class="kpi-label">Active Hospitals</span>
                            <span class="kpi-value"><?php echo number_format($activeHospitals); ?></span>
                            <span class="kpi-change positive"><i class="fas fa-arrow-up"></i> of <?php echo $hospitalTotal; ?></span>
                        </div>
                        <div class="kpi-icon"><i class="fas fa-hospital"></i></div>
                        <div class="kpi-progress"><div class="kpi-bar" style="width:<?php echo $hospitalTotal > 0 ? round(($activeHospitals / $hospitalTotal) * 100) : 0; ?>%"></div></div>
                    </div>
                </div>

                <div class="charts-row">
                    <div class="chart-card chart-large">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Appointment Trends</h3>
                                <p class="chart-sub">Last 6 months from the live appointment table</p>
                            </div>
                        </div>
                        <canvas id="appointmentChart" height="100"></canvas>
                    </div>
                    <div class="chart-card chart-small">
                        <div class="chart-header">
                            <div>
                                <h3 class="chart-title">Doctor Specializations</h3>
                                <p class="chart-sub">Distribution by specialty</p>
                            </div>
                        </div>
                        <canvas id="specializationChart" height="180"></canvas>
                        <div class="chart-legend" id="specLegend"></div>
                    </div>
                </div>

                <div class="bottom-row">
                    <div class="system-status-card">
                        <h3 class="card-section-title"><i class="fas fa-server"></i> System Status</h3>
                        <div class="status-items">
                            <div class="status-item"><span class="status-label">Web Server</span><span class="status-val status-ok"><i class="fas fa-circle"></i> Operational</span></div>
                            <div class="status-item"><span class="status-label">Database</span><span class="status-val status-ok"><i class="fas fa-circle"></i> Connected</span></div>
                            <div class="status-item"><span class="status-label">Doctors Active</span><span class="status-val status-ok"><?php echo $activeDoctors; ?> / <?php echo $doctorTotal; ?></span></div>
                            <div class="status-item"><span class="status-label">Pending Appointments</span><span class="status-val <?php echo $pendingAppointments > 0 ? 'status-warn' : 'status-ok'; ?>"><?php echo $pendingAppointments; ?></span></div>
                            <div class="status-item"><span class="status-label">Unpaid Bills</span><span class="status-val <?php echo $unpaidBills > 0 ? 'status-warn' : 'status-ok'; ?>"><?php echo $unpaidBills; ?></span></div>
                            <div class="status-item"><span class="status-label">Live Records</span><span class="status-val status-ok"><?php echo $appointmentTotal + $patientTotal + $doctorTotal; ?> tracked</span></div>
                        </div>
                    </div>
                    <div class="activity-card">
                        <h3 class="card-section-title"><i class="fas fa-clock-rotate-left"></i> Recent Activity</h3>
                        <div class="activity-list" id="activityList">
                            <?php foreach (array_slice($activity, 0, 6) as $item): ?>
                            <div class="activity-item act-blue">
                                <div class="act-dot"></div>
                                <div class="act-info">
                                    <span class="act-text"><?php echo esc($item['action']); ?> by <strong><?php echo esc($item['user']); ?></strong></span>
                                    <span class="act-time"><?php echo esc($item['date_label']); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>

            <section id="section-doctors" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Doctor Management</h1>
                        <p class="dash-section-sub">Live doctor accounts from the database</p>
                    </div>
                    <div class="section-title-actions">
                        <input type="text" placeholder="Search doctors..." class="section-search" oninput="filterTable('doctorTable', this.value)">
                        <select class="section-filter" onchange="filterByStatus('doctorTable', this.value, 5)">
                            <option value="">All Status</option>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="table-card">
                    <table class="data-table" id="doctorTable">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Doctor</th>
                                <th>Specialization</th>
                                <th>Hospital</th>
                                <th>Patients</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctorRows as $index => $row): ?>
                            <tr data-doctor-id="<?php echo (int) $row['id']; ?>">
                                <td><?php echo str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-avatar"><?php echo esc($row['initials']); ?></div>
                                        <div>
                                            <span class="table-name"><?php echo esc($row['full_name']); ?></span>
                                            <span class="table-email"><?php echo esc($row['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="spec-tag spec-cardio"><?php echo esc($row['specialty'] ?: 'General'); ?></span></td>
                                <td><?php echo esc($row['hospital'] ?: 'Not assigned'); ?></td>
                                <td><?php echo (int) $row['patient_count']; ?></td>
                                <td><span class="status-pill <?php echo $row['ui_status'] === 'Active' ? 'pill-active' : 'pill-inactive'; ?>"><?php echo esc($row['ui_status']); ?></span></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-view" onclick="viewDoctor(<?php echo (int) $row['id']; ?>)"><i class="fas fa-eye"></i> View</button>
                                        <button class="<?php echo $row['ui_status'] === 'Active' ? 'btn-disable' : 'btn-enable'; ?>" onclick="toggleDoctor(this, <?php echo (int) $row['id']; ?>, '<?php echo esc($row['full_name']); ?>')">
                                            <i class="fas <?php echo $row['ui_status'] === 'Active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                            <?php echo $row['ui_status'] === 'Active' ? 'Disable' : 'Enable'; ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-patients" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Patient Management</h1>
                        <p class="dash-section-sub">Registered patients and their appointment activity</p>
                    </div>
                    <div class="section-title-actions">
                        <input type="text" placeholder="Search patients..." class="section-search" oninput="filterTable('patientTable', this.value)">
                    </div>
                </div>
                <div class="table-card">
                    <table class="data-table" id="patientTable">
                        <thead>
                            <tr><th>#</th><th>Patient</th><th>Age</th><th>Email</th><th>Last Visit</th><th>Appointments</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patientRows as $index => $row): ?>
                            <tr>
                                <td><?php echo str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT); ?></td>
                                <td>
                                    <div class="table-user">
                                        <div class="table-avatar"><?php echo esc($row['initials']); ?></div>
                                        <div>
                                            <span class="table-name"><?php echo esc($row['full_name']); ?></span>
                                            <span class="table-email"><?php echo esc($row['phone']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $row['self_age'] ? (int) $row['self_age'] : '—'; ?></td>
                                <td><?php echo esc($row['email']); ?></td>
                                <td><?php echo $row['last_visit'] ? esc(date('d M Y', strtotime($row['last_visit']))) : 'No visits yet'; ?></td>
                                <td><?php echo (int) $row['appointment_count']; ?></td>
                                <td><button class="btn-view" onclick="viewPatient(<?php echo (int) $row['id']; ?>)"><i class="fas fa-eye"></i> View</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-appointments" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Appointment Management</h1>
                        <p class="dash-section-sub">All appointments across the platform</p>
                    </div>
                    <div class="section-title-actions">
                        <input type="text" placeholder="Search appointments..." class="section-search" oninput="filterTable('apptTable', this.value)">
                        <select class="section-filter" onchange="filterByStatus('apptTable', this.value, 5)">
                            <option value="">All Status</option>
                            <option value="Confirmed">Confirmed</option>
                            <option value="Pending">Pending</option>
                            <option value="Cancelled">Cancelled</option>
                            <option value="Completed">Completed</option>
                        </select>
                        <input type="date" class="section-filter" onchange="filterByDate(this.value)" title="Filter by date">
                    </div>
                </div>
                <div class="table-card">
                    <table class="data-table" id="apptTable">
                        <thead>
                            <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Hospital</th><th>Date & Time</th><th>Status</th><th>Action</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointmentRows as $row): ?>
                            <tr>
                                <td><?php echo 'APT' . str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                <td><?php echo esc($row['patient_display']); ?></td>
                                <td><?php echo esc($row['doctor_display']); ?></td>
                                <td><?php echo esc($row['hospital_display']); ?></td>
                                <td><?php echo esc(date('d M Y', strtotime($row['appointment_date'])) . ' · ' . $row['appointment_time']); ?></td>
                                <td><span class="status-pill pill-<?php echo strtolower($row['status']); ?>"><?php echo esc($row['status']); ?></span></td>
                                <td><button class="btn-view" onclick="viewAppt('APT<?php echo str_pad((string) $row['id'], 4, '0', STR_PAD_LEFT); ?>')"><i class="fas fa-eye"></i> View</button></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-hospitals" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Hospital Management</h1>
                        <p class="dash-section-sub">Live hospital records and related counts</p>
                    </div>
                </div>
                <div class="hospital-grid">
                    <?php foreach ($hospitalRows as $row): ?>
                    <div class="hospital-card" data-hospital-id="<?php echo (int) $row['id']; ?>">
                        <div class="hosp-header">
                            <div class="hosp-icon"><i class="fas fa-hospital"></i></div>
                            <span class="status-pill <?php echo $row['ui_status'] === 'Active' ? 'pill-active' : 'pill-inactive'; ?>"><?php echo esc($row['ui_status']); ?></span>
                        </div>
                        <h3 class="hosp-name"><?php echo esc($row['name']); ?></h3>
                        <p class="hosp-city"><i class="fas fa-location-dot"></i> <?php echo esc($row['location']); ?></p>
                        <div class="hosp-stats">
                            <div><span class="hs-num"><?php echo (int) $row['doctor_count']; ?></span><span class="hs-lbl">Doctors</span></div>
                            <div><span class="hs-num"><?php echo (int) $row['patient_count']; ?></span><span class="hs-lbl">Patients</span></div>
                            <div><span class="hs-num"><?php echo (int) $row['appointment_count']; ?></span><span class="hs-lbl">Appts</span></div>
                        </div>
                        <div class="hosp-actions">
                            <button class="btn-view" onclick="viewHospital(<?php echo (int) $row['id']; ?>)"><i class="fas fa-eye"></i> View</button>
                            <button class="<?php echo $row['ui_status'] === 'Active' ? 'btn-disable' : 'btn-enable'; ?>" onclick="toggleHospital(this, <?php echo (int) $row['id']; ?>, '<?php echo esc($row['name']); ?>')">
                                <i class="fas <?php echo $row['ui_status'] === 'Active' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                <?php echo $row['ui_status'] === 'Active' ? 'Disable' : 'Enable'; ?>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="section-analytics" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Analytics & Reports</h1>
                        <p class="dash-section-sub">Database-driven charts and usage patterns</p>
                    </div>
                </div>
                <div class="analytics-grid">
                    <div class="analytics-card">
                        <div class="chart-header"><h3 class="chart-title">Weekly Appointment Volume</h3></div>
                        <canvas id="weeklyChart" height="120"></canvas>
                    </div>
                    <div class="analytics-card">
                        <div class="chart-header"><h3 class="chart-title">New Registrations (6 Months)</h3></div>
                        <canvas id="regChart" height="120"></canvas>
                    </div>
                    <div class="analytics-card analytics-full">
                        <div class="chart-header"><h3 class="chart-title">Peak Usage Hours</h3><p class="chart-sub">Appointments grouped by scheduled hour</p></div>
                        <canvas id="peakChart" height="80"></canvas>
                    </div>
                </div>
                <div class="metrics-row">
                    <div class="metric-card"><div class="metric-icon" style="background:#0080FF20;color:#0080FF"><i class="fas fa-percent"></i></div><div class="metric-info"><span class="metric-val"><?php echo $appointmentTotal > 0 ? round((($appointmentTotal - $pendingAppointments - count(array_filter($appointmentRows, fn($row) => $row['status'] === 'Cancelled'))) / $appointmentTotal) * 100, 1) : 0; ?>%</span><span class="metric-label">Completion / confirmation rate</span></div></div>
                    <div class="metric-card"><div class="metric-icon" style="background:#818CF820;color:#818CF8"><i class="fas fa-file-invoice-dollar"></i></div><div class="metric-info"><span class="metric-val"><?php echo number_format(count($billingRows)); ?></span><span class="metric-label">Billing records</span></div></div>
                    <div class="metric-card"><div class="metric-icon" style="background:#10B98120;color:#10B981"><i class="fas fa-user-plus"></i></div><div class="metric-info"><span class="metric-val"><?php echo number_format($patientTotal); ?></span><span class="metric-label">Registered patients</span></div></div>
                    <div class="metric-card"><div class="metric-icon" style="background:#F59E0B20;color:#F59E0B"><i class="fas fa-clock"></i></div><div class="metric-info"><span class="metric-val"><?php echo $todayAppointments; ?></span><span class="metric-label">Appointments today</span></div></div>
                </div>
            </section>

            <section id="section-alerts" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Alerts & Notifications</h1>
                        <p class="dash-section-sub">Derived from pending work and record status</p>
                    </div>
                    <div class="section-title-actions">
                        <button class="btn-secondary" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
                    </div>
                </div>
                <div class="alerts-list" id="alertsList">
                    <?php foreach ($alerts as $alert): ?>
                    <div class="alert-item alert-<?php echo $alert['type']; ?>">
                        <div class="alert-icon"><i class="fas fa-circle-exclamation"></i></div>
                        <div class="alert-info">
                            <span class="alert-title"><?php echo esc($alert['title']); ?></span>
                            <span class="alert-desc"><?php echo esc($alert['desc']); ?></span>
                            <span class="alert-time">Live status</span>
                        </div>
                        <button class="btn-view alert-btn" onclick="resolveAlert(this)"><?php echo esc($alert['action']); ?></button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>

            <section id="section-activity" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">Activity Log</h1>
                        <p class="dash-section-sub">Recent database activity across users, doctors, and appointments</p>
                    </div>
                </div>
                <div class="table-card">
                    <table class="data-table">
                        <thead><tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($activity as $item): ?>
                            <tr>
                                <td><?php echo esc($item['date_label']); ?></td>
                                <td><?php echo esc($item['user']); ?></td>
                                <td><span class="spec-tag spec-cardio"><?php echo esc($item['role']); ?></span></td>
                                <td><?php echo esc($item['action']); ?></td>
                                <td><span class="status-pill pill-<?php echo strtolower($item['status']) === 'inactive' ? 'cancelled' : (strtolower($item['status']) === 'active' ? 'completed' : strtolower($item['status'])); ?>"><?php echo esc($item['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </section>

            <section id="section-settings" class="dash-section">
                <div class="section-title-bar">
                    <div>
                        <h1 class="dash-section-title">System Settings</h1>
                        <p class="dash-section-sub">Admin profile and interface controls</p>
                    </div>
                </div>
                <div class="settings-grid">
                    <div class="settings-card">
                        <h3 class="settings-card-title"><i class="fas fa-palette"></i> Appearance</h3>
                        <div class="setting-item"><span>Dark Mode</span><label class="toggle-switch"><input type="checkbox" id="darkModeToggle" onchange="toggleDarkMode()"><span class="toggle-slider"></span></label></div>
                        <div class="setting-item"><span>Compact Sidebar</span><label class="toggle-switch"><input type="checkbox" onchange="toggleCompactSidebar()"><span class="toggle-slider"></span></label></div>
                        <div class="setting-item"><span>Live data mode</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                    </div>
                    <div class="settings-card">
                        <h3 class="settings-card-title"><i class="fas fa-bell"></i> Notifications</h3>
                        <div class="setting-item"><span>Pending Appointment Alerts</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                        <div class="setting-item"><span>Billing Alerts</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                        <div class="setting-item"><span>Doctor Account Alerts</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                    </div>
                    <div class="settings-card">
                        <h3 class="settings-card-title"><i class="fas fa-shield-halved"></i> Security</h3>
                        <div class="setting-item"><span>Admin Session</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                        <div class="setting-item"><span>Secure Login</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                        <div class="setting-item"><span>Database Guard</span><label class="toggle-switch"><input type="checkbox" checked disabled><span class="toggle-slider"></span></label></div>
                    </div>
                    <div class="settings-card">
                        <h3 class="settings-card-title"><i class="fas fa-user-shield"></i> Admin Profile</h3>
                        <div class="form-group"><label class="form-label">Display Name</label><input type="text" class="form-input" value="<?php echo esc($adminName); ?>" id="adminName"></div>
                        <div class="form-group"><label class="form-label">Email</label><input type="email" class="form-input" value="<?php echo esc($adminEmail); ?>" id="adminEmail"></div>
                        <button class="btn-primary-hero" style="margin-top:12px;padding:10px 20px;font-size:14px" onclick="saveAdminProfile()"><i class="fas fa-save"></i> Save Changes</button>
                    </div>
                </div>
            </section>
        </div>
    </main>
</div>

<div class="modal-overlay" id="infoModal" onclick="closeModal(event)">
    <div class="modal-box modal-box-lg" id="modalContent"></div>
</div>

<div class="toast-container" id="toastContainer"></div>

<script>
window.ADMIN_DATA = <?php echo json_encode($jsPayload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
</script>
<script src="assets/js/dashboard.js?v=3"></script>
</body>
</html>




