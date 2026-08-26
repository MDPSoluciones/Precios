<?php
require __DIR__ . '/config.php';

if (empty($_SESSION['usuario'])) {
    json_response(['error' => 'No autorizado'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$accion = $_GET['accion'] ?? '';

$compras = load_json(COMPRAS_FILE);

switch ($method) {

    case 'GET':
        // Devuelve todas las compras
        json_response(['compras' => $compras]);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input) json_response(['error' => 'Datos inválidos'], 400);

        if ($accion === 'toggle_factura') {
            // Alternar rápido el estado de "tiene factura"
            $id = $input['id'] ?? null;
            foreach ($compras as &$c) {
                if ($c['id'] === $id) {
                    $c['tiene_factura'] = !$c['tiene_factura'];
                    $c['actualizado_por'] = current_user();
                    $c['actualizado'] = date('c');
                }
            }
            unset($c);
            save_json(COMPRAS_FILE, $compras);
            json_response(['ok' => true]);
        }

        if ($accion === 'eliminar') {
            $id = $input['id'] ?? null;
            $compras = array_values(array_filter($compras, fn($c) => $c['id'] !== $id));
            save_json(COMPRAS_FILE, $compras);
            json_response(['ok' => true]);
        }

        // Crear o editar
        $id = $input['id'] ?? null;

        $nueva = [
            'id' => $id ?: uniqid('c_'),
            'fecha' => $input['fecha'] ?? date('Y-m-d'),
            'sitio' => trim($input['sitio'] ?? ''),
            'id_externo' => trim($input['id_externo'] ?? ''),
            'vendedor' => trim($input['vendedor'] ?? ''),
            'producto' => trim($input['producto'] ?? ''),
            'monto' => floatval($input['monto'] ?? 0),
            'tiene_factura' => (bool)($input['tiene_factura'] ?? false),
            'link_pdf' => trim($input['link_pdf'] ?? ''),
            'notas' => trim($input['notas'] ?? ''),
        ];

        if ($id) {
            // Edición: buscar y reemplazar conservando quién la creó originalmente
            $encontrada = false;
            foreach ($compras as &$c) {
                if ($c['id'] === $id) {
                    $nueva['registrado_por'] = $c['registrado_por'] ?? current_user();
                    $nueva['creado'] = $c['creado'] ?? date('c');
                    $nueva['actualizado_por'] = current_user();
                    $nueva['actualizado'] = date('c');
                    $c = $nueva;
                    $encontrada = true;
                }
            }
            unset($c);
            if (!$encontrada) json_response(['error' => 'No encontrada'], 404);
        } else {
            $nueva['registrado_por'] = current_user();
            $nueva['creado'] = date('c');
            $compras[] = $nueva;
        }

        save_json(COMPRAS_FILE, $compras);
        json_response(['ok' => true, 'compra' => $nueva]);
        break;

    default:
        json_response(['error' => 'Método no soportado'], 405);
}
