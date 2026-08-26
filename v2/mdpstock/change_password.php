<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) { header('Location: login.php'); exit; }
$err=''; $msg='';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current'] ?? '';
    $new = $_POST['new'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    if (!$current || !$new || !$confirm) $err='Please complete all fields.';
    elseif ($new !== $confirm) $err='New passwords do not match.';
    else {
        $stmt = $mysqli->prepare('SELECT password_hash FROM users WHERE id_user = ?');
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $stmt->bind_result($hash);
        if ($stmt->fetch()) {
            if (password_verify($current, $hash)) {
                $new_hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt2 = $mysqli->prepare('UPDATE users SET password_hash = ? WHERE id_user = ?');
                $stmt2->bind_param('si', $new_hash, $_SESSION['user_id']);
                if ($stmt2->execute()) $msg='Password updated.';
                else $err='Error updating password.';
            } else $err='Current password incorrect.';
        } else $err='User not found.';
        $stmt->close();
    }
}
?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Change password</title></head><body class="bg-light">
<div class="container py-4">
  <a href="index.php">&larr; Back</a>
  <h3>Change password</h3>
  <?php if($err) echo '<div class="alert alert-danger">'.htmlspecialchars($err).'</div>'; ?>
  <?php if($msg) echo '<div class="alert alert-success">'.htmlspecialchars($msg).'</div>'; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label">Current password</label>
      <input name="current" type="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">New password</label>
      <input name="new" type="password" class="form-control" required>
    </div>
    <div class="mb-3">
      <label class="form-label">Confirm new password</label>
      <input name="confirm" type="password" class="form-control" required>
    </div>
    <button class="btn btn-accent">Change password</button>
  </form>
</div></body></html>