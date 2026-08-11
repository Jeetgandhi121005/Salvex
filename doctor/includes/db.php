<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "salvex_db";

$conn = mysqli_connect($host, $user, $pass, $dbname);

if (!$conn) {
    die(json_encode(["status" => "error", "message" => "Connection failed"]));
}
?>