<?php
// lib.php — funciones compartidas del catálogo (datos, precios, dólar, publicación).
//
// Lo usan:
//   - api.php               (panel, con login)
//   - actualizar_dolar.php  (público, solo refresca la cotización automática)
//
// No inicia sesión ni pide login: eso lo hace cada archivo que lo incluye.

define('CAT_DATA_DIR', __DIR__ . '/data');
define('CAT_PRODUCTOS', CAT_DATA_DIR . '/productos.json');
define('CAT_CONFIG', CAT_DATA_DIR . '/config.json');
define('CAT_BACKUPS', CAT_DATA_DIR . '/backups');
define('CAT_SITE_ROOT', dirname(__DIR__, 2));                 // raíz del sitio
define('CAT_PUBLICO', CAT_SITE_ROOT . '/data/catalogo.json'); // lo que lee el sitio
define('CAT_UPLOAD_SUB', 'images/productos');                 // carpeta de fotos subidas
define('CAT_MAX_FOTO', 6 * 1024 * 1024);                      // 6 MB

// Dólar automático: misma fuente y mismo criterio que el Simulador de Cuotas
// (dólar blue "venta" de DolarAPI.com + un ajuste en pesos).
define('CAT_DOLAR_API', 'https://dolarapi.com/v1/dolares/blue');
define('CAT_DOLAR_AJUSTE', 20);        // ajuste inicial en pesos (se cambia desde el panel)
define('CAT_DOLAR_CADA', 30 * 60);     // cada cuánto se vuelve a consultar (segundos)
define('CAT_DOLAR_REINTENTO', 5 * 60); // espera antes de reintentar si la consulta falló

// Mismas categorías que usa el menú del sitio.
const CAT_CONDICIONES = [
    'Apple Nuevos', 'Apple Usados', 'Android Nuevos', 'Android Usados',
    'Notebooks Nuevas', 'Notebooks Usadas', 'PC Escritorio',
    'Tablets Nuevas', 'Tablets Usadas', 'Accesorios',
];

// ---------------------------------------------------------------------------
// Archivos
// ---------------------------------------------------------------------------
function cat_load($path) {
    if (!file_exists($path)) return [];
    $data = json_decode((string)@file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

// Guardado atómico. Si no se puede escribir lanza una excepción con un mensaje claro.
function cat_save($path, $data) {
    $dir = dirname($path);
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        throw new RuntimeException('No se pudo crear la carpeta ' . basename($dir) . '. Revisá los permisos en el hosting.');
    }
    $tmp = $path . '.tmp';
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false || @file_put_contents($tmp, $json) === false || !@rename($tmp, $path)) {
        throw new RuntimeException('No se pudo guardar ' . basename($path) . '. Revisá los permisos de escritura en el hosting.');
    }
}

// Candado para que dos guardados a la vez no se pisen.
// $esperar = false → si otro proceso lo tiene, devuelve null en vez de esperar.
function cat_lock($esperar = true) {
    if (!is_dir(CAT_DATA_DIR)) @mkdir(CAT_DATA_DIR, 0755, true);
    $h = @fopen(CAT_DATA_DIR . '/.lock', 'c');
    if (!$h) return null;
    if (!flock($h, $esperar ? LOCK_EX : LOCK_EX | LOCK_NB)) { fclose($h); return null; }
    return $h;
}

// ---------------------------------------------------------------------------
// Configuración y precios
// ---------------------------------------------------------------------------
function cat_config() {
    $c = cat_load(CAT_CONFIG);
    $api = is_array($c['dolar_api'] ?? null) ? $c['dolar_api'] : [];
    return [
        'modo' => ($c['modo'] ?? 'auto') === 'manual' ? 'manual' : 'auto',
        'cotizacion' => isset($c['cotizacion']) ? (float)$c['cotizacion'] : 1580.0,
        'ajuste' => isset($c['ajuste']) ? (float)$c['ajuste'] : (float)CAT_DOLAR_AJUSTE,
        'pct_transf' => isset($c['pct_transf']) ? (float)$c['pct_transf'] : 3.0,
        'dolar_api' => [
            'venta' => isset($api['venta']) ? (float)$api['venta'] : null,   // último valor informado por la API
            'fecha' => $api['fecha'] ?? null,                                // fecha que informa la API
            'consultado' => (int)($api['consultado'] ?? 0),                  // última consulta exitosa (timestamp)
            'intento' => (int)($api['intento'] ?? 0),                        // último intento (timestamp)
        ],
    ];
}

function cat_guardar_config($cfg, $quien = null) {
    $cfg['actualizado'] = date('c');
    if ($quien !== null) $cfg['actualizado_por'] = $quien;
    cat_save(CAT_CONFIG, $cfg);
}

// Precio en pesos: el fijo si lo tiene, si no USD × cotización. Transferencia = + %.
function cat_precios($p, $cfg) {
    $fijo = (float)($p['precioFijo'] ?? 0);
    $pesos = $fijo > 0 ? $fijo : round((float)($p['precioUSD'] ?? 0) * $cfg['cotizacion']);
    return [
        'precioPesos' => (float)$pesos,
        'precioTransf' => (float)round($pesos * (1 + $cfg['pct_transf'] / 100)),
    ];
}

// Regenera /data/catalogo.json con los productos disponibles (lo que lee el sitio).
function cat_publicar($productos, $cfg = null) {
    $cfg = $cfg ?? cat_config();
    $publicos = [];
    foreach ($productos as $p) {
        if (empty($p['disponible'])) continue;
        $precios = cat_precios($p, $cfg);
        $publicos[] = [
            'producto' => $p['producto'],
            'descripcion' => $p['descripcion'],
            'condicion' => $p['condicion'],
            'tipo' => $p['tipo'],
            'precioUSD' => (float)$p['precioUSD'],
            'precioPesos' => $precios['precioPesos'],
            'precioTransf' => $precios['precioTransf'],
            'imagen' => $p['imagen'],
        ];
    }
    cat_save(CAT_PUBLICO, ['actualizado' => date('c'), 'productos' => $publicos]);
}

// ---------------------------------------------------------------------------
// Dólar automático
// ---------------------------------------------------------------------------
// Consulta DolarAPI desde el servidor. Devuelve ['venta' => float, 'fecha' => string] o null.
function cat_dolar_consultar() {
    $raw = false;
    if (function_exists('curl_init')) {
        $ch = curl_init(CAT_DOLAR_API);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 6,
            CURLOPT_CONNECTTIMEOUT => 4,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_USERAGENT => 'MDPSoluciones-Catalogo/1.0',
        ]);
        $raw = curl_exec($ch);
        if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) $raw = false;
        curl_close($ch);
    } else {
        $ctx = stream_context_create(['http' => ['timeout' => 6, 'user_agent' => 'MDPSoluciones-Catalogo/1.0']]);
        $raw = @file_get_contents(CAT_DOLAR_API, false, $ctx);
    }
    $data = is_string($raw) ? json_decode($raw, true) : null;
    $venta = is_array($data) ? (float)($data['venta'] ?? 0) : 0;
    // Filtro de cordura: un valor absurdo no debe llegar a los precios del sitio.
    if ($venta < 100 || $venta > 1000000) return null;
    return ['venta' => $venta, 'fecha' => is_string($data['fechaActualizacion'] ?? null) ? $data['fechaActualizacion'] : null];
}

// Si la cotización está en automático y ya pasó el tiempo de espera (o $forzar),
// vuelve a consultar el dólar y, si cambió, republica los precios del sitio.
// Devuelve: 'cambio' | 'igual' | 'reciente' | 'manual' | 'error'
// IMPORTANTE: llamar con el candado tomado (cat_lock).
function cat_dolar_refrescar($forzar = false, $quien = null) {
    $cfg = cat_config();
    if ($cfg['modo'] !== 'auto') return 'manual';

    $ahora = time();
    $api = $cfg['dolar_api'];
    if (!$forzar) {
        if ($api['consultado'] && $ahora - $api['consultado'] < CAT_DOLAR_CADA) return 'reciente';
        if ($api['intento'] && $ahora - $api['intento'] < CAT_DOLAR_REINTENTO) return 'reciente';
    }

    $dolar = cat_dolar_consultar();
    $cfg['dolar_api']['intento'] = $ahora;
    if (!$dolar) {
        // Sin conexión con la API: queda la última cotización guardada.
        cat_guardar_config($cfg);
        return 'error';
    }

    $anterior = $cfg['cotizacion'];
    $cfg['dolar_api'] = ['venta' => $dolar['venta'], 'fecha' => $dolar['fecha'], 'consultado' => $ahora, 'intento' => $ahora];
    $cfg['cotizacion'] = $dolar['venta'] + $cfg['ajuste'];
    cat_guardar_config($cfg, $quien);

    if (abs($cfg['cotizacion'] - $anterior) < 0.005) return 'igual';
    cat_publicar(cat_load(CAT_PRODUCTOS), $cfg);
    return 'cambio';
}
