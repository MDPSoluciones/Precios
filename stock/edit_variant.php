<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$err='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $color = trim($_POST['color'] ?? '');
    $storage = intval($_POST['storage'] ?? 0);
    if ($color && $storage) {
        $stmt = $mysqli->prepare('UPDATE variants SET color=?, storage=? WHERE id_variant=?');
        $stmt->bind_param('sii', $color, $storage, $id);
        if ($stmt->execute()) header('Location: index.php');
        else $err='Error saving.';
    } else $err='Please complete fields.';
}
$stmt = $mysqli->prepare('SELECT v.*, mo.name AS model, b.name AS brand FROM variants v JOIN models mo ON v.id_model=mo.id_model JOIN brands b ON mo.id_brand=b.id_brand WHERE v.id_variant=?');
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows===0) { header('Location: index.php'); exit; }
$var = $res->fetch_assoc();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Edit variant</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Edit variant - <?=htmlspecialchars($var['brand'].' '.$var['model'])?></h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Color</label>
      <input name="color" class="form-control" value="<?=htmlspecialchars($var['color'])?>" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Storage (GB)</label>
      <input name="storage" type="number" class="form-control" value="<?=htmlspecialchars($var['storage'])?>" required>
    </div>
    <button class="btn btn-accent">Save variant</button>
  </form>
</div></body></html>