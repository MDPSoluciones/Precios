<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    if ($name) {
        $stmt = $mysqli->prepare('INSERT INTO brands (name) VALUES (?)');
        $stmt->bind_param('s', $name);
        if ($stmt->execute()) header('Location: index.php');
        else $err = 'Error: maybe brand already exists.';
    } else $err = 'Please enter a name.';
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Add brand</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Add brand</h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Brand name</label>
      <input name="name" class="form-control" required>
    </div>
    <button class="btn btn-accent">Create brand</button>
  </form>
</div></body></html>