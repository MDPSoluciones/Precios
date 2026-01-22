<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$err='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $id_brand = intval($_POST['id_brand'] ?? 0);
    if ($name && $id_brand) {
        $stmt = $mysqli->prepare('UPDATE models SET name=?, id_brand=? WHERE id_model=?');
        $stmt->bind_param('sii', $name, $id_brand, $id);
        if ($stmt->execute()) header('Location: index.php');
        else $err='Error saving.';
    } else $err='Please complete fields.';
}
$stmt = $mysqli->prepare('SELECT * FROM models WHERE id_model=?');
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows===0) { header('Location: index.php'); exit; }
$model = $res->fetch_assoc();
$brands = $mysqli->query('SELECT * FROM brands ORDER BY name');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Edit model</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Edit model</h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Brand</label>
      <select name="id_brand" class="form-control" required>
        <?php while($m = $brands->fetch_assoc()): ?>
          <option value="<?= $m['id_brand'] ?>" <?= $m['id_brand']==$model['id_brand'] ? 'selected' : '' ?>><?= htmlspecialchars($m['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Model name</label>
      <input name="name" class="form-control" value="<?=htmlspecialchars($model['name'])?>" required>
    </div>
    <button class="btn btn-accent">Save model</button>
  </form>
</div></body></html>