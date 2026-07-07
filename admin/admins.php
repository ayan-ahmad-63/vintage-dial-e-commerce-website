<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$currentPage = 'admins';
$pageTitle = 'Admin Management';

$msg = '';
$err = '';

// Handle Add Admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm = $_POST['confirm'];
        $role = $_POST['role'] ?? 'Administrator';

        if (empty($name) || empty($email) || empty($password)) {
            $err = 'Name, email, and password are required.';
        } elseif (strlen($password) < 6) {
            $err = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $err = 'Passwords do not match.';
        } else {
            $check = $db->prepare("SELECT id FROM admins WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                $err = 'An admin with this email already exists.';
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $db->prepare("INSERT INTO admins (full_name, email, phone, password, role) VALUES (?,?,?,?,?)");
                $stmt->execute([$name, $email, $phone, $hashed, $role]);
                $msg = "Admin \"$name\" added successfully!";
            }
        }
    }

    if ($_POST['action'] === 'delete') {
        $id = intval($_POST['admin_id']);
        if ($id == $_SESSION['admin_id']) {
            $err = 'You cannot delete your own account.';
        } else {
            $stmt = $db->prepare("DELETE FROM admins WHERE id = ?");
            $stmt->execute([$id]);
            $msg = 'Admin deleted successfully.';
        }
    }
}

// Fetch all admins
$admins = $db->query("SELECT * FROM admins ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admins | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <section class="content">

      <?php if ($msg): ?>
      <div style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-check-circle"></i> <?= htmlspecialchars($msg) ?>
      </div>
      <?php endif; ?>
      <?php if ($err): ?>
      <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($err) ?>
      </div>
      <?php endif; ?>

      <!-- HEADER -->
      <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; flex-wrap:wrap; gap:12px;">
        <div>
          <h3 style="font-size:18px; font-weight:600;">All Administrators</h3>
          <p class="text-muted" style="font-size:13px;">Manage who has access to this admin panel</p>
        </div>
        <button class="btn btn-primary" onclick="openModal('addAdminModal')">
          <i class="fas fa-user-plus"></i> Add New Admin
        </button>
      </div>

      <!-- ADMINS TABLE -->
      <div class="card">
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>#</th>
                  <th>Name</th>
                  <th>Email</th>
                  <th>Phone</th>
                  <th>Role</th>
                  <th>Created</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i=1; foreach ($admins as $a): ?>
                <?php
                $ini = '';
                $pts = explode(' ', $a['full_name']);
                foreach ($pts as $pt) $ini .= strtoupper(substr($pt, 0, 1));
                $ini = substr($ini, 0, 2);
                $isMe = ($a['id'] == $_SESSION['admin_id']);
                ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td style="display:flex; align-items:center; gap:10px;">
                    <div class="reviewer-avatar"><?= $ini ?></div>
                    <div>
                      <strong><?= htmlspecialchars($a['full_name']) ?></strong>
                      <?php if ($isMe): ?><span class="badge badge-active" style="margin-left:6px;">You</span><?php endif; ?>
                    </div>
                  </td>
                  <td><?= htmlspecialchars($a['email']) ?></td>
                  <td><?= htmlspecialchars($a['phone'] ?? '—') ?></td>
                  <td><span class="badge badge-pending"><?= htmlspecialchars($a['role']) ?></span></td>
                  <td><?= date('d M Y', strtotime($a['created_at'])) ?></td>
                  <td>
                    <?php if (!$isMe): ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove this admin?')">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="admin_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                    </form>
                    <?php else: ?>
                    <span class="text-muted" style="font-size:12px;">—</span>
                    <?php endif; ?>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </section>
  </div>

  <!-- ADD ADMIN MODAL -->
  <div class="modal-overlay" id="addAdminModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Add New Administrator</h3>
        <button class="modal-close" onclick="closeModal('addAdminModal')">&times;</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="add">
        <div class="modal-body">
          <div class="form-row">
            <div class="form-group">
              <label>Full Name *</label>
              <input type="text" name="name" placeholder="e.g. Ali Ahmed" required>
            </div>
            <div class="form-group">
              <label>Email *</label>
              <input type="email" name="email" placeholder="e.g. ali@vintagedial.com" required>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Phone</label>
              <input type="text" name="phone" placeholder="e.g. 0300-1234567">
            </div>
            <div class="form-group">
              <label>Role</label>
              <select name="role">
                <option value="Super Administrator">Super Administrator</option>
                <option value="Administrator">Administrator</option>
                <option value="Manager">Manager</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-group">
              <label>Password * (min 6 chars)</label>
              <input type="password" name="password" placeholder="Enter password" required>
            </div>
            <div class="form-group">
              <label>Confirm Password *</label>
              <input type="password" name="confirm" placeholder="Confirm password" required>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('addAdminModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add Admin</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
    function openModal(id){document.getElementById(id).classList.add('show');}
    function closeModal(id){document.getElementById(id).classList.remove('show');}
    window.onclick=function(e){if(!e.target.closest('.notif-wrapper')){var d=document.getElementById('notifDropdown');if(d)d.classList.remove('show');}}
  </script>
</body>
</html>
