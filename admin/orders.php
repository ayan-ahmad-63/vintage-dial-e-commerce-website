<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once '../includes/asset_helpers.php';
$currentPage = 'orders';
$pageTitle = 'Order Management';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $orderId = intval($_POST['order_id']);
    $newStatus = $_POST['new_status'];
    $note = trim($_POST['note'] ?? '');
    $allowed = ['Pending', 'Processing', 'Shipped', 'Delivered', 'Cancelled'];

    if (!in_array($newStatus, $allowed, true)) {
        header('Location: orders.php?msg=error');
        exit;
    }

    try {
        $db->beginTransaction();

        $orderStmt = $db->prepare("SELECT status, quantity, product_id FROM orders WHERE id = ? FOR UPDATE");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch();

        if (!$order) {
            throw new Exception('Order not found.');
        }

        $currentStatus = $order['status'];
        $qty = (int) $order['quantity'];
        $productId = (int) $order['product_id'];

        if ($currentStatus !== $newStatus) {
            if ($newStatus === 'Cancelled' && $currentStatus !== 'Cancelled') {
                $db->prepare("UPDATE products SET stock = stock + ? WHERE id = ?")->execute([$qty, $productId]);
            } elseif ($currentStatus === 'Cancelled' && $newStatus !== 'Cancelled') {
                $stockStmt = $db->prepare("SELECT stock FROM products WHERE id = ? FOR UPDATE");
                $stockStmt->execute([$productId]);
                $stock = (int) $stockStmt->fetchColumn();

                if ($stock < $qty) {
                    throw new Exception('Insufficient stock to reactivate this order.');
                }

                $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?")->execute([$qty, $productId]);
            }
        }

        $stmt = $db->prepare("UPDATE orders SET status = ?, note = ? WHERE id = ?");
        $stmt->execute([$newStatus, $note, $orderId]);
        $db->commit();

        header('Location: orders.php?msg=updated');
        exit;
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        header('Location: orders.php?msg=error');
        exit;
    }
}

// Filters
$statusFilter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$where = [];
$params = [];

if ($statusFilter) {
    $where[] = "o.status = ?";
    $params[] = $statusFilter;
}
if ($search) {
    $where[] = "(o.order_code LIKE ? OR c.full_name LIKE ? OR p.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$orderBy = match($sort) {
    'oldest' => 'o.order_date ASC',
    'high'   => 'o.total_amount DESC',
    'low'    => 'o.total_amount ASC',
    default  => 'o.order_date DESC',
};

$stmt = $db->prepare("
    SELECT o.*, c.full_name as customer_name, c.phone, c.city, c.address,
           p.name as product_name, p.image as product_image
    FROM orders o
    LEFT JOIN customers c ON o.customer_id = c.id
    LEFT JOIN products p ON o.product_id = p.id
    $whereSQL
    ORDER BY $orderBy
");
$stmt->execute($params);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage orders on Vintage Dial admin panel">
  <title>Orders | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>

<body>
  <?php include 'includes/sidebar.php'; ?>

  <div class="main">
    <?php include 'includes/topbar.php'; ?>

    <section class="content">

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'updated'): ?>
      <div style="background:#d1fae5; color:#065f46; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-check-circle"></i> Order status updated successfully!
      </div>
      <?php endif; ?>

      <?php if (isset($_GET['msg']) && $_GET['msg'] === 'error'): ?>
      <div style="background:#fee2e2; color:#991b1b; padding:12px 16px; border-radius:10px; margin-bottom:16px; font-size:13px;">
        <i class="fas fa-exclamation-circle"></i> Order status update failed. Please check the available stock and try again.
      </div>
      <?php endif; ?>

      <!-- FILTERS -->
      <div class="card">
        <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
          <form method="GET" class="filters" style="display:flex; gap:12px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search orders…" value="<?= htmlspecialchars($search) ?>">
            <select name="status" onchange="this.form.submit()">
              <option value="">All Status</option>
              <option value="Pending" <?= $statusFilter === 'Pending' ? 'selected' : '' ?>>Pending</option>
              <option value="Processing" <?= $statusFilter === 'Processing' ? 'selected' : '' ?>>Processing</option>
              <option value="Shipped" <?= $statusFilter === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
              <option value="Delivered" <?= $statusFilter === 'Delivered' ? 'selected' : '' ?>>Delivered</option>
              <option value="Cancelled" <?= $statusFilter === 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <select name="sort" onchange="this.form.submit()">
              <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest First</option>
              <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
              <option value="high" <?= $sort === 'high' ? 'selected' : '' ?>>Amount: High → Low</option>
              <option value="low" <?= $sort === 'low' ? 'selected' : '' ?>>Amount: Low → High</option>
            </select>
            <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-search"></i> Search</button>
          </form>
          <span class="text-muted" style="font-size:13px;">Showing <?= count($orders) ?> orders</span>
        </div>
      </div>

      <!-- ORDERS TABLE -->
      <div class="card">
        <div class="card-body">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Product</th>
                  <th>Qty</th>
                  <th>Amount</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($orders as $o): ?>
                <tr>
                  <td>#<?= htmlspecialchars($o['order_code']) ?></td>
                  <td><?= htmlspecialchars($o['customer_name'] ?? 'N/A') ?></td>
                  <td style="display:flex; align-items:center; gap:8px;">
                    <?php if (!empty($o['product_image'])): ?>
                    <img src="<?= htmlspecialchars(asset_url($o['product_image'])) ?>" alt="<?= htmlspecialchars($o['product_name'] ?? 'Product') ?>">
                    <?php endif; ?>
                    <?= htmlspecialchars($o['product_name'] ?? 'N/A') ?>
                  </td>
                  <td><?= $o['quantity'] ?></td>
                  <td>Rs. <?= number_format($o['total_amount']) ?></td>
                  <td><?= date('d M Y', strtotime($o['order_date'])) ?></td>
                  <td><span class="badge badge-<?= strtolower($o['status']) ?>"><?= $o['status'] ?></span></td>
                  <td>
                    <button class="btn btn-sm btn-outline" onclick="openStatusModal(<?= $o['id'] ?>, '<?= $o['order_code'] ?>', '<?= $o['status'] ?>')"><i class="fas fa-edit"></i></button>
                    <button class="btn btn-sm btn-outline" onclick="openViewModal(<?= htmlspecialchars(json_encode($o)) ?>)"><i class="fas fa-eye"></i></button>
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

  <!-- UPDATE STATUS MODAL -->
  <div class="modal-overlay" id="statusModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Update Order Status — <span id="modalOrderId"></span></h3>
        <button class="modal-close" onclick="closeModal('statusModal')">&times;</button>
      </div>
      <form method="POST" action="orders.php">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="order_id" id="modalOrderDbId">
        <div class="modal-body">
          <div class="form-group">
            <label>New Status</label>
            <select name="new_status" id="newStatus">
              <option value="Pending">Pending</option>
              <option value="Processing">Processing</option>
              <option value="Shipped">Shipped</option>
              <option value="Delivered">Delivered</option>
              <option value="Cancelled">Cancelled</option>
            </select>
          </div>
          <div class="form-group">
            <label>Note (optional)</label>
            <textarea name="note" placeholder="Add a note about this status change…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
          <button type="submit" class="btn btn-primary">Update Status</button>
        </div>
      </form>
    </div>
  </div>

  <!-- VIEW ORDER MODAL -->
  <div class="modal-overlay" id="viewModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Order Details — <span id="viewOrderId"></span></h3>
        <button class="modal-close" onclick="closeModal('viewModal')">&times;</button>
      </div>
      <div class="modal-body" id="viewModalBody"></div>
      <div class="modal-footer">
        <button class="btn btn-outline" onclick="closeModal('viewModal')">Close</button>
        <button class="btn btn-dark" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
      </div>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    function openStatusModal(dbId, orderCode, currentStatus) {
      document.getElementById('modalOrderId').textContent = '#' + orderCode;
      document.getElementById('modalOrderDbId').value = dbId;
      document.getElementById('newStatus').value = currentStatus;
      document.getElementById('statusModal').classList.add('show');
    }

    function openViewModal(order) {
      document.getElementById('viewOrderId').textContent = '#' + order.order_code;
      document.getElementById('viewModalBody').innerHTML = `
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
          <div><strong style="font-size:12px; color:var(--clr-text-muted);">CUSTOMER</strong><p>${order.customer_name || 'N/A'}</p></div>
          <div><strong style="font-size:12px; color:var(--clr-text-muted);">PHONE</strong><p>${order.phone || 'N/A'}</p></div>
          <div><strong style="font-size:12px; color:var(--clr-text-muted);">CITY</strong><p>${order.city || 'N/A'}</p></div>
          <div><strong style="font-size:12px; color:var(--clr-text-muted);">DATE</strong><p>${order.order_date}</p></div>
          <div><strong style="font-size:12px; color:var(--clr-text-muted);">PRODUCT</strong><p>${order.product_name || 'N/A'} × ${order.quantity}</p></div>
          <div><strong style="font-size:12px; color:var(--clr-text-muted);">AMOUNT</strong><p style="font-weight:700; font-size:18px;">Rs. ${parseInt(order.total_amount).toLocaleString()}</p></div>
        </div>
        <hr style="border:0; border-top:1px solid var(--clr-border); margin:16px 0;">
        <div><strong style="font-size:12px; color:var(--clr-text-muted);">STATUS</strong>
          <p><span class="badge badge-${order.status.toLowerCase()}">${order.status}</span></p>
        </div>
        ${order.note ? '<div style="margin-top:12px;"><strong style="font-size:12px; color:var(--clr-text-muted);">NOTE</strong><p>' + order.note + '</p></div>' : ''}`;
      document.getElementById('viewModal').classList.add('show');
    }

    function closeModal(id) {
      document.getElementById(id).classList.remove('show');
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
