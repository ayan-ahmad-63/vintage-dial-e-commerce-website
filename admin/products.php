<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once '../includes/asset_helpers.php';
$currentPage = 'products';
$pageTitle = 'Product Management';

// Handle Add Product
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name']);
        $subtitle = trim($_POST['subtitle']);
        $catId = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $desc = trim($_POST['description']);
        $image = null;

        if (!empty($name) && $price > 0) {
            $imagePaths = store_uploaded_assets($_FILES['images'] ?? [], 'prod');
            if (!empty($imagePaths)) {
                $image = json_encode($imagePaths);
            }
            $stmt = $db->prepare("INSERT INTO products (name, subtitle, category_id, price, stock, description, image) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$name, $subtitle, $catId, $price, $stock, $desc, $image]);
        }
        header('Location: products.php?msg=added');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['prod_id']);
        $name = trim($_POST['name']);
        $subtitle = trim($_POST['subtitle']);
        $catId = intval($_POST['category_id']);
        $price = floatval($_POST['price']);
        $stock = intval($_POST['stock']);
        $desc = trim($_POST['description']);

        if (!empty($name)) {
            $imagePaths = store_uploaded_assets($_FILES['images'] ?? [], 'prod');
            if (!empty($imagePaths)) {
                $stmt = $db->prepare("UPDATE products SET name=?, subtitle=?, category_id=?, price=?, stock=?, description=?, image=? WHERE id=?");
                $stmt->execute([$name, $subtitle, $catId, $price, $stock, $desc, json_encode($imagePaths), $id]);
            } else {
                $stmt = $db->prepare("UPDATE products SET name=?, subtitle=?, category_id=?, price=?, stock=?, description=? WHERE id=?");
                $stmt->execute([$name, $subtitle, $catId, $price, $stock, $desc, $id]);
            }
        }
        header('Location: products.php?msg=updated');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['prod_id']);
        $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: products.php?msg=deleted');
        exit;
    }
}

// Filters
$catFilter = $_GET['category'] ?? '';
$stockFilter = $_GET['stock'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($catFilter) {
    $where[] = "c.name = ?";
    $params[] = $catFilter;
}
if ($stockFilter === 'instock') {
    $where[] = "p.stock > 5";
} elseif ($stockFilter === 'low') {
    $where[] = "p.stock > 0 AND p.stock <= 5";
} elseif ($stockFilter === 'out') {
    $where[] = "p.stock = 0";
}
if ($search) {
    $where[] = "(p.name LIKE ? OR p.subtitle LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id
    $whereSQL
    ORDER BY p.created_at DESC
");
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories for dropdowns
$allCategories = $db->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

// Inventory stats
$inStock = $db->query("SELECT COUNT(*) as cnt FROM products WHERE stock > 5")->fetch()['cnt'];
$lowStock = $db->query("SELECT COUNT(*) as cnt FROM products WHERE stock > 0 AND stock <= 5")->fetch()['cnt'];
$outOfStock = $db->query("SELECT COUNT(*) as cnt FROM products WHERE stock = 0")->fetch()['cnt'];
$totalUnits = $db->query("SELECT COALESCE(SUM(stock), 0) as total FROM products")->fetch()['total'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage products on Vintage Dial admin panel">
  <title>Products | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <section class="content">

      <?php if (isset($_GET['msg'])): ?>
      <div style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-check-circle"></i>
        <?php
        $msgs = ['added' => 'Product added successfully!', 'updated' => 'Product updated successfully!', 'deleted' => 'Product deleted successfully!'];
        echo $msgs[$_GET['msg']] ?? 'Action completed!';
        ?>
      </div>
      <?php endif; ?>

      <!-- HEADER + ADD BUTTON -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <form method="GET" class="filters" style="display:flex; gap:12px; flex-wrap:wrap;">
          <input type="text" name="search" placeholder="Search products…" value="<?= htmlspecialchars($search) ?>">
          <select name="category" onchange="this.form.submit()">
            <option value="">All Categories</option>
            <?php foreach ($allCategories as $c): ?>
            <option value="<?= htmlspecialchars($c['name']) ?>" <?= $catFilter === $c['name'] ? 'selected' : '' ?>><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <select name="stock" onchange="this.form.submit()">
            <option value="">All Stock</option>
            <option value="instock" <?= $stockFilter === 'instock' ? 'selected' : '' ?>>In Stock</option>
            <option value="low" <?= $stockFilter === 'low' ? 'selected' : '' ?>>Low Stock</option>
            <option value="out" <?= $stockFilter === 'out' ? 'selected' : '' ?>>Out of Stock</option>
          </select>
          <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-search"></i></button>
        </form>
        <button class="btn btn-primary" onclick="openModal('addProdModal')">
          <i class="fas fa-plus"></i> Add Product
        </button>
      </div>

      <!-- PRODUCTS TABLE -->
      <div class="card">
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Image</th>
                  <th>Name</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Stock</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($products as $p): ?>
                <tr>
                  <td>
                    <?php if ($p['image']): ?>
                    <img src="<?= htmlspecialchars(asset_url(first_image_path($p['image']))) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:50px; height:50px; border-radius:8px; object-fit:cover;">
                    <?php else: ?>
                    <div style="width:50px;height:50px;border-radius:8px;background:var(--clr-bg);display:flex;align-items:center;justify-content:center;"><i class="fas fa-image" style="color:var(--clr-text-muted);"></i></div>
                    <?php endif; ?>
                  </td>
                  <td><strong><?= htmlspecialchars($p['name']) ?></strong><br><span class="text-muted" style="font-size:11px;"><?= htmlspecialchars($p['subtitle'] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($p['category_name'] ?? 'Uncategorized') ?></td>
                  <td>Rs. <?= number_format($p['price']) ?></td>
                  <td>
                    <span style="font-weight:600;<?= $p['stock'] <= 5 && $p['stock'] > 0 ? ' color:var(--clr-warning);' : ($p['stock'] == 0 ? ' color:var(--clr-danger);' : '') ?>"><?= $p['stock'] ?></span>
                  </td>
                  <td>
                    <?php if ($p['stock'] == 0): ?>
                    <span class="badge badge-cancelled">Out of Stock</span>
                    <?php elseif ($p['stock'] <= 5): ?>
                    <span class="badge badge-pending">Low Stock</span>
                    <?php else: ?>
                    <span class="badge badge-active">In Stock</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <button class="btn btn-sm btn-outline" onclick="openEditProd(<?= htmlspecialchars(json_encode($p)) ?>)"><i class="fas fa-edit"></i></button>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="prod_id" value="<?= $p['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- INVENTORY SUMMARY -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div class="stat-info"><h3><?= $inStock ?></h3><p>In Stock</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold"><i class="fas fa-exclamation-triangle"></i></div>
          <div class="stat-info"><h3><?= $lowStock ?></h3><p>Low Stock (≤ 5)</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-times-circle"></i></div>
          <div class="stat-info"><h3><?= $outOfStock ?></h3><p>Out of Stock</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-cubes"></i></div>
          <div class="stat-info"><h3><?= $totalUnits ?></h3><p>Total Units</p></div>
        </div>
      </div>

    </section>
  </div>

  <!-- ADD PRODUCT MODAL -->
  <div class="modal-overlay" id="addProdModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Add New Product</h3>
        <button class="modal-close" onclick="closeModal('addProdModal')">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Product Name *</label>
              <input type="text" name="name" placeholder="e.g. SNR060" required>
            </div>
            <div class="form-group">
              <label>Subtitle</label>
              <input type="text" name="subtitle" placeholder="e.g. Prospex Diver">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select name="category_id">
                <?php foreach ($allCategories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Price (Rs.) *</label>
              <input type="number" name="price" placeholder="0" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Stock Quantity</label>
              <input type="number" name="stock" placeholder="0" value="0">
            </div>
            <div class="form-group">
              <label>Product Images</label>
              <input type="file" name="images[]" accept="image/*" multiple>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Detailed product description…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('addProdModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Product</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT PRODUCT MODAL -->
  <div class="modal-overlay" id="editProdModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Edit Product</h3>
        <button class="modal-close" onclick="closeModal('editProdModal')">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="prod_id" id="editProdId">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Product Name *</label>
              <input type="text" name="name" id="editProdName" required>
            </div>
            <div class="form-group">
              <label>Subtitle</label>
              <input type="text" name="subtitle" id="editProdSubtitle">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Category</label>
              <select name="category_id" id="editProdCat">
                <?php foreach ($allCategories as $c): ?>
                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="form-group">
              <label>Price (Rs.)</label>
              <input type="number" name="price" id="editProdPrice">
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Stock Quantity</label>
              <input type="number" name="stock" id="editProdStock">
            </div>
            <div class="form-group">
              <label>Update Images</label>
              <input type="file" name="images[]" accept="image/*" multiple>
            </div>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" id="editProdDesc"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('editProdModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
    }
    function openModal(id) { document.getElementById(id).classList.add('show'); }
    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    function openEditProd(p) {
      document.getElementById('editProdId').value = p.id;
      document.getElementById('editProdName').value = p.name;
      document.getElementById('editProdSubtitle').value = p.subtitle || '';
      document.getElementById('editProdCat').value = p.category_id;
      document.getElementById('editProdPrice').value = p.price;
      document.getElementById('editProdStock').value = p.stock;
      document.getElementById('editProdDesc').value = p.description || '';
      openModal('editProdModal');
    }

    window.onclick = function(event) {
      if (!event.target.closest('.notif-wrapper')) {
        const dd = document.getElementById('notifDropdown');
        if (dd) dd.classList.remove('show');
      }
    }
  </script>
</body>

</html>
