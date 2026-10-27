<?php
// lib.php — Documentación: datos, archivos y permisos.
// Datos en data/documentacion.json · archivos en data/archivos/ (protegidos con .htaccess).
date_default_timezone_set('America/Argentina/Buenos_Aires');

const DOC_DATA     = __DIR__ . '/data/documentacion.json';
const DOC_ARCHIVOS = __DIR__ . '/data/archivos';
const DOC_MAX_MB   = 25;
const DOC_DIAS_NUEVO = 7;   // días que se muestra la etiqueta NUEVO / ACTUALIZADO

// Tipos permitidos: extensión => [tipo para el ícono, Content-Type, se puede ver en el navegador]
const DOC_TIPOS = [
    'pdf'  => ['pdf', 'application/pdf', true],
    'jpg'  => ['img', 'image/jpeg', true],
    'jpeg' => ['img', 'image/jpeg', true],
    'png'  => ['img', 'image/png', true],
    'webp' => ['img', 'image/webp', true],
    'gif'  => ['img', 'image/gif', true],
    'mp4'  => ['vid', 'video/mp4', true],
    'doc'  => ['doc', 'application/msword', false],
    'docx' => ['doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', false],
    'xls'  => ['xls', 'application/vnd.ms-excel', false],
    'xlsx' => ['xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', false],
    'ppt'  => ['ppt', 'application/vnd.ms-powerpoint', false],
    'pptx' => ['ppt', 'application/vnd.openxmlformats-officedocument.presentationml.presentation', false],
];

// Categorías iniciales, armadas a partir de los instructivos que ya existen
function doc_categorias_iniciales() {
    return [
        ['id' => 'ventas',       'nombre' => 'Ventas y cobros',        'icono' => '💰'],
        ['id' => 'catalogo',     'nombre' => 'Catálogo de productos',  'icono' => '🛒'],
        ['id' => 'presupuestos', 'nombre' => 'Presupuestos y remitos', 'icono' => '📄'],
        ['id' => 'tareas',       'nombre' => 'Tareas',                 'icono' => '✅'],
        ['id' => 'comunicados',  'nombre' => 'Comunicados',            'icono' => '📣'],
    ];
}

function doc_vacio() { return ['ultimo_id' => 0, 'categorias' => doc_categorias_iniciales(), 'docs' => []]; }

function doc_leer() {
    if (!file_exists(DOC_DATA)) return doc_vacio();
    $fp = fopen(DOC_DATA, 'r'); flock($fp, LOCK_SH);
    $d = json_decode(stream_get_contents($fp), true);
    flock($fp, LOCK_UN); fclose($fp);
    return is_array($d) ? $d : doc_vacio();
}

// Lee, modifica y guarda con bloqueo (si la función devuelve false, no se guarda)
function doc_modificar(callable $fn) {
    if (!is_dir(dirname(DOC_DATA))) mkdir(dirname(DOC_DATA), 0755, true);
    $nuevo = !file_exists(DOC_DATA);
    $fp = fopen(DOC_DATA, 'c+');
    if (!$fp || !flock($fp, LOCK_EX)) throw new RuntimeException('No se pudo abrir el archivo de documentación');
    $d = $nuevo ? null : json_decode(stream_get_contents($fp), true);
    if (!is_array($d)) $d = doc_vacio();
    try {
        $r = $fn($d);
        if ($r !== false) {
            ftruncate($fp, 0); rewind($fp);
            fwrite($fp, json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            fflush($fp);
        }
    } finally {
        flock($fp, LOCK_UN); fclose($fp);
    }
    return $r;
}

// Empleados que deben confirmar lectura: usuarios con rol "usuario" (los admins no cuentan)
function doc_empleados() {
    $out = [];
    foreach (load_json(USERS_FILE) as $u) {
        if (!empty($u['usuario']) && ($u['rol'] ?? 'usuario') !== 'admin') $out[] = $u['usuario'];
    }
    return $out;
}

function doc_buscar_indice($d, $id) {
    foreach ($d['docs'] as $i => $doc) if ((int)$doc['id'] === (int)$id) return $i;
    return null;
}

function doc_lectura_de($doc, $usuario) {
    foreach (($doc['lecturas'] ?? []) as $u => $f) if (strcasecmp($u, $usuario) === 0) return $f;
    return null;
}

// Cantidad de documentos que el usuario tiene pendientes de confirmar
function doc_pendientes($usuario) {
    if (!$usuario || is_admin()) return 0;
    $n = 0;
    foreach (doc_leer()['docs'] as $doc) if (!empty($doc['confirmar']) && !doc_lectura_de($doc, $usuario)) $n++;
    return $n;
}

// Valida y guarda un archivo subido. Devuelve los datos del archivo o lanza excepción con el motivo.
function doc_guardar_subida($f, $id) {
    if (!$f || !isset($f['error'])) throw new RuntimeException('No llegó ningún archivo');
    if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
        throw new RuntimeException('El archivo supera el máximo permitido por el servidor (' . ini_get('upload_max_filesize') . ')');
    }
    if ($f['error'] !== UPLOAD_ERR_OK) throw new RuntimeException('Error al subir el archivo (código ' . $f['error'] . ')');
    if ($f['size'] > DOC_MAX_MB * 1048576) throw new RuntimeException('El archivo pesa más de ' . DOC_MAX_MB . ' MB');
    if ($f['size'] <= 0) throw new RuntimeException('El archivo está vacío');

    $original = basename(str_replace('\\', '/', (string)$f['name']));
    $ext = strtolower(pathinfo($original, PATHINFO_EXTENSION));
    if (!isset(DOC_TIPOS[$ext])) {
        throw new RuntimeException('Tipo de archivo no permitido. Se aceptan: ' . implode(', ', array_keys(DOC_TIPOS)));
    }
    // Control extra del contenido real (si el hosting tiene finfo)
    if (function_exists('finfo_open')) {
        $mime = (string)finfo_file(finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']);
        $esperado = DOC_TIPOS[$ext][1];
        $grupo = explode('/', $esperado)[0];
        $okOffice = in_array($ext, ['doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'], true)
            && preg_match('#^application/(zip|octet-stream|vnd\.|msword|x-ole-storage|CDFV2)#i', $mime);
        if ($mime !== $esperado && strpos($mime, $grupo . '/') !== 0 && !$okOffice) {
            throw new RuntimeException('El contenido del archivo no coincide con su extensión');
        }
    }
    if (!is_dir(DOC_ARCHIVOS)) mkdir(DOC_ARCHIVOS, 0755, true);
    $guardado = $id . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], DOC_ARCHIVOS . '/' . $guardado)) {
        throw new RuntimeException('No se pudo guardar el archivo en el servidor');
    }
    return [
        'original' => mb_substr($original, 0, 150),
        'guardado' => $guardado,
        'ext' => $ext,
        'tipo' => DOC_TIPOS[$ext][0],
        'tamano' => (int)$f['size'],
    ];
}

function doc_ahora() { return date('Y-m-d H:i'); }
function doc_texto($s, $max) { return mb_substr(trim(preg_replace('/\s+/u', ' ', (string)$s)), 0, $max); }
