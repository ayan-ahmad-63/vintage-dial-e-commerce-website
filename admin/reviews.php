<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$currentPage = 'reviews';
$pageTitle = 'Feedback & Reviews';

// Handle reply
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reply') {
        $reviewId = intval($_POST['review_id']);
        $reply = trim($_POST['reply']);
        if (!empty($reply)) {
            $stmt = $db->prepare("UPDATE reviews SET admin_reply = ?, status = 'replied' WHERE id = ?");
            $stmt->execute([$reply, $reviewId]);
        }
        header('Location: reviews.php?msg=replied');
        exit;
    }

    if ($_POST['action'] === 'delete') {
        $reviewId = intval($_POST['review_id']);
        $stmt = $db->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->execute([$reviewId]);
        header('Location: reviews.php?msg=deleted');
        exit;
    }
}

// Filters
$ratingFilter = $_GET['rating'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$productFilter = $_GET['product'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($ratingFilter) {
    $where[] = "r.rating = ?";
    $params[] = $ratingFilter;
}
if ($statusFilter) {
    $where[] = "r.status = ?";
    $params[] = $statusFilter;
}
if ($productFilter) {
    $where[] = "p.name = ?";
    $params[] = $productFilter;
}
if ($search) {
    $where[] = "(c.full_name LIKE ? OR r.review_text LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare("
    SELECT r.*, c.full_name as customer_name, p.name as product_name
    FROM reviews r
    LEFT JOIN customers c ON r.customer_id = c.id
    LEFT JOIN products p ON r.product_id = p.id
    $whereSQL
    ORDER BY r.review_date DESC
");
$stmt->execute($params);
$reviews = $stmt->fetchAll();

// Stats
$avgRating = $db->query("SELECT ROUND(AVG(rating), 1) as avg FROM reviews")->fetch()['avg'] ?? 0;
$totalReviews = $db->query("SELECT COUNT(*) as cnt FROM reviews")->fetch()['cnt'];
$approvedCount = $db->query("SELECT COUNT(*) as cnt FROM reviews WHERE status = 'replied'")->fetch()['cnt'];
$pendingCount = $db->query("SELECT COUNT(*) as cnt FROM reviews WHERE status = 'pending'")->fetch()['cnt'];

// Rating breakdown
$ratingBreakdown = [];
for ($i = 5; $i >= 1; $i--) {
    $cnt = $db->prepare("SELECT COUNT(*) as cnt FROM reviews WHERE rating = ?");
    $cnt->execute([$i]);
    $ratingBreakdown[$i] = $cnt->fetch()['cnt'];
}
$maxRating = max(array_values($ratingBreakdown)) ?: 1;

// Products for filter
$allProducts = $db->query("SELECT DISTINCT p.name FROM products p INNER JOIN reviews r ON r.product_id = p.id ORDER BY p.name")->fetchAll(DB_FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage feedback and reviews on Vintage Dial admin panel">
  <title>Reviews | Vintage Dial Admin</title>
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
        <?= $_GET['msg'] === 'replied' ? 'Reply submitted successfully!' : 'Review deleted successfully!' ?>
      </div>
      <?php endif; ?>

      <!-- REVIEW STATS -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-icon gold"><i class="fas fa-star"></i></div>
          <div class="stat-info"><h3><?= $avgRating ?></h3><p>Average Rating</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon blue"><i class="fas fa-comments"></i></div>
          <div class="stat-info"><h3><?= $totalReviews ?></h3><p>Total Reviews</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon green"><i class="fas fa-check-circle"></i></div>
          <div class="stat-info"><h3><?= $approvedCount ?></h3><p>Replied</p></div>
        </div>
        <div class="stat-card">
          <div class="stat-icon red"><i class="fas fa-clock"></i></div>
          <div class="stat-info"><h3><?= $pendingCount ?></h3><p>Pending Reply</p></div>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="card">
        <div class="card-body" style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
          <form method="GET" class="filters" style="display:flex; gap:12px; flex-wrap:wrap;">
            <input type="text" name="search" placeholder="Search reviews…" value="<?= htmlspecialchars($search) ?>">
            <select name="rating" onchange="this.form.submit()">
              <option value="">All Ratings</option>
              <?php for ($i = 5; $i >= 1; $i--): ?>
              <option value="<?= $i ?>" <?= $ratingFilter == $i ? 'selected' : '' ?>><?= $i ?> Star<?= $i > 1 ? 's' : '' ?></option>
              <?php endfor; ?>
            </select>
            <select name="status" onchange="this.form.submit()">
              <option value="">All Status</option>
              <option value="replied" <?= $statusFilter === 'replied' ? 'selected' : '' ?>>Replied</option>
              <option value="pending" <?= $statusFilter === 'pending' ? 'selected' : '' ?>>Pending Reply</option>
            </select>
            <select name="product" onchange="this.form.submit()">
              <option value="">All Products</option>
              <?php foreach ($allProducts as $pName): ?>
              <option value="<?= htmlspecialchars($pName) ?>" <?= $productFilter === $pName ? 'selected' : '' ?>><?= htmlspecialchars($pName) ?></option>
              <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-outline"><i class="fas fa-search"></i></button>
          </form>
          <span class="text-muted" style="font-size:13px;">Showing <?= count($reviews) ?> reviews</span>
        </div>
      </div>

      <!-- RATING BREAKDOWN -->
      <div class="grid-2" style="margin-bottom:24px;">
        <div class="card" style="margin-bottom:0;">
          <div class="card-header"><h3>Rating Breakdown</h3></div>
          <div class="card-body">
            <?php for ($i = 5; $i >= 1; $i--): ?>
            <?php $pct = $maxRating > 0 ? round(($ratingBreakdown[$i] / $maxRating) * 100) : 0; ?>
            <div style="display:flex; align-items:center; gap:12px; margin-bottom:12px;">
              <span style="font-size:13px; font-weight:600; width:50px;"><?= $i ?> <i class="fas fa-star" style="color:var(--clr-gold); font-size:11px;"></i></span>
              <div style="flex:1; height:10px; background:#f0f0f0; border-radius:5px; overflow:hidden;">
                <div style="height:100%; width:<?= $pct ?>%; background:<?= $i >= 4 ? 'var(--clr-gold)' : ($i === 3 ? 'var(--clr-warning)' : 'var(--clr-danger)') ?>; border-radius:5px;"></div>
              </div>
              <span class="text-muted" style="font-size:12px; width:30px;"><?= $ratingBreakdown[$i] ?></span>
            </div>
            <?php endfor; ?>
          </div>
        </div>

        <!-- Overall Sentiment -->
        <div class="chart-container" style="margin-bottom:0;">
          <h3 style="font-size:16px; font-weight:600; margin-bottom:20px;">Customer Sentiment</h3>
          <?php
          $positive = $totalReviews > 0 ? round((($ratingBreakdown[5] + $ratingBreakdown[4]) / $totalReviews) * 100) : 0;
          $neutral = $totalReviews > 0 ? round(($ratingBreakdown[3] / $totalReviews) * 100) : 0;
          $negative = $totalReviews > 0 ? 100 - $positive - $neutral : 0;
          $p1 = $positive;
          $p2 = $positive + $neutral;
          ?>
          <div class="donut-chart" style="background: conic-gradient(#10b981 0% <?=$p1?>%, #f59e0b <?=$p1?>% <?=$p2?>%, #ef4444 <?=$p2?>% 100%);">
            <div class="donut-center">
              <strong><?= $positive ?>%</strong>
              <span>Positive</span>
            </div>
          </div>
          <div class="chart-legend" style="margin-top:20px;">
            <div class="legend-item"><span class="legend-dot" style="background:#10b981;"></span> Positive (<?=$positive?>%)</div>
            <div class="legend-item"><span class="legend-dot" style="background:#f59e0b;"></span> Neutral (<?=$neutral?>%)</div>
            <div class="legend-item"><span class="legend-dot" style="background:#ef4444;"></span> Negative (<?=$negative?>%)</div>
          </div>
        </div>
      </div>

      <!-- REVIEWS LIST -->
      <div class="card">
        <div class="card-header"><h3>All Reviews</h3></div>
        <div class="card-body">
          <?php foreach ($reviews as $rev): ?>
          <?php
          $custInitials = '';
          $cParts = explode(' ', $rev['customer_name'] ?? 'NA');
          foreach ($cParts as $cp) $custInitials .= strtoupper(substr($cp, 0, 1));
          $custInitials = substr($custInitials, 0, 2);
          ?>
          <div class="review-card">
            <div class="review-meta">
              <div class="reviewer">
                <div class="reviewer-avatar"><?= $custInitials ?></div>
                <div>
                  <strong><?= htmlspecialchars($rev['customer_name'] ?? 'Anonymous') ?></strong>
                  <span class="text-muted" style="font-size:12px; margin-left:8px;"><?= htmlspecialchars($rev['product_name'] ?? 'N/A') ?></span>
                </div>
              </div>
              <div style="display:flex; align-items:center; gap:8px;">
                <div class="stars">
                  <?php for ($s = 1; $s <= 5; $s++): ?>
                  <i class="fas fa-star <?= $s > $rev['rating'] ? 'empty' : '' ?>"></i>
                  <?php endfor; ?>
                </div>
                <span class="text-muted" style="font-size:11px;"><?= date('d M Y', strtotime($rev['review_date'])) ?></span>
              </div>
            </div>
            <p><?= htmlspecialchars($rev['review_text']) ?></p>

            <?php if ($rev['admin_reply']): ?>
            <div style="background:#f0fdf4; border-left:3px solid var(--clr-success); padding:12px; border-radius:0 8px 8px 0; margin-top:10px;">
              <p style="font-size:12px; color:var(--clr-success); font-weight:600; margin-bottom:4px;"><i class="fas fa-reply"></i> Admin Reply</p>
              <p style="font-size:13px; color:var(--clr-text);"><?= htmlspecialchars($rev['admin_reply']) ?></p>
            </div>
            <?php endif; ?>

            <div class="review-actions">
              <?php if ($rev['status'] === 'pending'): ?>
              <button class="btn btn-sm btn-primary" onclick="openReplyModal(<?= $rev['id'] ?>, '<?= addslashes($rev['customer_name'] ?? 'Customer') ?>', '<?= addslashes($rev['product_name'] ?? 'N/A') ?>')"><i class="fas fa-reply"></i> Reply</button>
              <?php else: ?>
              <button class="btn btn-sm btn-outline" onclick="openReplyModal(<?= $rev['id'] ?>, '<?= addslashes($rev['customer_name'] ?? 'Customer') ?>', '<?= addslashes($rev['product_name'] ?? 'N/A') ?>')"><i class="fas fa-edit"></i> Edit Reply</button>
              <?php endif; ?>
              <form method="POST" style="display:inline;" onsubmit="return confirm('Delete this review?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="review_id" value="<?= $rev['id'] ?>">
                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i> Delete</button>
              </form>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

    </section>
  </div>

  <!-- REPLY MODAL -->
  <div class="modal-overlay" id="replyModal">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Reply to <span id="replyTo"></span></h3>
        <button class="modal-close" onclick="closeModal('replyModal')">&times;</button>
      </div>
      <form method="POST">
        <input type="hidden" name="action" value="reply">
        <input type="hidden" name="review_id" id="replyReviewId">
        <div class="modal-body">
          <p class="text-muted mb-16" style="font-size:13px;">Product: <strong id="replyProduct"></strong></p>
          <div class="form-group">
            <label>Your Reply</label>
            <textarea name="reply" id="replyText" placeholder="Write a thoughtful response…" rows="5" required></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline" onclick="closeModal('replyModal')">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="fas fa-reply"></i> Submit Reply</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function toggleSidebar() {
      document.getElementById('sidebar').classList.toggle('open');
      document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    function closeModal(id) { document.getElementById(id).classList.remove('show'); }

    function openReplyModal(reviewId, name, product) {
      document.getElementById('replyReviewId').value = reviewId;
      document.getElementById('replyTo').textContent = name;
      document.getElementById('replyProduct').textContent = product;
      document.getElementById('replyText').value = '';
      document.getElementById('replyModal').classList.add('show');
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
