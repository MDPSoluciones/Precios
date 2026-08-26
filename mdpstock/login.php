<?php
require 'db.php';
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare('SELECT id_user, password_hash FROM users WHERE username = ?');
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $stmt->bind_result($id, $hash);
    if ($stmt->fetch()) {
        if (password_verify($password, $hash)) {
            $_SESSION['user_id'] = $id;
            $_SESSION['username'] = $username;
            header('Location: index.php');
            exit;
        } else {
            $err = 'Usuario o contraseña incorrectos.';
        }
    } else {
        $err = 'Usuario o contraseña incorrectos.';
    }
    $stmt->close();
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<title>Login - Stock Celulares</title>
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-6">
      <div class="card shadow-sm">
        <div class="card-body">
          <h3 class="card-title mb-3">Iniciar sesión</h3>
          <?php if($err): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($err)?></div>
          <?php endif; ?>
          <form method="post">
            <div class="mb-3">
              <label class="form-label">Usuario</label>
              <input name="username" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Contraseña</label>
              <input name="password" type="password" class="form-control" required>
            </div>
            <button class="btn btn-primary">Entrar</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>