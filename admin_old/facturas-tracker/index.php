<?php
require __DIR__ . '/config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Control de Facturas - MDP Soluciones</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
  <header class="topbar">
    <img src="https://mdpsoluciones.com.ar/images/LogoPrincDor.png" alt="MDP Soluciones" class="topbar-logo">
    <h1>Control de Facturas</h1>
    <div class="topbar-user">
      <a href="https://drive.google.com/drive/folders/1XNs_bkbvAsSPM3AM787UMAva5mA9cDGA?usp=sharing" target="_blank" rel="noopener" class="btn btn-oro btn-drive">📁 Carpeta de facturas (Drive)</a>
      <span>Hola, <?= htmlspecialchars(current_user()) ?></span>
    </div>
  </header>

  <main class="container">
    <div class="toolbar">
      <button id="btnNueva" class="btn btn-oro">+ Nueva compra</button>

      <div class="filtros">
        <input type="text" id="buscar" placeholder="Buscar por sitio, vendedor o producto...">
        <select id="filtroFactura">
          <option value="">Todas</option>
          <option value="si">Con factura</option>
          <option value="no">Sin factura</option>
        </select>
      </div>

      <div class="resumen" id="resumen"></div>
    </div>

    <div class="tabla-wrap">
      <table id="tablaCompras">
        <thead>
          <tr>
            <th>ID</th>
            <th>Fecha</th>
            <th>Sitio</th>
            <th>ID proveedor</th>
            <th>Vendedor</th>
            <th>Producto/s</th>
            <th>Monto</th>
            <th>Factura A</th>
            <th>PDF</th>
            <th>Registró</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyCompras">
          <!-- filas por JS -->
        </tbody>
      </table>
      <p id="sinResultados" class="sin-resultados" style="display:none;">No hay compras que coincidan.</p>
    </div>
  </main>

  <!-- Modal alta/edición -->
  <div id="modalOverlay" class="modal-overlay" style="display:none;">
    <div class="modal">
      <h2 id="modalTitulo">Nueva compra</h2>
      <form id="formCompra">
        <input type="hidden" id="compraId">

        <label>Fecha</label>
        <input type="date" id="fecha" required>

        <label>Sitio de compra</label>
        <input type="text" id="sitio" placeholder="Ej: MercadoLibre, distribuidor X..." required>

        <label>ID de compra en el proveedor</label>
        <input type="text" id="idExterno" placeholder="Ej: # 2000013827834395">

        <label>Vendedor</label>
        <input type="text" id="vendedor" placeholder="Nombre del vendedor / razón social">

        <label>Producto/s</label>
        <textarea id="producto" rows="2" placeholder="Ej: 3x funda iPhone 13, 1x cargador 20W" required></textarea>

        <label>Monto</label>
        <input type="number" id="monto" step="0.01" min="0" required>

        <label class="check-inline">
          <input type="checkbox" id="tieneFactura">
          Tengo la factura A
        </label>

        <label>Link al PDF (Google Drive u otro)</label>
        <a href="https://drive.google.com/drive/folders/1XNs_bkbvAsSPM3AM787UMAva5mA9cDGA?usp=sharing" target="_blank" rel="noopener" class="link-drive">📁 Abrir carpeta de Drive para subir/copiar el PDF</a>
        <input type="url" id="linkPdf" placeholder="https://drive.google.com/...">

        <label>Notas</label>
        <textarea id="notas" rows="2" placeholder="Opcional"></textarea>

        <div class="modal-botones">
          <button type="button" id="btnCancelar" class="btn btn-secundario">Cancelar</button>
          <button type="submit" class="btn btn-oro">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <script src="assets/app.js"></script>
</body>
</html>
