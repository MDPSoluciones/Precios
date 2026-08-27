<?php
require __DIR__ . '/config.php';
require_login_api();

$actual = $_POST['clave_actual'] ?? '';
$nueva = $_POST['clave_nueva'] ?? '';
$confirmar = $_POST['clave_confirmar'] ?? '';

if ($actual === '' || $nueva === '' || $confirmar === '') {
    json_response(['ok' => false, 'error' => 'Completá los 3 campos.']);
}
if ($nueva !== $confirmar) {
    json_response(['ok' => false, 'error' => 'La clave nueva y su confirmación no coinciden.']);
}
if (strlen($nueva) < 6) {
    json_response(['ok' => false, 'error' => 'La clave nueva debe tener al menos 6 caracteres.']);
}

$usuario = current_user();
$users = load_json(USERS_FILE);
$idx = null;
foreach ($users as $i => $u) {
    if (strcasecmp($u['usuario'], $usuario) === 0) {
        $idx = $i;
        break;
    }
}

if ($idx === null) {
    json_response(['ok' => false, 'error' => 'Usuario no encontrado.']);
}
if (!password_verify($actual, $users[$idx]['clave_hash'])) {
    json_response(['ok' => false, 'error' => 'La clave actual no es correcta.']);
}

$users[$idx]['clave_hash'] = password_hash($nueva, PASSWORD_DEFAULT);
save_json(USERS_FILE, $users);

json_response(['ok' => true, 'mensaje' => 'Clave actualizada correctamente.']);
