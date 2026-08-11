<?php
session_start();
include 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    $sql    = "SELECT * FROM doctors WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $doctor = mysqli_fetch_assoc($result);
        if ((int) ($doctor['is_active'] ?? 1) !== 1) {
            header("Location: signin.php?error=inactive");
            exit();
        }
        if (password_verify($password, $doctor['password'])) {
            $_SESSION['doctor_id']       = $doctor['id'];
            $_SESSION['doctor_name']     = $doctor['full_name'];
            $_SESSION['doctor_email']    = $doctor['email'];
            $_SESSION['doctor_specialty']= $doctor['specialty'];
            $_SESSION['doctor_hospital'] = $doctor['hospital'];
            $_SESSION['doctor_exp']      = $doctor['experience'];
            $_SESSION['doctor_time']     = $doctor['available_time'];
            $_SESSION['doctor_status']   = $doctor['status'];
            header("Location: dashboard.php");
            exit();
        } else {
            header("Location: signin.php?error=invalid");
            exit();
        }
    } else {
        header("Location: signin.php?error=notfound");
        exit();
    }
}
header("Location: signin.php");
exit();
?>
