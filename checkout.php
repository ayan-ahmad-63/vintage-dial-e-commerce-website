<?php
$pageTitle = "Proceed to Checkout";
include 'includes/header.php';

// Check if cart is empty
if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    header('Location: cart.php');
    exit;
}

// Check if customer is logged in
$isLoggedIn = isset($_SESSION['customer_id']);
$cust = [];
if ($isLoggedIn) {
    try {
        $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
        $stmt->execute([$_SESSION['customer_id']]);
        $cust = $stmt->fetch();
    } catch (Exception $e) {
        // Quietly fail
    }
}

// Calculate totals
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) {
    $itemCost = $item['price'];
    if ($item['wrap'] === 'Yes') {
        $itemCost += 500;
    }
    $subtotal += $itemCost * $item['qty'];
}

$discountPct = isset($_SESSION['discount_pct']) ? intval($_SESSION['discount_pct']) : 0;
$discountAmount = $subtotal * ($discountPct / 100);
$discountedSubtotal = $subtotal - $discountAmount;
$shipping = $discountedSubtotal > 100000 ? 0 : 1500;
$grandTotal = $discountedSubtotal + $shipping;

$orderSuccess = false;
$orderCode = '';
$formErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'place_order') {
    if (!$isLoggedIn) {
        $formErrors[] = "You must be logged in to place an order.";
    } else {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $note = trim($_POST['order_notes'] ?? '');
        $paymentMethod = $_POST['payment'] ?? 'cod';
        
        // Basic Server Side Validation
        if (empty($name)) $formErrors[] = "Full Name is required.";
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $formErrors[] = "A valid Email Address is required.";
        if (empty($phone)) $formErrors[] = "Phone Number is required.";
        if (empty($address)) $formErrors[] = "Shipping Address is required.";
        if (empty($city)) $formErrors[] = "City is required.";
        
        // Check stock availability
        foreach ($_SESSION['cart'] as $item) {
            $chk = $db->prepare("SELECT name, stock FROM products WHERE id = ?");
            $chk->execute([$item['id']]);
            $prodStock = $chk->fetch();
            if ($prodStock && $prodStock['stock'] < $item['qty']) {
                $formErrors[] = "Insufficient stock for product " . htmlspecialchars($prodStock['name']) . ". Available stock: " . $prodStock['stock'];
            }
        }
        
        if (empty($formErrors)) {
            try {
                // Begin Transaction
                $db->beginTransaction();
                
                // Generate Unique Order Code
                $orderCode = 'VD-' . mt_rand(100000, 999999);
                $customerId = $_SESSION['customer_id'];
                
                // Update customer shipping details if they were empty
                $upCust = $db->prepare("UPDATE customers SET phone = COALESCE(NULLIF(phone, ''), ?), city = COALESCE(NULLIF(city, ''), ?), address = COALESCE(NULLIF(address, ''), ?) WHERE id = ?");
                $upCust->execute([$phone, $city, $address, $customerId]);
                
                // Loop through cart items and insert orders
                foreach ($_SESSION['cart'] as $item) {
                    $itemCost = $item['price'];
                    $itemWrap = $item['wrap'];
                    $itemSize = $item['size'];
                    
                    if ($itemWrap === 'Yes') {
                        $itemCost += 500;
                    }
                    $itemTotal = $itemCost * $item['qty'];
                    
                    // Build order detail note
                    $orderNote = "Size: $itemSize, GiftWrap: $itemWrap.";
                    if (!empty($note)) {
                        $orderNote .= " Customer Notes: $note";
                    }
                    if ($paymentMethod !== 'cod') {
                        $orderNote .= " Paid via " . ucfirst($paymentMethod) . ".";
                    }
                    
                    // Insert into orders table
                    $ins = $db->prepare("INSERT INTO orders (order_code, customer_id, product_id, quantity, total_amount, status, note) VALUES (?, ?, ?, ?, ?, 'Pending', ?)");
                    $ins->execute([$orderCode, $customerId, $item['id'], $item['qty'], $itemTotal, $orderNote]);
                    
                    // Deduct stock
                    $upStock = $db->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                    $upStock->execute([$item['qty'], $item['id']]);
                }
                
                // Update customer total spent & order count
                $upStats = $db->prepare("UPDATE customers SET total_orders = total_orders + 1, total_spent = total_spent + ? WHERE id = ?");
                $upStats->execute([$grandTotal, $customerId]);
                
                $db->commit();
                
                // Clear Cart and coupon details
                $_SESSION['cart'] = [];
                $_SESSION['promo_code'] = '';
                $_SESSION['discount_pct'] = 0;
                
                $orderSuccess = true;
            } catch (Exception $e) {
                $db->rollBack();
                $formErrors[] = "Failed to submit order. Please try again. Error: " . $e->getMessage();
            }
        }
    }
}
?>

<style>
.checkout-section {
    padding: 60px 0;
    background: #fdfcf9;
    min-height: 80vh;
}

.checkout-container {
    background: #ffffff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
}

.section-title-lg {
    font-size: 32px;
    font-weight: 700;
    color: #111;
}

.card-box {
    background: #fff;
    border: 1px solid #eae5dc;
    border-radius: 12px;
    padding: 30px;
    margin-bottom: 25px;
}

.card-box h3 {
    font-size: 18px;
    font-weight: 700;
    border-bottom: 2px solid #eae5dc;
    padding-bottom: 12px;
    margin-bottom: 20px;
    color: #111;
}

.checkout-input {
    border-radius: 8px !important;
    border-color: #eae5dc !important;
    padding: 12px !important;
    height: auto !important;
    font-size: 14px !important;
}

.checkout-input:focus {
    border-color: #b08d57 !important;
    box-shadow: 0 0 0 0.2rem rgba(176, 141, 87, 0.15) !important;
}

/* PAYMENT OPTIONS */
.payment-option {
    border: 1px solid #eae5dc;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 12px;
    cursor: pointer;
    transition: 0.2s;
}

.payment-option:hover {
    background: #fffdf5;
}

.payment-option.active {
    border-color: #b08d57;
    background: #fffdf5;
}

.payment-details {
    margin-top: 10px;
    font-size: 13px;
    color: #666;
    display: none;
    border-left: 3px solid #b08d57;
    padding-left: 15px;
}

.payment-option.active .payment-details {
    display: block;
}

/* ORDER SUMMARY */
.order-item-row {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #f1ece2;
    font-size: 14px;
}

.order-summary-box {
    background: #fffdfa;
    border: 1px solid #b08d57;
    border-radius: 12px;
    padding: 30px;
}

.order-summary-box h3 {
    font-size: 18px;
    font-weight: 700;
    border-bottom: 2px solid #eae5dc;
    padding-bottom: 12px;
    margin-bottom: 20px;
}

.summary-row-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 12px;
    font-size: 14px;
    color: #555;
}

.summary-row-total {
    border-top: 2px solid #eae5dc;
    padding-top: 15px;
    margin-top: 15px;
    display: flex;
    justify-content: space-between;
    font-size: 20px;
    font-weight: 700;
    color: #111;
}
</style>

<section class="checkout-section">
    <div class="container">
        <div class="checkout-container">

            <?php if ($orderSuccess): ?>
            <!-- Success Screen -->
            <div class="text-center py-5">
                <i class="fas fa-check-circle mb-4" style="font-size: 72px; color: #4cae4c;"></i>
                <h1 class="font-weight-bold">Order Confirmed!</h1>
                <p class="text-muted mt-2" style="font-size:16px;">Thank you for your purchase. Your order has been
                    placed successfully.</p>
                <div class="my-4 p-4 d-inline-block"
                    style="background:#fdfaf2; border:1px solid #eae5dc; border-radius:12px; min-width:300px;">
                    <div class="text-muted" style="font-size:12px;">ORDER CODE</div>
                    <div class="font-weight-bold" style="font-size:24px; color:#b08d57;">
                        #<?= htmlspecialchars($orderCode) ?></div>
                </div>
                <p class="text-muted" style="font-size:13px; max-width:500px; margin:0 auto 25px auto;">You can track
                    the status of your order in your Profile History. Cash on delivery orders will be verified via phone
                    call shortly.</p>
                <div>
                    <a href="order.php" class="btn px-4 py-3 mr-2"
                        style="background:#111; color:#fff; border-radius:30px; font-weight:600; font-size:14px;">VIEW
                        MY ORDERS</a>
                    <a href="watches.php" class="btn btn-outline-secondary px-4 py-3"
                        style="border-radius:30px; font-weight:600; font-size:14px;">CONTINUE SHOPPING</a>
                </div>
            </div>
            <?php else: ?>

            <div class="text-center mb-5">
                <h1 class="section-title-lg">Checkout Process</h1>
                <p class="text-muted">Almost there — please review your details and confirm purchase</p>
            </div>

            <?php if (!empty($formErrors)): ?>
            <div class="alert alert-danger">
                <h5 class="font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Form Validation Failed:</h5>
                <ul class="mb-0 pl-3">
                    <?php foreach ($formErrors as $err): ?>
                    <li><?= htmlspecialchars($err) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if (!$isLoggedIn): ?>
            <div class="alert alert-vintage text-center p-5">
                <i class="fas fa-user-lock mb-3" style="font-size:40px; color:#b08d57;"></i>
                <h4 class="font-weight-bold text-dark">Sign In Required to Place Order</h4>
                <p class="text-muted mx-auto mb-4" style="max-width:500px;">To ensure security, track shipment, and
                    maintain warranty claims, you must have an active client account with Vintage Dial.</p>
                <a href="login.php" class="btn btn-dark px-4 py-2 mr-2"
                    style="border-radius:20px; font-weight:600;">LOGIN</a>
                <a href="signup.php" class="btn btn-outline-secondary px-4 py-2"
                    style="border-radius:20px; font-weight:600;">CREATE ACCOUNT</a>
            </div>
            <?php else: ?>

            <form action="checkout.php" method="POST" id="checkoutForm">
                <input type="hidden" name="action" value="place_order">

                <div class="row">
                    <!-- Left Column: Form -->
                    <div class="col-lg-7">

                        <!-- Billing Box -->
                        <div class="card-box">
                            <h3>Billing & Shipping Details</h3>
                            <div class="form-group">
                                <label class="font-weight-bold" style="font-size:12px;">FULL NAME *</label>
                                <input type="text" name="full_name" class="form-control checkout-input" required
                                    value="<?= htmlspecialchars($cust['full_name'] ?? '') ?>"
                                    placeholder="e.g. Jamil Ahmed">
                            </div>
                            <div class="form-row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold" style="font-size:12px;">EMAIL ADDRESS *</label>
                                    <input type="email" name="email" class="form-control checkout-input" required
                                        value="<?= htmlspecialchars($cust['email'] ?? '') ?>"
                                        placeholder="e.g. jamil@test.com">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold" style="font-size:12px;">PHONE NUMBER *</label>
                                    <input type="text" name="phone" class="form-control checkout-input" required
                                        value="<?= htmlspecialchars($cust['phone'] ?? '') ?>"
                                        placeholder="e.g. 0300-1234567">
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="col-md-8 form-group">
                                    <label class="font-weight-bold" style="font-size:12px;">SHIPPING ADDRESS *</label>
                                    <input type="text" name="address" class="form-control checkout-input" required
                                        value="<?= htmlspecialchars($cust['address'] ?? '') ?>"
                                        placeholder="House #, Street name, Area">
                                </div>
                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold" style="font-size:12px;">CITY *</label>
                                    <input type="text" name="city" class="form-control checkout-input" required
                                        value="<?= htmlspecialchars($cust['city'] ?? '') ?>" placeholder="e.g. Lahore">
                                </div>
                            </div>
                            <div class="form-group mb-0">
                                <label class="font-weight-bold" style="font-size:12px;">ORDER NOTES (OPTIONAL)</label>
                                <textarea name="order_notes" rows="3" class="form-control checkout-input"
                                    placeholder="Any delivery instructions, landmark, or custom specs..."></textarea>
                            </div>
                        </div>

                        <!-- Payment Box -->
                        <div class="card-box">
                            <h3>Payment Method</h3>

                            <!-- COD -->
                            <div class="payment-option active" onclick="selectPaymentMethod('cod')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="payment_cod" name="payment" value="cod"
                                        class="custom-control-input" checked>
                                    <label class="custom-control-label font-weight-bold" for="payment_cod"
                                        style="cursor:pointer;">Cash on Delivery</label>
                                </div>
                                <div class="payment-details">
                                    <p class="mb-0 mt-2">Pay with cash upon arrival. No additional processing fees
                                        apply. Our support agent will call to verify the order before shipping.</p>
                                </div>
                            </div>

                            <!-- CARD -->
                            <div class="payment-option" onclick="selectPaymentMethod('card')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="payment_card" name="payment" value="card"
                                        class="custom-control-input">
                                    <label class="custom-control-label font-weight-bold" for="payment_card"
                                        style="cursor:pointer;">Credit / Debit Card</label>
                                </div>
                                <div class="payment-details">
                                    <div class="form-group mt-2">
                                        <input type="text" class="form-control checkout-input"
                                            placeholder="Card Number (4000 1234 5678 9010)">
                                    </div>
                                    <div class="form-row">
                                        <div class="col-6 form-group"><input type="text"
                                                class="form-control checkout-input" placeholder="MM/YY"></div>
                                        <div class="col-6 form-group"><input type="text"
                                                class="form-control checkout-input" placeholder="CVV"></div>
                                    </div>
                                </div>
                            </div>

                            <!-- JAZZCASH -->
                            <div class="payment-option" onclick="selectPaymentMethod('jazzcash')">
                                <div class="custom-control custom-radio">
                                    <input type="radio" id="payment_jazz" name="payment" value="jazzcash"
                                        class="custom-control-input">
                                    <label class="custom-control-label font-weight-bold" for="payment_jazz"
                                        style="cursor:pointer;">Mobile Wallet (JazzCash / EasyPaisa)</label>
                                </div>
                                <div class="payment-details">
                                    <div class="input-group mt-2">
                                        <input type="text" class="form-control checkout-input"
                                            placeholder="Mobile Account Number (e.g. 03001234567)">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-dark btn-sm font-weight-bold px-3"
                                                style="border-radius:0 8px 8px 0;">Send OTP</button>
                                        </div>
                                    </div>
                                    <div class="form-group mt-2">
                                        <input type="text" class="form-control checkout-input"
                                            placeholder="Verification Code (6-Digits)">
                                    </div>
                                </div>
                            </div>

                        </div>

                    </div>

                    <!-- Right Column: Order Summary -->
                    <div class="col-lg-5">
                        <div class="order-summary-box">
                            <h3>Order Summary</h3>

                            <?php foreach ($_SESSION['cart'] as $item): 
                      $itemPrice = $item['price'];
                      if ($item['wrap'] === 'Yes') {
                          $itemPrice += 500;
                      }
                  ?>
                            <div class="order-item-row">
                                <div>
                                    <strong><?= htmlspecialchars($item['name']) ?></strong> x <?= $item['qty'] ?>
                                    <div class="text-muted" style="font-size:11px;">Size:
                                        <?= htmlspecialchars($item['size']) ?>, Wrap:
                                        <?= htmlspecialchars($item['wrap']) ?></div>
                                </div>
                                <span class="font-weight-bold">Rs.
                                    <?= number_format($itemPrice * $item['qty']) ?></span>
                            </div>
                            <?php endforeach; ?>

                            <div class="summary-row-item mt-3">
                                <span>Cart Subtotal</span>
                                <span>Rs. <?= number_format($subtotal) ?></span>
                            </div>

                            <?php if ($discountAmount > 0): ?>
                            <div class="summary-row-item" style="color: #4cae4c; font-weight:600;">
                                <span>Discount (<?= $discountPct ?>%)</span>
                                <span>-Rs. <?= number_format($discountAmount) ?></span>
                            </div>
                            <?php endif; ?>

                            <div class="summary-row-item">
                                <span>Shipping Fee</span>
                                <span><?= $shipping > 0 ? 'Rs. ' . number_format($shipping) : 'FREE' ?></span>
                            </div>

                            <div class="summary-row-total">
                                <span>Total Amount</span>
                                <span style="color:#b08d57;">Rs. <?= number_format($grandTotal) ?></span>
                            </div>

                            <button type="submit" class="btn btn-dark btn-block mt-4 py-3"
                                style="border-radius:30px; font-weight:600; font-size:15px; text-transform:uppercase; background:#111;">
                                PLACE ORDER
                            </button>
                            <div class="text-center mt-3">
                                <a href="cart.php" style="font-size:12px; color:#555;"><i class="fas fa-edit"></i> Edit
                                    Shopping Cart</a>
                            </div>
                        </div>
                    </div>
                </div>

            </form>

            <?php endif; ?>

            <?php endif; ?>

        </div>
    </div>
</section>

<script>
function selectPaymentMethod(method) {
    document.querySelectorAll('.payment-option').forEach(opt => {
        opt.classList.remove('active');
    });

    // Check corresponding radio button
    const radio = document.getElementById('payment_' + (method === 'jazzcash' ? 'jazz' : method));
    if (radio) radio.checked = true;

    radio.closest('.payment-option').classList.add('active');
}
</script>

<?php include 'includes/footer.php'; ?>