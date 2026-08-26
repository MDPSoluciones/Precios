<?php
require __DIR__ . '/config.php';

$autorizado = !empty($_SESSION['usuario']) || (isset($_GET['key']) && $_GET['key'] === SETUP_KEY);

$mensaje = '';
$error = '';

if (!$autorizado) {
    http_response_code(403);
    die('Acceso no autorizado. Si es la primera vez, entrá con ?key=TU_SETUP_KEY (ver config.php), o iniciá sesión primero.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    if ($usuario === '' || $clave === '') {
        $error = 'Completá usuario y clave.';
    } elseif (strlen($clave) < 6) {
        $error = 'La clave debe tener al menos 6 caracteres.';
    } else {
        $users = load_json(USERS_FILE);
        foreach ($users as $u) {
            if (strcasecmp($u['usuario'], $usuario) === 0) {
                $error = 'Ese usuario ya existe.';
                break;
            }
        }
        if (!$error) {
            $users[] = [
                'usuario' => $usuario,
                'clave_hash' => password_hash($clave, PASSWORD_DEFAULT),
                'creado' => date('c'),
            ];
            save_json(USERS_FILE, $users);
            $mensaje = "Usuario '$usuario' creado con éxito. Ya tiene acceso a Presupuesto y a Control de Facturas.";
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
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
  <div class="login-card">
    <img src="https://mdpsoluciones.com.ar/images/LogoPrincDor.png" alt="MDP Soluciones" class="login-logo">
    <h1>Gestionar usuarios</h1>
    <p class="aviso">Este login es único: cualquier usuario creado acá entra tanto a Presupuesto como a Control de Facturas.</p>

    <?php if ($mensaje): ?><p class="ok"><?= htmlspecialchars($mensaje) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

    <form method="POST">
      <label>Nuevo usuario</label>
      <input type="text" name="usuario" required>

      <label>Clave (mín. 6 caracteres)</label>
      <input type="password" name="clave" required minlength="6">

      <button type="submit">Crear usuario</button>
    </form>

    <h2 class="subtitulo">Usuarios existentes</h2>
    <ul class="lista-usuarios">
      <?php foreach ($users as $u): ?>
        <li><?= htmlspecialchars($u['usuario']) ?></li>
      <?php endforeach; ?>
      <?php if (empty($users)): ?><li>Ninguno todavía.</li><?php endif; ?>
    </ul>

    <p class="aviso"><a href="login.php">&larr; Volver al login</a></p>
    <p class="aviso"><strong>Importante:</strong> por seguridad, una vez que tengas los usuarios que necesitás, borrá este archivo (gestionar_usuarios.php) del servidor o cambiá la SETUP_KEY en config.php.</p>
  </div>
</body>
</html>
