<?php
// cocos_lib.php — VERSIÓN DE PRUEBA (v4): arma la lista de precios SOLO con datos de Cocos CRM.
//
// Fuente: API pública del catálogo de Cocos (https://cocoscrm.com/api-docs).
//   - Inventario: una fila por unidad disponible (lo vendido desaparece solo).
//   - Configuración de la tienda: modo del dólar, recargo, % transferencia.
//   - Fotos: consultas NO documentadas que usa el propio catálogo de Cocos
//     (pueden cambiar sin aviso; si fallan, se usan las fotos locales o la de por defecto).
//
// Las notas internas de cada equipo (device_notes / device_problems) se descartan
// apenas llegan: nunca se guardan ni se publican.

define('COCOS_SLUG', 'mdpsoluciones');
define('COCOS_API', 'https://cocoscrm.com/api/rpc/');
define('COCOS_REST', 'https://jtxdsypkhyyojllfdkdj.supabase.co/rest/v1/');
// Clave pública que figura en la documentación de Cocos (la misma que usa cualquier visitante del catálogo).
define('COCOS_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6Imp0eGRzeXBraHl5b2psbGZka2RqIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NTcxMTMxOTksImV4cCI6MjA3MjY4OTE5OX0.wpVKwHSaFC8S4rXFDrKYR88GXOo-xRYm_tFZ6pgbmTI');
define('COCOS_CACHE_DIR', __DIR__ . '/cache');
define('COCOS_CACHE_TTL', 5 * 60);          // Cocos recomienda guardar la respuesta entre 1 y 5 minutos
define('COCOS_DOLAR_API', 'https://dolarapi.com/v1/dolares/blue');
define('COCOS_DOLAR_TTL', 30 * 60);
define('COCOS_SITE_ROOT', dirname(__DIR__));

// ---------------------------------------------------------------------------
// HTTP + caché
// ---------------------------------------------------------------------------
function cocos_http($url, $body = null, $headers = [], $timeout = 12) {
    if (!function_exists('curl_init')) {
        $opts = ['http' => ['timeout' => $timeout, 'ignore_errors' => true,
            'method' => $body === null ? 'GET' : 'POST',
            'header' => implode("\r\n", $headers), 'content' => $body ?? '']];
        $raw = @file_get_contents($url, false, stream_context_create($opts));
        return $raw === false ? null : json_decode($raw, true);
    }
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $timeout, CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER => $headers, CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MDPSoluciones-Catalogo/1.0)',
        CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,   // evita demoras de IPv6 en algunas redes/PC
    ]);
    if ($body !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
    $raw = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ($raw === false || $code < 200 || $code >= 300) ? null : json_decode($raw, true);
}

function cocos_headers() {
    return ['apikey: ' . COCOS_KEY, 'Authorization: Bearer ' . COCOS_KEY, 'Content-Type: application/json'];
}
// Primero la dirección documentada (cocoscrm.com/api/rpc). Si no responde, el mismo servicio
// directo en su servidor de datos (es el que usa el propio catálogo de Cocos).
function cocos_rpc($fn, $base = null) {
    $body = json_encode(['p_slug' => COCOS_SLUG]);
    if ($base !== null) return cocos_http($base . $fn, $body, cocos_headers());
    $r = cocos_http(COCOS_API . $fn, $body, cocos_headers(), 8);
    return $r !== null ? $r : cocos_http(COCOS_REST . 'rpc/' . $fn, $body, cocos_headers());
}

function cocos_cache_get($name, $ttl, &$edad = null) {
    $f = COCOS_CACHE_DIR . "/$name.json";
    if (!is_file($f)) return null;
    $edad = time() - filemtime($f);
    $d = json_decode((string)@file_get_contents($f), true);
    return ($ttl === null || $edad < $ttl) ? $d : null;
}
function cocos_cache_put($name, $data) {
    if (!is_dir(COCOS_CACHE_DIR)) @mkdir(COCOS_CACHE_DIR, 0755, true);
    $f = COCOS_CACHE_DIR . "/$name.json";
    @file_put_contents("$f.tmp", json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    @rename("$f.tmp", $f);
}

// Trae (o lee de la caché) todo lo que hace falta de Cocos.
function cocos_datos(&$info = []) {
    $d = cocos_cache_get('cocos', COCOS_CACHE_TTL, $edad);
    if ($d) { $info['origen'] = 'cache'; $info['edad'] = $edad; return $d; }

    $inv = cocos_rpc('get_public_catalog_inventory');
    $org = cocos_rpc('get_organization_by_catalog_slug');
    if (is_array($inv) && is_array($org)) {
        // Fuera notas internas (pueden tener IMEI, nombres, etc.)
        $inv = array_map(function ($u) {
            unset($u['device_notes'], $u['device_problems']);
            return $u;
        }, $inv);
        $o = $org[0] ?? [];
        $d = [
            'inv' => $inv,
            'org' => [[
                'public_catalog_dollar_mode' => $o['public_catalog_dollar_mode'] ?? null,
                'public_catalog_dollar_rate' => $o['public_catalog_dollar_rate'] ?? null,
                'transfer_percentage' => $o['transfer_percentage'] ?? null,
                'show_prices_in_ars' => $o['show_prices_in_ars'] ?? null,
            ]],
            // Fotos (no documentado): primero las propias de la tienda, después las genéricas por modelo
            'org_images' => cocos_rpc('get_org_catalog_images', COCOS_REST . 'rpc/') ?: [],
            'model_images' => cocos_http(COCOS_REST . 'model_images?select=model,color,image_url', null, cocos_headers()) ?: [],
        ];
        cocos_cache_put('cocos', $d);
        $info['origen'] = 'cocos';
        $info['edad'] = 0;
        return $d;
    }
    // Cocos no respondió: se usa la última copia guardada, aunque sea vieja
    $d = cocos_cache_get('cocos', null, $edad);
    if ($d) { $info['origen'] = 'cache-vieja'; $info['edad'] = $edad; return $d; }
    throw new RuntimeException('No se pudo consultar Cocos CRM.');
}

// Dólar según la configuración pública de la tienda en Cocos.
function cocos_dolar($org, &$info = []) {
    $modo = $org['public_catalog_dollar_mode'] ?? 'api_plus';
    $valor = (float)($org['public_catalog_dollar_rate'] ?? 0);
    $info['modo'] = $modo;
    $info['valor_config'] = $valor;
    if (!in_array($modo, ['api', 'api_plus'], true) && $valor > 100) {   // dólar fijo cargado en Cocos
        $info['cotizacion'] = $valor;
        return $valor;
    }
    $blue = cocos_cache_get('dolar', COCOS_DOLAR_TTL);
    if (!$blue) {
        $r = cocos_http(COCOS_DOLAR_API);
        if (is_array($r) && (float)($r['venta'] ?? 0) > 100) { $blue = ['venta' => (float)$r['venta']]; cocos_cache_put('dolar', $blue); }
        else $blue = cocos_cache_get('dolar', null);
    }
    if (!$blue) throw new RuntimeException('No se pudo consultar el dólar.');
    $info['blue'] = $blue['venta'];
    $cot = $blue['venta'] + ($modo === 'api_plus' ? $valor : 0);
    $info['cotizacion'] = $cot;
    return $cot;
}

// ---------------------------------------------------------------------------
// Categorías: Cocos → menú del sitio
// ---------------------------------------------------------------------------
function cocos_norm($s) {
    $s = mb_strtolower(trim((string)$s), 'UTF-8');
    $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
    return preg_replace('/\s+/', ' ', $s);
}

// Reglas por palabra clave para lo que Cocos tiene en "personalizado" (el orden importa).
const COCOS_REGLAS = [
    ['/airpods?|auricular|headset|buds|earbuds/', 'Accesorios', 'Auriculares'],
    ['/parlante|\bjbl\b|speaker/', 'Accesorios', 'Parlantes'],
    ['/cargador|cable|fuente|adaptador/', 'Accesorios', 'Cargadores'],
    ['/reloj|smart ?band|watch/', 'Accesorios', 'Smartwatch'],
    ['/ps5|ps4|playstation|joystick|consola|quest|oculos|oculus|nintendo|xbox/', 'Accesorios', 'Gaming'],
    ['/teclado|mouse|monitor|combo/', 'Accesorios', 'Periféricos'],
    ['/ipad|tablet|galaxy tab/', 'Tablets', 'Tablets'],
    ['/mini ?pc|prodesk|optiplex|pc gamer|all in one/', 'PC Escritorio', 'PC'],
    ['/notebook|laptop|elitebook|probook|latitude|lactitud|thinkpad|macbook|asus tuf|\b[lt]14s?\b|\d{2}(\.\d)?\s*("|“|”|pulgadas)/', 'Notebooks', 'Notebooks'],
    ['/motorola|\bmoto\b|samsung|galaxy|xiaomi|redmi|poco|\ba\d{2}\b/', 'Android', 'Celulares'],
];

// Devuelve [condicion del sitio, tipo, regla usada]
function cocos_categoria($u) {
    $cat = $u['product_category'] ?? '';
    $nuevo = ($u['condition_grade'] ?? '') === 'Sellado';
    $grupo = null; $tipo = null; $regla = 'categoría ' . $cat;

    switch ($cat) {
        case 'iphone': $grupo = 'Apple'; $tipo = 'Celulares'; break;
        case 'samsung': $grupo = 'Android'; $tipo = 'Celulares'; break;
        case 'ipad': $grupo = 'Tablets'; $tipo = 'Tablets'; break;
        case 'mac': case 'notebook': $grupo = 'Notebooks'; $tipo = 'Notebooks'; break;
        case 'watch': $grupo = 'Accesorios'; $tipo = 'Smartwatch'; break;
        case 'audio': $grupo = 'Accesorios'; $tipo = 'Auriculares'; break;
        default:
            $m = cocos_norm($u['model'] ?? '');
            foreach (COCOS_REGLAS as $r) {
                if (preg_match($r[0], $m)) { $grupo = $r[1]; $tipo = $r[2]; $regla = 'palabra clave ' . trim($r[0], '/'); break; }
            }
            if (!$grupo) { $grupo = 'Accesorios'; $tipo = 'Otros'; $regla = 'sin coincidencia → Otros'; }
    }
    $map = [
        'Apple' => $nuevo ? 'Apple Nuevos' : 'Apple Usados',
        'Android' => $nuevo ? 'Android Nuevos' : 'Android Usados',
        'Tablets' => $nuevo ? 'Tablets Nuevas' : 'Tablets Usadas',
        'Notebooks' => $nuevo ? 'Notebooks Nuevas' : 'Notebooks Usadas',
        'PC Escritorio' => 'PC Escritorio',
        'Accesorios' => 'Accesorios',
    ];
    return [$map[$grupo], $tipo, $regla];
}

// ---------------------------------------------------------------------------
// Fotos
// ---------------------------------------------------------------------------
function cocos_indice_fotos($lista) {
    $idx = ['mc' => [], 'm' => []];
    foreach ($lista as $i) {
        if (empty($i['image_url'])) continue;
        $m = cocos_norm($i['model'] ?? ''); $c = cocos_norm($i['color'] ?? '');
        $idx['mc']["$m|$c"] = $idx['mc']["$m|$c"] ?? $i['image_url'];
        $idx['m'][$m] = $idx['m'][$m] ?? $i['image_url'];
    }
    return $idx;
}

function cocos_foto($u, $orgIdx, $genIdx) {
    $m = cocos_norm($u['model'] ?? ''); $c = cocos_norm($u['color'] ?? '');
    foreach ([[$orgIdx, 'mc', "$m|$c", 'Cocos (foto de la tienda)'], [$genIdx, 'mc', "$m|$c", 'Cocos (foto genérica)'],
              [$orgIdx, 'm', $m, 'Cocos (otro color)'], [$genIdx, 'm', $m, 'Cocos (genérica, otro color)']] as [$idx, $k, $key, $origen]) {
        if (!empty($idx[$k][$key])) return [$idx[$k][$key], $origen];
    }
    // Fotos que ya tiene el sitio: images/iphone-16-pro-01.png, images/iphone-18-pro.png, etc.
    $slug = trim(preg_replace('/[^a-z0-9]+/', '-', $m), '-');
    foreach (["images/$slug-01.png", "images/$slug.png", "images/$slug-01.jpg", "images/$slug.jpg"] as $p) {
        if (is_file(COCOS_SITE_ROOT . '/' . $p)) return [$p, 'foto local del sitio'];
    }
    return ['', 'sin foto (imagen por defecto)'];
}

// ---------------------------------------------------------------------------
// Armado del catálogo (mismo formato que data/catalogo.json)
// ---------------------------------------------------------------------------
function cocos_storage($s) {
    $s = trim((string)$s);
    if ($s === '') return '';
    if (preg_match('/^\d+$/', $s)) return $s . 'GB';
    return strtoupper(str_replace(' ', '', $s));
}

function cocos_catalogo() {
    $info = [];
    $d = cocos_datos($info);
    $org = $d['org'][0] ?? [];
    $cot = cocos_dolar($org, $info);
    $pct = (float)($org['transfer_percentage'] ?? 3);
    $orgIdx = cocos_indice_fotos($d['org_images'] ?? []);
    $genIdx = cocos_indice_fotos($d['model_images'] ?? []);

    $grupos = []; $sinPrecio = [];
    foreach ($d['inv'] as $u) {
        $precio = (float)($u['sale_price'] ?? 0);
        $oferta = (float)($u['offer_price'] ?? 0);
        if ($oferta > 0 && ($precio <= 0 || $oferta < $precio)) $precio = $oferta;
        if ($precio <= 0) { $sinPrecio[] = $u; continue; }

        $clave = implode('|', [$u['product_category'] ?? '', $u['condition_grade'] ?? '', cocos_norm($u['model'] ?? ''),
            cocos_norm($u['storage'] ?? ''), cocos_norm($u['color'] ?? ''), $precio]);
        $grupos[$clave][] = $u + ['_precio' => $precio];
    }

    $productos = []; $detalle = [];
    foreach ($grupos as $unidades) {
        $u = $unidades[0];
        [$condicion, $tipo, $regla] = cocos_categoria($u);

        // Descripción: capacidad · color · batería · extras
        $partes = [];
        if ($s = cocos_storage($u['storage'] ?? '')) $partes[] = $s;
        $color = trim((string)($u['color'] ?? ''));
        if (mb_strlen($color) > 1) $partes[] = mb_strtoupper($color, 'UTF-8');
        $bat = array_values(array_filter(array_map(fn($x) => $x['battery_health_pct'] ?? null, $unidades), fn($b) => $b !== null && $b !== ''));
        if ($bat) {
            $min = min($bat); $max = max($bat);
            $partes[] = 'BATERÍA ' . ($min == $max ? "$min%" : "$min% A $max%");
        }
        $todos = fn($campo) => count(array_filter($unidades, fn($x) => !empty($x[$campo]))) === count($unidades);
        if ($todos('is_esim')) $partes[] = 'ESIM';
        if ($todos('has_box')) $partes[] = 'CON CAJA';
        if (($u['condition_grade'] ?? '') === 'ASIS') $partes[] = 'ASIS';
        if (is_array($u['specifications'] ?? null)) {
            foreach ($u['specifications'] as $v) if (is_scalar($v) && $v !== '') $partes[] = mb_strtoupper((string)$v, 'UTF-8');
        }

        [$foto, $origenFoto] = cocos_foto($u, $orgIdx, $genIdx);
        $pesos = round($u['_precio'] * $cot);
        $p = [
            'producto' => mb_strtoupper(trim($u['model'] ?? 'Sin nombre'), 'UTF-8'),
            'descripcion' => implode(' · ', $partes),
            'condicion' => $condicion,
            'tipo' => $tipo,
            'precioUSD' => (float)$u['_precio'],
            'precioPesos' => (float)$pesos,
            'precioTransf' => (float)round($pesos * (1 + $pct / 100)),
            'imagen' => $foto,
        ];
        $productos[] = $p;
        $detalle[] = $p + ['unidades' => count($unidades), 'cocos_categoria' => $u['product_category'] ?? '',
            'cocos_estado' => $u['condition_grade'] ?? '', 'regla' => $regla, 'origen_foto' => $origenFoto];
    }

    return [
        'productos' => $productos,
        'detalle' => $detalle,
        'sin_precio' => array_map(fn($u) => ['modelo' => $u['model'] ?? '', 'capacidad' => $u['storage'] ?? '',
            'color' => $u['color'] ?? '', 'estado' => $u['condition_grade'] ?? '', 'categoria' => $u['product_category'] ?? ''], $sinPrecio),
        'unidades' => count($d['inv']),
        'info' => $info + ['pct_transf' => $pct],
    ];
}
