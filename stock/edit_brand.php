<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: index.php'); exit; }
$err='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $stmt = $mysqli->prepare('UPDATE brands SET name=? WHERE id_brand=?');
        $stmt->bind_param('si', $name, $id);
        if ($stmt->execute()) header('Location: index.php');
        else $err='Error saving.';
    } else $err='Empty name.';
}
$stmt = $mysqli->prepare('SELECT * FROM brands WHERE id_brand=?');
$stmt->bind_param('i',$id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows===0) { header('Location: index.php'); exit; }
$brand = $res->fetch_assoc();
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Edit brand</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Edit brand</h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Name</label>
      <input name="name" class="form-control" value="<?=htmlspecialchars($brand['name'])?>" required>
    </div>
    <button class="btn btn-accent">Save</button>
  </form>
</div></body></html>