<?php
$servername = "srv803.hstgr.io";   // o el host remoto de Hostinger, ej: "mysql.hostinger.com"
$username   = "u745852668_chivax";
$password   = "HoSt(2003)-";
$database   = "u745852668_stock_cel";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
?>
