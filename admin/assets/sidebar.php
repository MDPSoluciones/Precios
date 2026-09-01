<?php
// Se incluye desde admin/index.php (el shell del panel).
// $activo = clave de la sección resaltada al cargar (solo importa la primera vez;
// después el JS de abajo va resaltando según lo que se clickea).
$activo = $activo ?? '';

$items = [
    'inicio'      => ['label' => 'Inicio',              'href' => 'home.php',               'icon' => 'home'],
    'presupuesto' => ['label' => 'Presupuesto',          'href' => 'presupuesto/',           'icon' => 'file'],
    'facturas'    => ['label' => 'Control de Facturas',  'href' => 'facturas-tracker/',      'icon' => 'clipboard'],
    'etiquetas'   => ['label' => 'Etiquetas',            'href' => 'etiquetas/',              'icon' => 'tag'],
    'cuotas'      => ['label' => 'Simulador de Cuotas',  'href' => 'cuotas/',                 'icon' => 'percent'],
    'caja'        => ['label' => 'Carga de Caja',        'href' => 'caja/',                   'icon' => 'wallet'],
    'productos'   => ['label' => 'Carga de Productos',   'href' => 'productos/',              'icon' => 'upload'],
    'inventario'  => ['label' => 'Inventario',           'href' => 'inventario/',             'icon' => 'box'],
];

if (is_admin()) {
    $items['usuarios'] = ['label' => 'Gestionar usuarios', 'href' => 'gestionar_usuarios.php', 'icon' => 'users'];
}

// Cada usuario puede reordenar el menú arrastrando; su orden se guarda en su
// propio registro (users.json) y se aplica acá. Si hay ítems nuevos que el
// usuario todavía no tiene en su orden guardado (por ej. una herramienta que
// se agregó después), quedan al final en su posición por defecto.
$usuarioData = current_user_data();
if ($usuarioData && !empty($usuarioData['orden_menu']) && is_array($usuarioData['orden_menu'])) {
    $ordenados = [];
    foreach ($usuarioData['orden_menu'] as $clave) {
        if (isset($items[$clave])) {
            $ordenados[$clave] = $items[$clave];
            unset($items[$clave]);
        }
    }
    $items = $ordenados + $items;
}

function nav_icon($name) {
    $icons = [
        'home'      => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>',
        'file'      => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="m9 13 2 2 4-4"/>',
        'tag'       => '<path d="M11 3H4v7l10 10 7-7L11 3Z"/><circle cx="8" cy="8" r="1.4"/>',
        'percent'   => '<circle cx="7" cy="7" r="2.3"/><circle cx="17" cy="17" r="2.3"/><path d="M18 6 6 18"/>',
        'wallet'    => '<path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 7v11a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-4"/><path d="M15 13h4v4h-4a2 2 0 0 1 0-4Z"/>',
        'box'       => '<path d="M3 9.5 12 4l9 5.5V19a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/>',
        'users'     => '<circle cx="9" cy="8" r="3"/><path d="M3 20c0-3.3 2.7-6 6-6s6 2.7 6 6"/><circle cx="17" cy="8" r="2.4"/><path d="M23 20c0-2.6-1.7-4.8-4-5.6"/>',
        'upload'    => '<path d="M12 3v12"/><path d="M7.5 7.5 12 3l4.5 4.5"/><path d="M4 15v4a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4"/>',
    ];
    return $icons[$name] ?? '';
}

$usuario = current_user();
$inicial = $usuario ? strtoupper(substr($usuario, 0, 2)) : '--';
?>
<button class="sidebar-toggle" onclick="document.getElementById('mdpSidebar').classList.toggle('abierto'); document.getElementById('mdpOverlay').classList.toggle('abierto');" aria-label="Abrir menú">
  <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 6h18M3 12h18M3 18h18"/></svg>
</button>
<div id="mdpOverlay" class="sidebar-overlay" onclick="document.getElementById('mdpSidebar').classList.remove('abierto'); this.classList.remove('abierto');"></div>

<aside class="sidebar" id="mdpSidebar">
  <div class="sidebar-brand">
    <img src="https://mdpsoluciones.com.ar/images/LogoPrincDor.png" alt="MDP Soluciones">
    <span>Panel MDP</span>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($items as $key => $item): ?>
      <a class="nav-item <?= $key === $activo ? 'activo' : '' ?>"
         href="<?= htmlspecialchars($item['href']) ?>"
         target="toolFrame"
         data-tool="<?= htmlspecialchars($key) ?>"
         draggable="true"
         onclick="mdpMarcarActivo(this)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><?= nav_icon($item['icon']) ?></svg>
        <?= htmlspecialchars($item['label']) ?>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="avatar"><?= htmlspecialchars($inicial) ?></div>
    <div class="quien">
      <strong><?= htmlspecialchars($usuario ?? '') ?></strong>
      <a href="#" onclick="mdpAbrirModalClave(); return false;">Cambiar clave</a>
      &middot;
      <a href="logout.php" target="_top">Cerrar sesión</a>
    </div>
  </div>
</aside>

<!-- Modal de "cambiar mi clave", disponible siempre desde el panel -->
<div id="modalClave" class="modal-overlay" onclick="if (event.target === this) mdpCerrarModalClave();">
  <div class="modal-box">
    <button class="modal-close" onclick="mdpCerrarModalClave()">&times;</button>
    <h3>Cambiar mi clave</h3>
    <form id="formClave" onsubmit="return mdpGuardarClave(event)">
      <label>Clave actual</label>
      <input type="password" name="clave_actual" required>

      <label>Clave nueva</label>
      <input type="password" name="clave_nueva" required minlength="6">

      <label>Repetir clave nueva</label>
      <input type="password" name="clave_confirmar" required minlength="6">

      <button type="submit" class="btn btn-oro">Guardar</button>
    </form>
    <p id="claveMensaje" class="aviso"></p>
  </div>
</div>

<script>
function mdpMarcarActivo(el) {
  document.querySelectorAll('.nav-item').forEach(function (n) { n.classList.remove('activo'); });
  el.classList.add('activo');
  var sb = document.getElementById('mdpSidebar');
  var ov = document.getElementById('mdpOverlay');
  if (sb) sb.classList.remove('abierto');
  if (ov) ov.classList.remove('abierto');
}

function mdpAbrirModalClave() {
  document.getElementById('modalClave').classList.add('abierto');
}

function mdpCerrarModalClave() {
  document.getElementById('modalClave').classList.remove('abierto');
  document.getElementById('formClave').reset();
  var msj = document.getElementById('claveMensaje');
  msj.textContent = '';
}

async function mdpGuardarClave(e) {
  e.preventDefault();
  var form = e.target;
  var msj = document.getElementById('claveMensaje');
  msj.style.color = '';
  msj.textContent = 'Guardando...';

  try {
    var res = await fetch('cambiar_clave.php', { method: 'POST', body: new FormData(form) });
    var data = await res.json();
    if (data.ok) {
      msj.style.color = '#2e7d32';
      msj.textContent = data.mensaje;
      form.reset();
    } else {
      msj.style.color = '#a33';
      msj.textContent = data.error || 'No se pudo cambiar la clave.';
    }
  } catch (err) {
    msj.style.color = '#a33';
    msj.textContent = 'Error de conexión.';
  }
  return false;
}

// ===== Reordenar el menú arrastrando (se guarda por usuario) =====
(function () {
  var nav = document.querySelector('.sidebar-nav');
  if (!nav) return;
  var arrastrando = null;

  function itemsActuales() {
    return Array.prototype.slice.call(nav.querySelectorAll('.nav-item'));
  }

  function guardarOrden() {
    var orden = itemsActuales().map(function (n) { return n.dataset.tool; });
    fetch('guardar_orden_menu.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(orden)
    }).catch(function () { /* si falla, el orden vuelve a como estaba al recargar */ });
  }

  itemsActuales().forEach(function (item) {
    item.addEventListener('dragstart', function () {
      arrastrando = item;
      item.classList.add('arrastrando');
    });
    item.addEventListener('dragend', function () {
      item.classList.remove('arrastrando');
      arrastrando = null;
      guardarOrden();
    });
    item.addEventListener('dragover', function (e) {
      e.preventDefault();
      if (!arrastrando || arrastrando === item) return;
      var rect = item.getBoundingClientRect();
      var mitad = rect.top + rect.height / 2;
      if (e.clientY < mitad) {
        nav.insertBefore(arrastrando, item);
      } else {
        nav.insertBefore(arrastrando, item.nextSibling);
      }
    });
  });
})();
</script>
