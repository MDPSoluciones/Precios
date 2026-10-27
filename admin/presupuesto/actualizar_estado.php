<?php
// Marca o desmarca un estado (confirmado / comprado / entregado) de un presupuesto del historial.
require __DIR__ . '/../config.php';
require_login_api();
date_default_timezone_set('America/Argentina/Buenos_Aires');

header("Content-Type: application/json");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(["status" => "error", "mensaje" => "Método no permitido"]);
    exit;
}

$ESTADOS = ['confirmado', 'comprado', 'entregado'];
$archivo = "historial.json";

$data   = json_decode(file_get_contents("php://input"), true);
$codigo = $data['codigo'] ?? '';
$estado = $data['estado'] ?? '';
$valor  = !empty($data['valor']);

if ($codigo === '' || !in_array($estado, $ESTADOS, true)) {
    echo json_encode(["status" => "error", "mensaje" => "Datos incompletos"]);
    exit;
}

// Bloqueo para que dos personas marcando a la vez no se pisen
$fp = fopen($archivo, 'c+');
if (!$fp || !flock($fp, LOCK_EX)) {
    echo json_encode(["status" => "error", "mensaje" => "No se pudo abrir el historial"]);
    exit;
}

$historial = json_decode(stream_get_contents($fp), true) ?: [];
$encontrado = false;

foreach ($historial as &$item) {
    if (($item['presupuesto']['codigo'] ?? '') !== $codigo) continue;
    $encontrado = true;
    if (!isset($item['estados']) || !is_array($item['estados'])) $item['estados'] = [];
    if ($valor) {
        $item['estados'][$estado] = [
            'fecha'   => date('Y-m-d'),
            'usuario' => current_user() ?: '',
        ];
    } else {
        unset($item['estados'][$estado]);
    }
    $estados = $item['estados'];
    break;
}
unset($item);

if (!$encontrado) {
    flock($fp, LOCK_UN);
    fclose($fp);
    echo json_encode(["status" => "error", "mensaje" => "Presupuesto no encontrado"]);
    exit;
}

ftruncate($fp, 0);
rewind($fp);
fwrite($fp, json_encode($historial, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
fflush($fp);
flock($fp, LOCK_UN);
fclose($fp);

echo json_encode(["status" => "ok", "estados" => (object)$estados]);
?>
