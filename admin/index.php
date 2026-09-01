<?php
require __DIR__ . '/config.php';
require_login();
$activo = 'inicio';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Panel - MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="assets/style.css?v=3">
</head>
<body>
<div class="panel">
  <?php include __DIR__ . '/assets/sidebar.php'; ?>

  <div class="contenido-frame">
    <iframe id="toolFrame" name="toolFrame" src="home.php" title="Herramienta"></iframe>
  </div>
</div>
</body>
</html>
