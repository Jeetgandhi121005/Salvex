<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

$doctorId = (int) ($_SESSION['doctor_id'] ?? 0);
$action = $_POST['action'] ?? '';

if ($doctorId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

if ($action === 'update_doctor_status') {
    $newStatus = trim((string) ($_POST['new_status'] ?? ''));
    $allowed = ['available', 'busy', 'offline'];

    if (!in_array($newStatus, $allowed, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid doctor status']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE doctors SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $doctorId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['doctor_status'] = $newStatus;
    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'update_appointment') {
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $newStatus = trim((string) ($_POST['new_status'] ?? ''));
    $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

    if ($appointmentId <= 0 || !in_array($newStatus, $allowed, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid appointment update']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param('sii', $newStatus, $appointmentId, $doctorId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Appointment not found for this doctor']);
    }

    $stmt->close();
    exit;
}

if ($action === 'save_notes') {
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $notes = trim((string) ($_POST['notes'] ?? ''));

    if ($appointmentId <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid appointment']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE appointments SET notes = ? WHERE id = ? AND doctor_id = ?");
    $stmt->bind_param('sii', $notes, $appointmentId, $doctorId);
    $stmt->execute();

    if ($stmt->affected_rows >= 0) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Notes could not be saved']);
    }

    $stmt->close();
    exit;
}

if ($action === 'save_full_consultation') {
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $soap = json_decode($_POST['soap'] ?? '[]', true) ?: [];
    $meds = json_decode($_POST['medicines'] ?? '[]', true) ?: [];
    $tests = json_decode($_POST['tests'] ?? '[]', true) ?: [];

    $ownerStmt = $conn->prepare("SELECT id FROM appointments WHERE id = ? AND doctor_id = ? LIMIT 1");
    $ownerStmt->bind_param('ii', $appointmentId, $doctorId);
    $ownerStmt->execute();
    $owned = $ownerStmt->get_result()->fetch_assoc();
    $ownerStmt->close();

    if (!$owned) {
        echo json_encode(['status' => 'error', 'message' => 'Appointment not found for this doctor']);
        exit;
    }

    mysqli_begin_transaction($conn);

    try {
        $soapStmt = $conn->prepare(
            "INSERT INTO soap_notes (appointment_id, subjective, objective, assessment, plan)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
             subjective = VALUES(subjective),
             objective = VALUES(objective),
             assessment = VALUES(assessment),
             plan = VALUES(plan)"
        );
        $soapStmt->bind_param(
            'issss',
            $appointmentId,
            $soap['s'],
            $soap['o'],
            $soap['a'],
            $soap['p']
        );
        $soapStmt->execute();
        $soapStmt->close();

        $deleteMeds = $conn->prepare("DELETE FROM prescriptions WHERE appointment_id = ?");
        $deleteMeds->bind_param('i', $appointmentId);
        $deleteMeds->execute();
        $deleteMeds->close();

        foreach ($meds as $medicine) {
            if (empty($medicine['name'])) {
                continue;
            }

            $medStmt = $conn->prepare(
                "INSERT INTO prescriptions (appointment_id, medicine_name, dosage, duration, instructions)
                 VALUES (?, ?, ?, ?, ?)"
            );
            $instructions = $medicine['instructions'] ?? '';
            $medStmt->bind_param(
                'issss',
                $appointmentId,
                $medicine['name'],
                $medicine['dosage'],
                $medicine['duration'],
                $instructions
            );
            $medStmt->execute();
            $medStmt->close();
        }

        $deleteTests = $conn->prepare("DELETE FROM medical_tests WHERE appointment_id = ?");
        $deleteTests->bind_param('i', $appointmentId);
        $deleteTests->execute();
        $deleteTests->close();

        foreach ($tests as $test) {
            if (empty($test['name'])) {
                continue;
            }

            $testStmt = $conn->prepare("INSERT INTO medical_tests (appointment_id, test_name, status) VALUES (?, ?, 'Pending')");
            $testStmt->bind_param('is', $appointmentId, $test['name']);
            $testStmt->execute();
            $testStmt->close();
        }

        mysqli_commit($conn);
        echo json_encode(['status' => 'success']);
    } catch (Throwable $error) {
        mysqli_rollback($conn);
        echo json_encode(['status' => 'error', 'message' => $error->getMessage()]);
    }

    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
