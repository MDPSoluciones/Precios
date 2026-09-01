<?php
require __DIR__ . '/config.php';

// Bootstrap: si todavía no hay sesión, se puede entrar una sola vez con ?key=SETUP_KEY
// para crear el primer usuario admin. Una vez logueado, solo pueden entrar admins.
$viaSetupKey = isset($_GET['key']) && $_GET['key'] === SETUP_KEY;
$autorizado = $viaSetupKey || (current_user() && is_admin());

$mensaje = '';
$error = '';

if (!$autorizado) {
    http_response_code(403);
    if (current_user()) {
        die('No tenés permisos de administrador para ver esta página.');
    }
    die('Acceso no autorizado. Si es la primera vez, entrá con ?key=TU_SETUP_KEY (ver config.php), o iniciá sesión con una cuenta admin.');
}

function encontrar_usuario(&$users, $nombre) {
    foreach ($users as $i => $u) {
        if (strcasecmp($u['usuario'], $nombre) === 0) return $i;
    }
    return null;
}

$users = load_json(USERS_FILE);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? 'crear';

    if ($accion === 'crear') {
        $usuario = trim($_POST['usuario'] ?? '');
        $clave = $_POST['clave'] ?? '';
        $rol = ($_POST['rol'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';

        if ($usuario === '' || $clave === '') {
            $error = 'Completá usuario y clave.';
        } elseif (strlen($clave) < 6) {
            $error = 'La clave debe tener al menos 6 caracteres.';
        } elseif (encontrar_usuario($users, $usuario) !== null) {
            $error = 'Ese usuario ya existe.';
        } else {
            $users[] = [
                'usuario' => $usuario,
                'clave_hash' => password_hash($clave, PASSWORD_DEFAULT),
                'rol' => $rol,
                'creado' => date('c'),
            ];
            save_json(USERS_FILE, $users);
            $mensaje = "Usuario '$usuario' creado con éxito.";
        }
    }

    if ($accion === 'reset_clave') {
        $objetivo = trim($_POST['usuario_objetivo'] ?? '');
        $claveNueva = $_POST['clave_nueva'] ?? '';
        $idx = encontrar_usuario($users, $objetivo);

        if ($idx === null) {
            $error = 'Usuario no encontrado.';
        } elseif (strlen($claveNueva) < 6) {
            $error = 'La clave nueva debe tener al menos 6 caracteres.';
        } else {
            $users[$idx]['clave_hash'] = password_hash($claveNueva, PASSWORD_DEFAULT);
            save_json(USERS_FILE, $users);
            $mensaje = "Clave de '{$users[$idx]['usuario']}' actualizada.";
        }
    }

    if ($accion === 'cambiar_rol') {
        $objetivo = trim($_POST['usuario_objetivo'] ?? '');
        $rolNuevo = ($_POST['rol_nuevo'] ?? 'usuario') === 'admin' ? 'admin' : 'usuario';
        $idx = encontrar_usuario($users, $objetivo);

        if ($idx === null) {
            $error = 'Usuario no encontrado.';
        } elseif (current_user() && strcasecmp($objetivo, current_user()) === 0) {
            $error = 'No podés cambiar tu propio rol.';
        } else {
            $users[$idx]['rol'] = $rolNuevo;
            save_json(USERS_FILE, $users);
            $mensaje = "Rol de '{$users[$idx]['usuario']}' actualizado a $rolNuevo.";
        }
    }
}

$users = load_json(USERS_FILE);
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Gestionar usuarios - MDP Soluciones</title>
<link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body class="login-body">
  <div class="login-card ancho">
    <img src="https://mdpsoluciones.com.ar/images/LogoPrincDor.png" alt="MDP Soluciones" class="login-logo">
    <h1>Gestionar usuarios</h1>
    <p class="aviso">Los usuarios admin pueden crear cuentas, resetear claves y asignar roles.</p>

    <?php if ($mensaje): ?><p class="ok"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <h2 class="subtitulo">Crear usuario</h2>
    <form method="POST">
      <input type="hidden" name="accion" value="crear">
      <label>Nuevo usuario</label>
      <input type="text" name="usuario" required>

      <label>Clave (mín. 6 caracteres)</label>
      <input type="password" name="clave" required minlength="6">

      <label>Rol</label>
      <select name="rol">
        <option value="usuario">Usuario</option>
        <option value="admin">Admin</option>
      </select>

      <button type="submit" class="btn btn-oro">Crear usuario</button>
    </form>

    <h2 class="subtitulo">Usuarios existentes</h2>
    <div class="tabla-usuarios">
      <?php foreach ($users as $u): $esYo = current_user() && strcasecmp($u['usuario'], current_user()) === 0; $rol = $u['rol'] ?? 'usuario'; ?>
        <div class="fila-usuario">
          <div class="fila-usuario-nombre">
            <strong><?= htmlspecialchars($u['usuario']) ?></strong>
            <span class="rol-badge rol-<?= htmlspecialchars($rol) ?>"><?= htmlspecialchars($rol) ?></span>
          </div>

          <form method="POST" class="fila-usuario-accion">
            <input type="hidden" name="accion" value="reset_clave">
            <input type="hidden" name="usuario_objetivo" value="<?= htmlspecialchars($u['usuario']) ?>">
            <input type="password" name="clave_nueva" placeholder="Nueva clave" minlength="6" required>
            <button type="submit" class="btn btn-secundario">Resetear</button>
          </form>

          <?php if (!$esYo): ?>
          <form method="POST" class="fila-usuario-accion">
            <input type="hidden" name="accion" value="cambiar_rol">
            <input type="hidden" name="usuario_objetivo" value="<?= htmlspecialchars($u['usuario']) ?>">
            <select name="rol_nuevo">
              <option value="usuario" <?= $rol === 'usuario' ? 'selected' : '' ?>>Usuario</option>
              <option value="admin" <?= $rol === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
            <button type="submit" class="btn btn-secundario">Cambiar rol</button>
          </form>
          <?php else: ?>
            <span class="aviso">(vos)</span>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
      <?php if (empty($users)): ?><p>Ninguno todavía.</p><?php endif; ?>
    </div>

    <?php if (!current_user()): ?>
      <p class="aviso"><a href="login.php">&larr; Volver al login</a></p>
      <p class="aviso"><strong>Importante:</strong> por seguridad, una vez que tengas los usuarios que necesitás, cambiá la SETUP_KEY en config.php.</p>
    <?php endif; ?>
  </div>
</body>
</html>
