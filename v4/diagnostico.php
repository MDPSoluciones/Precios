<?php
// diagnostico.php — revisa por qué la v4 no puede leer Cocos (extensiones, certificados, conexión).
// Se puede borrar cuando todo funcione.
ini_set('display_errors', '1');
error_reporting(E_ALL);
header('Content-Type: text/plain; charset=utf-8');

echo "PHP " . PHP_VERSION . " (" . PHP_OS . ")\n";
echo "php.ini: " . (php_ini_loaded_file() ?: 'NINGUNO (PHP está usando valores por defecto)') . "\n\n";

echo "== Extensiones\n";
foreach (['curl', 'mbstring', 'openssl', 'json'] as $ext) {
    echo str_pad($ext, 10) . (extension_loaded($ext) ? 'OK' : 'FALTA') . "\n";
}
echo "\n== Configuración\n";
foreach (['curl.cainfo', 'openssl.cafile', 'allow_url_fopen', 'extension_dir'] as $k) {
    echo str_pad($k, 16) . var_export(ini_get($k), true) . "\n";
}

echo "\n== Carpeta de caché\n";
$dir = __DIR__ . '/cache';
if (!is_dir($dir)) @mkdir($dir, 0755, true);
echo is_dir($dir) && is_writable($dir) ? "OK, se puede escribir en $dir\n" : "NO se puede escribir en $dir\n";

function probar($nombre, $url, $body = null, $headers = [], $extra = []) {
    echo "\n== $nombre\n$url\n";
    if (!function_exists('curl_init')) { echo "No se puede probar: falta la extensión curl.\n"; return; }
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10, CURLOPT_HTTPHEADER => $headers] + $extra);
    if ($body !== null) { curl_setopt($ch, CURLOPT_POST, true); curl_setopt($ch, CURLOPT_POSTFIELDS, $body); }
    $raw = curl_exec($ch);
    echo "HTTP " . curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if (curl_errno($ch)) echo " | ERROR curl " . curl_errno($ch) . ": " . curl_error($ch);
    echo " | " . strlen((string)$raw) . " bytes\n";
    if ($raw !== false && strlen($raw) < 400) echo "Respuesta: $raw\n";
    curl_close($ch);
}

require __DIR__ . '/cocos_lib.php';
$body = json_encode(['p_slug' => COCOS_SLUG]);
$ua = [CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; MDPSoluciones-Catalogo/1.0)'];
probar('Cocos documentada, con navegador', COCOS_API . 'get_public_catalog_inventory', $body, cocos_headers(), $ua);
probar('Cocos documentada, solo IPv4', COCOS_API . 'get_public_catalog_inventory', $body, cocos_headers(), $ua + [CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4]);
probar('Cocos servidor de datos (directo)', COCOS_REST . 'rpc/get_public_catalog_inventory', $body, cocos_headers(), $ua);
probar('Dólar (DolarAPI)', COCOS_DOLAR_API);

echo "\n== Armado del catálogo\n";
try {
    $c = cocos_catalogo();
    echo "OK: " . count($c['productos']) . " productos, " . $c['unidades'] . " unidades, dólar $" . $c['info']['cotizacion'] . " (datos: " . $c['info']['origen'] . ")\n";
} catch (Throwable $e) {
    echo get_class($e) . ': ' . $e->getMessage() . "\n" . basename($e->getFile()) . ':' . $e->getLine() . "\n";
}
