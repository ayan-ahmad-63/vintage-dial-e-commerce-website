<?php
$pageTitle = "My Shopping Cart";
include 'includes/header.php';

// Initialize promo details if empty
$discountPct = isset($_SESSION['discount_pct']) ? intval($_SESSION['discount_pct']) : 0;
$promoCode = isset($_SESSION['promo_code']) ? $_SESSION['promo_code'] : '';

// Calculate pricing totals
$subtotal = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $itemCost = $item['price'];
        if ($item['wrap'] === 'Yes') {
            $itemCost += 500; // Gift wrap surcharge
        }
        $subtotal += $itemCost * $item['qty'];
    }
}

$discountAmount = $subtotal * ($discountPct / 100);
$discountedSubtotal = $subtotal - $discountAmount;

// Shipping calculation (free over Rs. 100,000, otherwise Rs. 1,500)
$shipping = 0;
if ($subtotal > 0) {
    $shipping = $discountedSubtotal > 100000 ? 0 : 1500;
}
$grandTotal = $discountedSubtotal + $shipping;
?>

<style>
  .cart-section {
    padding: 60px 0;
    background: #fdfcf9;
    min-height: 80vh;
  }
  .cart-container {
    background: #ffffff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.03);
  }
  .cart-header h1 {
    font-size: 32px;
    font-weight: 700;
    color: #111;
  }
  .cart-table {
    width: 100%;
    margin-top: 30px;
  }
  .cart-table th {
    border-bottom: 2px solid #eae5dc;
    padding-bottom: 15px;
    font-weight: 700;
    color: #111;
    font-size: 14px;
    text-transform: uppercase;
  }
  .cart-row {
    border-bottom: 1px solid #f1ece2;
  }
  .cart-row td {
    padding: 25px 0;
    vertical-align: middle;
  }
  .cart-img {
    width: 90px;
    height: 90px;
    object-fit: contain;
    background: #faf8f5;
    border-radius: 10px;
    border: 1px solid #eee;
    padding: 8px;
  }
  .cart-qty-input {
    width: 50px;
    text-align: center;
    border: 1px solid #eae5dc;
    border-radius: 6px;
    padding: 4px;
    font-weight: 600;
  }
  .qty-btn {
    border: 1px solid #eae5dc;
    background: #faf8f5;
    color: #111;
    width: 28px;
    height: 28px;
    border-radius: 6px;
    font-weight: bold;
    cursor: pointer;
    transition: 0.2s;
  }
  .qty-btn:hover {
    background: #b08d57;
    color: #fff;
    border-color: #b08d57;
  }
  .btn-remove {
    background: none;
    border: none;
    color: #d9534f;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    transition: 0.2s;
  }
  .btn-remove:hover {
    color: #c9302c;
    text-decoration: underline;
  }
  
  /* SUMMARY CARD */
  .summary-card {
    background: #fffdfa;
    border: 1px solid #b08d57;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 4px 20px rgba(176,141,87,0.05);
  }
  .summary-card h3 {
    font-size: 18px;
    font-weight: 700;
    border-bottom: 2px solid #eae5dc;
    padding-bottom: 12px;
    margin-bottom: 20px;
  }
  .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
    color: #555;
  }
  .summary-total {
    border-top: 2px solid #eae5dc;
    padding-top: 15px;
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
    font-size: 18px;
    font-weight: 700;
    color: #111;
  }
</style>

<section class="cart-section">
  <div class="container">
    <div class="cart-container">
      
      <div class="cart-header text-center mb-4">
        <h1>My Shopping Cart</h1>
        <p class="text-muted">Review your selected timepieces before proceeding to checkout</p>
      </div>

      <!-- Messages -->
      <?php if (isset($_GET['msg'])): ?>
        <?php if ($_GET['msg'] === 'removed'): ?>
          <div class="alert alert-warning"><i class="fas fa-trash-alt mr-2"></i> Item removed from your cart.</div>
        <?php elseif ($_GET['msg'] === 'promo_applied'): ?>
          <div class="alert alert-success"><i class="fas fa-tags mr-2"></i> Promo code applied successfully! (<?= $discountPct ?>% Off)</div>
        <?php elseif ($_GET['msg'] === 'promo_invalid'): ?>
          <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i> Invalid promo code. Please try again.</div>
        <?php elseif ($_GET['msg'] === 'updated'): ?>
          <div class="alert alert-info"><i class="fas fa-sync mr-2"></i> Cart updated successfully.</div>
        <?php endif; ?>
      <?php endif; ?>

      <?php if (empty($_SESSION['cart'])): ?>
        <!-- Empty Cart State -->
        <div class="text-center py-5">
          <i class="fas fa-shopping-cart mb-4" style="font-size: 56px; color: #eae5dc;"></i>
          <h3>Your cart is empty</h3>
          <p class="text-muted mt-2">You haven't added any luxury watches to your cart yet.</p>
          <a href="watches.php" class="btn px-4 py-2 mt-3" style="background:#b08d57; color:#fff; border-radius:30px; font-weight:600;">EXPLORE TIMEPIECES</a>
        </div>
      <?php else: ?>
        <div class="row mt-4">
          <!-- Cart Items Table -->
          <div class="col-lg-8">
            <div class="table-responsive">
              <table class="cart-table text-left">
                <thead>
                  <tr>
                    <th colspan="2">Watch Details</th>
                    <th>Variations</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th class="text-right">Total</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($_SESSION['cart'] as $key => $item): 
                      $basePrice = $item['price'];
                      $wrapCost = ($item['wrap'] === 'Yes') ? 500 : 0;
                      $itemTotal = ($basePrice + $wrapCost) * $item['qty'];
                  ?>
                    <tr class="cart-row">
                      <td style="width: 100px;">
                        <img src="<?= htmlspecialchars(asset_url(first_image_path($item['image']))) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="cart-img">
                      </td>
                      <td>
                        <strong style="font-size:16px; color:#111;"><?= htmlspecialchars($item['name']) ?></strong>
                        <div class="text-muted" style="font-size:11px;"><?= htmlspecialchars($item['subtitle']) ?></div>
                        
                        <!-- Remove button form -->
                        <form action="cart_action.php" method="POST" class="mt-2">
                          <input type="hidden" name="action" value="remove">
                          <input type="hidden" name="cart_key" value="<?= $key ?>">
                          <button type="submit" class="btn-remove p-0">Remove Item</button>
                        </form>
                      </td>
                      <td style="font-size: 13px; color: #555;">
                        Size: <?= htmlspecialchars($item['size']) ?><br>
                        Wrap: <?= htmlspecialchars($item['wrap']) ?> <?= $wrapCost > 0 ? '(+Rs. 500)' : '' ?>
                      </td>
                      <td>
                        <div style="font-size:14px; font-weight:600;">Rs. <?= number_format($basePrice + $wrapCost) ?></div>
                      </td>
                      <td>
                        <div class="d-flex align-items-center">
                          <!-- Decr Qty Form -->
                          <form action="cart_action.php" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_key" value="<?= $key ?>">
                            <input type="hidden" name="qty" value="<?= $item['qty'] - 1 ?>">
                            <button type="submit" class="qty-btn mr-1">-</button>
                          </form>
                          
                          <input type="text" class="cart-qty-input" value="<?= $item['qty'] ?>" readonly>
                          
                          <!-- Incr Qty Form -->
                          <form action="cart_action.php" method="POST" style="margin:0;">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="cart_key" value="<?= $key ?>">
                            <input type="hidden" name="qty" value="<?= $item['qty'] + 1 ?>">
                            <button type="submit" class="qty-btn ml-1">+</button>
                          </form>
                        </div>
                      </td>
                      <td class="text-right">
                        <div style="font-size:15px; font-weight:700; color:#b08d57;">Rs. <?= number_format($itemTotal) ?></div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            
            <!-- Promo Code Form -->
            <div class="mt-4 p-4 border-radius-10" style="background:#faf8f5; border: 1px dashed #eae5dc; border-radius: 10px;">
              <form action="cart_action.php" method="POST" class="form-inline justify-content-between">
                <input type="hidden" name="action" value="apply_promo">
                <div class="form-group mb-2 mb-md-0">
                  <label class="font-weight-bold mr-3" style="font-size:13px; color:#111;">PROMO CODE:</label>
                  <input type="text" name="promo_code" class="form-control" placeholder="Enter coupon code" value="<?= htmlspecialchars($promoCode) ?>" style="border-radius:8px; border-color:#eae5dc; text-transform:uppercase;">
                </div>
                <button type="submit" class="btn btn-dark px-4 py-2 mt-2 mt-md-0" style="border-radius:20px; font-weight:600; font-size:12px;">APPLY COUPON</button>
              </form>
              <div class="text-muted mt-2" style="font-size:11px;">
                💡 Hint: Try coupon code <strong>WELCOME10</strong> for 10% off or <strong>VINTAGE20</strong> for 20% off.
              </div>
            </div>
          </div>
          
          <!-- Summary Sidebar -->
          <div class="col-lg-4 mt-5 mt-lg-0">
            <div class="summary-card">
              <h3>Order Summary</h3>
              
              <div class="summary-row">
                <span>Cart Subtotal</span>
                <span>Rs. <?= number_format($subtotal) ?></span>
              </div>
              
              <?php if ($discountAmount > 0): ?>
                <div class="summary-row" style="color: #4cae4c; font-weight:600;">
                  <span>Promo Code Discount (<?= $discountPct ?>%)</span>
                  <span>-Rs. <?= number_format($discountAmount) ?></span>
                </div>
              <?php endif; ?>
              
              <div class="summary-row">
                <span>Shipping Cost</span>
                <span><?= $shipping > 0 ? 'Rs. ' . number_format($shipping) : 'FREE SHIPPING' ?></span>
              </div>
              
              <div class="summary-total">
                <span>Order Total</span>
                <span>Rs. <?= number_format($grandTotal) ?></span>
              </div>
              
              <a href="checkout.php" class="btn btn-block mt-4 py-3" style="background:#111; color:#fff; font-weight:600; border-radius:30px; transition:0.3s; font-size:14px; text-transform:uppercase;">
                PROCEED TO CHECKOUT
              </a>
              <div class="text-center mt-3">
                <a href="watches.php" style="font-size:12px; color:#b08d57; font-weight:600;"><i class="fas fa-arrow-left mr-1"></i> Continue Shopping</a>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
