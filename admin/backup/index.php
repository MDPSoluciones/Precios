<?php
// Backups — solo administradores
require __DIR__ . '/../config.php';
require_admin();
require __DIR__ . '/lib.php';

if (empty($_SESSION['backup_csrf'])) $_SESSION['backup_csrf'] = bin2hex(random_bytes(24));
$msg = null; $err = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['backup_csrf'], (string)($_POST['csrf'] ?? ''))) {
        $msg = 'La sesión venció. Probá de nuevo.'; $err = true;
    } else {
        $op = $_POST['op'] ?? '';
        if ($op === 'config') {
            $email = trim($_POST['email'] ?? '');
            if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $msg = 'La casilla de mail no es válida'; $err = true;
            } else {
                $c = bk_config();
                $c['email'] = $email;
                $c['dias'] = max(7, min(365, (int)($_POST['dias'] ?? 30)));
                bk_guardar_config($c);
                $msg = 'Configuración guardada';
            }
        } elseif ($op === 'generar') {
            $r = bk_generar('manual · ' . current_user());
            $msg = $r['mensaje'] . (isset($r['archivo']) ? ': ' . $r['archivo'] : ''); $err = !$r['ok'];
        } elseif ($op === 'mail') {
            $r = bk_enviar_mail($_POST['f'] ?? '');
            $msg = $r['mensaje']; $err = !$r['ok'];
        }
    }
    // Patrón POST → redirect → GET (evita reenviar al recargar)
    $_SESSION['backup_msg'] = [$msg, $err];
    header('Location: index.php');
    exit;
}
if (!empty($_SESSION['backup_msg'])) { [$msg, $err] = $_SESSION['backup_msg']; unset($_SESSION['backup_msg']); }

$cfg = bk_config();
$lista = bk_listar();
$ultimo = bk_ultimo();
$archivos = bk_archivos();
$h = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
$rutaScript = __DIR__ . '/backup_diario.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Backups - Panel MDP Soluciones</title>
<link rel="icon" href="https://mdpsoluciones.com.ar/images/logoCircDor.png" type="image/x-icon">
<link rel="stylesheet" href="../assets/style.css?v=3">
<style>
  .bk-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:16px;margin-bottom:18px}
  .bk-card{background:#fff;border-radius:14px;padding:18px 20px;box-shadow:var(--sombra)}
  .bk-card h2{font-size:22px;margin-bottom:10px}
  .bk-card p{margin:6px 0;font-size:14px}
  .bk-card label{display:block;font-size:12px;font-weight:600;color:#7a776e;margin:10px 0 4px;text-transform:uppercase;letter-spacing:.05em}
  .bk-card input{width:100%;padding:9px 11px;border:1px solid var(--humo);border-radius:8px;font-family:'DM Sans',sans-serif;font-size:14px}
  .bk-card .btn{margin-top:12px}
  .ok{color:#2f7d4f;font-weight:700}.mal{color:#a33;font-weight:700}
  .msg{padding:10px 14px;border-radius:10px;margin-bottom:16px;font-weight:600;background:#e6efe8;color:#2f7d4f}
  .msg.err{background:#fbe5e5;color:#a33}
  table{width:100%;border-collapse:collapse;background:#fff;border-radius:14px;overflow:hidden;box-shadow:var(--sombra);font-size:14px}
  th{text-align:left;font-size:11px;letter-spacing:.06em;text-transform:uppercase;color:#6b6b6b;background:#f3f0e8;padding:10px 12px}
  td{padding:9px 12px;border-top:1px solid #f0ece2}
  td form{display:inline}
  .btn.mini{padding:5px 11px;font-size:12px;margin:0;text-decoration:none;display:inline-block}
  code{background:#f3f0e8;padding:3px 6px;border-radius:5px;font-size:12.5px;word-break:break-all}
  details{margin-top:18px;background:#fff;border-radius:14px;padding:14px 18px;box-shadow:var(--sombra)}
  details summary{cursor:pointer;font-weight:600}
  details ul{columns:2;font-size:12.5px;color:#555}
  @media (max-width:700px){details ul{columns:1}}
</style>
</head>
<body>
<div class="frame-page">
  <div class="contenido-header">
    <h1>Backups</h1>
    <p>Copia diaria de todos los datos del panel (presupuestos, clientes, catálogo, usuarios, tareas, etc.).</p>
  </div>

  <?php if ($msg): ?><div class="msg <?= $err ? 'err' : '' ?>"><?= $h($msg) ?></div><?php endif; ?>

  <div class="bk-grid">
    <div class="bk-card">
      <h2>Último backup</h2>
      <?php if ($ultimo): ?>
        <p><?= $ultimo['ok'] ? '<span class="ok">✓ Correcto</span>' : '<span class="mal">✕ Con error</span>' ?> · <?= $h(date('d/m/Y H:i', strtotime($ultimo['cuando']))) ?> (<?= $h($ultimo['origen'] ?? '') ?>)</p>
        <p><?= $h($ultimo['mensaje']) ?><?= isset($ultimo['cantidad']) ? ' · ' . (int)$ultimo['cantidad'] . ' archivos' : '' ?></p>
        <?php if (!empty($ultimo['mail'])): ?><p>Mail: <?= $ultimo['mail']['ok'] ? '<span class="ok">' : '<span class="mal">' ?><?= $h($ultimo['mail']['mensaje']) ?></span></p><?php endif; ?>
        <?php if (strtotime($ultimo['cuando']) < time() - 36 * 3600): ?><p class="mal">⚠ Hace más de un día que no se genera. Revisá el Cron Job en Hostinger.</p><?php endif; ?>
      <?php else: ?>
        <p class="mal">Todavía no se generó ningún backup.</p>
      <?php endif; ?>
      <form method="post"><input type="hidden" name="csrf" value="<?= $h($_SESSION['backup_csrf']) ?>"><input type="hidden" name="op" value="generar">
        <button class="btn btn-oro">Generar backup ahora</button></form>
    </div>

    <div class="bk-card">
      <h2>Envío por mail</h2>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= $h($_SESSION['backup_csrf']) ?>"><input type="hidden" name="op" value="config">
        <label for="email">Casilla que recibe el backup diario</label>
        <input id="email" name="email" type="email" value="<?= $h($cfg['email']) ?>" placeholder="ej: backups.mdp@gmail.com">
        <label for="dias">Días que se guardan en el servidor</label>
        <input id="dias" name="dias" type="number" min="7" max="365" value="<?= (int)$cfg['dias'] ?>">
        <button class="btn btn-oro">Guardar</button>
      </form>
      <p style="font-size:12.5px;color:#7a776e">Si queda vacío, el backup se genera igual pero no se envía. Revisá la carpeta de spam la primera vez.</p>
    </div>
  </div>

  <table>
    <tr><th>Backup</th><th>Fecha</th><th>Tamaño</th><th></th></tr>
    <?php foreach ($lista as $b): ?>
    <tr>
      <td><?= $h($b['nombre']) ?></td>
      <td><?= $h(date('d/m/Y H:i', $b['fecha'])) ?></td>
      <td><?= $h(bk_tamano($b['tamano'])) ?></td>
      <td style="text-align:right">
        <a class="btn btn-secundario mini" href="descargar.php?f=<?= urlencode($b['nombre']) ?>">⬇ Descargar</a>
        <?php if ($cfg['email']): ?>
        <form method="post"><input type="hidden" name="csrf" value="<?= $h($_SESSION['backup_csrf']) ?>"><input type="hidden" name="op" value="mail"><input type="hidden" name="f" value="<?= $h($b['nombre']) ?>">
          <button class="btn btn-secundario mini">✉ Enviar</button></form>
        <?php endif; ?>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php if (!$lista): ?><tr><td colspan="4" style="color:#7a776e">No hay backups todavía.</td></tr><?php endif; ?>
  </table>

  <details>
    <summary>Qué se incluye (<?= count($archivos) ?> archivos) y cómo se programa</summary>
    <ul><?php foreach ($archivos as $rel): ?><li><?= $h($rel) ?></li><?php endforeach; ?></ul>
    <p style="font-size:13px">Cron Job en Hostinger (hPanel → Avanzado → Cron Jobs), una vez por día:<br>
      <code>php <?= $h($rutaScript) ?></code></p>
  </details>
</div>
</body>
</html>
