<?php
require __DIR__ . '/../config.php';
require_login_api();

header("Content-Type: application/json");

$archivo = "clientes.json";

if (!file_exists($archivo)) {
    echo json_encode([]);
    exit;
}

echo file_get_contents($archivo);
?>