<?php
// actualizar_dolar.php — PÚBLICO A PROPÓSITO (no pide login).
//
// Lo llama el sitio de precios cada vez que alguien lo abre. Si la cotización
// está en automático y pasaron más de 30 minutos desde la última consulta,
// vuelve a pedir el dólar blue y republica los precios. Así el sitio se
// mantiene al día aunque nadie entre al panel.
//
// Es seguro dejarlo abierto: no recibe ningún dato, no muestra nada interno
// y, por más veces que se lo llame, consulta el dólar como mucho una vez
// cada 30 minutos.

require __DIR__ . '/lib.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$estado = 'ocupado';
try {
    $lock = cat_lock(false);   // si justo hay otro guardado en curso, no se hace nada
    if ($lock) $estado = cat_dolar_refrescar(false);
} catch (Throwable $e) {
    $estado = 'error';
}

// "cambio" = los precios del sitio se acaban de actualizar (el sitio vuelve a leerlos).
echo json_encode(['cambio' => $estado === 'cambio']);
