<?php
require __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = $_POST['clave'] ?? '';

    $users = load_json(USERS_FILE);
    $found = null;
    foreach ($users as $u) {
        if (strcasecmp($u['usuario'], $usuario) === 0) {
            $found = $u;
            break;
        }
    }

    if ($found && password_verify($clave, $found['clave_hash'])) {
        $_SESSION['usuario'] = $found['usuario'];
        header('Location: index.php');
        exit;
    } else {
        $error = 'Usuario o clave incorrectos.';
    }
}

$sinUsuarios = empty(load_json(USERS_FILE));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ingresar - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-body">
  <div class="login-card">
    <img src="https://mdpsoluciones.com.ar/images/LogoPrincDor.png" alt="MDP Soluciones" class="login-logo">
    <h1>Panel MDP</h1>
    <p class="login-sub">Ingresá con tu usuario del panel</p>

    <?php if ($sinUsuarios): ?>
      <p class="aviso">No hay usuarios creados todavía. <a href="gestionar_usuarios.php">Creá el primer usuario acá</a>.</p>
    <?php endif; ?>

    <?php if ($error): ?>
      <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
      <label>Usuario</label>
      <input type="text" name="usuario" required autofocus>

      <label>Clave</label>
      <input type="password" name="clave" required>

      <button type="submit" class="btn btn-oro">Ingresar</button>
    </form>
  </div>
</body>
</html>
