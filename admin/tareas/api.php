<?php
// api.php — Tareas: todas las lecturas y cambios pasan por acá.
// Los datos viven en data/tareas.json. Cada tarea guarda su propio historial (no se borra).
require __DIR__ . '/../config.php';
require_login_api();
date_default_timezone_set('America/Argentina/Buenos_Aires');

const TAREAS_FILE = __DIR__ . '/data/tareas.json';
const TAREAS_LOG  = __DIR__ . '/data/tareas_log.json';   // tareas eliminadas por un admin
const ESTADOS     = ['pendiente', 'proceso', 'revision', 'finalizado', 'cancelado'];
const ESTADO_TXT  = ['pendiente' => 'Pendiente', 'proceso' => 'En proceso', 'revision' => 'Revisión',
                     'finalizado' => 'Finalizado', 'cancelado' => 'Cancelado'];
const PRIORIDADES = ['alta', 'media', 'baja'];

function responder($data, $code = 200) { json_response($data, $code); }
function error_api($msg, $code = 400) { json_response(['status' => 'error', 'mensaje' => $msg], $code); }
function ahora() { return date('Y-m-d H:i'); }

// Usuarios del panel (users.json) → lista para asignar
function usuarios_panel() {
    $lista = [];
    foreach (load_json(USERS_FILE) as $u) {
        if (!empty($u['usuario'])) $lista[] = ['usuario' => $u['usuario'], 'rol' => $u['rol'] ?? 'usuario'];
    }
    return $lista;
}
function nombre_valido($n) {
    foreach (usuarios_panel() as $u) if (strcasecmp($u['usuario'], $n) === 0) return $u['usuario'];
    return null;
}
function mismo($a, $b) { return strcasecmp((string)$a, (string)$b) === 0; }
function esta_en($t, $u) { foreach ($t['asignados'] as $a) if (mismo($a, $u)) return true; return false; }

// ── Permisos ────────────────────────────────────────────────────────────────
// Admin: todo. Empleado: ve sus tareas, las que creó y la bolsa (sin asignar).
function puede_ver($t, $u, $admin) { return $admin || esta_en($t, $u) || mismo($t['creado_por'], $u) || !$t['asignados']; }
function puede_editar($t, $u, $admin) { return $admin || esta_en($t, $u) || mismo($t['creado_por'], $u); }
// Finalizar o cancelar: solo el admin o quien creó la tarea
function puede_cerrar($t, $u, $admin) { return $admin || mismo($t['creado_por'], $u); }

// ── Lectura / escritura con bloqueo (evita que dos guardados se pisen) ──────
function con_tareas(callable $fn) {
    if (!is_dir(dirname(TAREAS_FILE))) mkdir(dirname(TAREAS_FILE), 0755, true);
    $fp = fopen(TAREAS_FILE, 'c+');
    if (!$fp || !flock($fp, LOCK_EX)) error_api('No se pudo abrir el archivo de tareas', 500);
    $datos = json_decode(stream_get_contents($fp), true);
    if (!is_array($datos)) $datos = ['ultimo_id' => 0, 'tareas' => []];
    try {
        $resultado = $fn($datos);
    } catch (Throwable $e) {
        flock($fp, LOCK_UN); fclose($fp);
        throw $e;
    }
    if ($resultado !== false) {
        ftruncate($fp, 0); rewind($fp);
        fwrite($fp, json_encode($datos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        fflush($fp);
    }
    flock($fp, LOCK_UN); fclose($fp);
    return $datos;
}
function leer_tareas() {
    if (!file_exists(TAREAS_FILE)) return ['ultimo_id' => 0, 'tareas' => []];
    $fp = fopen(TAREAS_FILE, 'r'); flock($fp, LOCK_SH);
    $d = json_decode(stream_get_contents($fp), true);
    flock($fp, LOCK_UN); fclose($fp);
    return is_array($d) ? $d : ['ultimo_id' => 0, 'tareas' => []];
}

function limpiar_texto($s, $max) { return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$s)), 0, $max); }
function limpiar_fecha($f) { return preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$f) ? $f : ''; }
function limpiar_asignados($lista) {
    $out = [];
    foreach ((array)$lista as $n) { $v = nombre_valido($n); if ($v && !in_array($v, $out, true)) $out[] = $v; }
    return $out;
}
function registrar(&$t, $usuario, $accion) {
    $t['historial'][] = ['fecha' => ahora(), 'usuario' => $usuario, 'accion' => $accion];
    $t['actualizado'] = ahora();
}

// ─────────────────────────────────────────────────────────────────────────────
$usuario = current_user();
$admin   = is_admin();
$accion  = $_GET['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'listar') {
    $d = leer_tareas();
    $visibles = array_values(array_filter($d['tareas'], fn($t) => puede_ver($t, $usuario, $admin)));
    responder(['status' => 'ok', 'tareas' => $visibles, 'usuarios' => usuarios_panel(),
               'yo' => $usuario, 'admin' => $admin]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') error_api('Método no permitido', 405);

$in = json_decode(file_get_contents('php://input'), true) ?: [];
if (empty($_SESSION['tareas_csrf']) || !hash_equals($_SESSION['tareas_csrf'], (string)($in['csrf'] ?? ''))) {
    error_api('La sesión venció. Recargá la página.', 403);
}

// ── Crear ────────────────────────────────────────────────────────────────────
if ($accion === 'crear') {
    $titulo = limpiar_texto($in['titulo'] ?? '', 140);
    if ($titulo === '') error_api('Falta el título');
    $nueva = null;
    con_tareas(function (&$d) use ($in, $titulo, $usuario, &$nueva) {
        $d['ultimo_id'] = (int)($d['ultimo_id'] ?? 0) + 1;
        $pasos = [];
        foreach ((array)($in['pasos'] ?? []) as $p) {
            $p = limpiar_texto($p, 120);
            if ($p !== '') $pasos[] = ['texto' => $p, 'hecho' => false];
        }
        $t = [
            'id' => $d['ultimo_id'],
            'titulo' => $titulo,
            'descripcion' => mb_substr(trim((string)($in['descripcion'] ?? '')), 0, 2000),
            'categoria' => limpiar_texto($in['categoria'] ?? '', 40),
            'prioridad' => in_array($in['prioridad'] ?? '', PRIORIDADES, true) ? $in['prioridad'] : 'media',
            'vence' => limpiar_fecha($in['vence'] ?? ''),
            'estado' => 'pendiente',
            'asignados' => limpiar_asignados($in['asignados'] ?? []),
            'creado_por' => $usuario,
            'creado' => ahora(),
            'actualizado' => ahora(),
            'pasos' => $pasos,
            'comentarios' => [],
            'historial' => [],
        ];
        registrar($t, $usuario, 'creó la tarea' . ($t['asignados'] ? ' y la asignó a ' . implode(', ', $t['asignados']) : ' (en la bolsa)'));
        $d['tareas'][] = $t;
        $nueva = $t;
    });
    responder(['status' => 'ok', 'tarea' => $nueva]);
}

// ── Acciones sobre una tarea existente ──────────────────────────────────────
$id = (int)($in['id'] ?? 0);
if (!$id) error_api('Falta la tarea');

$resultado = null; $errorMsg = null; $borrada = false;
con_tareas(function (&$d) use ($id, $in, $accion, $usuario, $admin, &$resultado, &$errorMsg, &$borrada) {
    $idx = null;
    foreach ($d['tareas'] as $i => $t) if ((int)$t['id'] === $id) { $idx = $i; break; }
    if ($idx === null) { $errorMsg = 'La tarea no existe (¿la eliminaron?)'; return false; }
    $t = &$d['tareas'][$idx];
    if (!puede_ver($t, $usuario, $admin)) { $errorMsg = 'No tenés acceso a esta tarea'; return false; }

    switch ($accion) {
        case 'tomar':   // sumarse uno mismo (desde la bolsa o a una tarea compartida)
            if ($t['estado'] === 'cancelado' || $t['estado'] === 'finalizado') { $errorMsg = 'La tarea ya está cerrada'; return false; }
            if (esta_en($t, $usuario)) break;
            $libre = !$t['asignados'];
            $t['asignados'][] = $usuario;
            registrar($t, $usuario, $libre ? 'tomó la tarea' : 'se sumó a la tarea');
            break;

        case 'soltar':  // salir de la tarea (si queda sin nadie, vuelve a la bolsa)
            if (!esta_en($t, $usuario)) break;
            $t['asignados'] = array_values(array_filter($t['asignados'], fn($a) => !mismo($a, $usuario)));
            registrar($t, $usuario, 'dejó la tarea' . (!$t['asignados'] ? ' (volvió a la bolsa)' : ''));
            break;

        case 'asignar': // definir la lista completa de asignados
            if (!puede_editar($t, $usuario, $admin)) { $errorMsg = 'Solo pueden cambiar asignados quienes están en la tarea, quien la creó o un admin'; return false; }
            $nuevos = limpiar_asignados($in['asignados'] ?? []);
            $suma = array_values(array_udiff($nuevos, $t['asignados'], 'strcasecmp'));
            $saca = array_values(array_udiff($t['asignados'], $nuevos, 'strcasecmp'));
            if (!$suma && !$saca) break;
            $t['asignados'] = $nuevos;
            $partes = [];
            if ($suma) $partes[] = 'sumó a ' . implode(', ', $suma);
            if ($saca) $partes[] = 'quitó a ' . implode(', ', $saca);
            registrar($t, $usuario, implode(' y ', $partes));
            break;

        case 'estado':
            $nuevo = $in['estado'] ?? '';
            if (!in_array($nuevo, ESTADOS, true)) { $errorMsg = 'Estado inválido'; return false; }
            if ($nuevo === $t['estado']) break;
            if (!puede_editar($t, $usuario, $admin)) { $errorMsg = 'Tomá la tarea antes de cambiar su estado'; return false; }
            $cierra = in_array($nuevo, ['finalizado', 'cancelado'], true) || in_array($t['estado'], ['finalizado', 'cancelado'], true);
            if ($cierra && !puede_cerrar($t, $usuario, $admin)) {
                $errorMsg = 'Solo un admin o quien creó la tarea puede finalizarla, cancelarla o reabrirla. Pasala a Revisión.';
                return false;
            }
            registrar($t, $usuario, 'estado: ' . ESTADO_TXT[$t['estado']] . ' → ' . ESTADO_TXT[$nuevo]);
            $t['estado'] = $nuevo;
            break;

        case 'editar':  // título, descripción, categoría, prioridad, vencimiento
            if (!puede_editar($t, $usuario, $admin)) { $errorMsg = 'No podés editar esta tarea'; return false; }
            $cambios = [];
            $campos = [
                'titulo' => fn($v) => limpiar_texto($v, 140),
                'descripcion' => fn($v) => mb_substr(trim((string)$v), 0, 2000),
                'categoria' => fn($v) => limpiar_texto($v, 40),
                'prioridad' => fn($v) => in_array($v, PRIORIDADES, true) ? $v : null,
                'vence' => fn($v) => limpiar_fecha($v),
            ];
            $nombres = ['titulo' => 'título', 'descripcion' => 'descripción', 'categoria' => 'categoría', 'prioridad' => 'prioridad', 'vence' => 'vencimiento'];
            foreach ($campos as $c => $fn) {
                if (!array_key_exists($c, $in)) continue;
                $v = $fn($in[$c]);
                if ($v === null || ($c === 'titulo' && $v === '') || $v === ($t[$c] ?? '')) continue;
                $t[$c] = $v;
                $cambios[] = $c === 'descripcion' ? 'descripción' : $nombres[$c] . ': ' . ($v === '' ? '(vacío)' : $v);
            }
            if ($cambios) registrar($t, $usuario, 'cambió ' . implode(', ', $cambios));
            break;

        case 'paso':    // tildar / destildar / agregar / quitar un paso
            if (!puede_editar($t, $usuario, $admin)) { $errorMsg = 'Tomá la tarea antes de marcar avances'; return false; }
            $op = $in['op'] ?? '';
            $i = (int)($in['indice'] ?? -1);
            if ($op === 'agregar') {
                $txt = limpiar_texto($in['texto'] ?? '', 120);
                if ($txt === '') { $errorMsg = 'El paso está vacío'; return false; }
                $t['pasos'][] = ['texto' => $txt, 'hecho' => false];
                registrar($t, $usuario, 'agregó el paso "' . $txt . '"');
            } elseif (!isset($t['pasos'][$i])) {
                $errorMsg = 'Paso inexistente'; return false;
            } elseif ($op === 'tildar') {
                $t['pasos'][$i]['hecho'] = !$t['pasos'][$i]['hecho'];
                registrar($t, $usuario, ($t['pasos'][$i]['hecho'] ? 'completó' : 'desmarcó') . ' el paso "' . $t['pasos'][$i]['texto'] . '"');
            } elseif ($op === 'quitar') {
                registrar($t, $usuario, 'quitó el paso "' . $t['pasos'][$i]['texto'] . '"');
                array_splice($t['pasos'], $i, 1);
            } else { $errorMsg = 'Operación inválida'; return false; }
            break;

        case 'comentar':
            $txt = mb_substr(trim((string)($in['texto'] ?? '')), 0, 1000);
            if ($txt === '') { $errorMsg = 'El comentario está vacío'; return false; }
            $t['comentarios'][] = ['fecha' => ahora(), 'usuario' => $usuario, 'texto' => $txt];
            $t['actualizado'] = ahora();
            break;

        case 'eliminar': // solo admin; queda una copia completa en tareas_log.json
            if (!$admin) { $errorMsg = 'Solo un admin puede eliminar tareas. Podés cancelarla.'; return false; }
            $copia = $t;
            $copia['eliminada'] = ['fecha' => ahora(), 'usuario' => $usuario];
            $log = load_json(TAREAS_LOG);
            $log[] = $copia;
            save_json(TAREAS_LOG, $log);
            unset($t);
            array_splice($d['tareas'], $idx, 1);
            $borrada = true;
            return;

        default:
            $errorMsg = 'Acción desconocida'; return false;
    }
    $resultado = $t;
});

if ($errorMsg) error_api($errorMsg, 403);
responder($borrada ? ['status' => 'ok', 'eliminada' => $id] : ['status' => 'ok', 'tarea' => $resultado]);
