<?php
require __DIR__ . '/../config.php';
require_login_api();

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error"]);
    exit;
}

$archivo = "historial.json";

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(["status" => "error"]);
    exit;
}

// Crear archivo si no existe
if (!file_exists($archivo)) {
    file_put_contents($archivo, json_encode([]));
}

$historial = json_decode(file_get_contents($archivo), true) ?: [];

// ── Derivar el próximo número a partir de los códigos existentes ──────────
// Busca el mayor número en todos los códigos tipo PRES-XXXXX y suma 1.
// Esto es auto-reparable: no depende de contador.txt ni se desfasa.
$maxNum = 0;
foreach ($historial as $item) {
    $codigo = $item['presupuesto']['codigo'] ?? '';
    if (preg_match('/PRES-(\d+)/i', $codigo, $m)) {
        $maxNum = max($maxNum, (int)$m[1]);
    }
}
$siguiente = $maxNum + 1;
$codigo = "PRES-" . str_pad($siguiente, 5, "0", STR_PAD_LEFT);

// Agregar código al presupuesto
$data["presupuesto"]["codigo"] = $codigo;

// Guardar en historial
$historial[] = $data;
file_put_contents($archivo, json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo json_encode([
    "status" => "ok",
    "codigo" => $codigo
]);
?>
