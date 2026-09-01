<?php
require __DIR__ . '/../config.php';
require_login_api();

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

$archivo = "clientes.json";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['nombre'])) {
    echo json_encode(["status" => "error", "mensaje" => "Datos inválidos"]);
    exit;
}

// crear archivo si no existe
if (!file_exists($archivo)) {
    file_put_contents($archivo, json_encode([]));
}

// leer clientes existentes
$clientes = json_decode(file_get_contents($archivo), true);

// evitar duplicados por nombre
foreach ($clientes as $c) {
    if (strtolower($c['nombre']) === strtolower($data['nombre'])) {
        echo json_encode(["status" => "existe"]);
        exit;
    }
}

// guardar nuevo cliente
$clientes[] = $data;

file_put_contents($archivo, json_encode($clientes, JSON_PRETTY_PRINT));

echo json_encode(["status" => "ok"]);
?>