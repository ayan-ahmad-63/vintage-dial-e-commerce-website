<?php
$pageTitle = "Order Details";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force Login
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$orderCode = isset($_GET['code']) ? trim($_GET['code']) : '';
$customerId = $_SESSION['customer_id'];

if (empty($orderCode)) {
    header('Location: order.php');
    exit;
}

try {
    // Fetch items in this order
    $stmt = $db->prepare("
        SELECT o.*, p.name as product_name, p.subtitle as product_subtitle, p.image as product_image, p.price as base_price,
               (SELECT COUNT(*) FROM reviews r WHERE r.customer_id = o.customer_id AND r.product_id = o.product_id) AS review_count,
               (SELECT status FROM reviews r WHERE r.customer_id = o.customer_id AND r.product_id = o.product_id ORDER BY r.review_date DESC LIMIT 1) AS review_status
        FROM orders o
        JOIN products p ON o.product_id = p.id
        WHERE o.order_code = ? AND o.customer_id = ?
    ");
    $stmt->execute([$orderCode, $customerId]);
    $orderItems = $stmt->fetchAll();
    
    if (empty($orderItems)) {
        header('Location: order.php');
        exit;
    }
    
    // Fetch customer shipping details
    $custStmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $custStmt->execute([$customerId]);
    $cust = $custStmt->fetch();
    
    // Aggregate values
    $orderStatus = $orderItems[0]['status'];
    $orderDate = $orderItems[0]['order_date'];
    $orderNote = $orderItems[0]['note'];
    
    $subtotal = 0;
    foreach ($orderItems as $item) {
        $subtotal += $item['total_amount'];
    }
    
    // Discount calculations if stored in note or computed. 
    // In our setup, orders total spent was saved during checkout. Let's assume grand total is the sum of items total + shipping.
    // If subtotal is > 100,000, shipping is free, else 1500.
    $shipping = $subtotal > 100000 ? 0 : 1500;
    $grandTotal = $subtotal + $shipping;
    
    // Estimated delivery date (3 days after order date)
    $estDelivery = date('d F Y', strtotime($orderDate . ' + 3 days'));
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Map status to progress bar steps
$statusStep = 1;
if ($orderStatus === 'Processing') $statusStep = 2;
elseif ($orderStatus === 'Shipped') $statusStep = 3;
elseif ($orderStatus === 'Delivered') $statusStep = 4;
elseif ($orderStatus === 'Cancelled') $statusStep = 0;
?>

<style>
  .order-detail-section {
    padding: 60px 0;
    background: #fdfcf9;
    min-height: 80vh;
  }
  .order-detail-container {
    background: #ffffff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  }
  
  /* STATUS TRACKER BAR */
  .tracker-wrap {
    margin: 40px 0;
  }
  .tracker-steps {
    display: flex;
    justify-content: space-between;
    position: relative;
  }
  .tracker-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 40px;
    right: 40px;
    height: 4px;
    background: #eae5dc;
    z-index: 1;
  }
  .tracker-progress-line {
    position: absolute;
    top: 20px;
    left: 40px;
    height: 4px;
    background: #b08d57;
    z-index: 2;
    transition: 0.5s;
    <?php
      $width = '0%';
      if ($statusStep == 2) $width = '33%';
      elseif ($statusStep == 3) $width = '66%';
      elseif ($statusStep >= 4) $width = '100%';
      echo "width: $width;";
    ?>
  }
  .tracker-step {
    position: relative;
    z-index: 3;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: 80px;
  }
  .tracker-dot {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #fff;
    border: 3px solid #eae5dc;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #bbb;
    font-size: 16px;
    transition: 0.3s;
  }
  .tracker-step.completed .tracker-dot {
    border-color: #b08d57;
    background: #b08d57;
    color: #fff;
  }
  .tracker-step.active .tracker-dot {
    border-color: #b08d57;
    color: #b08d57;
    box-shadow: 0 0 12px rgba(176,141,87,0.3);
  }
  .tracker-label {
    margin-top: 10px;
    font-size: 12px;
    font-weight: 700;
    color: #999;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    text-align: center;
  }
  .tracker-step.completed .tracker-label, .tracker-step.active .tracker-label {
    color: #111;
  }
  
  .details-table th {
    border-bottom: 2px solid #eae5dc;
    padding-bottom: 12px;
    font-weight: 700;
    font-size: 13px;
    color: #111;
    text-transform: uppercase;
  }
  .details-table td {
    padding: 15px 0;
    vertical-align: middle;
    border-bottom: 1px solid #f1ece2;
  }
  .info-box-details {
    border: 1px solid #eae5dc;
    border-radius: 12px;
    padding: 20px;
    height: 100%;
  }
  .info-box-details h4 {
    font-size: 14px;
    font-weight: 700;
    border-bottom: 1px solid #f1ece2;
    padding-bottom: 8px;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #111;
  }
  .item-thumb {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border: 1px solid #eee;
    border-radius: 6px;
    background: #faf8f5;
    padding: 2px;
  }
</style>

<section class="order-detail-section">
  <div class="container">
    <div class="order-detail-container">
      
      <!-- Header Options -->
      <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
          <a href="order.php" style="color:#b08d57; font-weight:600; font-size:13px;"><i class="fas fa-arrow-left"></i> Back to Orders</a>
          <h2 class="font-weight-bold mt-2" style="font-size:26px; color:#111;">Order Details #<?= htmlspecialchars($orderCode) ?></h2>
          <span class="text-muted" style="font-size:13px;">Placed on <?= date('d F Y \a\t h:i A', strtotime($orderDate)) ?></span>
        </div>
        <div class="mt-3 mt-md-0">
          <button class="btn btn-outline-dark px-3 py-2 mr-2" onclick="window.print()" style="border-radius:20px; font-weight:600; font-size:13px;"><i class="fas fa-print"></i> Print Invoice</button>
          <a href="contact.php" class="btn btn-dark px-3 py-2" style="border-radius:20px; font-weight:600; font-size:13px; background:#111;">Contact Support</a>
        </div>
      </div>
      
      <!-- Tracker Section -->
      <?php if ($orderStatus === 'Cancelled'): ?>
        <div class="alert alert-danger text-center p-4" style="border-radius:12px;">
          <i class="fas fa-times-circle mb-2" style="font-size:36px;"></i>
          <h5 class="font-weight-bold">Order Cancelled</h5>
          <p class="mb-0 text-muted" style="font-size:13px;">This order has been cancelled. If you believe this is an error or need replacement assistance, contact customer support.</p>
        </div>
      <?php else: ?>
        <div class="tracker-wrap">
          <div class="tracker-steps">
            <div class="tracker-progress-line"></div>
            
            <!-- Step 1: Placed -->
            <div class="tracker-step <?= $statusStep >= 1 ? ($statusStep == 1 ? 'active' : 'completed') : '' ?>">
              <div class="tracker-dot"><i class="fas fa-file-invoice"></i></div>
              <span class="tracker-label">Placed</span>
            </div>
            
            <!-- Step 2: Processing -->
            <div class="tracker-step <?= $statusStep >= 2 ? ($statusStep == 2 ? 'active' : 'completed') : '' ?>">
              <div class="tracker-dot"><i class="fas fa-cog"></i></div>
              <span class="tracker-label">Processing</span>
            </div>
            
            <!-- Step 3: Shipped -->
            <div class="tracker-step <?= $statusStep >= 3 ? ($statusStep == 3 ? 'active' : 'completed') : '' ?>">
              <div class="tracker-dot"><i class="fas fa-truck"></i></div>
              <span class="tracker-label">Shipped</span>
            </div>
            
            <!-- Step 4: Delivered -->
            <div class="tracker-step <?= $statusStep >= 4 ? 'active completed' : '' ?>">
              <div class="tracker-dot"><i class="fas fa-check-double"></i></div>
              <span class="tracker-label">Delivered</span>
            </div>
          </div>
        </div>
        
        <?php if ($statusStep < 4): ?>
          <div class="text-center p-3 mb-4" style="background:#fffdf5; border:1px solid #eae5dc; border-radius:12px;">
            <i class="fas fa-clock mr-2" style="color:#b08d57;"></i> Estimated Delivery Date: <strong><?= $estDelivery ?></strong>
          </div>
        <?php endif; ?>
      <?php endif; ?>
      
      <!-- Shipping and Summary Cards -->
      <div class="row my-4">
        <!-- Billing / Shipping Info -->
        <div class="col-md-6 mb-4 mb-md-0">
          <div class="info-box-details">
            <h4>Shipping Information</h4>
            <p class="mb-1"><strong>Recipient Name:</strong> <?= htmlspecialchars($cust['full_name']) ?></p>
            <p class="mb-1"><strong>Phone Number:</strong> <?= htmlspecialchars($cust['phone'] ?? '—') ?></p>
            <p class="mb-1"><strong>City:</strong> <?= htmlspecialchars($cust['city'] ?? '—') ?></p>
            <p class="mb-1"><strong>Shipping Address:</strong> <?= htmlspecialchars($cust['address'] ?? '—') ?></p>
            <p class="mb-0"><strong>Payment Type:</strong> Cash on Delivery (COD)</p>
          </div>
        </div>
        
        <!-- Order Notes -->
        <div class="col-md-6">
          <div class="info-box-details">
            <h4>Order Specifications & Notes</h4>
            <p class="mb-2"><strong>Specifications Summary:</strong></p>
            <div style="background:#faf8f5; border:1px solid #eae5dc; border-radius:8px; padding:12px; font-size:13px; color:#555;">
              <?= htmlspecialchars($orderNote) ?>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Order Items Detail Table -->
      <h4 class="mt-5 mb-3 font-weight-bold" style="font-size:16px; color:#111; text-transform:uppercase; letter-spacing:0.5px;">ORDERED ITEMS</h4>
      <div class="table-responsive">
        <table class="table details-table">
          <thead>
            <tr>
              <th colspan="2">Watch Details</th>
              <th>Unit Price</th>
              <th class="text-center">Quantity</th>
              <th class="text-right">Total</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($orderItems as $item): ?>
              <tr>
                <td style="width: 70px;">
                  <?php if (!empty($item['product_image'])): ?>
                  <img src="<?= htmlspecialchars(asset_url(first_image_path($item['product_image']))) ?>" class="item-thumb" alt="<?= htmlspecialchars($item['product_name']) ?>">
                  <?php endif; ?>
                </td>
                <td>
                  <strong style="font-size:14px; color:#111;"><?= htmlspecialchars($item['product_name']) ?></strong>
                  <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($item['product_subtitle']) ?></div>
                  <?php if ($item['status'] === 'Delivered'): ?>
                    <div style="margin-top:10px;">
                      <?php if (intval($item['review_count']) === 0): ?>
                        <a href="product-detail.php?id=<?= $item['product_id'] ?>&from_order=1#reviews" class="btn btn-sm btn-outline-dark" style="border-radius:20px; font-weight:600; font-size:12px;">Leave Review</a>
                      <?php elseif ($item['review_status'] === 'pending'): ?>
                        <span style="display:inline-block; background:#fff3cd; color:#856404; padding:6px 10px; border-radius:20px; font-size:12px;">Review pending approval</span>
                      <?php else: ?>
                        <span style="display:inline-block; background:#d4edda; color:#155724; padding:6px 10px; border-radius:20px; font-size:12px;">Reviewed</span>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>
                </td>
                <td>Rs. <?= number_format($item['base_price']) ?></td>
                <td class="text-center"><?= $item['quantity'] ?></td>
                <td class="text-right font-weight-bold">Rs. <?= number_format($item['total_amount']) ?></td>
              </tr>
            <?php endforeach; ?>
            
            <!-- Calculations -->
            <tr>
              <td colspan="3" class="border-0"></td>
              <td class="text-right border-0 font-weight-bold text-muted" style="font-size:13px;">SUBTOTAL</td>
              <td class="text-right border-0 font-weight-bold" style="font-size:14px;">Rs. <?= number_format($subtotal) ?></td>
            </tr>
            <tr>
              <td colspan="3" class="border-0"></td>
              <td class="text-right border-0 font-weight-bold text-muted" style="font-size:13px;">SHIPPING FEE</td>
              <td class="text-right border-0 font-weight-bold" style="font-size:14px;"><?= $shipping > 0 ? 'Rs. ' . number_format($shipping) : 'FREE' ?></td>
            </tr>
            <tr>
              <td colspan="3" class="border-0"></td>
              <td class="text-right border-0 font-weight-bold" style="font-size:15px; color:#111;">ORDER TOTAL</td>
              <td class="text-right border-0 font-weight-bold" style="font-size:18px; color:#b08d57;">Rs. <?= number_format($grandTotal) ?></td>
            </tr>
          </tbody>
        </table>
      </div>

    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
