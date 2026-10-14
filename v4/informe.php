<?php
// informe.php — VERSIÓN DE PRUEBA (v4): muestra cómo se traduce el inventario de Cocos a la web.
require __DIR__ . '/cocos_lib.php';

$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$n = fn($v) => number_format((float)$v, 0, ',', '.');
$orden = ['Apple Nuevos', 'Apple Usados', 'Android Nuevos', 'Android Usados', 'Notebooks Nuevas', 'Notebooks Usadas',
          'PC Escritorio', 'Tablets Nuevas', 'Tablets Usadas', 'Accesorios'];

try {
    $c = cocos_catalogo();
    $error = null;
} catch (Throwable $e) {
    $c = null;
    $error = $e->getMessage();
}

if ($c) {
    $det = $c['detalle'];
    usort($det, fn($a, $b) => (array_search($a['condicion'], $orden) <=> array_search($b['condicion'], $orden))
        ?: strcmp($a['tipo'], $b['tipo']) ?: strcmp($a['producto'], $b['producto']));
    $resumen = [];
    foreach ($det as $p) {
        $k = $p['condicion'] . ($p['condicion'] === 'Accesorios' ? ' › ' . $p['tipo'] : '');
        $resumen[$k] = ($resumen[$k] ?? 0) + 1;
    }
    $sinFoto = count(array_filter($det, fn($p) => $p['imagen'] === ''));
    $porRegla = count(array_filter($det, fn($p) => strpos($p['regla'], 'palabra') === 0 || strpos($p['regla'], 'sin') === 0));
    $i = $c['info'];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex">
<title>Informe Cocos → web (prueba)</title>
<style>
  :root{--carbon:#303030;--humo:#D9DAD4;--oro:#BEA167;--crema:#F5F1E8}
  *{box-sizing:border-box}
  body{margin:0;font-family:Arial,Helvetica,sans-serif;background:var(--crema);color:var(--carbon);padding:28px 20px 60px}
  .wrap{max-width:1200px;margin:0 auto}
  h1{font-size:26px;margin:0 0 4px}
  h2{font-size:18px;margin:34px 0 10px;border-bottom:2px solid var(--oro);padding-bottom:6px}
  .sub{color:#7a776e;margin:0 0 20px}
  .kpis{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px}
  .kpi{background:#fff;border-radius:10px;padding:14px 16px;box-shadow:0 2px 10px rgba(0,0,0,.05)}
  .kpi b{display:block;font-size:24px;margin-bottom:2px}
  .kpi span{font-size:13px;color:#7a776e}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:10px;overflow:hidden;font-size:13px}
  th{text-align:left;background:#efece4;font-size:11px;letter-spacing:.05em;text-transform:uppercase;color:#6b6b6b;padding:9px 10px}
  td{padding:8px 10px;border-top:1px solid #eee;vertical-align:middle}
  td.num,th.num{text-align:right;white-space:nowrap}
  img{width:42px;height:42px;object-fit:contain;background:var(--crema);border-radius:6px;display:block}
  .tag{font-size:11px;color:#7a776e}
  .aviso{background:#fff7e0;border:1px solid #e8dcb8;border-radius:10px;padding:12px 14px;font-size:14px;margin-top:18px}
  .err{background:#fde8e8;border:1px solid #e9b8b8}
  .tabla-wrap{overflow-x:auto}
  a{color:#8a6f36}
</style>
</head>
<body>
<div class="wrap">
  <h1>Informe: lista de precios armada desde Cocos CRM</h1>
  <p class="sub">Versión de prueba (v4). Muestra qué productos de Cocos aparecen en la web, en qué categoría y con qué foto. <a href="./">Ver el sitio de prueba</a></p>

<?php if ($error): ?>
  <div class="aviso err"><?= $h($error) ?></div>
<?php else: ?>
  <div class="kpis">
    <div class="kpi"><b><?= count($c['productos']) ?></b><span>productos en la web (agrupados)</span></div>
    <div class="kpi"><b><?= $c['unidades'] ?></b><span>unidades disponibles en Cocos</span></div>
    <div class="kpi"><b><?= count($c['sin_precio']) ?></b><span>unidades sin precio (no se muestran)</span></div>
    <div class="kpi"><b>$<?= $n($i['cotizacion']) ?></b><span>dólar usado<?= isset($i['blue']) ? ' (blue $' . $n($i['blue']) . ($i['modo'] === 'api_plus' ? ' + $' . $n($i['valor_config']) : '') . ')' : ' (fijo en Cocos)' ?></span></div>
    <div class="kpi"><b><?= $porRegla ?></b><span>productos ubicados por palabra clave</span></div>
    <div class="kpi"><b><?= $sinFoto ?></b><span>productos sin foto</span></div>
  </div>
  <p class="tag">Datos de Cocos <?= $i['origen'] === 'cocos' ? 'recién consultados' : 'guardados hace ' . round(($i['edad'] ?? 0) / 60) . ' min' ?>. Transferencia: +<?= $h($i['pct_transf']) ?> % (configuración de Cocos). Las notas internas de los equipos no se leen ni se publican.</p>

  <h2>Productos por sección del menú</h2>
  <div class="tabla-wrap"><table>
    <tr><th>Sección</th><th class="num">Productos</th></tr>
    <?php foreach ($resumen as $k => $v): ?><tr><td><?= $h($k) ?></td><td class="num"><?= $v ?></td></tr><?php endforeach; ?>
  </table></div>

  <h2>Detalle de cada producto</h2>
  <div class="tabla-wrap"><table>
    <tr><th></th><th>Producto</th><th>Sección web</th><th class="num">Unid.</th><th class="num">USD</th><th class="num">Efectivo</th><th>En Cocos</th><th>Cómo se ubicó</th><th>Foto</th></tr>
    <?php foreach ($det as $p):
        $src = $p['imagen'] === '' ? '../images/default.png' : (preg_match('#^https?://#', $p['imagen']) ? $p['imagen'] : '../' . $p['imagen']); ?>
    <tr>
      <td><img src="<?= $h($src) ?>" alt="" loading="lazy"></td>
      <td><b><?= $h($p['producto']) ?></b><br><span class="tag"><?= $h($p['descripcion']) ?></span></td>
      <td><?= $h($p['condicion']) ?><br><span class="tag"><?= $h($p['tipo']) ?></span></td>
      <td class="num"><?= $p['unidades'] ?></td>
      <td class="num"><?= $n($p['precioUSD']) ?></td>
      <td class="num">$<?= $n($p['precioPesos']) ?></td>
      <td><?= $h($p['cocos_categoria']) ?><br><span class="tag"><?= $h($p['cocos_estado']) ?></span></td>
      <td class="tag"><?= $h($p['regla']) ?></td>
      <td class="tag"><?= $h($p['origen_foto']) ?></td>
    </tr>
    <?php endforeach; ?>
  </table></div>

  <h2>Unidades sin precio en Cocos (no aparecen en la web)</h2>
  <?php if (!$c['sin_precio']): ?><p class="tag">Ninguna.</p><?php else: ?>
  <div class="tabla-wrap"><table>
    <tr><th>Modelo</th><th>Capacidad</th><th>Color</th><th>Estado</th><th>Categoría en Cocos</th></tr>
    <?php foreach ($c['sin_precio'] as $u): ?>
    <tr><td><?= $h($u['modelo']) ?></td><td><?= $h($u['capacidad']) ?></td><td><?= $h($u['color']) ?></td><td><?= $h($u['estado']) ?></td><td><?= $h($u['categoria']) ?></td></tr>
    <?php endforeach; ?>
  </table></div>
  <?php endif; ?>
<?php endif; ?>
</div>
</body>
</html>
