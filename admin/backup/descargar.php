<?php
// Descarga un backup (solo administradores)
require __DIR__ . '/../config.php';
require_admin();
require __DIR__ . '/lib.php';

$f = bk_ruta($_GET['f'] ?? '');
if (!$f) { http_response_code(404); exit('Backup no encontrado'); }

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($f) . '"');
header('Content-Length: ' . filesize($f));
header('X-Content-Type-Options: nosniff');
readfile($f);
