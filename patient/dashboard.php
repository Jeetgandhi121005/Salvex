<?php
session_start();
include 'includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

salvex_sync_billing_status($conn);

$view = isset($_GET['view']) ? $_GET['view'] : 'booking';

// Doctor-wise fees (same as JS)
// Fetch all doctor details and fees from the database
$doctor_data = [];
$fee_query = "SELECT full_name, consultation_fee FROM doctors";
$fee_res = mysqli_query($conn, $fee_query);

if ($fee_res) {
    while ($row = mysqli_fetch_assoc($fee_res)) {
        $doctor_data[$row['full_name']] = $row['consultation_fee'];
    }
}

// Global configuration (Can also be moved to a 'settings' table later)

$platform_fee = 49;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
    // Pass the dynamic database fees to the JavaScript dashboard logic
    window.DB_DOCTOR_FEES = <?php echo json_encode($doctor_data); ?>;
    window.PLATFORM_FEE = <?php echo $platform_fee; ?>;
</script>
    <title>Dashboard | Salvex</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/dashboard.css?v=3.0">
    <?php if ($view === 'reports'): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.0/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body>

<div class="app-layout">
    <aside class="sidebar">
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item <?php echo ($view==='booking')?'active':''; ?>">
                <i class="fa-solid fa-stethoscope"></i><span>Book Appointment</span>
            </a>
            <a href="dashboard.php?view=family" class="nav-item <?php echo ($view==='family')?'active':''; ?>">
                <i class="fa-solid fa-users"></i><span>Manage Family</span>
            </a>
            <a href="dashboard.php?view=appointments" class="nav-item <?php echo ($view==='appointments')?'active':''; ?>">
                <i class="fa-solid fa-calendar-check"></i><span>My Appointments</span>
            </a>
            <a href="dashboard.php?view=billing" class="nav-item <?php echo ($view==='billing')?'active':''; ?>">
                <i class="fa-solid fa-file-invoice-dollar"></i><span>Billing</span>
            </a>
            <a href="dashboard.php?view=reports" class="nav-item <?php echo ($view==='reports')?'active':''; ?>">
                <i class="fa-solid fa-chart-column"></i><span>Reports</span>
            </a>
            <a href="dashboard.php?view=help" class="nav-item <?php echo ($view==='help')?'active':''; ?>">
                <i class="fa-solid fa-headset"></i><span>Need Help</span>
            </a>
            <a href="index.php" class="nav-item index">
                <i class="fa-solid fa-house"></i><span>Home</span>
            </a>
            <a href="logout.php" class="nav-item logout">
                <i class="fa-solid fa-right-from-bracket"></i><span>Logout</span>
            </a>
        </nav>
    </aside>

    <main class="main-content">

        <?php if ($view === 'booking'): ?>
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1><i class="fa-solid fa-stethoscope" style="color:#2563eb;margin-right:10px;"></i>Selected Specialist</h1>
                <p>Please confirm the doctor details before booking</p>
            </div>
            <div class="doctor-info-banner" id="displayDoctorCard">
                <p>Loading your selection...</p>
            </div>
            <div class="booking-controls">
                <div class="date-scroller-container">

                    <!-- Patient Details — UPAR -->
                    <h3 class="booking-section-heading">
                        <i class="fa-solid fa-user"></i> Patient Details
                    </h3>
                    <div class="patient-fields-row" style="grid-template-columns:1fr;">
                        <div class="patient-field-group" style="width:100%;">
                            <label class="patient-field-label">Select Patient Profile</label>
                            <select id="patientProfileSelect" class="patient-field-input" style="appearance:auto;">
                                <option value="">Loading family profiles...</option>
                            </select>
                            <p id="patientProfileHint" style="margin:10px 0 0;color:#64748b;font-size:13px;">
                                Choose a saved family member profile to continue.
                            </p>
                        </div>
                    </div>
                    <div id="selectedPatientMeta" style="display:none;margin-top:14px;padding:14px 16px;border:1px solid #dbeafe;background:#eff6ff;border-radius:12px;color:#1e3a8a;font-size:14px;font-weight:600;">
                        <span id="selectedPatientRelation">Relation: —</span>
                        <span style="margin:0 10px;color:#93c5fd;">|</span>
                        <span id="selectedPatientAge">Age: —</span>
                        <span style="margin:0 10px;color:#93c5fd;">|</span>
                        <span id="selectedPatientDob">DOB: —</span>
                    </div>

                    <!-- Date -->
                    <h3 class="booking-section-heading" style="margin-top:24px;">
                        <i class="fa-solid fa-calendar-check"></i> Select Appointment Date
                    </h3>
                    <div class="date-wrapper" id="dateWrapper">
                        <input type="hidden" id="selectedAppointmentDate" name="appointment_date">
                    </div>

                    <!-- Time Slots -->
                    <h3 class="booking-section-heading" style="margin-top:24px;">
                        <i class="fa-solid fa-clock"></i> Choose a Time Slot
                    </h3>
                    <div class="slots-grid" id="timeSlotsContainer"></div>

                    <button class="auth-btn" onclick="handleBooking()" style="width:100%;margin-top:28px;padding:15px;">
                        Confirm & Book Now
                    </button>
                </div>
            </div>
        </div>

        <?php elseif ($view === 'family'): ?>
        <div class="family-container">
            <div class="content-header">
                <h2 style="font-size:1.5rem;color:#1e293b;">
                    <i class="fa-solid fa-users" style="color:#2563eb;margin-right:10px;"></i>Manage Family Members
                </h2>
                <button class="add-btn" onclick="openAddMemberModal()">ADD NEW PROFILE</button>
            </div>
            <div id="familyList" class="family-list-grid">
                <div id="emptyState" class="empty-state" style="grid-column:1/-1;">
                    <p>No family profiles added yet.</p>
                </div>
            </div>
        </div>

        <?php elseif ($view === 'appointments'): ?>
        <div class="appointments-view-container" style="padding:20px;">
            <div class="content-header" style="margin-bottom:25px;">
                <h2 style="font-size:1.5rem;color:#1e293b;">
                    <i class="fa-solid fa-calendar-check" style="color:#2563eb;margin-right:10px;"></i>My Appointments
                </h2>
            </div>
            <div class="appointments-list">
                <?php
                $u_id = $_SESSION['user_id'];
                $sql = "SELECT a.*, 
                    COALESCE(a.doctor_name, d.full_name) AS doctor_name,
                    COALESCE(a.specialty, d.specialty) AS specialty,
                    COALESCE(a.hospital_name, d.hospital) AS hospital_name,
                    c.reason_for_visit AS consultation_reason_for_visit,
                    c.diagnosis AS consultation_diagnosis,
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
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN consultations c ON c.appointment_id = a.id
                    WHERE a.user_id = '$u_id' AND a.status != 'Cancelled' 
                    ORDER BY a.id DESC ";
$res  = mysqli_query($conn, $sql);
                if ($res && mysqli_num_rows($res) > 0):
                    while ($row = mysqli_fetch_assoc($res)): ?>
                    <div class="appointment-card">
                        <div class="appt-main">
                            <div class="appt-icon-wrap"><i class="fa-solid fa-stethoscope"></i></div>
                            <div class="appt-info">
                                <h3><?php echo $row['doctor_name']; ?></h3>
                                <p class="appt-specialty"><?php echo $row['specialty']; ?></p>
                                <p class="appt-hospital"><i class="fa-solid fa-hospital"></i><?php echo $row['hospital_name']; ?></p>
                                <?php if (!empty($row['patient_name'])): ?>
                                <p class="appt-hospital"><i class="fa-solid fa-user"></i>Booked for: <?php echo htmlspecialchars($row['patient_name']); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="appt-meta">
                            <div class="appt-datetime">
                                <span><i class="fa-regular fa-calendar"></i> <?php echo date('d M Y', strtotime($row['appointment_date'])); ?></span>
                                <span><i class="fa-regular fa-clock"></i> <?php echo $row['appointment_time']; ?></span>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($row['status']); ?>"><?php echo $row['status']; ?></span>
                        </div>
                        <?php if (!empty($row['consultation_reason_for_visit']) || !empty($row['consultation_diagnosis']) || (int) $row['medicine_count'] > 0 || (int) $row['test_count'] > 0): ?>
                        <div style="margin-top:14px;padding:12px 14px;border:1px solid #dbeafe;border-radius:12px;background:#f8fbff;">
                            <div style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px 14px;">
                                <div>
                                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;">Reason for Visit</div>
                                    <div style="font-size:14px;color:#0f172a;margin-top:4px;"><?php echo htmlspecialchars($row['consultation_reason_for_visit'] ?: ($row['reason_for_visit'] ?: 'Pending update')); ?></div>
                                </div>
                                <div>
                                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;">Diagnosis</div>
                                    <div style="font-size:14px;color:#0f172a;margin-top:4px;"><?php echo htmlspecialchars($row['consultation_diagnosis'] ?: 'Pending consultation'); ?></div>
                                </div>
                                <div>
                                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;">Prescribed Medicines</div>
                                    <div style="font-size:14px;color:#0f172a;margin-top:4px;"><?php echo (int) $row['medicine_count']; ?> added</div>
                                </div>
                                <div>
                                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;">Recommended Tests</div>
                                    <div style="font-size:14px;color:#0f172a;margin-top:4px;"><?php echo (int) $row['test_count']; ?> added</div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="appt-actions">
                            <a href="appointment_details.php?id=<?php echo $row['id']; ?>" class="btn-view-detail" style="text-decoration:none; display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:6px; background:#eff6ff; color:#2563eb; font-weight:600; font-size:13px; border:1px solid #bfdbfe; cursor:pointer;">
        <i class="fa-solid fa-file-medical"></i> Full Details
    </a>
                            <button class="btn-reschedule" onclick="openRescheduleModal(<?php echo $row['id']; ?>,'<?php echo addslashes($row['doctor_name']); ?>','<?php echo $row['appointment_date']; ?>','<?php echo $row['appointment_time']; ?>')">
                                <i class="fa-solid fa-rotate"></i> Reschedule
                            </button>
                            <button class="btn-cancel-appt" onclick="confirmCancel(<?php echo $row['id']; ?>)">
                                <i class="fa-solid fa-xmark"></i> Cancel
                            </button>
                        </div>
                    </div>
                    <?php endwhile;
                else: ?>
                    <div class="empty-appointments">
                        <i class="fa-solid fa-calendar-xmark"></i>
                        <p>No appointments found.</p>
                        <a href="dashboard.php" class="book-now-link">Book your first appointment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($view === 'billing'): ?>
        <?php
        $u_id      = $_SESSION['user_id'];
        $filter    = isset($_GET['filter']) ? $_GET['filter'] : 'all';

        // Stats
        $all_sql   = "SELECT * FROM billing WHERE user_id='$u_id' ORDER BY billing_date DESC, id DESC";
        $all_res   = mysqli_query($conn, $all_sql);
        $all_rows  = [];
        $total_billed = 0;
        $paid_count = 0;
        $unpaid_count = 0;
        while ($r = mysqli_fetch_assoc($all_res)) {
            $all_rows[] = $r;
            $amount = (float) ($r['amount'] ?? 0);
            $total_billed += $amount;
            if (($r['status'] ?? '') === 'Paid') {
                $paid_count++;
            } else {
                $unpaid_count++;
            }
        }
        $total_paid = $total_billed;
        $pending_count = $unpaid_count;
        $confirmed_count = $paid_count;
        $cancelled_count = 0;
        $total_count = count($all_rows);

        // Filter
        $filtered = $filter === 'all' ? $all_rows : array_filter($all_rows, fn($r) => strtolower($r['status']) === $filter);
        ?>
        <div class="billing-view-container">
            <div class="content-header" style="margin-bottom:20px;">
                <h2 style="font-size:1.5rem;color:#1e293b;">
                    <i class="fa-solid fa-file-invoice-dollar" style="color:#2563eb;margin-right:10px;"></i>Billing & Payments
                </h2>
            </div>

            <!-- Stat Cards -->
            <div class="billing-summary-row">
                <div class="billing-stat-card">
                    <div class="stat-icon" style="background:#eff6ff;"><i class="fa-solid fa-calendar-check" style="color:#3b82f6;"></i></div>
                    <div class="stat-info"><span class="stat-label">Total Bills</span><span class="stat-value"><?php echo $total_count; ?></span></div>
                </div>
                <div class="billing-stat-card">
                    <div class="stat-icon" style="background:#f0fdf4;"><i class="fa-solid fa-indian-rupee-sign" style="color:#22c55e;"></i></div>
                    <div class="stat-info"><span class="stat-label">Total Amount</span><span class="stat-value">₹<?php echo number_format($total_paid); ?></span></div>
                </div>
                <div class="billing-stat-card">
                    <div class="stat-icon" style="background:#fff7ed;"><i class="fa-solid fa-hourglass-half" style="color:#f97316;"></i></div>
                    <div class="stat-info"><span class="stat-label">Unpaid</span><span class="stat-value"><?php echo $pending_count; ?></span></div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="billing-filter-tabs">
                <a href="dashboard.php?view=billing&filter=all" class="filter-tab <?php echo $filter==='all'?'active':''; ?>">
                    All <span class="filter-count"><?php echo $total_count; ?></span>
                </a>
                <a href="dashboard.php?view=billing&filter=unpaid" class="filter-tab filter-pending <?php echo $filter==='unpaid'?'active':''; ?>">
                    Unpaid <span class="filter-count"><?php echo $pending_count; ?></span>
                </a>
                <a href="dashboard.php?view=billing&filter=paid" class="filter-tab filter-confirmed <?php echo $filter==='paid'?'active':''; ?>">
                    Paid <span class="filter-count"><?php echo $confirmed_count; ?></span>
                </a>
            </div>

            <!-- Table -->
            <div class="billing-table-card">
                <div class="billing-table-header">
                    <h3><i class="fa-solid fa-receipt" style="margin-right:8px;color:#2563eb;"></i>Payment History</h3>
                </div>
                <?php if (count($filtered) > 0): ?>
                <div class="billing-table-wrap">
                    <table class="billing-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Invoice No</th>
                                <th>Doctor</th>
                                <th>Billing Date</th>
                                <th>Charge</th>
                                <th>Platform</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Bill</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php $sr=1; foreach ($filtered as $bill):
                            $invoice_no = $bill['invoice_no'] ?: ('INV-' . str_pad((string) $bill['id'], 6, '0', STR_PAD_LEFT));
                            $bill_amount = (float) ($bill['amount'] ?? 0);
                            $consult_fee = $bill_amount;
                            $platform_fee = 0;
                            $total_amount = $bill_amount;
                            $bill_data    = base64_encode(json_encode([
                                'invoice'  => $invoice_no,
                                'doctor'   => $bill['doctor_name'],
                                'specialty'=> 'Billing Record',
                                'hospital' => 'Salvex',
                                'date'     => date('d M Y', strtotime($bill['billing_date'])),
                                'time'     => 'Invoice Date',
                                'status'   => $bill['status'],
                                'patient'  => $bill['patient_name'] ?? '—',
                                'age'      => $bill['patient_age'] ?? '—',
                                'consult'  => $consult_fee,
                                'platform' => $platform_fee,
                                'total'    => $total_amount,
                                'ref'      => $invoice_no,
                                'amount'   => $bill_amount,
                            ])); ?>
                            <tr>
                                <td><?php echo $sr++; ?></td>
                                <td><strong><?php echo htmlspecialchars($invoice_no); ?></strong></td>
                                <td>
                                    <div class="doc-name-cell">
                                        <span class="doc-avatar"><i class="fa-solid fa-user-doctor"></i></span>
                                        <?php echo htmlspecialchars($bill['doctor_name']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="date-time-cell">
                                        <span><?php echo date('d M Y', strtotime($bill['billing_date'])); ?></span>
                                        <span class="time-text">Invoice Date</span>
                                    </div>
                                </td>
                                <td>₹<?php echo number_format($consult_fee); ?></td>
                                <td>₹<?php echo $platform_fee; ?></td>
                                <td class="total-amount">₹<?php echo number_format($total_amount); ?></td>
                                <td><span class="status-badge status-<?php echo strtolower($bill['status']) === 'paid' ? 'confirmed' : 'pending'; ?>"><?php echo htmlspecialchars($bill['status']); ?></span></td>
                                <td>
                                    <button class="btn-view-bill" onclick="openBillModal('<?php echo $bill_data; ?>')">
                                        <i class="fa-solid fa-file-invoice"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="empty-appointments" style="padding:60px;">
                        <i class="fa-solid fa-receipt"></i>
                        <p>No <?php echo $filter !== 'all' ? $filter : ''; ?> records found.</p>
                        <a href="dashboard.php" class="book-now-link">Book an appointment</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($view === 'reports'): ?>
        <?php include __DIR__ . '/includes/reports_view.php'; ?>

        <?php elseif ($view === 'help'): ?>
        <div class="help-view-container">
            <section class="help-section" style="margin-top:0;box-shadow:none;border:1px solid #e2e8f0;">
                <div class="section-header">
                    <h2><i class="fa-solid fa-headset" style="color:#2563eb;margin-right:10px;"></i>Need Help?</h2>
                    <p>Find answers to common questions or reach out to our team.</p>
                </div>
                <div class="help-top-row" style="display:flex;gap:20px;margin-bottom:25px;">
                    <div style="flex:1.5;background:#fff;padding:25px;border-radius:15px;border:1px solid #f1f5f9;">
                        <h3 style="color:#1e293b;margin-bottom:20px;">How to Book?</h3>
                        <div style="display:flex;flex-direction:column;gap:15px;">
                            <?php foreach([['1','Select your <strong>Specialist</strong>.'],['2','Pick a <strong>Date & 15-min Slot</strong>.'],['3','Click <strong>Confirm & Book</strong>.']] as $step): ?>
                            <div style="display:flex;align-items:center;gap:12px;">
                                <span style="background:#3b82f6;color:white;width:25px;height:25px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:bold;"><?php echo $step[0]; ?></span>
                                <p style="margin:0;font-size:15px;"><?php echo $step[1]; ?></p>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div style="flex:1;background:#fff;padding:25px;border-radius:15px;border:1px solid #f1f5f9;">
                        <h3 style="color:#1e293b;margin-bottom:20px;">Contact Us</h3>
                        <div style="display:flex;flex-direction:column;gap:15px;">
                            <div style="display:flex;align-items:center;gap:15px;background:#f8fafc;padding:12px;border-radius:10px;">
                                <i class="fa-solid fa-phone" style="color:#3b82f6;font-size:18px;"></i>
                                <div><p style="font-size:11px;color:#64748b;margin:0;text-transform:uppercase;">Support Line</p><p style="font-size:14px;font-weight:700;margin:0;">+91 79 1234 5678</p></div>
                            </div>
                            <div style="display:flex;align-items:center;gap:15px;background:#f8fafc;padding:12px;border-radius:10px;">
                                <i class="fa-solid fa-envelope" style="color:#3b82f6;font-size:18px;"></i>
                                <div><p style="font-size:11px;color:#64748b;margin:0;text-transform:uppercase;">Email</p><p style="font-size:14px;font-weight:700;margin:0;">support@salvex.com</p></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div style="background:#fff;padding:30px;border-radius:15px;border:1px solid #f1f5f9;">
                    <h3 style="margin-bottom:25px;color:#1e293b;font-size:20px;display:flex;align-items:center;font-weight:700;">
                        <i class="fa-solid fa-circle-question" style="color:#3b82f6;margin-right:12px;"></i>Frequently Asked Questions
                    </h3>
                    <?php foreach([
                        ['How do I book a specialist?', 'Search for a doctor on the home page, click their profile, and you will be redirected here to the <strong style="color:#3b82f6;">\'Book Appointment\'</strong> section to choose your slot.'],
                        ['Can I add family members to my profile?','Yes! Head over to the <strong style="color:#3b82f6;">\'Manage Family\'</strong> section to create profiles for your loved ones and book appointments for them.'],
                        ['Where can I see my past appointments?','You can view all your previous and upcoming bookings in the <strong style="color:#3b82f6;">\'My Appointments\'</strong> section.'],
                        ['How can I download my appointment invoice?', 'Go to the <strong style="color:#3b82f6;">\'Billing\'</strong> section and click on the download icon next to your transaction to save the receipt.'],
                        ['What are the support hours?','Our support team is available <strong style="color:#3b82f6;">Monday to Saturday, 10 AM – 7 PM</strong>.'],
                        ['Are there any extra platform fees?', 'Yes, a nominal platform fee of <strong style="color:#3b82f6;">₹49</strong> is charged to maintain secure services and 24/7 support.'],
                    ] as $faq): ?>
                    <div style="width:100%;background:#f8fafc;padding:20px;border-radius:12px;border-left:5px solid #3b82f6;margin-bottom:15px;">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                            <span style="background:#3b82f6;color:white;min-width:24px;height:24px;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:bold;">Q</span>
                            <p style="font-weight:700;font-size:16px;color:#1e293b;margin:0;"><?php echo $faq[0]; ?></p>
                        </div>
                        <p style="font-size:14px;color:#64748b;margin:0;padding-left:34px;line-height:1.6;"><?php echo $faq[1]; ?></p>
                    </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </div>
        <?php endif; ?>

    </main>
</div>

<!-- ===== CUSTOM ALERT MODAL ===== -->
<div id="salvexAlertModal" class="overlay-backdrop" style="display:none;">
    <div class="small-modal" style="max-width:340px;">
        <div class="small-modal-icon" id="alertIconWrap" style="background:#eff6ff;">
            <i class="fa-solid fa-circle-info" id="alertIcon" style="color:#3b82f6;"></i>
        </div>
        <h3 id="alertTitle">Alert</h3>
        <p id="alertMsg">Message here</p>
        <div class="small-modal-actions">
            <button class="btn-modal-secondary" style="flex:1;" onclick="closeAlert()">OK</button>
        </div>
    </div>
</div>

<!-- ===== BOOKING SUMMARY MODAL ===== -->
<div id="bookingSummaryModal" class="overlay-backdrop" style="display:none;">
    <div class="summary-modal">
        <div class="summary-header">
            <i class="fa-solid fa-calendar-lines-pen"></i>
            <div><h2>Booking Summary</h2><p>Review your appointment details</p></div>
            <button class="modal-close-btn" onclick="closeSummaryModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="summary-body">
            <?php foreach([
                ['sum-doctor','Doctor','eff6ff','fa-user-doctor','3b82f6'],
                ['sum-specialty','Specialty','f5f3ff','fa-stethoscope','8b5cf6'],
                ['sum-hospital','Hospital','fff7ed','fa-hospital','f97316'],
                ['sum-date','Date','fef9c3','fa-regular fa-calendar','ca8a04'],
                ['sum-time','Time Slot','f0fdf4','fa-regular fa-clock','22c55e'],
                ['sum-patient','Patient','fdf2f8','fa-person','ec4899'],
            ] as $item): ?>
            <div class="summary-item">
                <div class="summary-icon-wrap" style="background:#<?php echo $item[2];?>;">
                    <i class="fa-solid <?php echo $item[3];?>" style="color:#<?php echo $item[4];?>;"></i>
                </div>
                <div class="summary-detail">
                    <span class="summary-label"><?php echo $item[1]; ?></span>
                    <span class="summary-value" id="<?php echo $item[0]; ?>">—</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="summary-fee-section">
            <div class="fee-row"><span>Consultation Fee</span><span id="sum-consult-fee">₹0</span></div>
            <div class="fee-row"><span>Platform Fee</span><span>₹49</span></div>
            <div class="fee-divider"></div>
            <div class="fee-row total-row"><span>Total</span><span id="sum-total">₹0</span></div>
        </div>
        <button class="confirm-book-btn" onclick="submitBooking()">
            <i class="fa-solid fa-calendar-check" style="margin-right:8px;"></i>Confirm Appointment
        </button>
    </div>
</div>

<!-- ===== CONFIRMED MODAL ===== -->
<div id="bookingConfirmedModal" class="overlay-backdrop" style="display:none;">
    <div class="confirmed-modal">
        <div class="confirmed-check"><i class="fa-solid fa-check"></i></div>
        <h1 class="confirmed-title">Appointment Confirmed!</h1>
        <p class="confirmed-sub">Your appointment has been successfully booked. Please arrive <strong>10 minutes</strong> before your scheduled time with a valid ID.</p>
        <div class="booking-ref-badge">
            <i class="fa-solid fa-hashtag"></i><span>Booking Reference: </span><strong id="conf-ref">SLV-000000</strong>
        </div>
        <div class="confirmed-details-grid">
            <?php foreach([
                ['conf-doctor','DOCTOR','eff6ff','fa-user-doctor','3b82f6'],
                ['conf-specialty','SPECIALTY','f5f3ff','fa-stethoscope','8b5cf6'],
                ['conf-hospital','HOSPITAL','fff7ed','fa-hospital','f97316'],
                ['conf-date','DATE','fef9c3','fa-regular fa-calendar','ca8a04'],
                ['conf-time','TIME SLOT','f0fdf4','fa-regular fa-clock','22c55e'],
                ['conf-patient','PATIENT','fdf2f8','fa-person','ec4899'],
                ['conf-total','TOTAL AMOUNT','f0fdf4','fa-indian-rupee-sign','22c55e'],
            ] as $c): ?>
            <div class="conf-detail-card">
                <div class="conf-detail-icon" style="background:#<?php echo $c[2];?>;">
                    <i class="fa-solid <?php echo $c[3];?>" style="color:#<?php echo $c[4];?>;"></i>
                </div>
                <div>
                    <span class="conf-label"><?php echo $c[1]; ?></span>
                    <span class="conf-val" id="<?php echo $c[0]; ?>">—</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="confirmed-actions">
            <button class="btn-print" onclick="printSlip()"><i class="fa-solid fa-print"></i> Print Slip</button>
            <button class="btn-new-booking" onclick="goNewBooking()"><i class="fa-solid fa-plus"></i> New Booking</button>
            <button class="btn-go-home" onclick="goHome()"><i class="fa-solid fa-house"></i> Home</button>
        </div>
    </div>
</div>

<!-- ===== CANCEL MODAL ===== -->
<div id="cancelConfirmModal" class="overlay-backdrop" style="display:none;">
    <div class="small-modal">
        <div class="small-modal-icon" style="background:#fee2e2;">
            <i class="fa-solid fa-triangle-exclamation" style="color:#ef4444;"></i>
        </div>
        <h3>Cancel Appointment?</h3>
        <p>Are you sure you want to cancel this appointment? This action cannot be undone.</p>
        <input type="hidden" id="cancelAppointmentId" value="">
        <div class="small-modal-actions">
            <button class="btn-modal-secondary" onclick="closeCancelModal()">Keep it</button>
            <button class="btn-modal-danger" onclick="proceedCancel()">Yes, Cancel</button>
        </div>
    </div>
</div>

<!-- ===== RESCHEDULE MODAL ===== -->
<div id="rescheduleModal" class="overlay-backdrop" style="display:none;">
    <div class="reschedule-modal">
        <div class="summary-header">
            <div class="summary-icon-wrap" style="background:#eff6ff;width:48px;height:48px;display:flex;align-items:center;justify-content:center;border-radius:12px;">
                <i class="fa-solid fa-rotate" style="color:#3b82f6;font-size:20px;"></i>
            </div>
            <div><h2>Reschedule Appointment</h2><p id="reschedule-doctor-name" style="color:#64748b;font-size:14px;">—</p></div>
            <button class="modal-close-btn" onclick="closeRescheduleModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <input type="hidden" id="rescheduleAppointmentId">
        <div style="margin-top:20px;">
            <h4 style="font-size:14px;font-weight:600;color:#1e293b;margin-bottom:12px;">
                <i class="fa-regular fa-calendar" style="color:#3b82f6;margin-right:6px;"></i>Select New Date
            </h4>
            <div class="date-wrapper" id="rescheduleDateWrapper" style="display:flex;flex-direction:row;overflow-x:auto;gap:10px;padding:10px 0;scrollbar-width:none;">
                <input type="hidden" id="rescheduleSelectedDate">
            </div>
        </div>
        <div style="margin-top:20px;">
            <h4 style="font-size:14px;font-weight:600;color:#1e293b;margin-bottom:12px;">
                <i class="fa-regular fa-clock" style="color:#3b82f6;margin-right:6px;"></i>Select New Time Slot
            </h4>
            <div id="rescheduleTimeSlots" style="display:grid;grid-template-columns:repeat(4,1fr);gap:10px;"></div>
        </div>
        <button class="confirm-book-btn" style="margin-top:24px;" onclick="submitReschedule()">
            <i class="fa-solid fa-rotate" style="margin-right:8px;"></i>Confirm Reschedule
        </button>
    </div>
</div>

<!-- ===== BILL SLIP MODAL ===== -->
<div id="billSlipModal" class="overlay-backdrop" style="display:none;">
    <div class="bill-slip-modal">
        <div class="bill-slip-header">
            <img src="assets/images/Salvex_Logo.png" alt="Salvex" style="height:36px;">
            <div>
                <h2 style="margin:0;font-size:18px;color:#0f172a;">Appointment Bill</h2>
                <p style="margin:0;font-size:12px;color:#64748b;">Salvex Health Management System</p>
            </div>
            <button class="modal-close-btn" onclick="closeBillModal()"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div id="billSlipContent"><!-- JS se fill hoga --></div>
        <div class="bill-slip-actions">
            <button class="btn-go-home" onclick="closeBillModal()"><i class="fa-solid fa-xmark"></i> Close</button>
            <button class="btn-new-booking" onclick="printBill()"><i class="fa-solid fa-print"></i> Print Bill</button>
        </div>
    </div>
</div>

<!-- ===== ADD FAMILY MODAL ===== -->
<div id="addFamilyModal" class="overlay-backdrop" style="display:none;">
    <div class="success-card" style="max-width:500px;text-align:left;">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
            <h2 id="familyModalTitle" style="margin:0;">Add Family Member</h2>
            <i class="fa-solid fa-xmark" onclick="closeModal()" style="cursor:pointer;font-size:20px;color:#94a3b8;"></i>
        </div>
        <form id="addFamilyForm">
            <input type="hidden" name="member_id" id="familyMemberId">
            <div class="form-group" style="margin-bottom:15px;">
                <label style="display:block;margin-bottom:5px;font-weight:600;">Full Name</label>
                <input type="text" name="member_name" class="date-input" placeholder="Enter name" required style="margin-bottom:0;">
            </div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px;">
                <div class="form-group">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">Relation</label>
                    <select name="relation" class="date-input" required style="margin-bottom:0;">
                        <option value="">Select</option>
                        <option>Self</option><option>Grandfather</option><option>Grandmother</option>
                        <option>Father</option><option>Mother</option><option>Sibling</option>
                        <option>Spouse</option><option>Child</option><option>Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label style="display:block;margin-bottom:5px;font-weight:600;">Age</label>
                    <input type="number" name="member_age" class="date-input" placeholder="Age" required style="margin-bottom:0;">
                </div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label style="display:block;margin-bottom:8px;font-weight:600;">Date of Birth</label>
                    <div style="display:flex;gap:10px;">
                        <input type="text" id="dob-day" maxlength="2" placeholder="DD" class="date-input dob-field" style="text-align:center;margin-bottom:0;flex:1;" required>
                        <input type="text" id="dob-month" maxlength="2" placeholder="MM" class="date-input dob-field" style="text-align:center;margin-bottom:0;flex:1;" required>
                        <input type="text" id="dob-year" maxlength="4" placeholder="YYYY" class="date-input dob-field" style="text-align:center;margin-bottom:0;flex:2;" required>
                    </div>
                </div>
            </div>
            <button type="submit" id="familySubmitBtn" class="auth-btn" style="width:100%;padding:12px;">Save Profile</button>
        </form>
    </div>
</div>

<script>
    window._sessionUserName = "<?php echo addslashes($_SESSION['user_name'] ?? ''); ?>";
</script>
<script src="assets/js/dashboard.js?v=34"></script>
<?php if ($view === 'reports'): ?>
<script>
    (function () {
        if (!window.Chart || !window.PATIENT_REPORTS) return;

        const spendCtx = document.getElementById('patientSpendingChart');
        const appointmentCtx = document.getElementById('patientAppointmentTrendChart');

        if (spendCtx) {
            new Chart(spendCtx, {
                type: 'line',
                data: {
                    labels: window.PATIENT_REPORTS.spendLabels,
                    datasets: [{
                        label: 'Spend (INR)',
                        data: window.PATIENT_REPORTS.spendValues,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.12)',
                        fill: true,
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } }
                }
            });
        }

        if (appointmentCtx) {
            new Chart(appointmentCtx, {
                type: 'bar',
                data: {
                    labels: window.PATIENT_REPORTS.appointmentLabels,
                    datasets: [{
                        label: 'Appointments',
                        data: window.PATIENT_REPORTS.appointmentValues,
                        backgroundColor: '#22c55e',
                        borderRadius: 10
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } }
                }
            });
        }
    })();
</script>
<?php endif; ?>
</body>
</html>
