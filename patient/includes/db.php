<?php
$host = "localhost";
$user = "root"; 
$pass = ""; 
$dbname = "salvex_db";
$port = 3306;

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
