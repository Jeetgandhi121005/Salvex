<?php
include 'includes/db.php';

header('Content-Type: application/json');

$doctorId = (int) ($_GET['doctor_id'] ?? 0);
$date = trim((string) ($_GET['date'] ?? ''));

if ($doctorId <= 0 || $date === '') {
    echo json_encode([
        'status' => 'error',
        'message' => 'Doctor and date are required.',
        'slots' => [],
    ]);
    exit();
}

$stmt = $conn->prepare(
    "SELECT appointment_time
     FROM appointments
     WHERE doctor_id = ?
       AND appointment_date = ?
       AND status IN ('Pending', 'Confirmed', 'Completed')"
);
$stmt->bind_param('is', $doctorId, $date);
$stmt->execute();
$result = $stmt->get_result();

$slots = [];
while ($row = $result->fetch_assoc()) {
    $slots[] = trim((string) $row['appointment_time']);
}

$stmt->close();

echo json_encode([
    'status' => 'success',
    'slots' => $slots,
]);
?>
