<?php
$pageTitle = "My Orders";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force Login
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$customerId = $_SESSION['customer_id'];
$successMsg = '';

// Handle Reorder Request
if (isset($_GET['action']) && $_GET['action'] === 'reorder' && isset($_GET['code'])) {
    $reorderCode = trim($_GET['code']);
    try {
        // Fetch all items from that order code for this customer
        $stmt = $db->prepare("SELECT o.*, p.name, p.subtitle, p.price, p.image FROM orders o JOIN products p ON o.product_id = p.id WHERE o.order_code = ? AND o.customer_id = ?");
        $stmt->execute([$reorderCode, $customerId]);
        $items = $stmt->fetchAll();
        
        if (!empty($items)) {
            // Ensure cart is initialized
            if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
            
            foreach ($items as $item) {
                // Variations parsing (we saved them as 'Size: Standard, GiftWrap: No.' inside note)
                $size = 'Standard';
                $wrap = 'No';
                
                if (preg_match('/Size:\s*([^,]+)/i', $item['note'], $matches)) {
                    $size = trim($matches[1]);
                }
                if (preg_match('/GiftWrap:\s*([^.]+)/i', $item['note'], $matches)) {
                    $wrap = trim($matches[1]);
                }
                
                $cartKey = $item['product_id'] . '_' . strtolower($size) . '_' . strtolower($wrap);
                
                // Add to session cart
                $_SESSION['cart'][$cartKey] = [
                    'id' => $item['product_id'],
                    'name' => $item['name'],
                    'subtitle' => $item['subtitle'],
                    'price' => floatval($item['price']),
                    'image' => first_image_path($item['image']),
                    'size' => $size,
                    'wrap' => $wrap,
                    'qty' => $item['quantity']
                ];
            }
            
            header('Location: cart.php?msg=reordered');
            exit;
        }
    } catch (Exception $e) {
        $errorMsg = "Database error: " . $e->getMessage();
    }
}

// Fetch all orders for this customer
try {
    $stmt = $db->prepare("
        SELECT o.*, p.name as product_name, p.image as product_image,
               (SELECT COUNT(*) FROM reviews r WHERE r.customer_id = o.customer_id AND r.product_id = o.product_id) AS review_count,
               (SELECT status FROM reviews r WHERE r.customer_id = o.customer_id AND r.product_id = o.product_id ORDER BY r.review_date DESC LIMIT 1) AS review_status
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.customer_id = ?
        ORDER BY o.order_date DESC
    ");
    $stmt->execute([$customerId]);
    $allOrderItems = $stmt->fetchAll();
    
    // Group items by order code
    $ordersGrouped = [];
    foreach ($allOrderItems as $item) {
        $code = $item['order_code'];
        if (!isset($ordersGrouped[$code])) {
            $ordersGrouped[$code] = [
                'order_code' => $code,
                'order_date' => $item['order_date'],
                'status' => $item['status'],
                'items' => [],
                'total' => 0
            ];
        }
        $ordersGrouped[$code]['items'][] = $item;
        $ordersGrouped[$code]['total'] += $item['total_amount'];
    }
} catch (Exception $e) {
    $ordersGrouped = [];
    $dbError = $e->getMessage();
}
?>

<style>
  .orders-section {
    padding: 60px 0;
    background: #fdfcf9;
    min-height: 80vh;
  }
  .orders-container {
    background: #ffffff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  }
  .section-title {
    font-size: 28px;
    font-weight: 700;
    color: #111;
  }
  .sidebar-menu {
    border: 1px solid #eae5dc;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
  }
  .sidebar-menu a {
    display: block;
    padding: 15px 20px;
    color: #555;
    font-weight: 600;
    text-decoration: none;
    border-bottom: 1px solid #eae5dc;
    transition: 0.2s;
  }
  .sidebar-menu a:last-child {
    border-bottom: none;
  }
  .sidebar-menu a:hover, .sidebar-menu a.active {
    background: #fffdf5;
    color: #b08d57;
    padding-left: 25px;
  }
  
  /* ORDER CARD */
  .order-card {
    border: 1px solid #eae5dc;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 20px;
    background: #fff;
    transition: 0.3s;
  }
  .order-card:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.03);
    border-color: #b08d57;
  }
  .order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1ece2;
    padding-bottom: 15px;
    margin-bottom: 15px;
  }
  .order-code-text {
    font-size: 18px;
    font-weight: 700;
    color: #111;
  }
  .order-date-text {
    font-size: 12px;
    color: #777;
  }
  .status-badge {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
  }
  .status-pending { background: #fff3cd; color: #856404; }
  .status-processing { background: #d1ecf1; color: #0c5460; }
  .status-shipped { background: #cce5ff; color: #004085; }
  .status-delivered { background: #d4edda; color: #155724; }
  .status-cancelled { background: #f8d7da; color: #721c24; }
  
  .item-thumb {
    width: 60px;
    height: 60px;
    object-fit: contain;
    border: 1px solid #eee;
    border-radius: 8px;
    padding: 4px;
    background: #faf8f5;
  }
</style>

<section class="orders-section">
  <div class="container">
    <div class="row">
      <!-- Sidebar Navigation -->
      <div class="col-md-4 mb-4 mb-md-0">
        <div class="sidebar-menu">
          <a href="profile.php"><i class="fas fa-user-circle mr-2"></i> Account Details</a>
          <a href="order.php" class="active"><i class="fas fa-shopping-bag mr-2"></i> Order History</a>
          <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
        </div>
      </div>
      
      <!-- Orders List -->
      <div class="col-md-8">
        <div class="orders-container">
          <h2 class="section-title mb-4">Order History</h2>
          
          <?php if (isset($dbError)): ?>
            <div class="alert alert-danger">Error fetching orders: <?= htmlspecialchars($dbError) ?></div>
          <?php elseif (empty($ordersGrouped)): ?>
            <div class="text-center py-5">
              <i class="fas fa-box-open mb-3" style="font-size:48px; color:#eae5dc;"></i>
              <h4>No Orders Yet</h4>
              <p class="text-muted">You haven't placed any orders with Vintage Dial yet.</p>
              <a href="watches.php" class="btn px-4 py-2 mt-2" style="background:#b08d57; color:#fff; border-radius:30px;">Shop Timepieces</a>
            </div>
          <?php else: ?>
            
            <?php foreach ($ordersGrouped as $order): ?>
              <div class="order-card">
                <div class="order-card-header">
                  <div>
                    <span class="order-code-text">#<?= htmlspecialchars($order['order_code']) ?></span>
                    <div class="order-date-text mt-1">Placed on <?= date('d M Y, h:i A', strtotime($order['order_date'])) ?></div>
                  </div>
                  <span class="status-badge status-<?= strtolower($order['status']) ?>">
                    <?= htmlspecialchars($order['status']) ?>
                  </span>
                </div>
                
                <!-- Order Items Summary List -->
                <div class="order-items-list mb-3">
                  <?php foreach ($order['items'] as $item): ?>
                    <div class="d-flex align-items-center justify-content-between mb-3">
                      <div class="d-flex align-items-center">
                        <?php if (!empty($item['product_image'])): ?>
                        <img src="<?= htmlspecialchars(asset_url(first_image_path($item['product_image']))) ?>" class="item-thumb mr-3" alt="<?= htmlspecialchars($item['product_name']) ?>">
                        <?php endif; ?>
                        <div>
                          <strong style="font-size:14px; color:#111;"><?= htmlspecialchars($item['product_name']) ?></strong>
                          <div class="text-muted" style="font-size:11px;">Qty: <?= $item['quantity'] ?></div>
                        </div>
                      </div>
                      <span class="font-weight-bold" style="font-size:14px;">Rs. <?= number_format($item['total_amount']) ?></span>
                    </div>
                    <?php if ($item['status'] === 'Delivered'): ?>
                      <div class="d-flex justify-content-end mb-3">
                        <?php if (intval($item['review_count']) === 0): ?>
                          <a href="product-detail.php?id=<?= $item['product_id'] ?>&from_order=1#reviews" class="btn btn-sm btn-outline-dark px-3" style="border-radius:20px; font-weight:600;">Leave Review</a>
                        <?php elseif ($item['review_status'] === 'pending'): ?>
                          <span class="badge badge-warning" style="background:#fff3cd; color:#856404; padding:6px 12px; border-radius:20px; font-size:12px;">Review pending approval</span>
                        <?php else: ?>
                          <span class="badge badge-success" style="background:#d4edda; color:#155724; padding:6px 12px; border-radius:20px; font-size:12px;">Reviewed</span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  <?php endforeach; ?>
                </div>
                
                <!-- Total and Action Buttons -->
                <div class="d-flex justify-content-between align-items-center border-top pt-3" style="border-color:#f1ece2 !important;">
                  <div>
                    <span class="text-muted" style="font-size:12px;">Grand Total:</span>
                    <strong style="font-size:18px; color:#b08d57; display:block;">Rs. <?= number_format($order['total']) ?></strong>
                  </div>
                  <div>
                    <a href="order-detail.php?code=<?= urlencode($order['order_code']) ?>" class="btn btn-sm btn-outline-dark px-3 mr-2" style="border-radius:20px; font-weight:600;">View Details</a>
                    <a href="order.php?action=reorder&code=<?= urlencode($order['order_code']) ?>" class="btn btn-sm px-3" style="background:#b08d57; color:#fff; border-radius:20px; font-weight:600;">Reorder</a>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
            
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
