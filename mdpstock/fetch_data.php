<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$sql = "
SELECT 
    s.id_stock AS id_stock,
    b.id_brand,
    b.name AS brand,
    m.id_model,
    m.name AS model,
    v.id_variant,
    v.color,
    v.storage,
    s.quantity
FROM stock s
JOIN variants v ON s.id_variant = v.id_variant
JOIN models m ON v.id_model = m.id_model
JOIN brands b ON m.id_brand = b.id_brand
ORDER BY b.name, m.name
";

$result = $conn->query($sql);
$data = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
} else {
    echo json_encode(["error" => $conn->error]);
    exit;
}

echo json_encode($data);
