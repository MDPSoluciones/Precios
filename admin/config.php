<?php
// config.php (RAÍZ) — sesión y autenticación compartidas por TODAS las apps del portal
// (Presupuesto, Control de Facturas, y las que se agreguen después).
//
// Cada app solo necesita hacer:
//   require __DIR__ . '/../config.php';
//   require_login();           // si es una página HTML normal
//   // o
//   require_login_api();       // si es un endpoint que responde JSON (fetch/AJAX)

session_start();

define('AUTH_DATA_DIR', __DIR__ . '/data');
define('USERS_FILE', AUTH_DATA_DIR . '/users.json');

// URL base del portal, calculada automáticamente a partir de dónde vive este
// config.php dentro del servidor. Así los redirects funcionan igual si el
// portal está en la raíz del dominio (midominio.com/) o en una subcarpeta
// (midominio.com/presupuestos/), sin tener que tocar código.
$docRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$portalDir = realpath(__DIR__);
$base = '';
if ($docRoot && $portalDir && strpos($portalDir, $docRoot) === 0) {
    $base = substr($portalDir, strlen($docRoot));
    $base = str_replace('\\', '/', $base);
}
define('PORTAL_BASE', rtrim($base, '/'));

// Clave para crear el primer usuario / usuarios nuevos desde gestionar_usuarios.php
// CAMBIALA por algo tuyo antes de subir el sitio.
define('SETUP_KEY', 'mdp-2026-cambiar-esta-clave');

function load_json($path) {
    if (!file_exists($path)) return [];
    $content = file_get_contents($path);
    $data = json_decode($content, true);
    return is_array($data) ? $data : [];
}

function save_json($path, $data) {
    // Guardado atómico simple para evitar corromper el archivo si hay 2 escrituras a la vez
    $tmp = $path . '.tmp';
    file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    rename($tmp, $path);
}

function current_user() {
    return $_SESSION['usuario'] ?? null;
}

// Devuelve el registro completo del usuario logueado desde users.json (o null).
function current_user_data() {
    $usuario = current_user();
    if (!$usuario) return null;
    $users = load_json(USERS_FILE);
    foreach ($users as $u) {
        if (strcasecmp($u['usuario'], $usuario) === 0) {
            return $u;
        }
    }
    return null;
}

// Busca el usuario logueado en users.json y devuelve su rol ('admin' o 'usuario').
// Si el usuario no tiene el campo 'rol' (cuentas viejas), se lo trata como 'usuario'.
function current_user_role() {
    $u = current_user_data();
    return $u ? ($u['rol'] ?? 'usuario') : null;
}

function is_admin() {
    return current_user_role() === 'admin';
}

// Para páginas que solo puede ver un admin (gestionar_usuarios.php, etc.)
function require_admin() {
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        die('No tenés permisos de administrador para ver esta página.');
    }
}

function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Para páginas HTML normales: si no hay sesión, redirige al login del portal.
function require_login() {
    if (empty($_SESSION['usuario'])) {
        header('Location: ' . PORTAL_BASE . '/login.php');
        exit;
    }
}

// Para endpoints llamados por fetch/AJAX: si no hay sesión, responde 401 en JSON
// en vez de redirigir (una redirección rompería el fetch del lado del JS).
function require_login_api() {
    if (empty($_SESSION['usuario'])) {
        json_response(['error' => 'No autorizado', 'status' => 'error', 'mensaje' => 'No autorizado'], 401);
    }
}
