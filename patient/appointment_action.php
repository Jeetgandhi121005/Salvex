<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Something went wrong.'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit();
}

$action = $_POST['action'] ?? '';
$appointmentId = (int) ($_POST['appointment_id'] ?? 0);
$userId = (int) $_SESSION['user_id'];

if ($appointmentId <= 0) {
    $response['message'] = 'Invalid appointment selected.';
    echo json_encode($response);
    exit();
}

if ($action === 'cancel') {
    $stmt = $conn->prepare("UPDATE appointments SET status = 'Cancelled' WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $appointmentId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $response = ['status' => 'success', 'message' => 'Appointment cancelled successfully.'];
    } else {
        $response['message'] = 'Appointment could not be cancelled.';
    }

    $stmt->close();
    echo json_encode($response);
    exit();
}

if ($action === 'reschedule') {
    $newDate = trim((string) ($_POST['new_date'] ?? ''));
    $newTime = trim((string) ($_POST['new_time'] ?? ''));

    if ($newDate === '' || $newTime === '') {
        $response['message'] = 'Please select a new date and time.';
        echo json_encode($response);
        exit();
    }

    $checkStmt = $conn->prepare("SELECT doctor_id FROM appointments WHERE id = ? AND user_id = ? LIMIT 1");
    $checkStmt->bind_param('ii', $appointmentId, $userId);
    $checkStmt->execute();
    $appt = $checkStmt->get_result()->fetch_assoc();
    $checkStmt->close();

    if (!$appt) {
        $response['message'] = 'Appointment not found.';
        echo json_encode($response);
        exit();
    }

    $doctorId = (int) ($appt['doctor_id'] ?? 0);
    if ($doctorId > 0) {
        $slotStmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND id != ? AND status IN ('Pending', 'Confirmed') LIMIT 1");
        $slotStmt->bind_param('issi', $doctorId, $newDate, $newTime, $appointmentId);
        $slotStmt->execute();
        $taken = $slotStmt->get_result()->fetch_assoc();
        $slotStmt->close();

        if ($taken) {
            $response['message'] = 'That slot is already booked. Please pick another time.';
            echo json_encode($response);
            exit();
        }
    }

    $updateStmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'Pending' WHERE id = ? AND user_id = ?");
    $updateStmt->bind_param('ssii', $newDate, $newTime, $appointmentId, $userId);
    $updateStmt->execute();

    if ($updateStmt->affected_rows > 0) {
        $response = ['status' => 'success', 'message' => 'Appointment rescheduled successfully.'];
    } else {
        $response['message'] = 'Appointment could not be rescheduled.';
    }

    $updateStmt->close();
}

echo json_encode($response);
?>
