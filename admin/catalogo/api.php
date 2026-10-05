<?php
// api.php — Catálogo de productos (alta, edición, baja, precios, fotos).
//
// Guarda TODOS los productos en  admin/catalogo/data/productos.json  (privado)
// y, cada vez que algo cambia, publica solo los disponibles en
// /data/catalogo.json  (público), que es lo que lee el sitio de precios.
//
// Acciones (parámetro ?accion=):
//   GET  listar     → productos + configuración (y refresca el dólar si está en automático)
//   GET  imagenes   → fotos que ya existen en /images
//   POST guardar    → crea o edita un producto
//   POST estado     → marca Disponible / No disponible
//   POST eliminar   → borra un producto
//   POST config     → cotización (automática o manual), ajuste y % de transferencia
//   POST dolar      → vuelve a consultar el dólar ahora (botón "Actualizar ahora")
//   POST importar   → agrega productos en lote (desde el Google Sheet)
//   POST subir      → sube una foto a /images/productos

require __DIR__ . '/../config.php';
require_login_api();
require __DIR__ . '/lib.php';

// ---------------------------------------------------------------------------
// Utilidades
// ---------------------------------------------------------------------------
function cat_error($mensaje, $code = 400) {
    json_response(['ok' => false, 'error' => $mensaje], $code);
}

// Si falla un guardado (permisos en Hostinger, etc.) se responde el mensaje en JSON.
set_exception_handler(function ($e) {
    cat_error($e instanceof RuntimeException ? $e->getMessage() : 'Error interno del servidor.', 500);
});

function cat_num($v) {
    if (is_string($v)) $v = str_replace(',', '.', trim($v));
    return is_numeric($v) ? max(0.0, (float)$v) : 0.0;
}

function cat_texto($v, $max) {
    $v = is_scalar($v) ? trim((string)$v) : '';
    $v = preg_replace('/\s+/u', ' ', $v) ?? '';
    return function_exists('mb_substr') ? mb_substr($v, 0, $max) : substr($v, 0, $max);
}

// Solo rutas de imagen dentro de /images (nada de "..", URLs ni otros archivos).
function cat_imagen($v) {
    $v = is_string($v) ? trim(str_replace('\\', '/', $v)) : '';
    if ($v === '' || strpos($v, '..') !== false) return '';
    return preg_match('#^images/[\p{L}\p{N} _./()+-]+\.(png|jpe?g|webp|gif)$#iu', $v) ? $v : '';
}

// Valida y limpia lo que llega del formulario. Devuelve [producto, error].
function cat_normalizar($in) {
    $p = [
        'producto' => cat_texto($in['producto'] ?? '', 120),
        'descripcion' => cat_texto($in['descripcion'] ?? '', 300),
        'condicion' => cat_texto($in['condicion'] ?? '', 40),
        'tipo' => cat_texto($in['tipo'] ?? '', 40),
        'precioUSD' => cat_num($in['precioUSD'] ?? 0),
        'precioFijo' => cat_num($in['precioFijo'] ?? 0),
        'imagen' => cat_imagen($in['imagen'] ?? ''),
        'disponible' => !empty($in['disponible']),
    ];
    if ($p['tipo'] === '') $p['tipo'] = 'Otros';

    if ($p['producto'] === '') return [null, 'Falta el nombre del producto.'];
    if (!in_array($p['condicion'], CAT_CONDICIONES, true)) return [null, 'Elegí una categoría válida.'];
    if ($p['precioUSD'] <= 0 && $p['precioFijo'] <= 0) return [null, 'Cargá el precio en USD o un precio fijo en pesos.'];
    return [$p, null];
}

// Lo que ve el panel: cada producto con sus precios ya calculados.
function cat_respuesta($productos, $extra = []) {
    $cfg = cat_config();
    $lista = array_map(fn($p) => $p + cat_precios($p, $cfg), $productos);
    $api = $cfg['dolar_api'];
    json_response($extra + [
        'ok' => true,
        'productos' => array_values($lista),
        'config' => [
            'modo' => $cfg['modo'],
            'cotizacion' => $cfg['cotizacion'],
            'ajuste' => $cfg['ajuste'],
            'pct_transf' => $cfg['pct_transf'],
            'dolar_venta' => $api['venta'],
            'dolar_consultado' => $api['consultado'] ? date('c', $api['consultado']) : null,
        ],
        'condiciones' => CAT_CONDICIONES,
        'publicado' => file_exists(CAT_PUBLICO) ? date('c', filemtime(CAT_PUBLICO)) : null,
    ]);
}

// Una copia de seguridad por día de productos.json (se conservan las últimas 15).
function cat_backup() {
    if (!file_exists(CAT_PRODUCTOS)) return;
    if (!is_dir(CAT_BACKUPS)) @mkdir(CAT_BACKUPS, 0755, true);
    $hoy = CAT_BACKUPS . '/productos_' . date('Y-m-d') . '.json';
    if (file_exists($hoy)) return;
    @copy(CAT_PRODUCTOS, $hoy);
    $todos = glob(CAT_BACKUPS . '/productos_*.json') ?: [];
    sort($todos);
    foreach (array_slice($todos, 0, max(0, count($todos) - 15)) as $viejo) @unlink($viejo);
}

function cat_guardar_todo($productos) {
    cat_backup();
    cat_save(CAT_PRODUCTOS, array_values($productos));
    cat_publicar($productos);
}

function cat_clave($p) {
    $k = $p['producto'] . '|' . $p['descripcion'] . '|' . $p['condicion'] . '|' . $p['tipo'];
    return function_exists('mb_strtolower') ? mb_strtolower($k) : strtolower($k);
}

// ---------------------------------------------------------------------------
// Ruteo
// ---------------------------------------------------------------------------
$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

if ($method === 'GET') {
    if ($accion === 'listar') {
        // Al abrir el panel se aprovecha para refrescar el dólar automático (si ya toca).
        $lock = cat_lock();
        $dolar = cat_dolar_refrescar(false);
        cat_respuesta(cat_load(CAT_PRODUCTOS), ['dolar' => $dolar]);
    }

    if ($accion === 'imagenes') {
        $base = CAT_SITE_ROOT . '/images';
        $lista = [];
        if (is_dir($base)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base, FilesystemIterator::SKIP_DOTS));
            $it->setMaxDepth(3);
            foreach ($it as $f) {
                if (!$f->isFile()) continue;
                $rel = 'images/' . str_replace('\\', '/', substr($f->getPathname(), strlen($base) + 1));
                if (cat_imagen($rel) !== '') $lista[] = $rel;
                if (count($lista) >= 3000) break;
            }
        }
        sort($lista, SORT_NATURAL | SORT_FLAG_CASE);
        json_response(['ok' => true, 'imagenes' => $lista]);
    }

    cat_error('Acción no soportada.', 404);
}

if ($method !== 'POST') cat_error('Método no soportado.', 405);

// Protección CSRF: el panel manda el token de la sesión en un encabezado.
$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['catalogo_csrf']) || !is_string($token) || !hash_equals($_SESSION['catalogo_csrf'], $token)) {
    cat_error('La sesión venció. Recargá la página e intentá de nuevo.', 403);
}

// --- Subir foto (multipart, no JSON) ---------------------------------------
if ($accion === 'subir') {
    $f = $_FILES['foto'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($f['tmp_name'])) {
        cat_error('No se pudo recibir la foto. Probá con una imagen más liviana.');
    }
    if ($f['size'] > CAT_MAX_FOTO) cat_error('La foto pesa más de 6 MB.');

    $info = @getimagesize($f['tmp_name']);
    $ext = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'][$info[2] ?? 0] ?? null;
    if (!$ext) cat_error('El archivo no es una imagen válida (JPG, PNG, WEBP o GIF).');

    $nombre = pathinfo(cat_texto($_POST['nombre'] ?? $f['name'], 80), PATHINFO_FILENAME);
    if (function_exists('iconv')) $nombre = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre) ?: $nombre;
    $nombre = trim(preg_replace('/[^A-Za-z0-9]+/', '_', $nombre), '_');
    if ($nombre === '') $nombre = 'producto';
    $nombre = substr($nombre, 0, 50) . '_' . bin2hex(random_bytes(3)) . '.' . $ext;

    $dir = CAT_SITE_ROOT . '/' . CAT_UPLOAD_SUB;
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) cat_error('No se pudo crear la carpeta de fotos en el hosting.', 500);
    if (!@move_uploaded_file($f['tmp_name'], $dir . '/' . $nombre)) cat_error('No se pudo guardar la foto en el hosting.', 500);

    json_response(['ok' => true, 'ruta' => CAT_UPLOAD_SUB . '/' . $nombre]);
}

// --- El resto de las acciones reciben JSON ---------------------------------
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) cat_error('Datos inválidos.');

$lock = cat_lock();   // se libera solo al terminar el pedido
$productos = cat_load(CAT_PRODUCTOS);
$ahora = date('c');

switch ($accion) {

    case 'guardar':
        [$nuevo, $err] = cat_normalizar($input);
        if ($err) cat_error($err);
        $id = is_string($input['id'] ?? null) ? $input['id'] : '';

        if ($id !== '') {
            $encontrado = false;
            foreach ($productos as &$p) {
                if ($p['id'] === $id) {
                    $p = ['id' => $id] + $nuevo + [
                        'creado' => $p['creado'] ?? $ahora,
                        'creado_por' => $p['creado_por'] ?? current_user(),
                        'actualizado' => $ahora,
                        'actualizado_por' => current_user(),
                    ];
                    $encontrado = true;
                }
            }
            unset($p);
            if (!$encontrado) cat_error('Ese producto ya no existe. Recargá la página.', 404);
        } else {
            $id = uniqid('p_');
            $productos[] = ['id' => $id] + $nuevo + [
                'creado' => $ahora, 'creado_por' => current_user(),
                'actualizado' => $ahora, 'actualizado_por' => current_user(),
            ];
        }
        cat_guardar_todo($productos);
        cat_respuesta($productos, ['id' => $id]);

    case 'estado':
        $id = $input['id'] ?? '';
        $encontrado = false;
        foreach ($productos as &$p) {
            if ($p['id'] === $id) {
                $p['disponible'] = !empty($input['disponible']);
                $p['actualizado'] = $ahora;
                $p['actualizado_por'] = current_user();
                $encontrado = true;
            }
        }
        unset($p);
        if (!$encontrado) cat_error('Ese producto ya no existe. Recargá la página.', 404);
        cat_guardar_todo($productos);
        cat_respuesta($productos);

    case 'eliminar':
        $id = $input['id'] ?? '';
        $antes = count($productos);
        $productos = array_values(array_filter($productos, fn($p) => $p['id'] !== $id));
        if (count($productos) === $antes) cat_error('Ese producto ya no existe. Recargá la página.', 404);
        cat_guardar_todo($productos);
        cat_respuesta($productos);

    case 'config':
        $cfg = cat_config();
        $modo = ($input['modo'] ?? '') === 'manual' ? 'manual' : 'auto';
        $pct = cat_num($input['pct_transf'] ?? 0);
        if ($pct > 100) cat_error('El porcentaje de transferencia no puede superar 100.');
        $cfg['modo'] = $modo;
        $cfg['pct_transf'] = $pct;

        if ($modo === 'manual') {
            $cot = cat_num($input['cotizacion'] ?? 0);
            if ($cot <= 0) cat_error('La cotización del dólar tiene que ser mayor a 0.');
            $cfg['cotizacion'] = $cot;
            cat_guardar_config($cfg, current_user());
            cat_publicar($productos, $cfg);
            cat_respuesta($productos, ['dolar' => 'manual']);
        }

        // Automático: dólar blue + ajuste. Se consulta ahora mismo.
        $ajuste = $input['ajuste'] ?? CAT_DOLAR_AJUSTE;
        if (is_string($ajuste)) $ajuste = str_replace(',', '.', trim($ajuste));
        if (!is_numeric($ajuste) || abs((float)$ajuste) > 100000) cat_error('El ajuste no es válido.');
        $cfg['ajuste'] = (float)$ajuste;   // puede ser negativo o 0
        // Si ya hay un valor de la API, se aplica el ajuste nuevo aunque la consulta falle.
        if ($cfg['dolar_api']['venta']) $cfg['cotizacion'] = $cfg['dolar_api']['venta'] + $cfg['ajuste'];
        cat_guardar_config($cfg, current_user());
        $dolar = cat_dolar_refrescar(true, current_user());
        cat_publicar($productos);          // también cambia si se tocó el % de transferencia
        cat_respuesta($productos, ['dolar' => $dolar]);

    case 'dolar':
        $dolar = cat_dolar_refrescar(true, current_user());
        cat_respuesta($productos, ['dolar' => $dolar]);

    case 'importar':
        $filas = $input['productos'] ?? null;
        if (!is_array($filas) || count($filas) > 5000) cat_error('Datos inválidos.');
        $existentes = [];
        foreach ($productos as $p) $existentes[cat_clave($p)] = true;

        $agregados = 0; $repetidos = 0; $invalidos = 0;
        foreach ($filas as $fila) {
            if (!is_array($fila)) { $invalidos++; continue; }
            [$nuevo, $err] = cat_normalizar($fila);
            if ($err) { $invalidos++; continue; }
            if (isset($existentes[cat_clave($nuevo)])) { $repetidos++; continue; }
            $productos[] = ['id' => uniqid('p_') . $agregados] + $nuevo + [
                'creado' => $ahora, 'creado_por' => current_user() . ' (importado)',
                'actualizado' => $ahora, 'actualizado_por' => current_user(),
            ];
            $agregados++;
        }
        if ($agregados > 0) cat_guardar_todo($productos);
        cat_respuesta($productos, ['agregados' => $agregados, 'repetidos' => $repetidos, 'invalidos' => $invalidos]);

    default:
        cat_error('Acción no soportada.', 404);
}
