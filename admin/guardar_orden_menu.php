<?php
require __DIR__ . '/config.php';
require_login_api();

$input = json_decode(file_get_contents('php://input'), true);

if (!is_array($input)) {
    json_response(['ok' => false, 'error' => 'Formato inválido.']);
}

// Solo strings no vacíos, sin duplicados. Si algún día cambian las claves del
// menú, las que ya no existan simplemente se ignoran al renderizar el sidebar.
$orden = array_values(array_unique(array_filter($input, function ($x) {
    return is_string($x) && $x !== '';
})));

$usuario = current_user();
$users = load_json(USERS_FILE);

foreach ($users as $i => $u) {
    if (strcasecmp($u['usuario'], $usuario) === 0) {
        $users[$i]['orden_menu'] = $orden;
        save_json(USERS_FILE, $users);
        json_response(['ok' => true]);
    }
}

json_response(['ok' => false, 'error' => 'Usuario no encontrado.'], 404);
