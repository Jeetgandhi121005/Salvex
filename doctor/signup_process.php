<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name      = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone          = mysqli_real_escape_string($conn, $_POST['phone'] ?? '');
    $email          = mysqli_real_escape_string($conn, $_POST['email']);
    $specialty      = mysqli_real_escape_string($conn, $_POST['specialty']);
    $hospital       = mysqli_real_escape_string($conn, $_POST['hospital']);
    $experience     = mysqli_real_escape_string($conn, $_POST['experience'] ?? '');
    $available_time = mysqli_real_escape_string($conn, $_POST['available_time'] ?? '');
    $password       = $_POST['password'];
    $confirm        = $_POST['confirm_password'];

    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password)) {
        header("Location: signup.php?error=passstrength"); exit();
    }
    if ($password !== $confirm) {
        header("Location: signup.php?error=passmatch"); exit();
    }

    $check = "SELECT id FROM doctors WHERE email='$email'";
    if (mysqli_num_rows(mysqli_query($conn, $check)) > 0) {
        header("Location: signup.php?error=exists"); exit();
    }

    $hashed = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO doctors (full_name, email, password, phone, specialty, hospital, experience, available_time)
            VALUES ('$full_name','$email','$hashed','$phone','$specialty','$hospital','$experience','$available_time')";

    if (mysqli_query($conn, $sql)) {
        $id = mysqli_insert_id($conn);
        $_SESSION['doctor_id']        = $id;
        $_SESSION['doctor_name']      = $full_name;
        $_SESSION['doctor_email']     = $email;
        $_SESSION['doctor_specialty'] = $specialty;
        $_SESSION['doctor_hospital']  = $hospital;
        $_SESSION['doctor_exp']       = $experience;
        $_SESSION['doctor_time']      = $available_time;
        $_SESSION['doctor_status']    = 'available';
        header("Location: dashboard.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
header("Location: signup.php");
exit();
?>