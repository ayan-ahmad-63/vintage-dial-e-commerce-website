<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$currentPage = 'profile';
$pageTitle = 'Profile Settings';

$adminId = $_SESSION['admin_id'];
$admin = $db->prepare("SELECT * FROM admins WHERE id = ?");
$admin->execute([$adminId]);
$admin = $admin->fetch();

$profileMsg = '';
$profileErr = '';
$passMsg = '';
$passErr = '';

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_profile') {
        $name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        if (empty($name) || empty($email)) {
            $profileErr = 'Name and email are required.';
        } else {
            $stmt = $db->prepare("UPDATE admins SET full_name=?, email=?, phone=? WHERE id=?");
            $stmt->execute([$name, $email, $phone, $adminId]);
            $_SESSION['admin_name'] = $name;
            $_SESSION['admin_email'] = $email;
            $profileMsg = 'Profile updated successfully!';
            // Refresh admin data
            $admin['full_name'] = $name;
            $admin['email'] = $email;
            $admin['phone'] = $phone;
        }
    }

    if ($_POST['action'] === 'change_password') {
        $current = $_POST['current_password'];
        $newPass = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if (empty($current) || empty($newPass) || empty($confirm)) {
            $passErr = 'Please fill in all password fields.';
        } elseif (!password_verify($current, $admin['password'])) {
            $passErr = 'Current password is incorrect.';
        } elseif (strlen($newPass) < 6) {
            $passErr = 'New password must be at least 6 characters.';
        } elseif ($newPass !== $confirm) {
            $passErr = 'New passwords do not match.';
        } else {
            $hashed = password_hash($newPass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE admins SET password=? WHERE id=?");
            $stmt->execute([$hashed, $adminId]);
            $passMsg = '✓ Password changed successfully!';
            // Refresh admin data
            $admin['password'] = $hashed;
        }
    }
}

$initials = '';
$parts = explode(' ', $admin['full_name']);
foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
$initials = substr($initials, 0, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage admin profile on Vintage Dial admin panel">
  <title>Profile | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <section class="content">

      <!-- PROFILE HEADER -->
      <div class="card" style="margin-bottom:28px;">
        <div class="card-body" style="display:flex; align-items:center; gap:24px; flex-wrap:wrap;">
          <div class="profile-avatar-lg" style="margin:0;"><?= $initials ?></div>
          <div style="flex:1;">
            <h2 style="font-size:22px; font-weight:700; margin-bottom:4px;"><?= htmlspecialchars($admin['full_name']) ?></h2>
            <p class="text-muted" style="font-size:13px;"><?= htmlspecialchars($admin['email']) ?></p>
            <p class="text-muted" style="font-size:12px; margin-top:4px;">
              <i class="fas fa-shield-alt" style="color:var(--clr-gold);"></i> <?= htmlspecialchars($admin['role']) ?>
            </p>
          </div>
        </div>
      </div>

      <div class="grid-2">
        <!-- PERSONAL INFORMATION -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-user" style="color:var(--clr-gold); margin-right:8px;"></i> Personal Information</h3>
          </div>
          <div class="card-body">
            <?php if ($profileMsg): ?>
            <div style="background:#d1fae5; color:#065f46; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px;"><i class="fas fa-check-circle"></i> <?= $profileMsg ?></div>
            <?php endif; ?>
            <?php if ($profileErr): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px;"><?= $profileErr ?></div>
            <?php endif; ?>
            <form method="POST">
              <input type="hidden" name="action" value="update_profile">
              <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" value="<?= htmlspecialchars($admin['full_name']) ?>" required>
              </div>
              <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>" required>
              </div>
              <div class="form-group">
                <label>Phone Number</label>
                <input type="text" name="phone" value="<?= htmlspecialchars($admin['phone'] ?? '') ?>">
              </div>
              <div class="form-group">
                <label>Role</label>
                <input type="text" value="<?= htmlspecialchars($admin['role']) ?>" disabled style="background:#f9fafb; color:var(--clr-text-muted);">
              </div>
              <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            </form>
          </div>
        </div>

        <!-- CHANGE PASSWORD -->
        <div class="card">
          <div class="card-header">
            <h3><i class="fas fa-lock" style="color:var(--clr-gold); margin-right:8px;"></i> Change Password</h3>
          </div>
          <div class="card-body">
            <?php if ($passErr): ?>
            <div style="background:#fee2e2; color:#991b1b; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px;"><?= $passErr ?></div>
            <?php endif; ?>
            <?php if ($passMsg): ?>
            <div style="background:#d1fae5; color:#065f46; padding:10px 14px; border-radius:8px; font-size:13px; margin-bottom:16px;"><?= $passMsg ?></div>
            <?php endif; ?>
            <form method="POST">
              <input type="hidden" name="action" value="change_password">
              <div class="form-group">
                <label>Current Password</label>
                <input type="password" name="current_password" placeholder="Enter current password" required>
              </div>
              <div class="form-group">
                <label>New Password</label>
                <input type="password" name="new_password" id="newPass" placeholder="Enter new password" required>
              </div>
              <div class="form-group">
                <label>Confirm New Password</label>
                <input type="password" name="confirm_password" placeholder="Confirm new password" required>
              </div>
              <div id="passStrength" style="margin-bottom:16px; display:none;">
                <div style="display:flex; gap:4px; margin-bottom:6px;">
                  <div id="str1" style="height:4px; flex:1; background:#e5e7eb; border-radius:2px;"></div>
                  <div id="str2" style="height:4px; flex:1; background:#e5e7eb; border-radius:2px;"></div>
                  <div id="str3" style="height:4px; flex:1; background:#e5e7eb; border-radius:2px;"></div>
                  <div id="str4" style="height:4px; flex:1; background:#e5e7eb; border-radius:2px;"></div>
                </div>
                <span id="strText" style="font-size:11px;"></span>
              </div>
              <button type="submit" class="btn btn-dark"><i class="fas fa-key"></i> Update Password</button>
            </form>
          </div>
        </div>
      </div>

      <!-- DANGER ZONE -->
      <div class="card" style="margin-top:24px; border:1px solid var(--clr-danger);">
        <div class="card-header" style="background:#fef2f2;">
          <h3 style="color:var(--clr-danger);"><i class="fas fa-exclamation-triangle"></i> Danger Zone</h3>
        </div>
        <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
          <div>
            <strong>Delete Account</strong>
            <p class="text-muted" style="font-size:12px;">Once deleted, your account cannot be recovered.</p>
          </div>
          <button class="btn btn-danger" onclick="if(confirm('Are you absolutely sure? This action cannot be undone.')) alert('Account deletion requested.')">
            <i class="fas fa-trash"></i> Delete Account
          </button>
        </div>
      </div>

    </section>
  </div>

  <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
    document.getElementById('newPass').addEventListener('input', function(){
      var v=this.value, b=document.getElementById('passStrength');
      if(!v){b.style.display='none';return;}
      b.style.display='block';
      var s=0;
      if(v.length>=6)s++;if(v.length>=10)s++;
      if(/[A-Z]/.test(v)&&/[a-z]/.test(v))s++;
      if(/[0-9]/.test(v)&&/[^A-Za-z0-9]/.test(v))s++;
      var c=['#ef4444','#f59e0b','#3b82f6','#10b981'],l=['Weak','Fair','Good','Strong'];
      for(var i=1;i<=4;i++)document.getElementById('str'+i).style.background=i<=s?c[s-1]:'#e5e7eb';
      document.getElementById('strText').textContent=l[s-1]||'';
      document.getElementById('strText').style.color=c[s-1]||'#6b7280';
    });
    window.onclick=function(e){if(!e.target.closest('.notif-wrapper')){var d=document.getElementById('notifDropdown');if(d)d.classList.remove('show');}}
  </script>
</body>
</html>
