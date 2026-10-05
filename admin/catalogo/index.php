<?php
require __DIR__ . '/../config.php';
require_login();

// Token para proteger los guardados (lo valida api.php).
if (empty($_SESSION['catalogo_csrf'])) {
    $_SESSION['catalogo_csrf'] = bin2hex(random_bytes(24));
}

// Dónde está el sitio que muestra estos productos (para el botón "Ver sitio").
// Mientras esté en prueba es v3/. Al pasarlo a producción: '../../'
$SITIO_PUBLICO = '../../v3/';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Catálogo de Productos - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="../assets/style.css?v=3">
<link rel="stylesheet" href="catalogo.css?v=2">
</head>
<body>
  <div class="frame-page cat-page">
    <div class="contenido-header cat-header">
      <div>
        <h1>Catálogo de Productos</h1>
        <p>Lo que cargues acá es lo que se ve en la lista de precios del sitio.</p>
      </div>
      <a class="btn btn-secundario" href="<?= htmlspecialchars($SITIO_PUBLICO) ?>" target="_blank" rel="noopener">Ver sitio</a>
    </div>

    <!-- Cotización del dólar y recargo de transferencia -->
    <form class="cat-card cat-config" id="formConfig">
      <div class="cat-campo">
        <label for="cfgModo">Cotización del dólar</label>
        <select id="cfgModo">
          <option value="auto">Automática (dólar blue)</option>
          <option value="manual">Manual</option>
        </select>
      </div>
      <div class="cat-campo" id="campoAjuste">
        <label for="cfgAjuste">Ajuste sobre el blue ($)</label>
        <input type="number" id="cfgAjuste" step="1">
      </div>
      <div class="cat-campo" id="campoCotizacion" hidden>
        <label for="cfgCotizacion">Cotización ($)</label>
        <input type="number" id="cfgCotizacion" min="1" step="0.01">
      </div>
      <div class="cat-campo">
        <label for="cfgPct">Recargo por transferencia (%)</label>
        <input type="number" id="cfgPct" min="0" max="100" step="0.01" required>
      </div>
      <button type="submit" class="btn btn-oro">Guardar</button>

      <div class="cat-dolar">
        <span>💵 Dólar en uso: <strong id="dolarValor">-</strong></span>
        <span class="cat-nota" id="dolarInfo"></span>
        <button type="button" class="cat-link" id="btnDolar">Actualizar ahora</button>
      </div>
      <p class="cat-nota" id="cfgNota"></p>
    </form>

    <!-- Barra de herramientas -->
    <div class="cat-toolbar">
      <button type="button" class="btn btn-oro" id="btnNuevo">+ Nuevo producto</button>
      <input type="search" id="buscar" placeholder="Buscar producto o descripción...">
      <select id="filtroCondicion" aria-label="Filtrar por categoría">
        <option value="">Todas las categorías</option>
      </select>
      <select id="filtroEstado" aria-label="Filtrar por estado">
        <option value="">Todos</option>
        <option value="si">Disponibles</option>
        <option value="no">No disponibles</option>
      </select>
      <button type="button" class="btn btn-secundario" id="btnImportar">Importar desde Google Sheet</button>
    </div>

    <p class="cat-resumen" id="resumen"></p>

    <!-- Lista de productos -->
    <div class="cat-card cat-tabla-wrap">
      <table class="cat-tabla">
        <thead>
          <tr>
            <th class="col-foto"></th>
            <th>Producto</th>
            <th>Categoría</th>
            <th class="num">USD</th>
            <th class="num">Efectivo</th>
            <th class="num">Transferencia</th>
            <th>Disponible</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="tbody"></tbody>
      </table>
      <p class="cat-vacio" id="vacio" hidden></p>
    </div>
  </div>

  <!-- Modal alta / edición -->
  <div class="cat-overlay" id="modalProducto">
    <div class="cat-modal">
      <button type="button" class="cat-cerrar" data-cerrar aria-label="Cerrar">&times;</button>
      <h2 id="modalTitulo">Nuevo producto</h2>

      <form id="formProducto" novalidate>
        <input type="hidden" id="fId">

        <label for="fProducto">Producto</label>
        <input type="text" id="fProducto" maxlength="120" placeholder="Ej: IPHONE 15" required>

        <label for="fDescripcion">Descripción</label>
        <input type="text" id="fDescripcion" maxlength="300" placeholder="Ej: 128GB - CONSULTAR COLORES">

        <div class="cat-dos">
          <div>
            <label for="fCondicion">Categoría</label>
            <select id="fCondicion" required></select>
          </div>
          <div>
            <label for="fTipo">Tipo de producto</label>
            <input type="text" id="fTipo" list="listaTipos" maxlength="40" placeholder="Ej: Celulares" required>
            <datalist id="listaTipos"></datalist>
          </div>
        </div>

        <div class="cat-dos">
          <div>
            <label for="fUSD">Precio en USD</label>
            <input type="number" id="fUSD" min="0" step="0.01" placeholder="0">
          </div>
          <div>
            <label class="cat-check"><input type="checkbox" id="fUsarFijo"> Precio fijo en pesos</label>
            <input type="number" id="fFijo" min="0" step="1" placeholder="No cambia con el dólar" disabled>
          </div>
        </div>
        <p class="cat-preview" id="fPreview"></p>

        <label>Foto</label>
        <div class="cat-foto">
          <img id="fFotoPreview" alt="">
          <div class="cat-foto-botones">
            <button type="button" class="btn btn-secundario" id="btnSubirFoto">Subir foto</button>
            <button type="button" class="btn btn-secundario" id="btnElegirFoto">Elegir existente</button>
            <button type="button" class="cat-link" id="btnQuitarFoto">Quitar</button>
            <input type="file" id="fArchivo" accept="image/jpeg,image/png,image/webp,image/gif" hidden>
            <input type="hidden" id="fImagen">
            <span class="cat-nota" id="fFotoNota"></span>
          </div>
        </div>

        <label class="cat-check"><input type="checkbox" id="fDisponible" checked> Disponible (se muestra en el sitio)</label>

        <p class="cat-error" id="fError"></p>
        <div class="cat-modal-botones">
          <button type="button" class="btn btn-secundario" data-cerrar>Cancelar</button>
          <button type="submit" class="btn btn-oro" id="btnGuardar">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Selector de fotos existentes -->
  <div class="cat-overlay" id="modalFotos">
    <div class="cat-modal cat-modal-ancho">
      <button type="button" class="cat-cerrar" data-cerrar aria-label="Cerrar">&times;</button>
      <h2>Elegir una foto</h2>
      <input type="search" id="buscarFoto" placeholder="Buscar por nombre de archivo...">
      <div class="cat-galeria" id="galeria"></div>
    </div>
  </div>

  <div class="cat-toast" id="toast"></div>

  <script>
    window.CATALOGO = {
      csrf: <?= json_encode($_SESSION['catalogo_csrf']) ?>,
      raiz: '../../'  // desde admin/catalogo/ hasta la raíz del sitio (para mostrar las fotos)
    };
  </script>
  <script src="catalogo.js?v=2"></script>
</body>
</html>
