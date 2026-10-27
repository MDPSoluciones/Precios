<?php
// api.php — Documentación. Leer: todos los usuarios. Subir/editar/borrar: solo admin.
require __DIR__ . '/../config.php';
require_login_api();
require __DIR__ . '/lib.php';

function fallar($msg, $code = 400) { json_response(['status' => 'error', 'mensaje' => $msg], $code); }

$usuario = current_user();
$admin   = is_admin();
$accion  = $_GET['accion'] ?? '';

// ── Listado ──────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $accion === 'listar') {
    $d = doc_leer();
    $empleados = doc_empleados();
    $docs = [];
    foreach ($d['docs'] as $doc) {
        $item = $doc;
        unset($item['versiones']);
        $item['archivo'] = ['original' => $doc['archivo']['original'], 'ext' => $doc['archivo']['ext'],
                            'tipo' => $doc['archivo']['tipo'], 'tamano' => $doc['archivo']['tamano'],
                            'ver_en_navegador' => DOC_TIPOS[$doc['archivo']['ext']][2] ?? false];
        $item['mi_lectura'] = doc_lectura_de($doc, $usuario);
        if ($admin) {
            $item['cant_versiones'] = count($doc['versiones'] ?? []);
        } else {
            unset($item['lecturas']);   // el empleado solo ve su propia confirmación
        }
        $docs[] = $item;
    }
    json_response(['status' => 'ok', 'docs' => $docs, 'categorias' => $d['categorias'],
        'empleados' => $admin ? $empleados : [], 'yo' => $usuario, 'admin' => $admin,
        'max_mb' => min(DOC_MAX_MB, (int)ini_get('upload_max_filesize') ?: DOC_MAX_MB),
        'tipos' => array_keys(DOC_TIPOS), 'dias_nuevo' => DOC_DIAS_NUEVO]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') fallar('Método no permitido', 405);

// Las subidas llegan como formulario (archivo); el resto como JSON
$esForm = !empty($_POST) || !empty($_FILES);
$in = $esForm ? $_POST : (json_decode(file_get_contents('php://input'), true) ?: []);
if (empty($in) && empty($_FILES) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
    fallar('El archivo es demasiado grande para el servidor (máximo ' . ini_get('post_max_size') . ')', 413);
}
if (empty($_SESSION['doc_csrf']) || !hash_equals($_SESSION['doc_csrf'], (string)($in['csrf'] ?? ''))) {
    fallar('La sesión venció. Recargá la página.', 403);
}

// ── Confirmar lectura (cualquier usuario) ───────────────────────────────────
if ($accion === 'confirmar') {
    $id = (int)($in['id'] ?? 0);
    $doc = doc_modificar(function (&$d) use ($id, $usuario) {
        $i = doc_buscar_indice($d, $id);
        if ($i === null) return false;
        if (!doc_lectura_de($d['docs'][$i], $usuario)) $d['docs'][$i]['lecturas'][$usuario] = doc_ahora();
        return $d['docs'][$i];
    });
    if (!$doc) fallar('El documento no existe', 404);
    json_response(['status' => 'ok', 'fecha' => doc_lectura_de($doc, $usuario)]);
}

// ── De acá en adelante, solo admin ──────────────────────────────────────────
if (!$admin) fallar('Solo un administrador puede modificar la documentación', 403);

function categoria_valida($d, $cat) {
    foreach ($d['categorias'] as $c) if ($c['id'] === $cat) return true;
    return false;
}

try {
    switch ($accion) {
        case 'subir': {
            $titulo = doc_texto($in['titulo'] ?? '', 150);
            if ($titulo === '') fallar('Falta el título');
            $res = doc_modificar(function (&$d) use ($in, $titulo, $usuario) {
                if (!categoria_valida($d, $in['categoria'] ?? '')) throw new RuntimeException('Elegí una categoría');
                $id = (int)($d['ultimo_id'] ?? 0) + 1;
                $archivo = doc_guardar_subida($_FILES['archivo'] ?? null, $id);
                $d['ultimo_id'] = $id;
                $doc = [
                    'id' => $id, 'titulo' => $titulo,
                    'descripcion' => doc_texto($in['descripcion'] ?? '', 400),
                    'categoria' => $in['categoria'],
                    'confirmar' => !empty($in['confirmar']) && $in['confirmar'] !== 'false',
                    'archivo' => $archivo,
                    'subido_por' => $usuario, 'creado' => doc_ahora(), 'actualizado' => doc_ahora(),
                    'reemplazado' => null, 'lecturas' => new stdClass(), 'versiones' => [],
                ];
                $d['docs'][] = $doc;
                return $doc;
            });
            json_response(['status' => 'ok', 'id' => $res['id']]);
        }

        case 'editar': {
            $id = (int)($in['id'] ?? 0);
            $ok = doc_modificar(function (&$d) use ($id, $in) {
                $i = doc_buscar_indice($d, $id);
                if ($i === null) throw new RuntimeException('El documento no existe');
                $doc = &$d['docs'][$i];
                if (isset($in['titulo'])) { $t = doc_texto($in['titulo'], 150); if ($t === '') throw new RuntimeException('El título no puede quedar vacío'); $doc['titulo'] = $t; }
                if (isset($in['descripcion'])) $doc['descripcion'] = doc_texto($in['descripcion'], 400);
                if (isset($in['categoria'])) {
                    if (!categoria_valida($d, $in['categoria'])) throw new RuntimeException('Categoría inválida');
                    $doc['categoria'] = $in['categoria'];
                }
                if (isset($in['confirmar'])) $doc['confirmar'] = !empty($in['confirmar']) && $in['confirmar'] !== 'false';
                $doc['actualizado'] = doc_ahora();
                return true;
            });
            json_response(['status' => 'ok']);
        }

        case 'reemplazar': {   // nueva versión del archivo; la anterior queda guardada
            $id = (int)($in['id'] ?? 0);
            $reconfirmar = !empty($in['reconfirmar']) && $in['reconfirmar'] !== 'false';
            doc_modificar(function (&$d) use ($id, $reconfirmar, $usuario) {
                $i = doc_buscar_indice($d, $id);
                if ($i === null) throw new RuntimeException('El documento no existe');
                $nuevo = doc_guardar_subida($_FILES['archivo'] ?? null, $id);
                $doc = &$d['docs'][$i];
                $doc['versiones'][] = $doc['archivo'] + ['reemplazado' => doc_ahora(), 'por' => $usuario];
                $doc['archivo'] = $nuevo;
                $doc['reemplazado'] = doc_ahora();
                $doc['actualizado'] = doc_ahora();
                if ($reconfirmar) $doc['lecturas'] = new stdClass();
                return true;
            });
            json_response(['status' => 'ok']);
        }

        case 'eliminar': {     // el archivo se mueve a data/archivos/eliminados (no se borra)
            $id = (int)($in['id'] ?? 0);
            doc_modificar(function (&$d) use ($id, $usuario) {
                $i = doc_buscar_indice($d, $id);
                if ($i === null) throw new RuntimeException('El documento no existe');
                $doc = $d['docs'][$i];
                $dest = DOC_ARCHIVOS . '/eliminados';
                if (!is_dir($dest)) mkdir($dest, 0755, true);
                foreach (array_merge([$doc['archivo']], $doc['versiones'] ?? []) as $a) {
                    if (is_file(DOC_ARCHIVOS . '/' . $a['guardado'])) rename(DOC_ARCHIVOS . '/' . $a['guardado'], $dest . '/' . $a['guardado']);
                }
                $doc['eliminado'] = ['fecha' => doc_ahora(), 'por' => $usuario];
                file_put_contents($dest . '/registro.json', json_encode(
                    array_merge(json_decode(@file_get_contents($dest . '/registro.json') ?: '[]', true) ?: [], [$doc]),
                    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                array_splice($d['docs'], $i, 1);
                return true;
            });
            json_response(['status' => 'ok']);
        }

        case 'categoria_guardar': {   // crear o renombrar
            $nombre = doc_texto($in['nombre'] ?? '', 40);
            $icono = mb_substr(trim((string)($in['icono'] ?? '📁')), 0, 4) ?: '📁';
            if ($nombre === '') fallar('Poné un nombre para la categoría');
            $cat = doc_modificar(function (&$d) use ($in, $nombre, $icono) {
                foreach ($d['categorias'] as $c) {
                    if (strcasecmp($c['nombre'], $nombre) === 0 && $c['id'] !== ($in['id'] ?? '')) throw new RuntimeException('Ya existe una categoría con ese nombre');
                }
                if (!empty($in['id'])) {
                    foreach ($d['categorias'] as &$c) if ($c['id'] === $in['id']) { $c['nombre'] = $nombre; $c['icono'] = $icono; return $c; }
                    throw new RuntimeException('La categoría no existe');
                }
                $base = trim(preg_replace('/[^a-z0-9]+/', '-', strtolower(iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $nombre) ?: 'cat')), '-') ?: 'cat';
                $id = $base; $n = 2;
                while (categoria_valida($d, $id)) $id = $base . '-' . $n++;
                $c = ['id' => $id, 'nombre' => $nombre, 'icono' => $icono];
                $d['categorias'][] = $c;
                return $c;
            });
            json_response(['status' => 'ok', 'categoria' => $cat]);
        }

        case 'categoria_eliminar': {
            $id = (string)($in['id'] ?? '');
            doc_modificar(function (&$d) use ($id) {
                foreach ($d['docs'] as $doc) if ($doc['categoria'] === $id) throw new RuntimeException('La categoría tiene documentos. Movelos a otra antes de eliminarla.');
                $antes = count($d['categorias']);
                $d['categorias'] = array_values(array_filter($d['categorias'], fn($c) => $c['id'] !== $id));
                if (count($d['categorias']) === $antes) throw new RuntimeException('La categoría no existe');
                return true;
            });
            json_response(['status' => 'ok']);
        }

        default:
            fallar('Acción desconocida');
    }
} catch (RuntimeException $e) {
    fallar($e->getMessage());
}
