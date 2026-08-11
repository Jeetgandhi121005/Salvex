<?php
$servername = "localhost";
$username = "root"; // Aapka DB username
$password = "";     // Aapka DB password
$dbname = "salvex_db"; // Aapke database ka sahi naam yahan likhein

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>