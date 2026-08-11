<?php
$reportUserId = (int) ($_SESSION['user_id'] ?? 0);
$monthlySpendLabels = [];
$monthlySpendValues = [];
$appointmentTrendLabels = [];
$appointmentTrendValues = [];

$monthlySpendSql = "
    SELECT month_key, SUM(total_amount) AS total_amount
    FROM (
        SELECT DATE_FORMAT(billing_date, '%Y-%m') AS month_key, amount AS total_amount
        FROM billing
        WHERE user_id = {$reportUserId}
        UNION ALL
        SELECT DATE_FORMAT(purchase_date, '%Y-%m') AS month_key, amount AS total_amount
        FROM medicine_expenses
        WHERE patient_id = {$reportUserId}
    ) monthly_costs
    GROUP BY month_key
    ORDER BY month_key DESC
    LIMIT 6
";
$monthlySpendResult = mysqli_query($conn, $monthlySpendSql);
$monthlySpendRows = [];
while ($monthlySpendResult && ($row = mysqli_fetch_assoc($monthlySpendResult))) {
    $monthlySpendRows[] = $row;
}
$monthlySpendRows = array_reverse($monthlySpendRows);
foreach ($monthlySpendRows as $row) {
    $monthlySpendLabels[] = date('M Y', strtotime($row['month_key'] . '-01'));
    $monthlySpendValues[] = (float) $row['total_amount'];
}

$appointmentSummarySql = "
    SELECT
        COUNT(*) AS total_appointments,
        SUM(CASE WHEN a.status = 'Completed' THEN 1 ELSE 0 END) AS completed_appointments,
        SUM(CASE WHEN a.status = 'Cancelled' THEN 1 ELSE 0 END) AS cancelled_appointments,
        SUM(CASE WHEN a.appointment_date >= CURDATE() AND a.status IN ('Pending', 'Confirmed') THEN 1 ELSE 0 END) AS upcoming_appointments
    FROM appointments a
    WHERE a.user_id = {$reportUserId}
";
$appointmentSummary = mysqli_fetch_assoc(mysqli_query($conn, $appointmentSummarySql)) ?: [];

$favoriteDoctorSql = "
    SELECT COALESCE(doctor_name, d.full_name) AS doctor_name, COUNT(*) AS total_visits
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE a.user_id = {$reportUserId}
      AND a.status <> 'Cancelled'
    GROUP BY COALESCE(doctor_name, d.full_name)
    ORDER BY total_visits DESC, doctor_name ASC
    LIMIT 1
";
$favoriteDoctor = mysqli_fetch_assoc(mysqli_query($conn, $favoriteDoctorSql)) ?: [];

$favoriteHospitalSql = "
    SELECT COALESCE(a.hospital_name, d.hospital) AS hospital_name, COUNT(*) AS total_visits
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    WHERE a.user_id = {$reportUserId}
      AND a.status <> 'Cancelled'
    GROUP BY COALESCE(hospital_name, d.hospital)
    ORDER BY total_visits DESC, hospital_name ASC
    LIMIT 1
";
$favoriteHospital = mysqli_fetch_assoc(mysqli_query($conn, $favoriteHospitalSql)) ?: [];

$appointmentTrendSql = "
    SELECT DATE_FORMAT(a.appointment_date, '%Y-%m') AS month_key, COUNT(*) AS total_count
    FROM appointments a
    WHERE a.user_id = {$reportUserId}
    GROUP BY DATE_FORMAT(a.appointment_date, '%Y-%m')
    ORDER BY month_key DESC
    LIMIT 6
";
$appointmentTrendResult = mysqli_query($conn, $appointmentTrendSql);
$appointmentTrendRows = [];
while ($appointmentTrendResult && ($row = mysqli_fetch_assoc($appointmentTrendResult))) {
    $appointmentTrendRows[] = $row;
}
$appointmentTrendRows = array_reverse($appointmentTrendRows);
foreach ($appointmentTrendRows as $row) {
    $appointmentTrendLabels[] = date('M Y', strtotime($row['month_key'] . '-01'));
    $appointmentTrendValues[] = (int) $row['total_count'];
}

$historySql = "
    SELECT
        a.appointment_date,
        a.appointment_time,
        a.status,
        COALESCE(a.doctor_name, d.full_name) AS doctor_name,
        COALESCE(a.hospital_name, d.hospital) AS hospital_name,
        COALESCE(c.diagnosis, '') AS diagnosis,
        (
            SELECT COUNT(*)
            FROM prescriptions p
            WHERE p.appointment_id = a.id
        ) AS medicine_count,
        (
            SELECT COUNT(*)
            FROM medical_tests mt
            WHERE mt.appointment_id = a.id
        ) AS test_count
    FROM appointments a
    LEFT JOIN doctors d ON d.id = a.doctor_id
    LEFT JOIN consultations c ON c.appointment_id = a.id
    WHERE a.user_id = {$reportUserId}
    ORDER BY a.appointment_date DESC, a.id DESC
    LIMIT 8
";
$historyResult = mysqli_query($conn, $historySql);
?>
<div class="reports-view-container" style="padding:20px;">
    <div class="content-header" style="margin-bottom:25px;">
        <h2 style="font-size:1.5rem;color:#1e293b;">
            <i class="fa-solid fa-chart-column" style="color:#2563eb;margin-right:10px;"></i>My Reports
        </h2>
        <p style="margin-top:8px;color:#64748b;">A compact view of your appointment activity, spending, and care patterns.</p>
    </div>

    <div class="billing-summary-row" style="margin-bottom:20px;">
        <div class="billing-stat-card">
            <div class="stat-icon" style="background:#eff6ff;"><i class="fa-solid fa-calendar-check" style="color:#2563eb;"></i></div>
            <div class="stat-info"><span class="stat-label">Total Appointments</span><span class="stat-value"><?php echo (int) ($appointmentSummary['total_appointments'] ?? 0); ?></span></div>
        </div>
        <div class="billing-stat-card">
            <div class="stat-icon" style="background:#f0fdf4;"><i class="fa-solid fa-circle-check" style="color:#16a34a;"></i></div>
            <div class="stat-info"><span class="stat-label">Completed Visits</span><span class="stat-value"><?php echo (int) ($appointmentSummary['completed_appointments'] ?? 0); ?></span></div>
        </div>
        <div class="billing-stat-card">
            <div class="stat-icon" style="background:#fff7ed;"><i class="fa-solid fa-clock" style="color:#f97316;"></i></div>
            <div class="stat-info"><span class="stat-label">Upcoming</span><span class="stat-value"><?php echo (int) ($appointmentSummary['upcoming_appointments'] ?? 0); ?></span></div>
        </div>
        <div class="billing-stat-card">
            <div class="stat-icon" style="background:#f5f3ff;"><i class="fa-solid fa-indian-rupee-sign" style="color:#7c3aed;"></i></div>
            <div class="stat-info"><span class="stat-label">6-Month Spend</span><span class="stat-value">₹<?php echo number_format(array_sum($monthlySpendValues)); ?></span></div>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-bottom:20px;">
        <div class="billing-table-card" style="padding:20px;">
            <div class="billing-table-header"><h3><i class="fa-solid fa-user-doctor" style="margin-right:8px;color:#2563eb;"></i>Most Visited Doctor</h3></div>
            <p style="font-size:24px;font-weight:800;color:#0f172a;margin:12px 0 6px;"><?php echo htmlspecialchars((string) ($favoriteDoctor['doctor_name'] ?? 'No completed visits yet')); ?></p>
            <p style="margin:0;color:#64748b;">Visits: <?php echo (int) ($favoriteDoctor['total_visits'] ?? 0); ?></p>
        </div>
        <div class="billing-table-card" style="padding:20px;">
            <div class="billing-table-header"><h3><i class="fa-solid fa-hospital" style="margin-right:8px;color:#2563eb;"></i>Most Visited Hospital</h3></div>
            <p style="font-size:24px;font-weight:800;color:#0f172a;margin:12px 0 6px;"><?php echo htmlspecialchars((string) ($favoriteHospital['hospital_name'] ?? 'No hospital history yet')); ?></p>
            <p style="margin:0;color:#64748b;">Visits: <?php echo (int) ($favoriteHospital['total_visits'] ?? 0); ?></p>
        </div>
    </div>

    <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;margin-bottom:20px;">
        <div class="billing-table-card" style="padding:20px;">
            <div class="billing-table-header"><h3><i class="fa-solid fa-chart-line" style="margin-right:8px;color:#2563eb;"></i>Monthly Spending</h3></div>
            <canvas id="patientSpendingChart" height="160"></canvas>
        </div>
        <div class="billing-table-card" style="padding:20px;">
            <div class="billing-table-header"><h3><i class="fa-solid fa-chart-column" style="margin-right:8px;color:#2563eb;"></i>Appointment Trend</h3></div>
            <canvas id="patientAppointmentTrendChart" height="160"></canvas>
        </div>
    </div>

    <div class="billing-table-card">
        <div class="billing-table-header">
            <h3><i class="fa-solid fa-clock-rotate-left" style="margin-right:8px;color:#2563eb;"></i>Appointment History Summary</h3>
        </div>
        <?php if ($historyResult && mysqli_num_rows($historyResult) > 0): ?>
            <div class="billing-table-wrap">
                <table class="billing-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Hospital</th>
                            <th>Status</th>
                            <th>Diagnosis</th>
                            <th>Medicines</th>
                            <th>Tests</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($history = mysqli_fetch_assoc($historyResult)): ?>
                            <tr>
                                <td><?php echo htmlspecialchars(date('d M Y', strtotime($history['appointment_date'])) . ' · ' . $history['appointment_time']); ?></td>
                                <td><?php echo htmlspecialchars((string) $history['doctor_name']); ?></td>
                                <td><?php echo htmlspecialchars((string) $history['hospital_name']); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower((string) $history['status']); ?>"><?php echo htmlspecialchars((string) $history['status']); ?></span></td>
                                <td><?php echo htmlspecialchars((string) ($history['diagnosis'] ?: 'Pending consultation')); ?></td>
                                <td><?php echo (int) $history['medicine_count']; ?></td>
                                <td><?php echo (int) $history['test_count']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="empty-appointments" style="padding:50px;">
                <i class="fa-solid fa-chart-column"></i>
                <p>No report data is available yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
window.PATIENT_REPORTS = {
    spendLabels: <?php echo json_encode($monthlySpendLabels); ?>,
    spendValues: <?php echo json_encode($monthlySpendValues); ?>,
    appointmentLabels: <?php echo json_encode($appointmentTrendLabels); ?>,
    appointmentValues: <?php echo json_encode($appointmentTrendValues); ?>
};
</script>




