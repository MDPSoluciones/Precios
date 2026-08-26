<?php
require __DIR__ . '/../config.php';
require_login_api();

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "mensaje" => "Método no permitido"]);
    exit;
}

$archivo = "historial.json";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data || empty($data['codigo'])) {
    echo json_encode(["status" => "error", "mensaje" => "Código no especificado"]);
    exit;
}

if (!file_exists($archivo)) {
    echo json_encode(["status" => "error", "mensaje" => "Historial no encontrado"]);
    exit;
}

$historial = json_decode(file_get_contents($archivo), true);
$codigoBuscado = $data['codigo'];

$nuevaLista = array_values(array_filter($historial, function($item) use ($codigoBuscado) {
    return ($item['presupuesto']['codigo'] ?? '') !== $codigoBuscado;
}));

if (count($nuevaLista) === count($historial)) {
    echo json_encode(["status" => "error", "mensaje" => "Presupuesto no encontrado"]);
    exit;
}

file_put_contents($archivo, json_encode($nuevaLista, JSON_PRETTY_PRINT));

echo json_encode(["status" => "ok", "eliminado" => $codigoBuscado]);
?>
