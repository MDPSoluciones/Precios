<?php
// backup_diario.php — lo ejecuta el Cron Job de Hostinger una vez por día.
// Comando en hPanel → Avanzado → Cron Jobs:
//   php /home/USUARIO/public_html/admin/backup/backup_diario.php
// No se puede abrir desde el navegador.
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Este script solo lo ejecuta el Cron Job del hosting.');
}

require __DIR__ . '/lib.php';

$r = bk_generar('automático');
echo date('Y-m-d H:i:s') . ' · ' . $r['mensaje'] . (isset($r['archivo']) ? " · {$r['archivo']} ({$r['cantidad']} archivos)" : '') . PHP_EOL;

if ($r['ok'] && bk_config()['email']) {
    $m = bk_enviar_mail($r['archivo']);
    $r['mail'] = $m;
    bk_registrar($r);
    echo 'Mail: ' . $m['mensaje'] . PHP_EOL;
}
exit($r['ok'] ? 0 : 1);
