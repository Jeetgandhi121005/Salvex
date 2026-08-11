<?php
session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit();
}

salvex_sync_billing_status($conn);

function esc(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$from = $_GET['from'] ?? date('Y-m-01');
$to = $_GET['to'] ?? date('Y-m-d');
$hospitalFilter = trim((string) ($_GET['hospital'] ?? ''));
$doctorFilter = (int) ($_GET['doctor_id'] ?? 0);

$safeFrom = mysqli_real_escape_string($conn, $from);
$safeTo = mysqli_real_escape_string($conn, $to);
$safeHospital = mysqli_real_escape_string($conn, $hospitalFilter);

$filters = ["a.appointment_date BETWEEN '{$safeFrom}' AND '{$safeTo}'"];
$billingFilters = ["a.appointment_date BETWEEN '{$safeFrom}' AND '{$safeTo}'"];

if ($hospitalFilter !== '') {
    $filters[] = "COALESCE(a.hospital_name, d.hospital) = '{$safeHospital}'";
    $billingFilters[] = "COALESCE(a.hospital_name, d.hospital) = '{$safeHospital}'";
}

if ($doctorFilter > 0) {
    $filters[] = "a.doctor_id = {$doctorFilter}";
    $billingFilters[] = "a.doctor_id = {$doctorFilter}";
}

$whereClause = implode(' AND ', $filters);
$billingWhereClause = implode(' AND ', $billingFilters);

$doctorOptions = [];
$doctorOptionsResult = mysqli_query($conn, "SELECT id, full_name FROM doctors ORDER BY full_name ASC");
while ($doctorOptionsResult && ($row = mysqli_fetch_assoc($doctorOptionsResult))) {
    $doctorOptions[] = $row;
}

$hospitalOptions = [];
$hospitalOptionsResult = mysqli_query($conn, "SELECT name FROM hospitals ORDER BY name ASC");
while ($hospitalOptionsResult && ($row = mysqli_fetch_assoc($hospitalOptionsResult))) {
    $hospitalOptions[] = $row['name'];
}

$appointmentStatusSummarySql = "
    SELECT a.status, COUNT(*) AS total_count
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE {$whereClause}
    GROUP BY a.status
";
$appointmentStatusSummaryResult = mysqli_query($conn, $appointmentStatusSummarySql);
$statusSummary = ['Pending' => 0, 'Confirmed' => 0, 'Completed' => 0, 'Cancelled' => 0];
while ($appointmentStatusSummaryResult && ($row = mysqli_fetch_assoc($appointmentStatusSummaryResult))) {
    $statusSummary[$row['status']] = (int) $row['total_count'];
}

$revenueSummarySql = "
    SELECT
        COUNT(b.id) AS invoice_count,
        COALESCE(SUM(b.amount), 0) AS gross_revenue,
        COALESCE(SUM(CASE WHEN b.status = 'Paid' THEN b.amount ELSE 0 END), 0) AS paid_revenue,
        COALESCE(SUM(CASE WHEN b.status = 'Unpaid' THEN b.amount ELSE 0 END), 0) AS unpaid_revenue
    FROM billing b
    INNER JOIN appointments a ON a.id = b.appointment_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE {$billingWhereClause}
";
$revenueSummary = mysqli_fetch_assoc(mysqli_query($conn, $revenueSummarySql)) ?: [];

$doctorsRegisteredLastMonthSql = "
    SELECT COUNT(*) AS total_count
    FROM doctors
    WHERE created_at >= DATE_FORMAT(DATE_SUB(CURDATE(), INTERVAL 1 MONTH), '%Y-%m-01')
      AND created_at < DATE_FORMAT(CURDATE(), '%Y-%m-01')
";
$doctorsRegisteredLastMonth = (int) ((mysqli_fetch_assoc(mysqli_query($conn, $doctorsRegisteredLastMonthSql))['total_count'] ?? 0));

$hospitalPerformanceSql = "
    SELECT
        COALESCE(a.hospital_name, d.hospital) AS hospital_name,
        COUNT(*) AS total_appointments,
        COUNT(DISTINCT a.user_id) AS unique_patients
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE {$whereClause}
    GROUP BY COALESCE(a.hospital_name, d.hospital)
    ORDER BY total_appointments DESC, hospital_name ASC
";
$hospitalPerformanceResult = mysqli_query($conn, $hospitalPerformanceSql);
$hospitalPerformance = [];
while ($hospitalPerformanceResult && ($row = mysqli_fetch_assoc($hospitalPerformanceResult))) {
    $hospitalPerformance[] = $row;
}
$topHospital = $hospitalPerformance[0] ?? null;

$doctorCountPerHospitalSql = "
    SELECT hospital, COUNT(*) AS doctor_count
    FROM doctors
    WHERE hospital IS NOT NULL AND hospital <> ''
    GROUP BY hospital
    ORDER BY doctor_count DESC, hospital ASC
";
$doctorCountPerHospitalResult = mysqli_query($conn, $doctorCountPerHospitalSql);
$doctorCountPerHospital = [];
while ($doctorCountPerHospitalResult && ($row = mysqli_fetch_assoc($doctorCountPerHospitalResult))) {
    $doctorCountPerHospital[] = $row;
}

$bestDoctorSql = "
    SELECT
        d.id,
        d.full_name,
        d.hospital,
        d.specialty,
        COUNT(a.id) AS total_appointments,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS completed_appointments,
        ROUND(
            SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0) * 100,
            1
        ) AS completion_rate
    FROM doctors d
    LEFT JOIN appointments a ON a.doctor_id = d.id
    WHERE {$whereClause}
    GROUP BY d.id
    HAVING total_appointments > 0
    ORDER BY total_appointments DESC, completed_appointments DESC, completion_rate DESC, d.full_name ASC
    LIMIT 1
";
$bestDoctor = mysqli_fetch_assoc(mysqli_query($conn, $bestDoctorSql)) ?: [];

$doctorPerformanceSql = "
    SELECT
        d.id,
        d.full_name,
        d.specialty,
        d.hospital,
        COUNT(a.id) AS total_appointments,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS completed_appointments,
        ROUND(
            SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) / NULLIF(COUNT(a.id), 0) * 100,
            1
        ) AS completion_rate
    FROM doctors d
    LEFT JOIN appointments a ON a.doctor_id = d.id
    WHERE {$whereClause}
    GROUP BY d.id
    HAVING total_appointments > 0
    ORDER BY total_appointments DESC, completed_appointments DESC, d.full_name ASC
";
$doctorPerformanceResult = mysqli_query($conn, $doctorPerformanceSql);
$doctorPerformance = [];
while ($doctorPerformanceResult && ($row = mysqli_fetch_assoc($doctorPerformanceResult))) {
    $doctorPerformance[] = $row;
}

$revenueByMonthSql = "
    SELECT
        DATE_FORMAT(a.appointment_date, '%Y-%m') AS month_key,
        COALESCE(SUM(CASE WHEN b.status = 'Paid' THEN b.amount ELSE 0 END), 0) AS revenue
    FROM billing b
    INNER JOIN appointments a ON a.id = b.appointment_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE {$billingWhereClause}
    GROUP BY DATE_FORMAT(a.appointment_date, '%Y-%m')
    ORDER BY month_key ASC
";
$revenueByMonthResult = mysqli_query($conn, $revenueByMonthSql);
$revenueLabels = [];
$revenueValues = [];
while ($revenueByMonthResult && ($row = mysqli_fetch_assoc($revenueByMonthResult))) {
    $revenueLabels[] = date('M Y', strtotime($row['month_key'] . '-01'));
    $revenueValues[] = (float) $row['revenue'];
}

$appointmentExportSql = "
    SELECT
        a.id,
        a.appointment_date,
        a.appointment_time,
        a.status,
        COALESCE(a.patient_name, u.full_name) AS patient_name,
        COALESCE(a.doctor_name, d.full_name) AS doctor_name,
        COALESCE(a.hospital_name, d.hospital) AS hospital_name,
        COALESCE(c.diagnosis, '') AS diagnosis,
        b.amount,
        b.status AS billing_status
    FROM appointments a
    LEFT JOIN users u ON u.id = a.user_id
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN consultations c ON c.appointment_id = a.id
    LEFT JOIN billing b ON b.appointment_id = a.id
    WHERE {$whereClause}
    ORDER BY a.appointment_date DESC, a.id DESC
";
$appointmentExportResult = mysqli_query($conn, $appointmentExportSql);
$appointmentExportRows = [];
while ($appointmentExportResult && ($row = mysqli_fetch_assoc($appointmentExportResult))) {
    $appointmentExportRows[] = $row;
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="salvex_admin_reports_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Appointment ID', 'Date', 'Time', 'Patient', 'Doctor', 'Hospital', 'Status', 'Diagnosis', 'Amount', 'Billing Status']);
    foreach ($appointmentExportRows as $row) {
        fputcsv($output, [
            $row['id'],
            $row['appointment_date'],
            $row['appointment_time'],
            $row['patient_name'],
            $row['doctor_name'],
            $row['hospital_name'],
            $row['status'],
            $row['diagnosis'],
            $row['amount'],
            $row['billing_status'],
        ]);
    }
    fclose($output);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics | Salvex Admin</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <style>
        .reports-page { padding: 24px; }
        .filter-card, .content-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:20px; margin-bottom:20px; }
        .filter-grid, .summary-grid, .split-grid { display:grid; gap:16px; }
        .filter-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); align-items:end; }
        .summary-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
        .split-grid { grid-template-columns: 1.25fr 1fr; }
        .field-group { display:grid; gap:6px; }
        .field-group label { font-size:12px; font-weight:700; text-transform:uppercase; color:#64748b; }
        .field-group input, .field-group select { width:100%; border:1px solid #cbd5e1; border-radius:10px; padding:11px 12px; font:inherit; }
        .btn-row { display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
        .btn { border:0; border-radius:10px; padding:11px 16px; font:inherit; font-weight:700; cursor:pointer; text-decoration:none; display:inline-flex; gap:8px; align-items:center; }
        .btn-primary { background:#2563eb; color:#fff; }
        .btn-secondary { background:#eef2ff; color:#3730a3; }
        .btn-ghost { background:#f8fafc; color:#0f172a; border:1px solid #dbe2ea; }
        .summary-card { background:#fff; border:1px solid #e2e8f0; border-radius:16px; padding:18px; }
        .summary-label { display:block; font-size:12px; font-weight:700; text-transform:uppercase; color:#64748b; margin-bottom:8px; }
        .summary-value { font-size:28px; font-weight:800; color:#0f172a; }
        .summary-note { margin-top:6px; color:#64748b; font-size:13px; }
        .section-title { margin:0 0 14px; font-size:18px; font-weight:800; color:#0f172a; }
        .table-wrap { overflow:auto; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px 14px; text-align:left; border-bottom:1px solid #eef2f7; }
        th { font-size:12px; text-transform:uppercase; color:#64748b; }
        .pill { display:inline-flex; padding:4px 10px; border-radius:999px; font-size:12px; font-weight:700; }
        .pill-Pending { background:#fff7ed; color:#c2410c; }
        .pill-Confirmed { background:#eff6ff; color:#1d4ed8; }
        .pill-Completed { background:#ecfdf5; color:#047857; }
        .pill-Cancelled { background:#fef2f2; color:#b91c1c; }
        @media print { .sidebar, .topbar, .filter-card, .btn-row { display:none !important; } .reports-page { padding:0; } }
        @media (max-width: 1100px) { .filter-grid, .summary-grid, .split-grid { grid-template-columns:1fr; } }
    </style>
</head>
<body class="dashboard-body">
<div class="dash-layout">
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle-btn" onclick="document.getElementById('sidebar').classList.toggle('sidebar-collapsed')"><i class="fas fa-bars"></i></button>
        </div>
        <div class="sidebar-admin-info">
            <div class="admin-avatar"><i class="fas fa-user-shield"></i></div>
            <div class="admin-details">
                <span class="admin-name"><?php echo esc((string) ($_SESSION['admin_name'] ?? 'Admin')); ?></span>
                <span class="admin-role"><i class="fas fa-circle" style="color:#10B981;font-size:8px"></i> Online</span>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-label">Main</div>
            <a href="dashboard.php" class="nav-item"><i class="fas fa-gauge-high"></i><span>Dashboard</span></a>
            <a href="appointments.php" class="nav-item"><i class="fas fa-calendar-check"></i><span>Appointments</span></a>
            <div class="nav-section-label">Reports</div>
            <a href="reports.php" class="nav-item active"><i class="fas fa-file-chart-column"></i><span>Reports & Analytics</span></a>
            <a href="medicine_report.php" class="nav-item"><i class="fas fa-pills"></i><span>Medicine Expenses</span></a>
            <div class="nav-section-label">System</div>
            <a href="logout.php" class="nav-item nav-logout"><i class="fas fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>
    </aside>

    <main class="dash-main">
        <div class="reports-page">
            <div class="section-title-bar" style="margin-bottom:20px;">
                <div>
                    <h1 class="dash-section-title">Reports & Analytics</h1>
                    <p class="dash-section-sub">Filtered operational insights for doctors, hospitals, appointments, and revenue.</p>
                </div>
                <div class="btn-row">
                    <a class="btn btn-secondary" href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>"><i class="fas fa-download"></i> Export CSV</a>
                    <button class="btn btn-ghost" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
                </div>
            </div>

            <form class="filter-card" method="get">
                <div class="filter-grid">
                    <div class="field-group">
                        <label for="from">From Date</label>
                        <input id="from" type="date" name="from" value="<?php echo esc($from); ?>">
                    </div>
                    <div class="field-group">
                        <label for="to">To Date</label>
                        <input id="to" type="date" name="to" value="<?php echo esc($to); ?>">
                    </div>
                    <div class="field-group">
                        <label for="hospital">Hospital</label>
                        <select id="hospital" name="hospital">
                            <option value="">All hospitals</option>
                            <?php foreach ($hospitalOptions as $hospitalName): ?>
                                <option value="<?php echo esc($hospitalName); ?>" <?php echo $hospitalFilter === $hospitalName ? 'selected' : ''; ?>><?php echo esc($hospitalName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="field-group">
                        <label for="doctor_id">Doctor</label>
                        <select id="doctor_id" name="doctor_id">
                            <option value="0">All doctors</option>
                            <?php foreach ($doctorOptions as $doctor): ?>
                                <option value="<?php echo (int) $doctor['id']; ?>" <?php echo $doctorFilter === (int) $doctor['id'] ? 'selected' : ''; ?>><?php echo esc((string) $doctor['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="btn-row">
                        <button class="btn btn-primary" type="submit"><i class="fas fa-filter"></i> Apply Filters</button>
                    </div>
                </div>
            </form>

            <div class="summary-grid">
                <div class="summary-card">
                    <span class="summary-label">Paid Revenue</span>
                    <div class="summary-value">₹<?php echo number_format((float) ($revenueSummary['paid_revenue'] ?? 0)); ?></div>
                    <div class="summary-note">Paid invoices in filtered window</div>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Unpaid Revenue</span>
                    <div class="summary-value">₹<?php echo number_format((float) ($revenueSummary['unpaid_revenue'] ?? 0)); ?></div>
                    <div class="summary-note">Should trend down after the 4-hour automation</div>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Appointments</span>
                    <div class="summary-value"><?php echo array_sum($statusSummary); ?></div>
                    <div class="summary-note">Pending: <?php echo $statusSummary['Pending']; ?>, Completed: <?php echo $statusSummary['Completed']; ?></div>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Doctors Registered Last Month</span>
                    <div class="summary-value"><?php echo $doctorsRegisteredLastMonth; ?></div>
                    <div class="summary-note">Platform-wide onboarding metric</div>
                </div>
                <div class="summary-card">
                    <span class="summary-label">Best Performing Doctor</span>
                    <div class="summary-value" style="font-size:22px;"><?php echo esc((string) ($bestDoctor['full_name'] ?? 'No result')); ?></div>
                    <div class="summary-note"><?php echo (int) ($bestDoctor['total_appointments'] ?? 0); ?> appointments<?php if (!empty($bestDoctor['completion_rate'])): ?>, <?php echo esc((string) $bestDoctor['completion_rate']); ?>% completion<?php endif; ?></div>
                </div>
            </div>

            <div class="split-grid" style="margin-top:20px;">
                <div class="content-card">
                    <h2 class="section-title">Revenue Trend</h2>
                    <canvas id="revenueChart" height="130"></canvas>
                </div>
                <div class="content-card">
                    <h2 class="section-title">Appointment Status Breakdown</h2>
                    <canvas id="statusChart" height="130"></canvas>
                </div>
            </div>

            <div class="split-grid" style="margin-top:20px;">
                <div class="content-card">
                    <h2 class="section-title">Hospital Performance</h2>
                    <p style="margin-top:0;color:#64748b;">Top hospital: <strong><?php echo esc((string) ($topHospital['hospital_name'] ?? 'No data')); ?></strong></p>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Hospital</th><th>Appointments</th><th>Unique Patients</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($hospitalPerformance as $row): ?>
                                    <tr>
                                        <td><?php echo esc((string) ($row['hospital_name'] ?: 'Unassigned')); ?></td>
                                        <td><?php echo (int) $row['total_appointments']; ?></td>
                                        <td><?php echo (int) $row['unique_patients']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="content-card">
                    <h2 class="section-title">Doctors Per Hospital</h2>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr><th>Hospital</th><th>Doctor Count</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($doctorCountPerHospital as $row): ?>
                                    <tr>
                                        <td><?php echo esc((string) $row['hospital']); ?></td>
                                        <td><?php echo (int) $row['doctor_count']; ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="content-card" style="margin-top:20px;">
                <h2 class="section-title">Doctor Performance</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>Doctor</th><th>Specialty</th><th>Hospital</th><th>Total Appointments</th><th>Completed</th><th>Completion Rate</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($doctorPerformance as $row): ?>
                                <tr>
                                    <td><?php echo esc((string) $row['full_name']); ?></td>
                                    <td><?php echo esc((string) $row['specialty']); ?></td>
                                    <td><?php echo esc((string) $row['hospital']); ?></td>
                                    <td><?php echo (int) $row['total_appointments']; ?></td>
                                    <td><?php echo (int) $row['completed_appointments']; ?></td>
                                    <td><?php echo esc((string) ($row['completion_rate'] ?? 0)); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="content-card" style="margin-top:20px;">
                <h2 class="section-title">Export Preview</h2>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr><th>ID</th><th>Date</th><th>Patient</th><th>Doctor</th><th>Hospital</th><th>Status</th><th>Diagnosis</th><th>Amount</th><th>Billing</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($appointmentExportRows as $row): ?>
                                <tr>
                                    <td><?php echo (int) $row['id']; ?></td>
                                    <td><?php echo esc((string) ($row['appointment_date'] . ' ' . $row['appointment_time'])); ?></td>
                                    <td><?php echo esc((string) $row['patient_name']); ?></td>
                                    <td><?php echo esc((string) $row['doctor_name']); ?></td>
                                    <td><?php echo esc((string) $row['hospital_name']); ?></td>
                                    <td><span class="pill pill-<?php echo esc((string) $row['status']); ?>"><?php echo esc((string) $row['status']); ?></span></td>
                                    <td><?php echo esc((string) ($row['diagnosis'] ?: 'Pending consultation')); ?></td>
                                    <td>₹<?php echo number_format((float) ($row['amount'] ?? 0)); ?></td>
                                    <td><?php echo esc((string) ($row['billing_status'] ?? 'N/A')); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<script>
const revenueLabels = <?php echo json_encode($revenueLabels); ?>;
const revenueValues = <?php echo json_encode($revenueValues); ?>;
const statusLabels = <?php echo json_encode(array_keys($statusSummary)); ?>;
const statusValues = <?php echo json_encode(array_values($statusSummary)); ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'line',
    data: {
        labels: revenueLabels,
        datasets: [{
            label: 'Paid Revenue',
            data: revenueValues,
            borderColor: '#2563eb',
            backgroundColor: 'rgba(37,99,235,0.12)',
            tension: 0.35,
            fill: true
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } }
    }
});

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{
            data: statusValues,
            backgroundColor: ['#f59e0b', '#2563eb', '#10b981', '#ef4444']
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { position: 'bottom' } }
    }
});
</script>
</body>
</html>


