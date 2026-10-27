<?php
// ver.php?id=N[&descargar=1] — entrega el archivo solo a usuarios con sesión iniciada.
require __DIR__ . '/../config.php';
require_login();
require __DIR__ . '/lib.php';

$d = doc_leer();
$i = doc_buscar_indice($d, (int)($_GET['id'] ?? 0));
if ($i === null) { http_response_code(404); exit('Documento no encontrado'); }
$doc = $d['docs'][$i];
$a = $doc['archivo'];
$ruta = DOC_ARCHIVOS . '/' . basename($a['guardado']);
if (!is_file($ruta) || !isset(DOC_TIPOS[$a['ext']])) { http_response_code(404); exit('Archivo no encontrado'); }

[$tipo, $mime, $enNavegador] = DOC_TIPOS[$a['ext']];
$descargar = !empty($_GET['descargar']) || !$enNavegador;

// Nombre de descarga: el título del documento + extensión
$nombre = trim(preg_replace('/[^\w\-. ]+/u', '', $doc['titulo'])) ?: 'documento';
$nombre .= '.' . $a['ext'];

$tam = filesize($ruta);
$inicio = 0; $fin = $tam - 1;

header('Content-Type: ' . $mime);
header('X-Content-Type-Options: nosniff');
header('Accept-Ranges: bytes');
header('Cache-Control: private, max-age=300');
$ascii = preg_replace('/[^A-Za-z0-9\-. ]+/', '', (string)@iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre)) ?: 'documento.' . $a['ext'];
header("Content-Disposition: " . ($descargar ? 'attachment' : 'inline') . "; filename=\"$ascii\"; filename*=UTF-8''" . rawurlencode($nombre));

// Soporte de rangos (para poder adelantar los videos)
if (isset($_SERVER['HTTP_RANGE']) && preg_match('/bytes=(\d*)-(\d*)/', $_SERVER['HTTP_RANGE'], $m)) {
    if ($m[1] !== '') { $inicio = (int)$m[1]; if ($m[2] !== '') $fin = min((int)$m[2], $tam - 1); }
    elseif ($m[2] !== '') { $inicio = max(0, $tam - (int)$m[2]); }
    if ($inicio > $fin || $inicio >= $tam) { http_response_code(416); header("Content-Range: bytes */$tam"); exit; }
    http_response_code(206);
    header("Content-Range: bytes $inicio-$fin/$tam");
}
header('Content-Length: ' . ($fin - $inicio + 1));

$fp = fopen($ruta, 'rb');
fseek($fp, $inicio);
$quedan = $fin - $inicio + 1;
while ($quedan > 0 && !feof($fp)) {
    $bloque = fread($fp, min(65536, $quedan));
    echo $bloque;
    $quedan -= strlen($bloque);
    flush();
}
fclose($fp);
