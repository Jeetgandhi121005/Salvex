<?php
session_start();
include __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';

if ($action === 'toggle_doctor') {
    $doctorId = (int) ($_POST['doctor_id'] ?? 0);
    $isActive = (int) ($_POST['is_active'] ?? 0);
    $doctorStatus = $isActive === 1 ? 'available' : 'offline';

    $stmt = $conn->prepare("UPDATE doctors SET is_active = ?, status = ? WHERE id = ?");
    $stmt->bind_param('isi', $isActive, $doctorStatus, $doctorId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'toggle_hospital') {
    $hospitalId = (int) ($_POST['hospital_id'] ?? 0);
    $isActive = (int) ($_POST['is_active'] ?? 0);

    $stmt = $conn->prepare("UPDATE hospitals SET is_active = ? WHERE id = ?");
    $stmt->bind_param('ii', $isActive, $hospitalId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'update_appointment') {
    $appointmentId = (int) ($_POST['appointment_id'] ?? 0);
    $newStatus = trim((string) ($_POST['new_status'] ?? ''));
    $allowed = ['Pending', 'Confirmed', 'Completed', 'Cancelled'];

    if (!in_array($newStatus, $allowed, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid appointment status']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE appointments SET status = ? WHERE id = ?");
    $stmt->bind_param('si', $newStatus, $appointmentId);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success']);
    exit;
}

if ($action === 'save_profile') {
    $adminId = (int) $_SESSION['admin_id'];
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));

    if ($name === '' || $email === '') {
        echo json_encode(['status' => 'error', 'message' => 'Name and email are required.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE admin_users SET full_name = ?, email = ? WHERE id = ?");
    $stmt->bind_param('ssi', $name, $email, $adminId);
    $stmt->execute();
    $stmt->close();

    $_SESSION['admin_name'] = $name;
    $_SESSION['admin_email'] = $email;

    echo json_encode(['status' => 'success']);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
