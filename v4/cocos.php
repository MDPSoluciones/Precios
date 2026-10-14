<?php
// cocos.php — VERSIÓN DE PRUEBA (v4): devuelve la lista de precios armada SOLO con datos de Cocos CRM,
// en el mismo formato que data/catalogo.json (lo que lee el sitio).
require __DIR__ . '/cocos_lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

try {
    $c = cocos_catalogo();
    echo json_encode(['actualizado' => date('c'), 'fuente' => 'cocos', 'productos' => $c['productos']],
        JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
    http_response_code(502);
    echo json_encode(['error' => 'No se pudieron leer los productos de Cocos CRM.', 'productos' => []]);
}
