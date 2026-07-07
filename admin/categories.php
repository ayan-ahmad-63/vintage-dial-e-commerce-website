<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once '../includes/asset_helpers.php';
$currentPage = 'categories';
$pageTitle = 'Category Management';

// Handle Add Category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $status = $_POST['status'];
        $image = null;

        if (!empty($name)) {
            $upload = store_uploaded_asset($_FILES['image'] ?? [], 'cat');
            if ($upload['ok']) {
                $image = $upload['path'];
            }
            $stmt = $db->prepare("INSERT INTO categories (name, description, image, status) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $desc, $image, $status]);
        }
        header('Location: categories.php?msg=added');
        exit;
    }

    if ($_POST['action'] === 'edit') {
        $id = intval($_POST['cat_id']);
        $name = trim($_POST['name']);
        $desc = trim($_POST['description']);
        $status = $_POST['status'];

        if (!empty($name)) {
            $upload = store_uploaded_asset($_FILES['image'] ?? [], 'cat');
            if ($upload['ok']) {
                $stmt = $db->prepare("UPDATE categories SET name=?, description=?, image=?, status=? WHERE id=?");
                $stmt->execute([$name, $desc, $upload['path'], $status, $id]);
            } else {
                $stmt = $db->prepare("UPDATE categories SET name=?, description=?, status=? WHERE id=?");
                $stmt->execute([$name, $desc, $status, $id]);
            }
        }
        header('Location: categories.php?msg=updated');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['cat_id']);
        $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        header('Location: categories.php?msg=deleted');
        exit;
    }
}

// Fetch categories with product count
$categories = $db->query("
    SELECT c.*, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON p.category_id = c.id 
    GROUP BY c.id 
    ORDER BY c.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage product categories on Vintage Dial admin panel">
  <title>Categories | Vintage Dial Admin</title>
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
        $msgs = ['added' => 'Category added successfully!', 'updated' => 'Category updated successfully!', 'deleted' => 'Category deleted successfully!'];
        echo $msgs[$_GET['msg']] ?? 'Action completed!';
        ?>
      </div>
      <?php endif; ?>

      <!-- ADD CATEGORY BUTTON -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
          <h3 style="font-size:18px; font-weight:600;">All Categories</h3>
          <p class="text-muted" style="font-size:13px;">Manage your watch collections and categories</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addCatModal')">
          <i class="fas fa-plus"></i> Add Category
        </button>
      </div>

      <!-- CATEGORY GRID -->
      <div class="grid-2" id="catGrid">
        <?php foreach ($categories as $cat): ?>
        <div class="card">
          <div class="card-body" style="display:flex; align-items:flex-start; gap:20px;">
            <div style="width:90px; height:90px; border-radius:12px; overflow:hidden; flex-shrink:0; background:var(--clr-bg); display:flex; align-items:center; justify-content:center;">
              <?php if ($cat['image']): ?>
              <img src="<?= htmlspecialchars(asset_url($cat['image'])) ?>" alt="<?= htmlspecialchars($cat['name']) ?>" style="width:100%; height:100%; object-fit:cover;">
              <?php else: ?>
              <i class="fas fa-image" style="font-size:28px; color:var(--clr-text-muted);"></i>
              <?php endif; ?>
            </div>
            <div style="flex:1;">
              <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                <h3 style="font-size:17px; font-weight:600;"><?= htmlspecialchars($cat['name']) ?></h3>
                <span class="badge badge-<?= strtolower($cat['status']) ?>"><?= $cat['status'] ?></span>
              </div>
              <p class="text-muted" style="font-size:13px; line-height:1.5; margin-bottom:10px;"><?= htmlspecialchars($cat['description'] ?? 'No description') ?></p>
              <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
                <span class="text-muted" style="font-size:12px;"><i class="fas fa-box"></i> <?= $cat['product_count'] ?> Products</span>
                <span class="text-muted" style="font-size:12px; margin-left:8px;"><i class="fas fa-calendar"></i> Created: <?= date('d M Y', strtotime($cat['created_at'])) ?></span>
              </div>
              <div style="margin-top:12px; display:flex; gap:8px;">
                <button class="btn btn-sm btn-outline" onclick="openEditCat(<?= $cat['id'] ?>, '<?= addslashes($cat['name']) ?>', '<?= addslashes($cat['description'] ?? '') ?>', '<?= $cat['status'] ?>')"><i class="fas fa-edit"></i> Edit</button>
                <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete &quot;<?= addslashes($cat['name']) ?>&quot;?')">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="cat_id" value="<?= $cat['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
                </form>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </section>
  </div>

  <!-- ADD CATEGORY MODAL -->
  <div class="modal-overlay" id="addCatModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Add New Category</h3>
        <button class="modal-close" onclick="closeModal('addCatModal')">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="form-group">
            <label>Category Name *</label>
            <input type="text" name="name" placeholder="e.g. Limited Edition" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" placeholder="Describe this category…"></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label>Category Image</label>
            <input type="file" name="image" accept="image/*">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('addCatModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add Category</button>
        </div>
      </form>
    </div>
  </div>

  <!-- EDIT CATEGORY MODAL -->
  <div class="modal-overlay" id="editCatModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Edit Category</h3>
        <button class="modal-close" onclick="closeModal('editCatModal')">&times;</button>
      </div>
      <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="cat_id" id="editCatId">
        <div class="modal-body">
          <div class="form-group">
            <label>Category Name *</label>
            <input type="text" name="name" id="editCatName" required>
          </div>
          <div class="form-group">
            <label>Description</label>
            <textarea name="description" id="editCatDesc"></textarea>
          </div>
          <div class="form-group">
            <label>Status</label>
            <select name="status" id="editCatStatus">
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label>Update Image</label>
            <input type="file" name="image" accept="image/*">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('editCatModal')">Cancel</button>
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

    function openEditCat(id, name, desc, status) {
      document.getElementById('editCatId').value = id;
      document.getElementById('editCatName').value = name;
      document.getElementById('editCatDesc').value = desc;
      document.getElementById('editCatStatus').value = status;
      openModal('editCatModal');
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
