<?php
// config.php - ajustá estos datos con los de Hostinger
$DB_HOST = 'localhost'; // reemplazar
$DB_USER = 'root';
$DB_PASS = 'toor';
$DB_NAME = 'u745852668_stock_cel';

// Conexión mysqli
$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
if ($mysqli->connect_errno) {
    die('DB connect error: ' . $mysqli->connect_error);
}
session_start();
?>