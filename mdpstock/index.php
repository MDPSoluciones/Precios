<?php
include 'db.php';
require 'db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Gestión de Stock</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <h1>📱 Stock de Celulares</h1>

  <table id="stockTable">
    <thead>
      <tr>
        <th>Marca</th>
        <th>Modelo</th>
        <th>Variante</th>
        <th>Cantidad</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <!-- Se carga dinámicamente con JS -->
    </tbody>
  </table>

  <!-- Overlay del modal -->
<div id="overlay"></div>

<!-- Modal flotante -->
<div id="editModal">
  <h3>Editar Producto</h3>
  <form id="editForm">
    <input type="hidden" name="id_stock" id="id_stock">

    <label for="brand_id">Marca:</label>
    <select name="id_brand" id="brand_id" required></select>

    <label for="model_id">Modelo:</label>
    <select name="id_model" id="model_id" required></select>

    <label for="variant_id">Variante:</label>
    <select name="id_variant" id="variant_id" required></select>

    <label for="quantity">Cantidad:</label>
    <input type="number" name="quantity" id="quantity" min="0" required>

    <div class="modal-buttons">
      <button type="submit" id="saveBtn">💾 Guardar</button>
      <button type="button" id="cancelBtn">Cancelar</button>
    </div>
  </form>
</div>

<script src="script.js"></script>

</body>
</html>
