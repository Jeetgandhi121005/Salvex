<?php
session_start();
session_unset(); // Saare session variables saaf karein
session_destroy(); // Session khatam karein

// Wapas home page par bhejein
header("Location: index.php");
exit();
?>