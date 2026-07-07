<?php
require_once 'admin/config/db.php';
require_once 'includes/asset_helpers.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$productId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($productId <= 0) {
    header('Location: watches.php');
    exit;
}

// Fetch product details
try {
    $stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
    $stmt->execute([$productId]);
    $p = $stmt->fetch();
    if (!$p) {
        header('Location: watches.php');
        exit;
    }
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

$pageTitle = $p['name'] . " - " . $p['subtitle'];
include 'includes/header.php';

$reviewError = '';
$reviewSuccess = '';
$canLeaveReview = false;
$alreadyReviewed = false;
$reviewRequestStatus = '';

try {
    if (isset($_SESSION['customer_id'])) {
        $customerId = $_SESSION['customer_id'];

        $deliveredStmt = $db->prepare("SELECT COUNT(*) as cnt FROM orders WHERE customer_id = ? AND product_id = ? AND status = 'Delivered'");
        $deliveredStmt->execute([$customerId, $productId]);
        $hasDeliveredPurchase = $deliveredStmt->fetchColumn() > 0;

        $existingReviewStmt = $db->prepare("SELECT * FROM reviews WHERE customer_id = ? AND product_id = ? ORDER BY review_date DESC LIMIT 1");
        $existingReviewStmt->execute([$customerId, $productId]);
        $existingReview = $existingReviewStmt->fetch();

        if ($existingReview) {
            $alreadyReviewed = true;
            if ($existingReview['status'] === 'pending') {
                $reviewRequestStatus = 'Your review is awaiting approval.';
            } else {
                $reviewRequestStatus = 'You have already submitted a review for this product.';
            }
        }

        $canLeaveReview = $hasDeliveredPurchase && !$alreadyReviewed;
        if (!$hasDeliveredPurchase && isset($_SESSION['customer_id'])) {
            $reviewRequestStatus = 'You can submit a review only after your order has been delivered.';
        }
    }
} catch (Exception $e) {
    // Ignore eligibility errors and let form logic handle messages.
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    if (!isset($_SESSION['customer_id'])) {
        $reviewError = 'You must be logged in to submit a review.';
    } else {
        $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5;
        $reviewText = isset($_POST['review_text']) ? trim($_POST['review_text']) : '';
        $customerId = $_SESSION['customer_id'];

        if (!$canLeaveReview) {
            if ($alreadyReviewed) {
                $reviewError = 'You have already submitted a review for this product.';
            } else {
                $reviewError = 'You can submit a review only after your order is delivered.';
            }
        } elseif ($rating < 1 || $rating > 5) {
            $reviewError = 'Invalid rating value.';
        } elseif (empty($reviewText)) {
            $reviewError = 'Please write some feedback.';
        } else {
            try {
                $ins = $db->prepare("INSERT INTO reviews (customer_id, product_id, rating, review_text, status) VALUES (?, ?, ?, ?, 'pending')");
                $ins->execute([$customerId, $productId, $rating, $reviewText]);
                $reviewSuccess = 'Thank you! Your review has been submitted for approval.';
                $alreadyReviewed = true;
                $canLeaveReview = false;
                $reviewRequestStatus = 'Your review is awaiting approval.';
            } catch (Exception $e) {
                $reviewError = 'Error saving review: ' . $e->getMessage();
            }
        }
    }
}

// Fetch reviews for this product
try {
    $rStmt = $db->prepare("SELECT r.*, c.full_name as customer_name FROM reviews r JOIN customers c ON r.customer_id = c.id WHERE r.product_id = ? AND r.status = 'replied' ORDER BY r.review_date DESC");
    $rStmt->execute([$productId]);
    $reviews = $rStmt->fetchAll();

    // Calculate average rating
    $avgRating = 0;
    if (count($reviews) > 0) {
        $totalRating = 0;
        foreach ($reviews as $rev) {
            $totalRating += $rev['rating'];
        }
        $avgRating = round($totalRating / count($reviews), 1);
    }
} catch (Exception $e) {
    $reviews = [];
    $avgRating = 0;
}

// Fetch related products (same category, excluding current product)
try {
    $relStmt = $db->prepare("SELECT * FROM products WHERE category_id = ? AND id != ? LIMIT 3");
    $relStmt->execute([$p['category_id'], $p['id']]);
    $related = $relStmt->fetchAll();
} catch (Exception $e) {
    $related = [];
}

// Dynamic specs matching watch models
$specs = [];
if (strpos($p['name'], 'SNR055') !== false) {
    $specs = [
        'thickness' => '14.7mm',
        'diameter' => '44.8mm',
        'length' => '50.9mm',
        'case' => 'Titanium Case with Super Hard Coating',
        'crystal' => 'Sapphire Crystal with Anti-Reflective Coating',
        'lug_width' => '22mm',
        'water_resistance' => '100M / 330ft',
        'caliber' => 'Caliber 5R66',
        'movement_details' => 'Spring Drive GMT, Accuracy ±1 sec/day, 30 Jewels, 72 Hours Power Reserve'
    ];
} elseif (strpos($p['name'], 'SPB463') !== false) {
    $specs = [
        'thickness' => '13.0mm',
        'diameter' => '40.2mm',
        'length' => '46.0mm',
        'case' => 'Stainless Steel Case with Super Hard Coating',
        'crystal' => 'Dual Curved Sapphire Crystal with Inner AR Coating',
        'lug_width' => '20mm',
        'water_resistance' => '100M / 330ft',
        'caliber' => 'Caliber 6R55',
        'movement_details' => 'Automatic with Manual Winding, 21,600 vibrations/hour, 24 Jewels, 72 Hours Power Reserve, Date Function'
    ];
} else {
    $specs = [
        'thickness' => '12.5mm',
        'diameter' => '41.3mm',
        'length' => '48.2mm',
        'case' => 'Stainless Steel Case',
        'crystal' => 'Sapphire Crystal',
        'lug_width' => '20mm',
        'water_resistance' => '100M / 330ft',
        'caliber' => 'Caliber 6R35',
        'movement_details' => 'Automatic movement, 24 Jewels, 70 Hours Power Reserve'
    ];
}

// Find thumbnails dynamically
$thumbnails = normalize_image_paths($p['image'] ?? '');
if (empty($thumbnails)) {
    $pattern = __DIR__ . '/images/' . $p['name'] . '_*.{png,jpg,jpeg}';
    $files = glob($pattern, GLOB_BRACE);
    if (!empty($files)) {
        foreach ($files as $f) {
            $thumbnails[] = 'images/' . basename($f);
        }
    }
}

if (empty($thumbnails)) {
    $thumbnails[] = 'images/footer.jpeg';
}

$mainImage = first_image_path($thumbnails);
?>

<style>
.detail-wrapper {
    padding: 60px 0;
    background: #fcfbfa;
}

.detail-container {
    background: #ffffff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
}

.main-img-box {
    background: linear-gradient(145deg, #ffffff, #fbf9f6);
    border: 1px solid #eee;
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 480px;
}

.main-img-box img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
}

.thumbs-container {
    display: flex;
    gap: 10px;
    margin-top: 15px;
    justify-content: center;
}

.thumbs-container img {
    width: 70px;
    height: 70px;
    object-fit: contain;
    border: 1px solid #eae5dc;
    border-radius: 8px;
    padding: 5px;
    cursor: pointer;
    background: #fff;
    transition: 0.2s;
}

.thumbs-container img:hover,
.thumbs-container img.active {
    border-color: #b08d57;
    transform: scale(1.05);
}

.prod-name {
    font-size: 36px;
    font-weight: 700;
    color: #111;
}

.prod-subtitle-lg {
    font-size: 16px;
    color: #b08d57;
    letter-spacing: 1px;
    font-weight: 600;
    margin-bottom: 20px;
}

.price-tag {
    font-size: 30px;
    font-weight: 700;
    color: #b08d57;
    margin-bottom: 20px;
}

.desc-text {
    font-size: 15px;
    color: #555;
    line-height: 1.8;
    margin-bottom: 30px;
}

/* FEATURES & SPECS */
.spec-table {
    width: 100%;
    font-size: 14px;
    margin-bottom: 20px;
}

.spec-table tr {
    border-bottom: 1px solid #f1ece2;
}

.spec-table td {
    padding: 10px 5px;
}

.spec-label {
    font-weight: 600;
    color: #111;
    width: 150px;
}

.spec-value {
    color: #555;
}

.tabs-nav {
    display: flex;
    border-bottom: 2px solid #eae5dc;
    margin-bottom: 25px;
}

.tab-btn {
    background: none;
    border: none;
    padding: 10px 20px;
    font-weight: 600;
    color: #777;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: 0.3s;
    outline: none !important;
}

.tab-btn:hover {
    color: #b08d57;
}

.tab-btn.active {
    color: #111;
    border-bottom-color: #b08d57;
}

/* REVIEWS */
.review-card {
    border-bottom: 1px solid #f1ece2;
    padding: 20px 0;
}

.review-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.review-stars {
    color: #ffc107;
}

.review-stars .empty-star {
    color: #e4e5e9;
}

.admin-reply-box {
    background: #fdfaf2;
    border-left: 3px solid #b08d57;
    padding: 12px 16px;
    border-radius: 0 8px 8px 0;
    margin-top: 15px;
    font-size: 13px;
}

/* RELATED PRODUCTS */
.related-card {
    background: #fff;
    border: 1px solid #eae5dc;
    border-radius: 12px;
    padding: 15px;
    text-align: center;
    transition: 0.3s;
}

.related-card:hover {
    border-color: #b08d57;
    transform: translateY(-5px);
}

.related-card img {
    height: 140px;
    object-fit: contain;
    margin-bottom: 12px;
}

.related-card h5 {
    font-size: 15px;
    font-weight: 700;
    margin-bottom: 6px;
}

.related-card p {
    color: #b08d57;
    font-weight: bold;
    font-size: 14px;
    margin: 0;
}
</style>

<section class="detail-wrapper">
    <div class="container">
        <div class="detail-container">
            <div class="row">

                <!-- Left Side: Images -->
                <div class="col-lg-6 mb-5 mb-lg-0">
                    <div class="main-img-box">
                        <img id="mainWatchImage" src="<?= htmlspecialchars(asset_url($mainImage)) ?>"
                            alt="<?= htmlspecialchars($p['name']) ?>">
                    </div>
                    <?php if (count($thumbnails) > 1): ?>
                    <div class="thumbs-container">
                        <?php foreach ($thumbnails as $idx => $t): ?>
                        <img src="<?= htmlspecialchars($t) ?>" class="<?= $idx === 0 ? 'active' : '' ?>"
                            onclick="switchProductImage(this)">
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Right Side: Content -->
                <div class="col-lg-6">
                    <span class="text-uppercase"
                        style="color:#b08d57; font-size:12px; font-weight:600; letter-spacing:2px;"><?= htmlspecialchars($p['category_name']) ?></span>
                    <h1 class="prod-name"><?= htmlspecialchars($p['name']) ?></h1>
                    <p class="prod-subtitle-lg"><?= htmlspecialchars($p['subtitle']) ?></p>
                    <div class="price-tag">Rs. <?= number_format($p['price']) ?></div>

                    <p class="desc-text"><?= htmlspecialchars($p['description']) ?></p>

                    <!-- Variations & Add to Cart form -->
                    <form action="cart_action.php" method="POST" class="mt-4">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">

                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:13px; color:#111;">STRAP SIZE</label>
                                <select name="variation_size" class="form-control"
                                    style="border-radius:8px; border-color:#eae5dc;">
                                    <option value="Standard">Standard Size (20-22cm)</option>
                                    <option value="Large">Large Size (23-25cm)</option>
                                    <option value="Small">Small Size (17-19cm)</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:13px; color:#111;">GIFT
                                    WRAPPING</label>
                                <select name="variation_wrap" class="form-control"
                                    style="border-radius:8px; border-color:#eae5dc;">
                                    <option value="No">No Wrapping</option>
                                    <option value="Yes">Gift Wrapped (+Rs. 500)</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-flex align-items-center mt-3 form-group">
                            <label class="font-weight-bold mr-3 mb-0" style="font-size:13px; color:#111;">QTY:</label>
                            <input type="number" name="qty" value="1" min="1" max="<?= $p['stock'] ?>"
                                class="form-control text-center"
                                style="width: 70px; border-radius:8px; border-color:#eae5dc;">
                            <span class="text-muted ml-3" style="font-size: 12px;"><?= $p['stock'] ?> units in
                                stock</span>
                        </div>

                        <div class="d-flex gap-3 mt-4" style="gap:15px;">
                            <button type="submit" name="buy_now" value="0" class="btn btn-lg px-4 py-3"
                                style="background:#b08d57; color:#fff; border-radius:30px; border:none; font-weight:600; font-size:15px; flex:1; box-shadow:0 8px 20px rgba(176,141,87,0.3);">
                                ADD TO CART
                            </button>
                            <button type="submit" name="buy_now" value="1" class="btn btn-lg btn-dark px-4 py-3"
                                style="border-radius:30px; font-weight:600; font-size:15px; flex:1;">
                                BUY NOW
                            </button>
                        </div>
                    </form>

                </div>
            </div>

            <!-- Specifications & Reviews Tabs -->
            <div class="row mt-5">
                <div class="col-12">
                    <div class="tabs-nav">
                        <button id="tab-specs-btn" class="tab-btn active"
                            onclick="switchDetailTab('specs')">SPECIFICATIONS</button>
                        <button id="tab-reviews-btn" class="tab-btn" onclick="switchDetailTab('reviews')">CUSTOMER
                            REVIEWS (<?= count($reviews) ?>)</button>
                    </div>

                    <!-- Specs Tab Content -->
                    <div id="tab-specs" class="tab-content-area">
                        <div class="row">
                            <div class="col-md-6">
                                <table class="spec-table">
                                    <tr>
                                        <td class="spec-label">Thickness</td>
                                        <td class="spec-value"><?= $specs['thickness'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="spec-label">Diameter</td>
                                        <td class="spec-value"><?= $specs['diameter'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="spec-label">Length</td>
                                        <td class="spec-value"><?= $specs['length'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="spec-label">Case Material</td>
                                        <td class="spec-value"><?= $specs['case'] ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <table class="spec-table">
                                    <tr>
                                        <td class="spec-label">Crystal Glass</td>
                                        <td class="spec-value"><?= $specs['crystal'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="spec-label">Water Resistance</td>
                                        <td class="spec-value"><?= $specs['water_resistance'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="spec-label">Movement Caliber</td>
                                        <td class="spec-value"><?= $specs['caliber'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="spec-label">Movement Specs</td>
                                        <td class="spec-value"><?= $specs['movement_details'] ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Tab Content -->
                    <div id="tab-reviews" class="tab-content-area" style="display:none;">
                        <?php if ($reviewSuccess): ?>
                        <div class="alert alert-success"><?= htmlspecialchars($reviewSuccess) ?></div>
                        <?php endif; ?>
                        <?php if ($reviewError): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($reviewError) ?></div>
                        <?php endif; ?>

                        <div class="row">
                            <!-- Reviews List -->
                            <div class="col-lg-7">
                                <h4>Customer Feedback</h4>
                                <?php if (empty($reviews)): ?>
                                <p class="text-muted py-3">There are no reviews for this watch yet. Be the first to
                                    share your experience!</p>
                                <?php else: ?>
                                <div class="avg-rating-badge mb-3 d-flex align-items-center">
                                    <span class="mr-2 font-weight-bold" style="font-size:24px;"><?= $avgRating ?></span>
                                    <div class="review-stars">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fas fa-star <?= $i > $avgRating ? 'empty-star' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <span class="text-muted ml-3" style="font-size:12px;">Based on
                                        <?= count($reviews) ?> reviews</span>
                                </div>

                                <?php foreach ($reviews as $rev): ?>
                                <div class="review-card">
                                    <div class="review-header">
                                        <strong
                                            style="font-size:14px;"><?= htmlspecialchars($rev['customer_name']) ?></strong>
                                        <span class="text-muted"
                                            style="font-size:11px;"><?= date('d M Y', strtotime($rev['review_date'])) ?></span>
                                    </div>
                                    <div class="review-stars mb-2">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                        <i class="fas fa-star <?= $i > $rev['rating'] ? 'empty-star' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                    <p style="font-size:13px; color:#444; line-height:1.6;">
                                        <?= htmlspecialchars($rev['review_text']) ?></p>

                                    <?php if ($rev['admin_reply']): ?>
                                    <div class="admin-reply-box">
                                        <strong style="color:#b08d57;"><i class="fas fa-reply"></i> Admin
                                            Response:</strong>
                                        <p class="mb-0 mt-1" style="color:#222; font-style:italic;">
                                            <?= htmlspecialchars($rev['admin_reply']) ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Review Form -->
                            <div class="col-lg-5 mt-5 mt-lg-0">
                                <div class="p-4 border-radius-12"
                                    style="background:#fffdf9; border: 1px solid #eae5dc; border-radius:12px;">
                                    <h4>Write a Review</h4>
                                    <?php if (isset($_SESSION['customer_id'])): ?>
                                        <?php if ($canLeaveReview): ?>
                                        <form action="product-detail.php?id=<?= $productId ?>" method="POST" class="mt-3">
                                            <input type="hidden" name="action" value="submit_review">
                                            <div class="form-group">
                                                <label class="font-weight-bold" style="font-size:12px;">YOUR RATING</label>
                                                <select name="rating" class="form-control" style="border-radius:8px;">
                                                    <option value="5">★★★★★ (5 Stars)</option>
                                                    <option value="4">★★★★☆ (4 Stars)</option>
                                                    <option value="3">★★★☆☆ (3 Stars)</option>
                                                    <option value="2">★★☆☆☆ (2 Stars)</option>
                                                    <option value="1">★☆☆☆☆ (1 Star)</option>
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="font-weight-bold" style="font-size:12px;">FEEDBACK COMMENTS</label>
                                                <textarea name="review_text" rows="4" class="form-control"
                                                    placeholder="Share your experience wearing this timepiece..." required
                                                    style="border-radius:8px;"></textarea>
                                            </div>
                                            <button type="submit" class="btn btn-dark btn-block py-2"
                                                style="border-radius:20px; font-weight:600;">SUBMIT REVIEW</button>
                                        </form>
                                        <?php else: ?>
                                        <div class="alert alert-info" style="background:#eff6ff; border-color:#dbeafe; color:#1d4ed8; border-radius:12px; padding:16px; font-size:13px;">
                                            <?= htmlspecialchars($reviewRequestStatus ?: 'You are not eligible to submit a review yet.') ?>
                                        </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                    <p class="text-muted mt-3" style="font-size:13px;">Please <a href="login.php"
                                            style="color:#b08d57; font-weight:600;">log in</a> to write a review for
                                        this watch.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Related Products Section -->
            <?php if (!empty($related)): ?>
            <div class="mt-5 border-top pt-5">
                <h3 class="mb-4 text-center font-weight-bold" style="font-size:22px; letter-spacing:1px;">YOU MAY ALSO
                    LIKE</h3>
                <div class="row">
                    <?php foreach ($related as $rel): ?>
                    <div class="col-md-4 mb-4">
                        <a href="product-detail.php?id=<?= $rel['id'] ?>" style="text-decoration:none; color:inherit;">
                            <div class="related-card">
                                <img src="<?= htmlspecialchars(asset_url($rel['image'])) ?>"
                                    alt="<?= htmlspecialchars($rel['name']) ?>" class="img-fluid">
                                <h5><?= htmlspecialchars($rel['name']) ?></h5>
                                <p>Rs. <?= number_format($rel['price']) ?></p>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script>
function switchProductImage(thumb) {
    document.getElementById('mainWatchImage').src = thumb.src;
    document.querySelectorAll('.thumbs-container img').forEach(img => {
        img.classList.remove('active');
    });
    thumb.classList.add('active');
}

function switchDetailTab(tabId) {
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('tab-specs').style.display = 'none';
    document.getElementById('tab-reviews').style.display = 'none';

    document.getElementById('tab-' + tabId + '-btn').classList.add('active');
    document.getElementById('tab-' + tabId).style.display = 'block';
}
</script>

<?php include 'includes/footer.php'; ?>