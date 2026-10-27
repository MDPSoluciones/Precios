<?php
// lib.php — Backup de todos los datos de la web (archivos .json / .txt que funcionan como base de datos).
// Lo usan: backup_diario.php (Cron Job de Hostinger) e index.php (página de admin).
date_default_timezone_set('America/Argentina/Buenos_Aires');

define('BK_RAIZ_ADMIN', realpath(__DIR__ . '/..'));          // carpeta admin/
define('BK_RAIZ_SITIO', realpath(__DIR__ . '/../..'));       // raíz del sitio (public_html)
define('BK_DIR', __DIR__ . '/data/backups');                  // dónde quedan los .zip
define('BK_CONFIG', __DIR__ . '/data/config.json');           // casilla de mail, días a guardar
define('BK_ESTADO', __DIR__ . '/data/ultimo.json');           // resultado del último backup

// Carpetas a revisar (con todas sus subcarpetas) y qué se excluye.
function bk_origenes() {
    return [
        'admin' => BK_RAIZ_ADMIN,
        'data'  => BK_RAIZ_SITIO . '/data',      // catálogo publicado del sitio (data/catalogo.json)
    ];
}
function bk_excluido($rutaRelativa) {
    // No se copian los propios backups, backups viejos, cachés ni temporales
    return (bool)preg_match('#(^|/)(backups?|Backups|cache|node_modules|\.git)(/|$)|\.tmp$|\.lock$#i', $rutaRelativa);
}

function bk_config() {
    $c = file_exists(BK_CONFIG) ? json_decode(file_get_contents(BK_CONFIG), true) : null;
    return array_merge(['email' => '', 'dias' => 30, 'remitente' => 'backup@mdpsoluciones.com.ar'], is_array($c) ? $c : []);
}
function bk_guardar_config($c) {
    if (!is_dir(dirname(BK_CONFIG))) mkdir(dirname(BK_CONFIG), 0755, true);
    file_put_contents(BK_CONFIG, json_encode($c, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

// Lista de archivos de datos: [ruta absoluta => ruta dentro del zip]
function bk_archivos() {
    $lista = [];
    foreach (bk_origenes() as $nombre => $dir) {
        if (!$dir || !is_dir($dir)) continue;
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile()) continue;
            $ext = strtolower($f->getExtension());
            if (!in_array($ext, ['json', 'txt'], true)) continue;
            $rel = $nombre . '/' . ltrim(str_replace('\\', '/', substr($f->getPathname(), strlen($dir))), '/');
            if (bk_excluido($rel)) continue;
            $lista[$f->getPathname()] = $rel;
        }
    }
    ksort($lista);
    return $lista;
}

// Genera el backup. Devuelve ['ok'=>bool, 'archivo'=>..., 'cantidad'=>..., 'tamano'=>..., 'mensaje'=>...]
function bk_generar($origen = 'manual') {
    if (!is_dir(BK_DIR)) mkdir(BK_DIR, 0755, true);
    $archivos = bk_archivos();
    if (!$archivos) return bk_registrar(['ok' => false, 'mensaje' => 'No se encontraron archivos de datos', 'origen' => $origen]);

    $base = 'backup_' . date('Y-m-d_Hi');
    $manifiesto = ['generado' => date('Y-m-d H:i:s'), 'origen' => $origen, 'archivos' => array_values($archivos)];

    if (class_exists('ZipArchive')) {
        $destino = BK_DIR . "/$base.zip";
        $zip = new ZipArchive();
        if ($zip->open($destino, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return bk_registrar(['ok' => false, 'mensaje' => 'No se pudo crear el zip', 'origen' => $origen]);
        }
        foreach ($archivos as $abs => $rel) $zip->addFile($abs, $rel);
        $zip->addFromString('LEEME.json', json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $zip->close();
    } else {
        // Plan B si el hosting no tiene ZipArchive: .tar.gz
        $tar = BK_DIR . "/$base.tar";
        $ph = new PharData($tar);
        foreach ($archivos as $abs => $rel) $ph->addFile($abs, $rel);
        $ph->addFromString('LEEME.json', json_encode($manifiesto, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $ph->compress(Phar::GZ);
        unset($ph);
        @unlink($tar);
        $destino = "$tar.gz";
    }

    $borrados = bk_rotar();
    return bk_registrar([
        'ok' => true, 'origen' => $origen,
        'archivo' => basename($destino), 'cantidad' => count($archivos), 'tamano' => filesize($destino),
        'mensaje' => 'Backup generado' . ($borrados ? " ($borrados viejo(s) eliminado(s))" : ''),
    ]);
}

// Borra los backups más viejos que N días (siempre deja al menos los 5 últimos)
function bk_rotar() {
    $dias = max(1, (int)bk_config()['dias']);
    $lista = bk_listar();
    $borrados = 0;
    foreach (array_slice($lista, 5) as $b) {
        if ($b['fecha'] < time() - $dias * 86400) { @unlink(BK_DIR . '/' . $b['nombre']); $borrados++; }
    }
    return $borrados;
}

function bk_listar() {
    $out = [];
    foreach (glob(BK_DIR . '/backup_*.{zip,tar.gz}', GLOB_BRACE) ?: [] as $f) {
        $out[] = ['nombre' => basename($f), 'tamano' => filesize($f), 'fecha' => filemtime($f)];
    }
    usort($out, fn($a, $b) => $b['fecha'] <=> $a['fecha']);
    return $out;
}

// Ruta segura de un backup existente (evita que pidan cualquier archivo del servidor)
function bk_ruta($nombre) {
    if (!preg_match('/^backup_\d{4}-\d{2}-\d{2}_\d{4}\.(zip|tar\.gz)$/', (string)$nombre)) return null;
    $f = BK_DIR . '/' . $nombre;
    return is_file($f) ? $f : null;
}

// Envía un backup por mail como adjunto (usa mail() del hosting)
function bk_enviar_mail($nombre) {
    $c = bk_config();
    $para = trim($c['email']);
    if (!$para) return ['ok' => false, 'mensaje' => 'No hay casilla de mail configurada'];
    if (!filter_var($para, FILTER_VALIDATE_EMAIL)) return ['ok' => false, 'mensaje' => 'La casilla configurada no es válida'];
    $f = bk_ruta($nombre);
    if (!$f) return ['ok' => false, 'mensaje' => 'No existe el backup'];
    if (filesize($f) > 15 * 1024 * 1024) return ['ok' => false, 'mensaje' => 'El backup pesa más de 15 MB; descargalo desde el panel'];

    $limite = 'mdp' . bin2hex(random_bytes(8));
    $asunto = 'Backup MDP Soluciones ' . date('d/m/Y');
    $remitente = $c['remitente'] ?: 'backup@mdpsoluciones.com.ar';
    $cabeceras = "From: Panel MDP <$remitente>\r\nMIME-Version: 1.0\r\nContent-Type: multipart/mixed; boundary=\"$limite\"";
    $texto = "Backup automático de los datos del panel de MDP Soluciones.\r\n\r\n"
           . "Archivo: $nombre\r\nGenerado: " . date('d/m/Y H:i', filemtime($f)) . "\r\n\r\n"
           . "Guardalo en un lugar seguro. Para restaurar, avisá al administrador del sitio.\r\n";
    $cuerpo = "--$limite\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: 8bit\r\n\r\n$texto\r\n"
            . "--$limite\r\nContent-Type: application/octet-stream; name=\"$nombre\"\r\n"
            . "Content-Transfer-Encoding: base64\r\nContent-Disposition: attachment; filename=\"$nombre\"\r\n\r\n"
            . chunk_split(base64_encode(file_get_contents($f))) . "\r\n--$limite--";
    $ok = @mail($para, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $cuerpo, $cabeceras);
    return ['ok' => $ok, 'mensaje' => $ok ? "Enviado a $para" : 'El servidor no pudo enviar el mail'];
}

function bk_registrar($r) {
    $r['cuando'] = date('Y-m-d H:i:s');
    if (!is_dir(dirname(BK_ESTADO))) mkdir(dirname(BK_ESTADO), 0755, true);
    file_put_contents(BK_ESTADO, json_encode($r, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return $r;
}
function bk_ultimo() { return file_exists(BK_ESTADO) ? json_decode(file_get_contents(BK_ESTADO), true) : null; }
function bk_tamano($b) { return $b > 1048576 ? round($b / 1048576, 1) . ' MB' : max(1, round($b / 1024)) . ' KB'; }
