<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

function respond($status, $message, $extra = [])
{
    echo json_encode(array_merge([
        'status' => $status,
        'message' => $message,
    ], $extra));
    exit();
}

function normalize_member_payload()
{
    $member_name = trim($_POST['member_name'] ?? '');
    $relation = trim($_POST['relation'] ?? '');
    $member_age = trim($_POST['member_age'] ?? '');
    $dob = trim($_POST['dob'] ?? '');

    if ($member_name === '' || $relation === '' || $member_age === '' || $dob === '') {
        respond('error', 'All family member fields are required.');
    }

    if (!preg_match('/^\d+$/', $member_age)) {
        respond('error', 'Age must be a valid number.');
    }

    $age = (int) $member_age;
    if ($age < 1 || $age > 120) {
        respond('error', 'Age must be between 1 and 120.');
    }

    if (!preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dob, $matches)) {
        respond('error', 'Date of birth must be in DD/MM/YYYY format.');
    }

    $day = (int) $matches[1];
    $month = (int) $matches[2];
    $year = (int) $matches[3];

    if (!checkdate($month, $day, $year)) {
        respond('error', 'Please enter a valid date of birth.');
    }

    return [
        'member_name' => $member_name,
        'relation' => $relation,
        'member_age' => $age,
        'dob' => sprintf('%02d/%02d/%04d', $day, $month, $year),
    ];
}

if (!isset($_SESSION['user_id'])) {
    respond('error', 'Unauthorized access.');
}

if (!$conn) {
    respond('error', 'Database connection failed.');
}

$patient_id = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_REQUEST['action'] ?? 'list';

if ($method === 'GET' && $action === 'list') {
    $stmt = mysqli_prepare(
        $conn,
        'SELECT id, patient_id, member_name, relation, member_age, dob, created_at
         FROM family_members
         WHERE patient_id = ?
         ORDER BY created_at DESC, id DESC'
    );

    if (!$stmt) {
        respond('error', 'Could not load family members.');
    }

    mysqli_stmt_bind_param($stmt, 'i', $patient_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    $members = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $members[] = $row;
    }

    mysqli_stmt_close($stmt);
    respond('success', 'Family members loaded.', ['members' => $members]);
}

if ($method !== 'POST') {
    respond('error', 'Unsupported request method.');
}

if ($action === 'create') {
    $payload = normalize_member_payload();
    $stmt = mysqli_prepare(
        $conn,
        'INSERT INTO family_members (patient_id, member_name, relation, member_age, dob)
         VALUES (?, ?, ?, ?, ?)'
    );

    if (!$stmt) {
        respond('error', 'Could not save family member.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'issis',
        $patient_id,
        $payload['member_name'],
        $payload['relation'],
        $payload['member_age'],
        $payload['dob']
    );

    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        respond('error', 'Could not save family member.');
    }

    $member_id = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    respond('success', 'Profile added successfully.', [
        'member' => [
            'id' => $member_id,
            'patient_id' => $patient_id,
            'member_name' => $payload['member_name'],
            'relation' => $payload['relation'],
            'member_age' => $payload['member_age'],
            'dob' => $payload['dob'],
        ],
    ]);
}

if ($action === 'update') {
    $member_id = (int) ($_POST['member_id'] ?? 0);
    if ($member_id <= 0) {
        respond('error', 'Invalid family member selected.');
    }

    $payload = normalize_member_payload();
    $stmt = mysqli_prepare(
        $conn,
        'UPDATE family_members
         SET member_name = ?, relation = ?, member_age = ?, dob = ?
         WHERE id = ? AND patient_id = ?'
    );

    if (!$stmt) {
        respond('error', 'Could not update family member.');
    }

    mysqli_stmt_bind_param(
        $stmt,
        'ssisii',
        $payload['member_name'],
        $payload['relation'],
        $payload['member_age'],
        $payload['dob'],
        $member_id,
        $patient_id
    );

    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected < 0) {
        respond('error', 'Could not update family member.');
    }

    respond('success', 'Profile updated successfully.', [
        'member' => [
            'id' => $member_id,
            'patient_id' => $patient_id,
            'member_name' => $payload['member_name'],
            'relation' => $payload['relation'],
            'member_age' => $payload['member_age'],
            'dob' => $payload['dob'],
        ],
    ]);
}

if ($action === 'delete') {
    $member_id = (int) ($_POST['member_id'] ?? 0);
    if ($member_id <= 0) {
        respond('error', 'Invalid family member selected.');
    }

    $stmt = mysqli_prepare(
        $conn,
        'DELETE FROM family_members WHERE id = ? AND patient_id = ?'
    );

    if (!$stmt) {
        respond('error', 'Could not delete family member.');
    }

    mysqli_stmt_bind_param($stmt, 'ii', $member_id, $patient_id);
    mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    if ($affected < 1) {
        respond('error', 'Family member not found or already deleted.');
    }

    respond('success', 'Profile deleted successfully.');
}

respond('error', 'Invalid action requested.');
?>
