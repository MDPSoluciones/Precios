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

// Requiere el nombre original para encontrarlo, y los nuevos datos
if (!$data || empty($data['nombreOriginal']) || empty($data['nombre'])) {
    echo json_encode(["status" => "error", "mensaje" => "Datos incompletos"]);
    exit;
}

if (!file_exists($archivo)) {
    echo json_encode(["status" => "error", "mensaje" => "Archivo no encontrado"]);
    exit;
}

$clientes = json_decode(file_get_contents($archivo), true);
$nombreOriginal = strtolower(trim($data['nombreOriginal']));
$encontrado = false;

foreach ($clientes as &$c) {
    if (strtolower(trim($c['nombre'] ?? '')) === $nombreOriginal) {
        $c['nombre']    = trim($data['nombre']);
        $c['cuit']      = trim($data['cuit'] ?? '');
        $c['direccion'] = trim($data['direccion'] ?? '');
        $c['mail']      = trim($data['mail'] ?? '');
        $encontrado = true;
        break;
    }
}
unset($c);

if (!$encontrado) {
    echo json_encode(["status" => "error", "mensaje" => "Cliente no encontrado"]);
    exit;
}

file_put_contents($archivo, json_encode($clientes, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo json_encode(["status" => "ok"]);
?>
