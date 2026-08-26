<?php
include 'db.php';
header('Content-Type: application/json; charset=utf-8');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$id_stock   = intval($_POST['id_stock'] ?? 0);
$id_variant = intval($_POST['id_variant'] ?? 0);
$quantity   = intval($_POST['quantity'] ?? 0);

if ($id_stock <= 0 || $id_variant <= 0) {
    echo json_encode(["error" => "Datos inválidos"]);
    exit;
}

$stmt = $conn->prepare("UPDATE stock SET id_variant = ?, quantity = ? WHERE id_stock = ?");
$stmt->bind_param("iii", $id_variant, $quantity, $id_stock);

if ($stmt->execute()) {
    echo json_encode(["success" => true]);
} else {
    echo json_encode(["error" => $stmt->error]);
}
