<?php
require __DIR__ . '/../config.php';
require_login_api();

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "mensaje" => "Método no permitido"]);
    exit;
}

$archivo = "clientes.json";
$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['nombre'])) {
    echo json_encode(["status" => "error", "mensaje" => "Nombre no especificado"]);
    exit;
}

if (!file_exists($archivo)) {
    echo json_encode(["status" => "error", "mensaje" => "Archivo no encontrado"]);
    exit;
}

$clientes = json_decode(file_get_contents($archivo), true);
$nombreBuscado = strtolower(trim($data['nombre']));

$nueva = array_values(array_filter($clientes, function($c) use ($nombreBuscado) {
    return strtolower(trim($c['nombre'] ?? '')) !== $nombreBuscado;
}));

if (count($nueva) === count($clientes)) {
    echo json_encode(["status" => "error", "mensaje" => "Cliente no encontrado"]);
    exit;
}

file_put_contents($archivo, json_encode($nueva, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(["status" => "ok"]);
?>
