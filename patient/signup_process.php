<?php
session_start();
include 'includes/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $phone = mysqli_real_escape_string($conn, $_POST['phone']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // 1. Password Strength Validation (8 chars + 1 Capital)
    if (strlen($password) < 8 || !preg_match('/[A-Z]/', $password)) {
        die("Error: Password must be at least 8 characters long and contain one capital letter.");
    }

    // 2. Check if passwords match
    if ($password !== $confirm_password) {
        die("Error: Passwords do not match.");
    }

    // 3. Check for existing email
    $check_email = "SELECT id FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $check_email);

    if (mysqli_num_rows($result) > 0) {
        die("Error: This email is already registered.");
    }

    // 4. Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // 5. Insert into Database
    $sql = "INSERT INTO users (full_name, phone, email, password) 
            VALUES ('$full_name', '$phone', '$email', '$hashed_password')";

    if (mysqli_query($conn, $sql)) {
        $_SESSION['user_id'] = mysqli_insert_id($conn);
        $_SESSION['user_name'] = $full_name;
        $_SESSION['user_email'] = $email;
        header("Location: index.php");
        exit();
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}
?>