<?php
session_start();
include 'includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: signin.php");
    exit();
}

$userId = (int) $_SESSION['user_id'];
$appointmentId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
salvex_sync_billing_status($conn);

if ($appointmentId <= 0) {
    die("Invalid appointment ID.");
}

$stmt = $conn->prepare(
    "SELECT a.*, d.full_name AS doctor_full_name, d.specialty AS doctor_specialty, d.hospital AS doctor_hospital,
            c.reason_for_visit AS consultation_reason_for_visit, c.diagnosis AS consultation_diagnosis, c.notes AS consultation_notes
     FROM appointments a
     LEFT JOIN doctors d ON a.doctor_id = d.id
     LEFT JOIN consultations c ON c.appointment_id = a.id
     WHERE a.id = ? AND a.user_id = ?
     LIMIT 1"
);
$stmt->bind_param('ii', $appointmentId, $userId);
$stmt->execute();
$appointment = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$appointment) {
    die("Appointment not found or access denied.");
}

$doctorName = $appointment['doctor_name'] ?: ($appointment['doctor_full_name'] ?? 'Not assigned');
$specialty = $appointment['specialty'] ?: ($appointment['doctor_specialty'] ?? 'Not available');
$hospitalName = $appointment['hospital_name'] ?: ($appointment['doctor_hospital'] ?? 'Not available');
$reasonForVisit = $appointment['consultation_reason_for_visit'] ?: ($appointment['reason_for_visit'] ?: 'Not specified');
$diagnosis = $appointment['consultation_diagnosis'] ?: 'Not recorded yet';
$doctorNotes = $appointment['consultation_notes'] ?: ($appointment['notes'] ?: 'No notes added yet.');

$soapStmt = $conn->prepare("SELECT * FROM soap_notes WHERE appointment_id = ?");
$soapStmt->bind_param('i', $appointmentId);
$soapStmt->execute();
$soap = $soapStmt->get_result()->fetch_assoc();
$soapStmt->close();

$medsStmt = $conn->prepare("SELECT * FROM prescriptions WHERE appointment_id = ?");
$medsStmt->bind_param('i', $appointmentId);
$medsStmt->execute();
$prescriptions = $medsStmt->get_result();

$testsStmt = $conn->prepare("SELECT * FROM medical_tests WHERE appointment_id = ?");
$testsStmt->bind_param('i', $appointmentId);
$testsStmt->execute();
$tests = $testsStmt->get_result();

$careStmt = $conn->prepare("SELECT * FROM care_instructions WHERE appointment_id = ?");
$careStmt->bind_param('i', $appointmentId);
$careStmt->execute();
$care = $careStmt->get_result()->fetch_assoc();
$careStmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Details | Salvex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/appointment_details.css">
</head>
<body>
<div class="app-layout">
    <main class="main-content" style="padding: 30px; max-width: 1200px; margin: 0 auto;">
        <div class="page-header">
            <a href="dashboard.php?view=appointments" class="back-btn"><i class="fa-solid fa-arrow-left"></i> Back to Appointments</a>
            <h2>Consultation Record</h2>
            <span class="status-badge status-<?= strtolower($appointment['status']) ?>"><?= htmlspecialchars($appointment['status']) ?></span>
        </div>

        <div class="info-cards-grid">
            <div class="detail-card">
                <h3><i class="fa-solid fa-user"></i> Patient Information</h3>
                <p><strong>Name:</strong> <?= htmlspecialchars($appointment['patient_name'] ?: 'Self') ?></p>
                <p><strong>Age:</strong> <?= htmlspecialchars((string) ($appointment['patient_age'] ?: 'N/A')) ?> years</p>
            </div>

            <div class="detail-card">
                <h3><i class="fa-solid fa-user-doctor"></i> Appointment Information</h3>
                <p><strong>Doctor:</strong> <?= htmlspecialchars($doctorName) ?></p>
                <p><strong>Specialty:</strong> <?= htmlspecialchars($specialty) ?></p>
                <p><strong>Hospital:</strong> <?= htmlspecialchars($hospitalName) ?></p>
                <p><strong>Date & Time:</strong> <?= date('d M Y', strtotime($appointment['appointment_date'])) ?> at <?= htmlspecialchars($appointment['appointment_time']) ?></p>
            </div>
        </div>

        <div class="main-details-grid">
            <div class="left-col">
                <div class="detail-card">
                    <h3><i class="fa-solid fa-clipboard-question"></i> Reason for Visit</h3>
                    <div class="data-group">
                        <label>Primary Complaint:</label>
                        <p><?= htmlspecialchars($reasonForVisit) ?></p>
                    </div>
                    <div class="data-group">
                        <label>Reported Symptoms:</label>
                        <p><?= htmlspecialchars($appointment['symptoms'] ?: 'Not specified') ?></p>
                    </div>
                    <div class="data-group">
                        <label>Diagnosis:</label>
                        <p><?= htmlspecialchars($diagnosis) ?></p>
                    </div>
                    <div class="data-group">
                        <label>Doctor Notes:</label>
                        <p><?= nl2br(htmlspecialchars($doctorNotes)) ?></p>
                    </div>
                </div>

                <?php if ($soap): ?>
                <div class="detail-card soap-card">
                    <h3><i class="fa-solid fa-notes-medical"></i> Consultation Summary (SOAP)</h3>
                    <div class="soap-grid">
                        <div class="soap-item">
                            <span class="soap-letter s">S</span>
                            <div>
                                <strong>Subjective</strong>
                                <p><?= nl2br(htmlspecialchars($soap['subjective'])) ?></p>
                            </div>
                        </div>
                        <div class="soap-item">
                            <span class="soap-letter o">O</span>
                            <div>
                                <strong>Objective</strong>
                                <p><?= nl2br(htmlspecialchars($soap['objective'])) ?></p>
                            </div>
                        </div>
                        <div class="soap-item">
                            <span class="soap-letter a">A</span>
                            <div>
                                <strong>Assessment</strong>
                                <p><?= nl2br(htmlspecialchars($soap['assessment'])) ?></p>
                            </div>
                        </div>
                        <div class="soap-item">
                            <span class="soap-letter p">P</span>
                            <div>
                                <strong>Plan</strong>
                                <p><?= nl2br(htmlspecialchars($soap['plan'])) ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="right-col">
                <div class="detail-card">
                    <h3><i class="fa-solid fa-pills"></i> Prescribed Medicines</h3>
                    <?php if ($prescriptions->num_rows > 0): ?>
                        <ul class="med-list">
                            <?php while ($med = $prescriptions->fetch_assoc()): ?>
                            <li>
                                <div class="med-name"><?= htmlspecialchars($med['medicine_name']) ?></div>
                                <div class="med-meta">
                                    <span><i class="fa-solid fa-clock"></i> <?= htmlspecialchars($med['dosage']) ?></span>
                                    <span><i class="fa-solid fa-calendar-days"></i> <?= htmlspecialchars($med['duration']) ?></span>
                                </div>
                                <?php if (!empty($med['instructions'])): ?>
                                    <div class="med-instructions">Note: <?= htmlspecialchars($med['instructions']) ?></div>
                                <?php endif; ?>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="empty-text">No medicines prescribed.</p>
                    <?php endif; ?>
                </div>

                <div class="detail-card">
                    <h3><i class="fa-solid fa-vial"></i> Recommended Tests</h3>
                    <?php if ($tests->num_rows > 0): ?>
                        <ul class="test-list">
                            <?php while ($test = $tests->fetch_assoc()): ?>
                            <li>
                                <span><?= htmlspecialchars($test['test_name']) ?></span>
                                <span class="test-badge <?= strtolower($test['status']) ?>"><?= htmlspecialchars($test['status']) ?></span>
                                <?php if (!empty($test['recommended_notes'])): ?>
                                    <span style="display:block;color:#64748b;font-size:12px;margin-top:4px;"><?= htmlspecialchars($test['recommended_notes']) ?></span>
                                <?php endif; ?>
                                <?php if ($test['report_file_url']): ?>
                                    <a href="<?= htmlspecialchars($test['report_file_url']) ?>" target="_blank" class="btn-view-report"><i class="fa-solid fa-file-pdf"></i> View</a>
                                <?php endif; ?>
                            </li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <p class="empty-text">No tests recommended.</p>
                    <?php endif; ?>
                </div>

                <?php if ($care): ?>
                <div class="detail-card">
                    <h3><i class="fa-solid fa-heart-pulse"></i> Care Instructions</h3>
                    <?php if (!empty($care['lifestyle_advice'])): ?>
                        <div class="care-item"><strong>Lifestyle Advice:</strong> <p><?= htmlspecialchars($care['lifestyle_advice']) ?></p></div>
                    <?php endif; ?>
                    <?php if (!empty($care['food_restrictions'])): ?>
                        <div class="care-item"><strong>Food Restrictions:</strong> <p><?= htmlspecialchars($care['food_restrictions']) ?></p></div>
                    <?php endif; ?>
                    <?php if (!empty($care['home_remedies'])): ?>
                        <div class="care-item"><strong>Home Remedies:</strong> <p><?= htmlspecialchars($care['home_remedies']) ?></p></div>
                    <?php endif; ?>
                    <?php if (!empty($care['precautions'])): ?>
                        <div class="care-item warning"><strong>Precautions:</strong> <p><?= htmlspecialchars($care['precautions']) ?></p></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>
</div>
</body>
</html>
