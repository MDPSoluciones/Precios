<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_model = $_POST['id_model'] ?? '';
    $color = trim($_POST['color'] ?? '');
    $storage = intval($_POST['storage'] ?? 0);
    $quantity = intval($_POST['quantity'] ?? 0);
    if ($id_model && $color && $storage) {
        $stmt = $mysqli->prepare('INSERT INTO variants (id_model, color, storage) VALUES (?,?,?)');
        $stmt->bind_param('isi', $id_model, $color, $storage);
        if ($stmt->execute()) {
            $id_var = $mysqli->insert_id;
            $stmt2 = $mysqli->prepare('INSERT INTO stock (id_variant, quantity) VALUES (?,?)');
            $stmt2->bind_param('ii', $id_var, $quantity);
            $stmt2->execute();
            header('Location: index.php');
            exit;
        } else {
            $err = 'Error creating variant. (maybe it exists)';
        }
    } else $err = 'Please complete all fields.';
}
$models = $mysqli->query('SELECT mo.id_model, mo.name, b.name AS brand FROM models mo JOIN brands b ON mo.id_brand = b.id_brand ORDER BY b.name, mo.name');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Add variant</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Add variant</h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Model</label>
      <select name="id_model" class="form-control" required>
        <?php while($m = $models->fetch_assoc()): ?>
          <option value="<?= $m['id_model'] ?>"><?= htmlspecialchars($m['brand'].' - '.$m['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Color</label>
      <input name="color" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Storage (GB)</label>
      <input name="storage" type="number" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Initial quantity</label>
      <input name="quantity" type="number" class="form-control" value="0" required>
    </div>
    <button class="btn btn-accent">Create variant</button>
  </form>
</div></body></html>