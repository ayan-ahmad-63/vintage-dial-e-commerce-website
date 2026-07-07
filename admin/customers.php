<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$currentPage = 'customers';
$pageTitle = 'Customer Management';

// Filters
$cityFilter = $_GET['city'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($cityFilter) {
    $where[] = "city = ?";
    $params[] = $cityFilter;
}
if ($search) {
    $where[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("SELECT * FROM customers $whereSQL ORDER BY created_at DESC");
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Stats
$totalCustomers = $db->query("SELECT COUNT(*) as cnt FROM customers")->fetch()['cnt'];
$newThisMonth = $db->query("SELECT COUNT(*) as cnt FROM customers WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetch()['cnt'];
$repeatBuyers = $db->query("SELECT COUNT(*) as cnt FROM customers WHERE total_orders > 1")->fetch()['cnt'];
$cities = $db->query("SELECT COUNT(DISTINCT city) as cnt FROM customers")->fetch()['cnt'];

// Get unique cities for filter dropdown
$allCities = $db->query("SELECT DISTINCT city FROM customers WHERE city IS NOT NULL ORDER BY city")->fetchAll(DB_FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage customers on Vintage Dial admin panel">
  <title>Customers | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <section class="content">

      <!-- STATS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-users"></i></div>
          <div class="stat-info"><h3><?= number_format($totalCustomers) ?></h3><p>Total Customers</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-user-plus"></i></div>
          <div class="stat-info"><h3><?= $newThisMonth ?></h3><p>New This Month</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon gold"><i class="fas fa-crown"></i></div>
          <div class="stat-info"><h3><?= $repeatBuyers ?></h3><p>Repeat Buyers</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon purple"><i class="fas fa-map-marker-alt"></i></div>
          <div class="stat-info"><h3><?= $cities ?></h3><p>Cities</p></div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="card">
        <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
          <form method="GET" class="filters" style="display:flex; gap:12px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search customers…" value="<?= htmlspecialchars($search) ?>">
            <select name="city" onchange="this.form.submit()">
              <option value="">All Cities</option>
              <?php foreach ($allCities as $c): ?>
              <option value="<?= htmlspecialchars($c) ?>" <?= $cityFilter === $c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-search"></i></button>
          </form>
          <span class="text-muted" style="font-size:13px;">Showing <?= count($customers) ?> customers</span>
        </div>
      </div>

      <!-- CUSTOMERS TABLE -->
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
                  <th>City</th>
                  <th>Orders</th>
                  <th>Total Spent</th>
                  <th>Joined</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php $i = 1; foreach ($customers as $cust): ?>
                <?php
                $initials = '';
                $parts = explode(' ', $cust['full_name']);
                foreach ($parts as $p) $initials .= strtoupper(substr($p, 0, 1));
                $initials = substr($initials, 0, 2);
                ?>
                <tr>
                  <td><?= $i++ ?></td>
                  <td style="display:flex; align-items:center; gap:10px;">
                    <div class="reviewer-avatar"><?= $initials ?></div>
                    <strong><?= htmlspecialchars($cust['full_name']) ?></strong>
                  </td>
                  <td><?= htmlspecialchars($cust['email']) ?></td>
                  <td><?= htmlspecialchars($cust['phone'] ?? 'N/A') ?></td>
                  <td><?= htmlspecialchars($cust['city'] ?? 'N/A') ?></td>
                  <td><?= $cust['total_orders'] ?></td>
                  <td>Rs. <?= number_format($cust['total_spent']) ?></td>
                  <td><?= date('d M Y', strtotime($cust['created_at'])) ?></td>
                  <td>
                    <button class="btn btn-sm btn-outline" onclick="viewCustomer(<?= htmlspecialchars(json_encode($cust)) ?>)"><i class="fas fa-eye"></i></button>
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

  <!-- VIEW CUSTOMER MODAL -->
  <div class="modal-overlay" id="custModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Customer Details</h3>
        <button class="modal-close" onclick="closeModal('custModal')">&times;</button>
      </div>
      <div class="modal-body" id="custModalBody"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('custModal')">Close</button>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    function viewCustomer(c) {
      const names = c.full_name.split(' ');
      const initials = names.map(n => n[0]).join('').substring(0, 2).toUpperCase();
      document.getElementById('custModalBody').innerHTML = `
        <div style="text-align:center; margin-bottom:20px;">
          <div class="profile-avatar-lg" style="margin:0 auto 12px;">${initials}</div>
          <h3 style="font-size:20px;">${c.full_name}</h3>
          <p class="text-muted">${c.city || 'N/A'}</p>
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div style="border:1px solid var(--clr-border); padding:14px; border-radius:10px;">
            <strong style="font-size:11px; color:var(--clr-text-muted); text-transform:uppercase;">Email</strong>
            <p style="margin-top:4px;">${c.email}</p>
          </div>
          <div style="border:1px solid var(--clr-border); padding:14px; border-radius:10px;">
            <strong style="font-size:11px; color:var(--clr-text-muted); text-transform:uppercase;">Phone</strong>
            <p style="margin-top:4px;">${c.phone || 'N/A'}</p>
          </div>
          <div style="border:1px solid var(--clr-border); padding:14px; border-radius:10px;">
            <strong style="font-size:11px; color:var(--clr-text-muted); text-transform:uppercase;">Address</strong>
            <p style="margin-top:4px;">${c.address || 'N/A'}</p>
          </div>
          <div style="border:1px solid var(--clr-border); padding:14px; border-radius:10px;">
            <strong style="font-size:11px; color:var(--clr-text-muted); text-transform:uppercase;">Member Since</strong>
            <p style="margin-top:4px;">${c.created_at}</p>
          </div>
          <div style="border:1px solid var(--clr-border); padding:14px; border-radius:10px;">
            <strong style="font-size:11px; color:var(--clr-text-muted); text-transform:uppercase;">Total Orders</strong>
            <p style="margin-top:4px; font-size:20px; font-weight:700;">${c.total_orders}</p>
          </div>
          <div style="border:1px solid var(--clr-border); padding:14px; border-radius:10px;">
            <strong style="font-size:11px; color:var(--clr-text-muted); text-transform:uppercase;">Total Spent</strong>
            <p style="margin-top:4px; font-size:20px; font-weight:700; color:var(--clr-gold);">Rs. ${parseInt(c.total_spent).toLocaleString()}</p>
          </div>
        </div>`;
      document.getElementById('custModal').classList.add('show');
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
