<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "coffee_shop_pos";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>