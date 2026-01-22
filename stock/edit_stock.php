<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id_var = $_GET['id_variant'] ?? null;
if (!$id_var) { header('Location: index.php'); exit; }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);
    $stmt = $mysqli->prepare('UPDATE stock SET quantity = ? WHERE id_variant = ?');
    $stmt->bind_param('ii', $quantity, $id_var);
    $stmt->execute();
    header('Location: index.php');
    exit;
}

$stmt = $mysqli->prepare('SELECT s.quantity, b.name AS brand, mo.name AS model, v.color, v.storage FROM stock s JOIN variants v ON s.id_variant = v.id_variant JOIN models mo ON v.id_model = mo.id_model JOIN brands b ON mo.id_brand = b.id_brand WHERE v.id_variant = ?');
$stmt->bind_param('i', $id_var);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows === 0) { header('Location: index.php'); exit; }
$row = $res->fetch_assoc();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Edit stock</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Edit stock - <?=htmlspecialchars($row['brand'].' '.$row['model'].' '.$row['color'].' '.$row['storage'].'GB')?></h3>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Quantity</label>
      <input name="quantity" type="number" class="form-control" value="<?=htmlspecialchars($row['quantity'])?>" required>
    </div>
    <button class="btn btn-accent">Save</button>
  </form>
</div></body></html>