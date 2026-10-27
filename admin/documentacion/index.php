<?php
require __DIR__ . '/../config.php';
require_login();

// Token para proteger subidas y cambios (lo valida api.php)
if (empty($_SESSION['doc_csrf'])) {
    $_SESSION['doc_csrf'] = bin2hex(random_bytes(24));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Documentación - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="../assets/style.css?v=3">
<link rel="stylesheet" href="documentacion.css?v=1">
</head>
<body>
<div class="frame-page docs-page">
  <div class="d-head">
    <div>
      <h1>Documentación</h1>
      <p id="sub">Cargando…</p>
    </div>
    <button class="btn btn-oro oculto" id="btnSubir">⬆ Subir documento</button>
  </div>

  <div id="pendientes"></div>

  <div class="layout">
    <aside class="cats" id="cats"></aside>
    <section>
      <div class="filtros">
        <input id="buscar" type="search" placeholder="Buscar manual… (ej: remito, tareas)">
        <select id="orden"><option value="fecha">Más recientes</option><option value="titulo">A → Z</option></select>
        <span class="toggle oculto" id="fPend">🔴 Pendientes de leer</span>
      </div>
      <div id="lista"></div>
    </section>
  </div>
</div>

<div class="overlay" id="overlay"><div class="modal" id="modal"></div></div>
<div class="toast" id="toast"></div>

<script>
  window.DOC_CFG = { csrf: <?= json_encode($_SESSION['doc_csrf']) ?> };
</script>
<script src="documentacion.js?v=1"></script>
</body>
</html>
