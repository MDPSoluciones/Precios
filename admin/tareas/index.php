<?php
require __DIR__ . '/../config.php';
require_login();

// Token para proteger los guardados (lo valida api.php)
if (empty($_SESSION['tareas_csrf'])) {
    $_SESSION['tareas_csrf'] = bin2hex(random_bytes(24));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tareas - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="../assets/style.css?v=3">
<link rel="stylesheet" href="tareas.css?v=1">
</head>
<body>
<div class="frame-page tareas">
  <div class="t-head">
    <div>
      <h1>Tareas</h1>
      <p id="subtitulo">Cargando…</p>
    </div>
    <div class="t-head-der">
      <div class="vistas oculto" id="selVistas">
        <button data-vista="a" class="on" title="Columnas por estado">▦ Por estado</button>
        <button data-vista="b" title="Columnas por empleado">👥 Por empleado</button>
        <button data-vista="c" title="Lista con filtros">☰ Lista</button>
      </div>
      <button class="btn btn-oro" id="btnNueva">+ Nueva tarea</button>
    </div>
  </div>

  <div class="filtros">
    <input id="fBuscar" type="search" placeholder="Buscar tarea...">
    <select id="fEmpleado" class="oculto"></select>
    <select id="fPrioridad">
      <option value="">Todas las prioridades</option>
      <option value="alta">Alta</option><option value="media">Media</option><option value="baja">Baja</option>
    </select>
    <select id="fCategoria"></select>
    <span class="toggle oculto" id="fMias">👤 Solo mis tareas</span>
    <div class="chips" id="fEstados"></div>
  </div>

  <section id="vista-a" class="oculto"></section>
  <section id="vista-b" class="oculto"></section>
  <section id="vista-c" class="oculto"></section>
  <section id="vista-emp" class="oculto"></section>
</div>

<div class="overlay" id="overlay"><div class="modal" id="modal"></div></div>
<div class="toast" id="toast"></div>
<datalist id="listaCategorias"></datalist>

<script>
  window.TAREAS_CFG = { csrf: <?= json_encode($_SESSION['tareas_csrf']) ?> };
</script>
<script src="tareas.js?v=1"></script>
</body>
</html>
