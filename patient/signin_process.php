<?php
session_start();
include 'includes/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // 1. Inputs clean karein (Typo Fixed)
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    
    // 2. Hidden field se destination uthao (index ya dashboard)
    $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'index';

    // 3. User ko dhoondo
    $sql = "SELECT * FROM users WHERE email = '$email'";
    $result = mysqli_query($conn, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        // 4. Password verify karo
        if (password_verify($password, $user['password'])) {
            
            // Session variables set karo
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];

            // 5. --- DYNAMIC REDIRECTION ---
            $redirect_to = isset($_POST['redirect_to']) ? $_POST['redirect_to'] : 'index';

            if ($redirect_to === 'dashboard') {
                header("Location: dashboard.php");
            } else {
                header("Location: index.php");
            }
            exit();

        } else {
            // Password galat hone par
            header("Location: signin.php?error=wrongpassword");
            exit();
        }
    } else {
        // Email nahi mila
        header("Location: signin.php?error=usernotfound");
        exit();
    }
} else {
    header("Location: signin.php");
    exit();
}
?>