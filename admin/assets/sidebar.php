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
    'inventario'  => ['label' => 'Inventario',           'href' => 'inventario/',             'icon' => 'box'],
];

function nav_icon($name) {
    $icons = [
        'home'      => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v9a1 1 0 0 0 1 1h4v-6h4v6h4a1 1 0 0 0 1-1v-9"/>',
        'file'      => '<path d="M7 3h7l5 5v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
        'clipboard' => '<rect x="6" y="4" width="12" height="17" rx="2"/><path d="M9 4V3a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1"/><path d="m9 13 2 2 4-4"/>',
        'tag'       => '<path d="M11 3H4v7l10 10 7-7L11 3Z"/><circle cx="8" cy="8" r="1.4"/>',
        'percent'   => '<circle cx="7" cy="7" r="2.3"/><circle cx="17" cy="17" r="2.3"/><path d="M18 6 6 18"/>',
        'wallet'    => '<path d="M3 7a2 2 0 0 1 2-2h13a1 1 0 0 1 1 1v3"/><path d="M3 7v11a2 2 0 0 0 2 2h14a1 1 0 0 0 1-1v-4"/><path d="M15 13h4v4h-4a2 2 0 0 1 0-4Z"/>',
        'box'       => '<path d="M3 9.5 12 4l9 5.5V19a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1Z"/>',
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
      <a href="logout.php" target="_top">Cerrar sesión</a>
    </div>
  </div>
</aside>

<script>
function mdpMarcarActivo(el) {
  document.querySelectorAll('.nav-item').forEach(function (n) { n.classList.remove('activo'); });
  el.classList.add('activo');
  var sb = document.getElementById('mdpSidebar');
  var ov = document.getElementById('mdpOverlay');
  if (sb) sb.classList.remove('abierto');
  if (ov) ov.classList.remove('abierto');
}
</script>
