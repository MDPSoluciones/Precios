<?php
require __DIR__ . '/../config.php';
require_login();

header('Content-Type: application/json; charset=utf-8');

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!is_array($data)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'JSON inválido']);
    exit;
}

// Solo se persisten las claves de configuración conocidas, para evitar que
// el endpoint pueda usarse para escribir datos arbitrarios en el archivo.
$camposPermitidos = [
    'dolar', 'transferPct', 'ivaPct', 'iibbPct', 'pct1', 'pct3', 'pct6', 'pct12',
    'sim_plataforma', 'sim_recibir_moneda', 'mp_costoCobro', 'mp_ivaComision', 'mp_cuotasSelect',
    'mp_pct2', 'mp_pct3', 'mp_pct6', 'mp_pct9', 'mp_pct12', 'mp_pct18',
    'mp_retCD', 'mp_retIIBB',
    'tr_costo', 'tr_iva', 'tr_iibb',
    'fact_iva1', 'fact_iva2', 'fact_iva3',
];

$limpio = [];
foreach ($camposPermitidos as $campo) {
    if (isset($data[$campo])) {
        $limpio[$campo] = $data[$campo];
    }
}

$json = json_encode($limpio, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$ok = file_put_contents(__DIR__ . '/config.json', $json);

if ($ok === false) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => 'No se pudo escribir config.json. Revisá los permisos de escritura de la carpeta en Hostinger.',
    ]);
    exit;
}

echo json_encode(['ok' => true]);
