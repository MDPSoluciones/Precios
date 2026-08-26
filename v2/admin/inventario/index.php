<?php
require __DIR__ . '/../config.php';
require_login();
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sistema de Inventario</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;0,600;1,400&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="inventario.css?v=4">
</head>
<body>

  <!-- TOP BAR -->
  <header class="topbar">
    <div class="logo">
      <span class="logo-mark"></span>
      INVENTARIO
    </div>
    <div class="topbar-center" id="saveStatus">
      <span class="save-dot save-dot--ok" id="saveDot"></span>
      <span id="saveLabel">Guardado localmente</span>
    </div>
    <div class="topbar-right">
      <button class="btn btn-outline btn-sm" onclick="UI.openZones()">⊙ Zonas</button>
      <button class="btn btn-outline btn-sm" onclick="UI.openSync()">⇄ Sincronizar Excel</button>
      <button class="btn btn-gold btn-sm"    onclick="UI.openModal(null)">+ Agregar</button>
    </div>
  </header>

  <!-- STATS BAR -->
  <div class="stats-bar" id="statsBar"></div>

  <!-- TOOLBAR -->
  <div class="toolbar">
    <div class="search-wrap">
      <input type="text" id="searchInput" placeholder="🔍 Buscar nombre, marca, código…">
      <button class="search-clear" id="searchClear" onclick="clearSearch()" title="Limpiar">✕</button>
    </div>
    <select class="filter-select" id="filterCat">
      <option value="">Categorías</option>
    </select>
    <select class="filter-select" id="filterUbi">
      <option value="">Ubicaciones</option>
    </select>
    <select class="filter-select" id="filterTipo">
      <option value="">Tipos</option>
    </select>
    <select class="filter-select" id="filterStock">
      <option value="">Stock</option>
      <option value="zero">Sin stock</option>
      <option value="low">Bajo (≤3)</option>
      <option value="ok">OK (&gt;3)</option>
    </select>
    <div class="toolbar-right">
      <button class="btn btn-outline btn-sm" onclick="App.exportCSV()">↓ CSV</button>
      <div class="view-toggle">
        <button class="view-btn active" id="viewBtnTable" onclick="UI.setView('table')" title="Tabla">☰</button>
        <button class="view-btn"        id="viewBtnGrid"  onclick="UI.setView('grid')"  title="Grilla">⊞</button>
      </div>
    </div>
  </div>

  <!-- MAIN CONTENT -->
  <main class="main">
    <div class="table-wrap" id="tableWrap">
      <div class="result-info" id="resultInfo"></div>
      <div id="tableView"></div>
      <div id="gridView" style="display:none; padding:16px"></div>
      <div class="pagination" id="pagination"></div>
    </div>
  </main>

  <!-- ITEM MODAL -->
  <div class="modal-bg" id="itemModalBg" onclick="UI.closeBgClick(event,'itemModalBg')">
    <div class="modal" id="itemModal">
      <h2 id="modalTitle">Agregar ítem</h2>
      <p class="modal-sub" id="modalSub">Complete los datos del nuevo producto</p>
      <div class="form-grid">
        <div class="form-group full">
          <label for="fNombre">Nombre / Descripción *</label>
          <input type="text" id="fNombre" placeholder="Ej: Cable USB-C a Lightning">
        </div>
        <div class="form-group">
          <label for="fCategoria">Categoría</label>
          <select id="fCategoria"></select>
        </div>
        <div class="form-group">
          <label for="fTipo">Tipo</label>
          <select id="fTipo">
            <option>Accesorio</option><option>Dispositivo</option>
            <option>Insumo</option><option>Herramienta</option>
            <option>Bien de uso</option><option>Pendiente</option>
          </select>
        </div>
        <div class="form-group">
          <label for="fMarca">Marca</label>
          <input type="text" id="fMarca" placeholder="Apple, Samsung…">
        </div>
        <div class="form-group">
          <label for="fModelo">Modelo</label>
          <input type="text" id="fModelo">
        </div>
        <div class="form-group">
          <label for="fCant">Cantidad</label>
          <input type="number" id="fCant" min="0">
        </div>
        <div class="form-group">
          <label for="fCodigo">Código</label>
          <input type="text" id="fCodigo">
        </div>
        <div class="form-group">
          <label for="fCosto">Costo (USD)</label>
          <input type="number" id="fCosto" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label for="fVenta">Precio Venta (USD)</label>
          <input type="number" id="fVenta" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label for="fReventa">Precio Reventa (USD)</label>
          <input type="number" id="fReventa" step="0.01" min="0">
        </div>
        <div class="form-group">
          <label for="fClasificacion">Clasificación</label>
          <select id="fClasificacion">
            <option value="">—</option>
            <option>Original</option><option>Réplica</option>
          </select>
        </div>
        <div class="form-group full">
          <label for="fUbicacion">Ubicación</label>
          <select id="fUbicacion" style="width:100%"></select>
        </div>
        <div class="form-group full">
          <label for="fNotas">Notas</label>
          <textarea id="fNotas" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-danger btn-sm" id="btnDelete" onclick="App.confirmDelete()" style="display:none; margin-right:auto">🗑 Eliminar</button>
        <button class="btn btn-cancel" onclick="UI.closeModal('itemModalBg')">Cancelar</button>
        <button class="btn btn-save"   onclick="App.saveItem()">Guardar</button>
      </div>
    </div>
  </div>

  <!-- SYNC MODAL -->
  <div class="modal-bg" id="syncModalBg" onclick="UI.closeBgClick(event,'syncModalBg')">
    <div class="modal modal--sync">
      <h2>Sincronizar</h2>
      <p class="modal-sub">Conectá con Google Sheets o trabajá con archivos Excel locales</p>

      <!-- TABS -->
      <div class="sync-tabs">
        <button class="sync-tab active" id="tabSheets" onclick="UI.switchSyncTab('sheets')">
          <span class="sync-tab-icon">📊</span> Google Sheets
        </button>
        <button class="sync-tab" id="tabExcel" onclick="UI.switchSyncTab('excel')">
          <span class="sync-tab-icon">📁</span> Archivo Excel / CSV
        </button>
      </div>

      <!-- TAB: GOOGLE SHEETS -->
      <div id="syncPanelSheets">

        <!-- Status banner -->
        <div class="sheets-status" id="sheetsStatus">
          <div class="sheets-status-dot" id="sheetsStatusDot"></div>
          <span id="sheetsStatusLabel">Sin configurar</span>
          <span class="sheets-status-detail" id="sheetsStatusDetail"></span>
        </div>

        <!-- URL config -->
        <div class="sheets-config">
          <div class="form-group">
            <label for="sheetsUrl">URL del Web App (Google Apps Script)</label>
            <div class="sheets-url-row">
              <input type="text" id="sheetsUrl" placeholder="https://script.google.com/macros/s/…/exec">
              <button class="btn btn-outline btn-sm" onclick="saveSheetUrl()">Guardar</button>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="sheets-actions">
          <div class="sheets-action-card">
            <div class="sheets-action-icon sheets-action-icon--pull">↓</div>
            <div>
              <h4>Traer datos del Sheet</h4>
              <p>Reemplaza el inventario local con los datos actuales del Google Sheet.</p>
            </div>
            <button class="btn btn-gold btn-sm" onclick="Sheets.pullManual()" id="btnPull">Traer datos</button>
          </div>
          <div class="sheets-action-card">
            <div class="sheets-action-icon sheets-action-icon--push">↑</div>
            <div>
              <h4>Enviar datos al Sheet</h4>
              <p>Sobreescribe el Google Sheet con el inventario actual del sistema.</p>
            </div>
            <button class="btn btn-outline btn-sm" onclick="Sheets.pushManual()" id="btnPush">Enviar datos</button>
          </div>
        </div>

        <!-- Setup guide -->
        <details class="sheets-guide">
          <summary>¿Cómo configurar Google Sheets? <span class="guide-steps-badge">5 pasos</span></summary>
          <ol class="guide-steps">
            <li>
              <strong>Abrí</strong> tu Google Sheet (o creá uno nuevo) y andá a
              <em>Extensiones → Apps Script</em>.
            </li>
            <li>
              <strong>Borrá</strong> el contenido del editor y <strong>pegá</strong> el código
              del archivo <code>apps-script.gs</code> que descargaste junto con este sistema.
            </li>
            <li>
              <strong>Guardá</strong> el proyecto (Ctrl+S) y hacé clic en
              <em>Implementar → Nueva implementación</em>.
            </li>
            <li>
              Elegí tipo <strong>Aplicación web</strong>, en <em>"Quién tiene acceso"</em>
              seleccioná <strong>"Cualquier persona"</strong> y hacé clic en
              <em>Implementar</em>. Autorizá los permisos cuando te lo pida.
            </li>
            <li>
              <strong>Copiá la URL</strong> que aparece (<code>https://script.google.com/…/exec</code>)
              y pegala en el campo de arriba.
            </li>
          </ol>
          <div class="guide-note">
            💡 El script crea automáticamente una hoja llamada <strong>Inventario</strong>
            con las columnas correctas la primera vez que enviás datos.
          </div>
        </details>

        <div class="sync-info" id="sheetsInfo"></div>
      </div>

      <!-- TAB: EXCEL -->
      <div id="syncPanelExcel" style="display:none">
        <div class="sync-cards">
          <div class="sync-card">
            <div class="sync-card-icon">⬆</div>
            <h3>Exportar</h3>
            <p>Descargá el inventario actual como Excel (.xlsx) con todas las columnas normalizadas y dos hojas de resumen.</p>
            <button class="btn btn-gold" onclick="App.exportXLSX()">↓ Descargar .xlsx</button>
          </div>
          <div class="sync-divider"></div>
          <div class="sync-card">
            <div class="sync-card-icon">⬇</div>
            <h3>Importar</h3>
            <p>Cargá un archivo Excel para actualizar el inventario. Las columnas deben coincidir con el formato exportado.</p>
            <div class="import-zone">
              <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" style="display:none" onchange="App.handleFileImport(event)">
              <div class="import-zone-inner" id="importZoneInner"
                onclick="document.getElementById('fileInput').click()"
                ondragover="App.dragOver(event)"
                ondragleave="App.dragLeave(event)"
                ondrop="App.dropFile(event)">
                <div class="import-icon">📂</div>
                <div class="import-text">Clic o arrastrá un archivo</div>
                <div class="import-hint">.xlsx · .xls · .csv</div>
              </div>
            </div>
            <div class="import-mode-row">
              <label class="radio-label">
                <input type="radio" name="importMode" value="replace" checked>
                Reemplazar todo
              </label>
              <label class="radio-label">
                <input type="radio" name="importMode" value="merge">
                Fusionar (añade nuevos, actualiza por ID)
              </label>
            </div>
          </div>
        </div>
        <div class="sync-info" id="syncInfo"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-cancel" onclick="UI.closeModal('syncModalBg')">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- CONFIRM MODAL -->
  <div class="modal-bg" id="confirmModalBg">
    <div class="modal modal--sm">
      <h2 id="confirmTitle">Confirmar acción</h2>
      <p class="modal-sub" id="confirmMsg"></p>
      <div class="modal-footer">
        <button class="btn btn-cancel" onclick="UI.closeModal('confirmModalBg')">Cancelar</button>
        <button class="btn btn-danger" id="confirmOk">Confirmar</button>
      </div>
    </div>
  </div>

  <!-- ZONES MODAL -->
  <div class="modal-bg" id="zonesModalBg" onclick="UI.closeBgClick(event,'zonesModalBg')">
    <div class="modal modal--zones">
      <h2>Gestión de Zonas</h2>
      <p class="modal-sub">Agregá, renombrá o eliminá zonas de ubicación</p>

      <!-- New zone input -->
      <div class="zone-add-row">
        <input type="text" id="newZoneName" placeholder="Nombre de la nueva zona…" onkeydown="if(event.key==='Enter') Zones.add()">
        <button class="btn btn-gold btn-sm" onclick="Zones.add()">+ Agregar zona</button>
      </div>

      <!-- Zone list -->
      <div class="zone-list-wrap">
        <div class="zone-list-header">
          <span>Zona</span>
          <span class="zone-col-count">Ítems</span>
          <span class="zone-col-actions"></span>
        </div>
        <div id="zoneList"></div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-cancel" onclick="UI.closeModal('zonesModalBg')">Cerrar</button>
      </div>
    </div>
  </div>

  <!-- RENAME ZONE MODAL -->
  <div class="modal-bg" id="renameZoneModalBg">
    <div class="modal modal--sm">
      <h2>Renombrar zona</h2>
      <p class="modal-sub" id="renameZoneOldName"></p>
      <div class="form-group" style="margin-top:4px">
        <label for="renameZoneInput">Nuevo nombre</label>
        <input type="text" id="renameZoneInput" onkeydown="if(event.key==='Enter') Zones.confirmRename()">
      </div>
      <div class="modal-footer">
        <button class="btn btn-cancel" onclick="UI.closeModal('renameZoneModalBg')">Cancelar</button>
        <button class="btn btn-save"   onclick="Zones.confirmRename()">Renombrar</button>
      </div>
    </div>
  </div>

  <!-- DELETE ZONE MODAL -->
  <div class="modal-bg" id="deleteZoneModalBg">
    <div class="modal modal--sm">
      <h2>Eliminar zona</h2>
      <p class="modal-sub" id="deleteZoneName"></p>
      <div id="deleteZoneBody"></div>
      <div class="modal-footer">
        <button class="btn btn-cancel" onclick="UI.closeModal('deleteZoneModalBg')">Cancelar</button>
        <button class="btn btn-danger" id="deleteZoneOk">Eliminar</button>
      </div>
    </div>
  </div>

  <!-- TOAST -->
  <div class="toast" id="toast"></div>

  <!-- SheetJS for Excel read/write -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
  <script src="inventario.js?v=4"></script>
</body>
</html>
