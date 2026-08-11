<?php
session_start();
require_once __DIR__ . '/includes/db.php';

if (!isset($_SESSION['doctor_id'])) {
    header('Location: signin.php');
    exit();
}

$doctorId = (int) $_SESSION['doctor_id'];
$appointmentId = (int) ($_GET['appointment_id'] ?? $_POST['appointment_id'] ?? 0);

if ($appointmentId <= 0) {
    http_response_code(400);
    exit('Invalid appointment selected.');
}

function h(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

$message = '';
$messageType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reasonForVisit = trim((string) ($_POST['reason_for_visit'] ?? ''));
    $diagnosis = trim((string) ($_POST['diagnosis'] ?? ''));
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($reasonForVisit === '') {
        $message = 'Reason for visit is required.';
        $messageType = 'error';
    } else {
        $ownerStmt = $conn->prepare(
            'SELECT id, user_id, doctor_id FROM appointments WHERE id = ? AND doctor_id = ? LIMIT 1'
        );
        $ownerStmt->bind_param('ii', $appointmentId, $doctorId);
        $ownerStmt->execute();
        $ownedAppointment = $ownerStmt->get_result()->fetch_assoc();
        $ownerStmt->close();

        if (!$ownedAppointment) {
            http_response_code(403);
            exit('Appointment not found for this doctor.');
        }

        $patientId = (int) $ownedAppointment['user_id'];
        $medicineIds = $_POST['medicine_id'] ?? [];
        $customMedicines = $_POST['custom_medicine'] ?? [];
        $dosages = $_POST['dosage'] ?? [];
        $durations = $_POST['duration'] ?? [];
        $instructions = $_POST['instructions'] ?? [];
        $testNames = $_POST['test_name'] ?? [];
        $testNotes = $_POST['test_notes'] ?? [];

        mysqli_begin_transaction($conn);

        try {
            $consultationStmt = $conn->prepare(
                "INSERT INTO consultations (appointment_id, doctor_id, patient_id, reason_for_visit, diagnosis, notes)
                 VALUES (?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                    doctor_id = VALUES(doctor_id),
                    patient_id = VALUES(patient_id),
                    reason_for_visit = VALUES(reason_for_visit),
                    diagnosis = VALUES(diagnosis),
                    notes = VALUES(notes)"
            );
            $consultationStmt->bind_param(
                'iiisss',
                $appointmentId,
                $doctorId,
                $patientId,
                $reasonForVisit,
                $diagnosis,
                $notes
            );
            $consultationStmt->execute();
            $consultationId = (int) ($consultationStmt->insert_id ?: 0);
            $consultationStmt->close();

            if ($consultationId <= 0) {
                $lookupStmt = $conn->prepare('SELECT id FROM consultations WHERE appointment_id = ? LIMIT 1');
                $lookupStmt->bind_param('i', $appointmentId);
                $lookupStmt->execute();
                $consultationId = (int) (($lookupStmt->get_result()->fetch_assoc()['id'] ?? 0));
                $lookupStmt->close();
            }

            $updateAppointmentStmt = $conn->prepare(
                'UPDATE appointments
                 SET reason_for_visit = ?, notes = ?, status = CASE WHEN status = "Pending" THEN "Confirmed" ELSE status END
                 WHERE id = ? AND doctor_id = ?'
            );
            $updateAppointmentStmt->bind_param('ssii', $reasonForVisit, $notes, $appointmentId, $doctorId);
            $updateAppointmentStmt->execute();
            $updateAppointmentStmt->close();

            $clearPrescriptionsStmt = $conn->prepare('DELETE FROM prescriptions WHERE appointment_id = ?');
            $clearPrescriptionsStmt->bind_param('i', $appointmentId);
            $clearPrescriptionsStmt->execute();
            $clearPrescriptionsStmt->close();

            $insertPrescriptionStmt = $conn->prepare(
                'INSERT INTO prescriptions
                    (appointment_id, consultation_id, doctor_id, patient_id, medicine_id, custom_entry, medicine_name, dosage, duration, instructions)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );

            foreach ($medicineIds as $index => $medicineIdRaw) {
                $medicineId = (int) $medicineIdRaw;
                $customMedicine = trim((string) ($customMedicines[$index] ?? ''));
                $dosage = trim((string) ($dosages[$index] ?? ''));
                $duration = trim((string) ($durations[$index] ?? ''));
                $instruction = trim((string) ($instructions[$index] ?? ''));

                if ($medicineId <= 0 && $customMedicine === '') {
                    continue;
                }

                $medicineName = $customMedicine;
                $customEntry = 1;

                if ($medicineId > 0) {
                    $medicineLookupStmt = $conn->prepare('SELECT name FROM medicines WHERE id = ? LIMIT 1');
                    $medicineLookupStmt->bind_param('i', $medicineId);
                    $medicineLookupStmt->execute();
                    $lookupName = (string) (($medicineLookupStmt->get_result()->fetch_assoc()['name'] ?? ''));
                    $medicineLookupStmt->close();
                    $medicineName = $customMedicine !== '' ? $customMedicine : $lookupName;
                    $customEntry = $customMedicine !== '' ? 1 : 0;
                }

                $medicineIdOrNull = $medicineId > 0 ? $medicineId : null;
                $insertPrescriptionStmt->bind_param(
                    'iiiiiissss',
                    $appointmentId,
                    $consultationId,
                    $doctorId,
                    $patientId,
                    $medicineIdOrNull,
                    $customEntry,
                    $medicineName,
                    $dosage,
                    $duration,
                    $instruction
                );
                $insertPrescriptionStmt->execute();
            }
            $insertPrescriptionStmt->close();

            $clearTestsStmt = $conn->prepare('DELETE FROM medical_tests WHERE appointment_id = ?');
            $clearTestsStmt->bind_param('i', $appointmentId);
            $clearTestsStmt->execute();
            $clearTestsStmt->close();

            $insertTestStmt = $conn->prepare(
                'INSERT INTO medical_tests
                    (appointment_id, consultation_id, doctor_id, patient_id, test_name, recommended_notes, status)
                 VALUES (?, ?, ?, ?, ?, ?, "Pending")'
            );

            foreach ($testNames as $index => $testNameRaw) {
                $testName = trim((string) $testNameRaw);
                $testNote = trim((string) ($testNotes[$index] ?? ''));

                if ($testName === '') {
                    continue;
                }

                $insertTestStmt->bind_param(
                    'iiiiss',
                    $appointmentId,
                    $consultationId,
                    $doctorId,
                    $patientId,
                    $testName,
                    $testNote
                );
                $insertTestStmt->execute();
            }
            $insertTestStmt->close();

            mysqli_commit($conn);
            $message = 'Consultation saved successfully.';
            $messageType = 'success';
        } catch (Throwable $exception) {
            mysqli_rollback($conn);
            $message = 'Could not save consultation: ' . $exception->getMessage();
            $messageType = 'error';
        }
    }
}

$appointmentStmt = $conn->prepare(
    "SELECT a.*, u.full_name AS user_full_name, u.email AS user_email, d.full_name AS doctor_full_name
     FROM appointments a
     INNER JOIN users u ON u.id = a.user_id
     INNER JOIN doctors d ON d.id = a.doctor_id
     WHERE a.id = ? AND a.doctor_id = ?
     LIMIT 1"
);
$appointmentStmt->bind_param('ii', $appointmentId, $doctorId);
$appointmentStmt->execute();
$appointment = $appointmentStmt->get_result()->fetch_assoc();
$appointmentStmt->close();

if (!$appointment) {
    http_response_code(404);
    exit('Appointment not found.');
}

$consultationStmt = $conn->prepare('SELECT * FROM consultations WHERE appointment_id = ? LIMIT 1');
$consultationStmt->bind_param('i', $appointmentId);
$consultationStmt->execute();
$consultation = $consultationStmt->get_result()->fetch_assoc() ?: [];
$consultationStmt->close();

$existingPrescriptions = [];
$prescriptionStmt = $conn->prepare('SELECT * FROM prescriptions WHERE appointment_id = ? ORDER BY id ASC');
$prescriptionStmt->bind_param('i', $appointmentId);
$prescriptionStmt->execute();
$prescriptionResult = $prescriptionStmt->get_result();
while ($row = $prescriptionResult->fetch_assoc()) {
    $existingPrescriptions[] = $row;
}
$prescriptionStmt->close();

$existingTests = [];
$testStmt = $conn->prepare('SELECT * FROM medical_tests WHERE appointment_id = ? ORDER BY id ASC');
$testStmt->bind_param('i', $appointmentId);
$testStmt->execute();
$testResult = $testStmt->get_result();
while ($row = $testResult->fetch_assoc()) {
    $existingTests[] = $row;
}
$testStmt->close();

$medicines = [];
$medicineResult = mysqli_query($conn, "SELECT id, name, generic_name, strength FROM medicines WHERE is_active = 1 ORDER BY name ASC");
while ($medicineResult && ($row = mysqli_fetch_assoc($medicineResult))) {
    $medicines[] = $row;
}

$reasonValue = (string) ($consultation['reason_for_visit'] ?? $appointment['reason_for_visit'] ?? '');
$diagnosisValue = (string) ($consultation['diagnosis'] ?? '');
$notesValue = (string) ($consultation['notes'] ?? $appointment['notes'] ?? '');

if (count($existingPrescriptions) === 0) {
    $existingPrescriptions[] = [
        'medicine_id' => '',
        'medicine_name' => '',
        'dosage' => '',
        'duration' => '',
        'instructions' => '',
        'custom_entry' => 0,
    ];
}

if (count($existingTests) === 0) {
    $existingTests[] = [
        'test_name' => '',
        'recommended_notes' => '',
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consultation Form | Salvex</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { font-family: 'Inter', sans-serif; margin: 0; background: #f8fafc; color: #0f172a; }
        .page { max-width: 1100px; margin: 0 auto; padding: 32px 20px 60px; }
        .topbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; gap: 12px; flex-wrap: wrap; }
        .back-link { color: #2563eb; text-decoration: none; font-weight: 700; }
        .headline { margin: 0; font-size: 30px; }
        .subhead { margin: 6px 0 0; color: #64748b; }
        .grid { display: grid; grid-template-columns: 1.1fr 2fr; gap: 24px; align-items: start; }
        .card { background: #fff; border: 1px solid #e2e8f0; border-radius: 18px; padding: 22px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04); }
        .card h3 { margin: 0 0 16px; font-size: 18px; }
        .meta-row { display: flex; justify-content: space-between; gap: 12px; padding: 12px 0; border-bottom: 1px solid #eef2f7; }
        .meta-row:last-child { border-bottom: none; padding-bottom: 0; }
        .meta-label { font-size: 12px; text-transform: uppercase; letter-spacing: 0.08em; color: #64748b; font-weight: 700; }
        .meta-value { font-weight: 600; text-align: right; }
        .status { display: inline-flex; align-items: center; gap: 8px; padding: 8px 12px; border-radius: 999px; font-size: 13px; font-weight: 700; background: #eff6ff; color: #1d4ed8; }
        .form-card { display: flex; flex-direction: column; gap: 22px; }
        .field-group { display: grid; gap: 8px; }
        .field-group label { font-weight: 700; font-size: 14px; }
        .field-group input, .field-group textarea, .field-group select { width: 100%; padding: 12px 14px; border: 1px solid #cbd5e1; border-radius: 12px; font: inherit; box-sizing: border-box; }
        .field-group textarea { min-height: 110px; resize: vertical; }
        .section-title { margin: 0 0 12px; font-size: 16px; font-weight: 800; }
        .repeat-list { display: grid; gap: 14px; }
        .repeat-card { border: 1px solid #dbeafe; background: #f8fbff; border-radius: 16px; padding: 16px; }
        .repeat-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 12px; }
        .repeat-grid.full { grid-template-columns: 1fr; }
        .repeat-actions { display: flex; justify-content: flex-end; margin-top: 12px; }
        .btn { border: 0; border-radius: 12px; padding: 12px 16px; font: inherit; font-weight: 700; cursor: pointer; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #0f172a; }
        .btn-danger { background: #fee2e2; color: #b91c1c; }
        .btn-row { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        .alert { padding: 14px 16px; border-radius: 14px; font-weight: 600; }
        .alert.success { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
        .alert.error { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
        .helper { font-size: 12px; color: #64748b; margin-top: 4px; }
        @media (max-width: 900px) { .grid { grid-template-columns: 1fr; } .repeat-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <div class="page">
        <div class="topbar">
            <div>
                <a class="back-link" href="dashboard.php"><i class="fa-solid fa-arrow-left"></i> Back to Doctor Dashboard</a>
                <h1 class="headline">Consultation Form</h1>
                <p class="subhead">Capture the consultation once and surface it everywhere the patient and admin need it.</p>
            </div>
            <div class="status"><i class="fa-solid fa-notes-medical"></i> Appointment #<?php echo (int) $appointment['id']; ?></div>
        </div>

        <?php if ($message !== ''): ?>
            <div class="alert <?php echo h($messageType); ?>" style="margin-bottom: 18px;"><?php echo h($message); ?></div>
        <?php endif; ?>

        <div class="grid">
            <aside class="card">
                <h3>Appointment Snapshot</h3>
                <div class="meta-row"><span class="meta-label">Patient</span><span class="meta-value"><?php echo h((string) ($appointment['patient_name'] ?: $appointment['user_full_name'])); ?></span></div>
                <div class="meta-row"><span class="meta-label">Age</span><span class="meta-value"><?php echo h((string) ($appointment['patient_age'] ?: 'N/A')); ?></span></div>
                <div class="meta-row"><span class="meta-label">Doctor</span><span class="meta-value"><?php echo h((string) $appointment['doctor_full_name']); ?></span></div>
                <div class="meta-row"><span class="meta-label">Schedule</span><span class="meta-value"><?php echo h((string) $appointment['appointment_date']); ?>, <?php echo h((string) $appointment['appointment_time']); ?></span></div>
                <div class="meta-row"><span class="meta-label">Status</span><span class="meta-value"><?php echo h((string) $appointment['status']); ?></span></div>
                <div class="meta-row"><span class="meta-label">Hospital</span><span class="meta-value"><?php echo h((string) ($appointment['hospital_name'] ?: 'Not provided')); ?></span></div>
            </aside>

            <form method="post" class="card form-card">
                <input type="hidden" name="appointment_id" value="<?php echo (int) $appointmentId; ?>">

                <div class="field-group">
                    <label for="reason_for_visit">Reason for Visit</label>
                    <textarea id="reason_for_visit" name="reason_for_visit" required><?php echo h($reasonValue); ?></textarea>
                </div>

                <div class="field-group">
                    <label for="diagnosis">Diagnosis</label>
                    <textarea id="diagnosis" name="diagnosis" placeholder="Optional but recommended"><?php echo h($diagnosisValue); ?></textarea>
                </div>

                <section>
                    <h2 class="section-title">Prescribed Medicines</h2>
                    <div class="repeat-list" id="medicine-list">
                        <?php foreach ($existingPrescriptions as $index => $prescription): ?>
                            <div class="repeat-card medicine-row">
                                <div class="repeat-grid">
                                    <div class="field-group">
                                        <label>Medicine Database</label>
                                        <select name="medicine_id[]">
                                            <option value="">Select from medicine list</option>
                                            <?php foreach ($medicines as $medicine): ?>
                                                <option value="<?php echo (int) $medicine['id']; ?>" <?php echo ((int) ($prescription['medicine_id'] ?? 0) === (int) $medicine['id']) ? 'selected' : ''; ?>>
                                                    <?php echo h((string) $medicine['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="field-group">
                                        <label>Custom Entry</label>
                                        <input type="text" name="custom_medicine[]" value="<?php echo h((string) ((int) ($prescription['custom_entry'] ?? 0) === 1 ? ($prescription['medicine_name'] ?? '') : '')); ?>" placeholder="Type custom medicine if not in the list">
                                    </div>
                                    <div class="field-group">
                                        <label>Dosage</label>
                                        <input type="text" name="dosage[]" value="<?php echo h((string) ($prescription['dosage'] ?? '')); ?>" placeholder="e.g. 1 tablet after meals">
                                    </div>
                                    <div class="field-group">
                                        <label>Duration</label>
                                        <input type="text" name="duration[]" value="<?php echo h((string) ($prescription['duration'] ?? '')); ?>" placeholder="e.g. 5 days">
                                    </div>
                                </div>
                                <div class="repeat-grid full">
                                    <div class="field-group">
                                        <label>Instructions</label>
                                        <textarea name="instructions[]" style="min-height:80px;"><?php echo h((string) ($prescription['instructions'] ?? '')); ?></textarea>
                                    </div>
                                </div>
                                <div class="repeat-actions">
                                    <button class="btn btn-danger" type="button" onclick="removeRow(this)">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="helper">Pick from the standard medicine database or enter a custom medication manually.</div>
                    <button type="button" class="btn btn-secondary" style="margin-top: 12px;" onclick="addMedicineRow()">Add Medicine</button>
                </section>

                <section>
                    <h2 class="section-title">Recommended Tests</h2>
                    <div class="repeat-list" id="test-list">
                        <?php foreach ($existingTests as $test): ?>
                            <div class="repeat-card test-row">
                                <div class="repeat-grid">
                                    <div class="field-group">
                                        <label>Test Name</label>
                                        <input type="text" name="test_name[]" value="<?php echo h((string) ($test['test_name'] ?? '')); ?>" placeholder="e.g. CBC, X-Ray Chest">
                                    </div>
                                    <div class="field-group">
                                        <label>Notes</label>
                                        <input type="text" name="test_notes[]" value="<?php echo h((string) ($test['recommended_notes'] ?? '')); ?>" placeholder="Optional notes for the patient">
                                    </div>
                                </div>
                                <div class="repeat-actions">
                                    <button class="btn btn-danger" type="button" onclick="removeRow(this)">Remove</button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="button" class="btn btn-secondary" style="margin-top: 12px;" onclick="addTestRow()">Add Test</button>
                </section>

                <div class="field-group">
                    <label for="notes">Consultation Notes</label>
                    <textarea id="notes" name="notes"><?php echo h($notesValue); ?></textarea>
                </div>

                <div class="btn-row">
                    <a class="back-link" href="../patient/appointment_details.php?id=<?php echo (int) $appointmentId; ?>" target="_blank">Preview patient-facing view</a>
                    <button type="submit" class="btn btn-primary">Save Consultation</button>
                </div>
            </form>
        </div>
    </div>

    <template id="medicine-row-template">
        <div class="repeat-card medicine-row">
            <div class="repeat-grid">
                <div class="field-group">
                    <label>Medicine Database</label>
                    <select name="medicine_id[]">
                        <option value="">Select from medicine list</option>
                        <?php foreach ($medicines as $medicine): ?>
                            <option value="<?php echo (int) $medicine['id']; ?>"><?php echo h((string) $medicine['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="field-group">
                    <label>Custom Entry</label>
                    <input type="text" name="custom_medicine[]" placeholder="Type custom medicine if not in the list">
                </div>
                <div class="field-group">
                    <label>Dosage</label>
                    <input type="text" name="dosage[]" placeholder="e.g. 1 tablet after meals">
                </div>
                <div class="field-group">
                    <label>Duration</label>
                    <input type="text" name="duration[]" placeholder="e.g. 5 days">
                </div>
            </div>
            <div class="repeat-grid full">
                <div class="field-group">
                    <label>Instructions</label>
                    <textarea name="instructions[]" style="min-height:80px;"></textarea>
                </div>
            </div>
            <div class="repeat-actions">
                <button class="btn btn-danger" type="button" onclick="removeRow(this)">Remove</button>
            </div>
        </div>
    </template>

    <template id="test-row-template">
        <div class="repeat-card test-row">
            <div class="repeat-grid">
                <div class="field-group">
                    <label>Test Name</label>
                    <input type="text" name="test_name[]" placeholder="e.g. CBC, X-Ray Chest">
                </div>
                <div class="field-group">
                    <label>Notes</label>
                    <input type="text" name="test_notes[]" placeholder="Optional notes for the patient">
                </div>
            </div>
            <div class="repeat-actions">
                <button class="btn btn-danger" type="button" onclick="removeRow(this)">Remove</button>
            </div>
        </div>
    </template>

    <script>
        function addMedicineRow() {
            const template = document.getElementById('medicine-row-template');
            document.getElementById('medicine-list').appendChild(template.content.cloneNode(true));
        }

        function addTestRow() {
            const template = document.getElementById('test-row-template');
            document.getElementById('test-list').appendChild(template.content.cloneNode(true));
        }

        function removeRow(button) {
            const card = button.closest('.repeat-card');
            const list = card && card.parentElement;
            if (!card || !list) return;

            if (list.children.length === 1) {
                card.querySelectorAll('input, textarea, select').forEach(function (field) {
                    field.value = '';
                });
                return;
            }

            card.remove();
        }
    </script>
</body>
</html>
