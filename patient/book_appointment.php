<?php
session_start();
include 'includes/db.php';
require_once __DIR__ . '/../shared/billing_sync.php';

header('Content-Type: application/json');

$response = ['status' => 'error', 'message' => 'Something went wrong.'];
salvex_sync_billing_status($conn);

function generateInvoiceNo(mysqli $conn): string
{
    do {
        $invoice = 'INV' . date('ymd') . strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));
        $safeInvoice = mysqli_real_escape_string($conn, $invoice);
        $check = mysqli_query($conn, "SELECT id FROM billing WHERE invoice_no = '{$safeInvoice}' LIMIT 1");
    } while ($check && mysqli_num_rows($check) > 0);

    return $invoice;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_SESSION['user_id'])) {
    echo json_encode($response);
    exit();
}

$userId = (int) $_SESSION['user_id'];
$doctorId = (int) ($_POST['doctor_id'] ?? 0);
$doctorName = trim((string) ($_POST['doctor_name'] ?? ''));
$hospitalName = trim((string) ($_POST['hospital_name'] ?? ''));
$specialty = trim((string) ($_POST['specialty'] ?? ''));
$date = trim((string) ($_POST['date'] ?? ''));
$time = trim((string) ($_POST['time'] ?? ''));
$patientName = trim((string) ($_POST['patient_name'] ?? ''));
$patientAge = (int) ($_POST['patient_age'] ?? 0);
$consultationFee = (float) ($_POST['consultation_fee'] ?? 0);
$reasonForVisit = trim((string) ($_POST['reason_for_visit'] ?? ''));
$symptoms = trim((string) ($_POST['symptoms'] ?? ''));

if ($doctorId <= 0 || $doctorName === '' || $date === '' || $time === '' || $patientName === '') {
    $response['message'] = 'Please select a doctor, patient profile, date, and time slot before booking.';
    echo json_encode($response);
    exit();
}

$doctorStmt = $conn->prepare("SELECT id, full_name, specialty, hospital, consultation_fee, is_active, status FROM doctors WHERE id = ? LIMIT 1");
$doctorStmt->bind_param('i', $doctorId);
$doctorStmt->execute();
$doctor = $doctorStmt->get_result()->fetch_assoc();
$doctorStmt->close();

if (!$doctor || (int) $doctor['is_active'] !== 1 || ($doctor['status'] ?? 'available') !== 'available') {
    $response['message'] = 'This doctor is not available for booking right now.';
    echo json_encode($response);
    exit();
}

$doctorName = $doctor['full_name'];
$specialty = $doctor['specialty'] ?: $specialty;
$hospitalName = $doctor['hospital'] ?: $hospitalName;
$consultationFee = $doctor['consultation_fee'] !== null ? (float) $doctor['consultation_fee'] : $consultationFee;
$platformFee = 49.0;
$billingAmount = $consultationFee + $platformFee;

$slotStmt = $conn->prepare("SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('Pending', 'Confirmed') LIMIT 1");
$slotStmt->bind_param('iss', $doctorId, $date, $time);
$slotStmt->execute();
$slotTaken = $slotStmt->get_result()->fetch_assoc();
$slotStmt->close();

if ($slotTaken) {
    $response['message'] = 'This time slot has already been booked. Please choose another slot.';
    echo json_encode($response);
    exit();
}

mysqli_begin_transaction($conn);

try {
    $insertAppt = $conn->prepare(
        "INSERT INTO appointments (
            user_id, patient_name, patient_age, appointment_date, appointment_time, status,
            reason_for_visit, symptoms, doctor_id, doctor_name, hospital_name, specialty
        ) VALUES (?, ?, ?, ?, ?, 'Pending', ?, ?, ?, ?, ?, ?)"
    );
    $insertAppt->bind_param(
        'isissssisss',
        $userId,
        $patientName,
        $patientAge,
        $date,
        $time,
        $reasonForVisit,
        $symptoms,
        $doctorId,
        $doctorName,
        $hospitalName,
        $specialty
    );
    $insertAppt->execute();
    $appointmentId = $insertAppt->insert_id;
    $insertAppt->close();

    $invoiceNo = generateInvoiceNo($conn);
    $billingDate = date('Y-m-d');

    $insertBill = $conn->prepare(
        "INSERT INTO billing (
            user_id, appointment_id, invoice_no, doctor_name, patient_name, patient_age,
            amount, billing_date, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Unpaid')"
    );
    $insertBill->bind_param(
        'iisssids',
        $userId,
        $appointmentId,
        $invoiceNo,
        $doctorName,
        $patientName,
        $patientAge,
        $billingAmount,
        $billingDate
    );
    $insertBill->execute();
    $insertBill->close();

    mysqli_commit($conn);

    $response = [
        'status' => 'success',
        'message' => 'Appointment booked successfully.',
        'appointment_id' => $appointmentId,
        'invoice_no' => $invoiceNo,
        'billing_amount' => $billingAmount,
        'billing_date' => $billingDate,
    ];
} catch (Throwable $error) {
    mysqli_rollback($conn);
    $response['message'] = 'Booking failed: ' . $error->getMessage();
}

echo json_encode($response);
?>
