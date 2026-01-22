<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_brand = $_POST['id_brand'] ?? '';
    $name = trim($_POST['name'] ?? '');
    if ($id_brand && $name) {
        $stmt = $mysqli->prepare('INSERT INTO models (id_brand, name) VALUES (?,?)');
        $stmt->bind_param('is', $id_brand, $name);
        if ($stmt->execute()) header('Location: index.php');
        else $err = 'Error creating model.';
    } else $err = 'Please complete all fields.';
}
$brands = $mysqli->query('SELECT * FROM brands ORDER BY name');
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Add model</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Add model</h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Brand</label>
      <select name="id_brand" class="form-control" required>
        <?php while($m = $brands->fetch_assoc()): ?>
          <option value="<?= $m['id_brand'] ?>"><?= htmlspecialchars($m['name']) ?></option>
        <?php endwhile; ?>
      </select>
    </div>
    <div class="mb-3">
      <label class="form-label">Model name</label>
      <input name="name" class="form-control" required>
    </div>
    <button class="btn btn-accent">Create model</button>
  </form>
</div></body></html>