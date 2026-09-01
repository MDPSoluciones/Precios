<?php
require __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Carga de Productos - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="../assets/style.css?v=3">
<style>
  .productos-iframe-wrap { margin-top: 4px; }
  .productos-iframe-wrap iframe {
    width: 100%; height: 1150px; border: none;
    border-radius: 10px;
  }
</style>
</head>
<body>
  <div class="frame-page">
    <div class="contenido-header">
      <h1>Carga de Productos</h1>
      <p>Cargá los datos del producto nuevo en el formulario.</p>
    </div>

    <div class="card-simple">
      <div class="productos-iframe-wrap">
        <iframe src="https://docs.google.com/forms/d/e/1FAIpQLScJZwcWaEllTPFqvByJoDgIrBvFdNDg4qpS9P0-lJfuoBcMoA/viewform?embedded=true" title="Carga de productos">Cargando…</iframe>
      </div>
    </div>
  </div>
</body>
</html>
