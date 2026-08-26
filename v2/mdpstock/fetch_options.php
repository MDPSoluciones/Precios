<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';

switch ($type) {
    case 'brands':
        $result = $conn->query("SELECT id_brand, name FROM brands ORDER BY name");
        $data = $result->fetch_all(MYSQLI_ASSOC);
        echo json_encode($data);
        break;

    case 'models':
        $brand_id = intval($_GET['brand_id'] ?? 0);
        $stmt = $conn->prepare("SELECT id_model, name FROM models WHERE id_brand = ? ORDER BY name");
        $stmt->bind_param("i", $brand_id);
        $stmt->execute();
        $res = $stmt->get_result();
        echo json_encode($res->fetch_all(MYSQLI_ASSOC));
        break;

    case 'variants':
        $model_id = intval($_GET['model_id'] ?? 0);
        $stmt = $conn->prepare("SELECT id_variant, color, storage FROM variants WHERE id_model = ? ORDER BY color, storage");
        $stmt->bind_param("i", $model_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $variants = [];
        while ($v = $res->fetch_assoc()) {
            $variants[] = [
                "id_variant" => $v["id_variant"],
                "name" => "{$v['color']} {$v['storage']}GB"
            ];
        }
        echo json_encode($variants);
        break;

    default:
        echo json_encode(["error" => "Tipo no válido"]);
}
