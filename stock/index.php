<?php
require 'config.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$search = $_GET['q'] ?? '';

$sql = "SELECT s.id_stock, b.id_brand, mo.id_model, v.id_variant, b.name AS brand, mo.name AS model, v.color, v.storage, s.quantity
FROM stock s
JOIN variants v ON s.id_variant = v.id_variant
JOIN models mo ON v.id_model = mo.id_model
JOIN brands b ON mo.id_brand = b.id_brand";
if ($search) {
    $sql .= " WHERE b.name LIKE ? OR mo.name LIKE ? OR v.color LIKE ? OR v.storage LIKE ?";
    $like = "%" . $search . "%";
    $stmt = $mysqli->prepare($sql);
    $stmt->bind_param('ssss', $like, $like, $like, $like);
    $stmt->execute();
    $res = $stmt->get_result();
} else {
    $res = $mysqli->query($sql);
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="styles.css" rel="stylesheet">
<title>Stock - Phones</title>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-custom">
  <div class="container-fluid">
    <a class="navbar-brand" href="index.php">Phone Stock</a>
    <div class="d-flex">
      <span class="navbar-text me-3">User: <?=htmlspecialchars($_SESSION['username'])?></span>
      <a class="btn btn-outline-light btn-sm me-2" href="change_password.php">Change password</a>
      <a class="btn btn-outline-light btn-sm" href="logout.php">Logout</a>
    </div>
  </div>
</nav>
<div class="container py-4">
  <div class="row mb-3">
    <div class="col-md-6">
      <form class="d-flex" method="get">
        <input class="form-control me-2" name="q" placeholder="Search by brand, model, color..." value="<?=htmlspecialchars($search)?>">
        <button class="btn btn-outline-primary">Search</button>
      </form>
    </div>
    <div class="col-md-6 text-end">
      <a class="btn btn-accent" href="add_brand.php">Add brand</a>
      <a class="btn btn-accent" href="add_model.php">Add model</a>
      <a class="btn btn-accent" href="add_variant.php">Add variant</a>
    </div>
  </div>
  <div class="card card-custom shadow-sm">
    <div class="card-body">
      <table class="table table-striped">
        <thead>
          <tr>
            <th>Brand</th><th>Model</th><th>Color</th><th>Storage</th><th>Quantity</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php while($row = $res->fetch_assoc()): ?>
          <tr>
            <td><?=htmlspecialchars($row['brand'])?> <br><a href="edit_brand.php?id=<?= $row['id_brand'] ?>" class="small">✏️ Edit brand</a></td>
            <td><?=htmlspecialchars($row['model'])?> <br><a href="edit_model.php?id=<?= $row['id_model'] ?>" class="small">✏️ Edit model</a></td>
            <td><?=htmlspecialchars($row['color'])?> <br><a href="edit_variant.php?id=<?= $row['id_variant'] ?>" class="small">✏️ Edit variant</a></td>
            <td><?=htmlspecialchars($row['storage'])?> GB</td>
            <td><?=htmlspecialchars($row['quantity'])?></td>
            <td>
              <a class="btn btn-sm btn-primary" href="edit_stock.php?id_variant=<?= $row['id_variant'] ?>">Edit stock</a>
            </td>
          </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
      <!-- Modal -->
      <div id="editModal" class="modal">
        <div class="modal-content">
          <span class="close">&times;</span>
          <h2>Edit Product</h2>
          <form id="editForm" method="POST" action="update_product.php">
            <input type="hidden" name="id_variante" id="id_variante">

            <label>Brand:</label>
            <input type="text" name="brand" id="brand" required>

            <label>Model:</label>
            <input type="text" name="model" id="model" required>

            <label>Color:</label>
            <input type="text" name="color" id="color" required>

            <label>Storage:</label>
            <input type="text" name="storage" id="storage" required>

            <label>Stock:</label>
            <input type="number" name="stock" id="stock" required>

            <button type="submit" class="btn-save">Save Changes</button>
          </form>
        </div>
      </div>

    </div>
  </div>
</div>
</body>
<script>
const modal = document.getElementById('editModal');
const closeBtn = document.querySelector('.close');

function openModal(id, brand, model, color, storage, stock) {
  document.getElementById('id_variante').value = id;
  document.getElementById('brand').value = brand;
  document.getElementById('model').value = model;
  document.getElementById('color').value = color;
  document.getElementById('storage').value = storage;
  document.getElementById('stock').value = stock;
  modal.style.display = 'block';
}

closeBtn.onclick = () => modal.style.display = 'none';
window.onclick = (e) => { if (e.target == modal) modal.style.display = 'none'; };
</script>
</html>
