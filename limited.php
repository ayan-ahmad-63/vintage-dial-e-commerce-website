<?php
$pageTitle = "Limited Edition Timepieces";
include 'includes/header.php';

// Pagination setup
$limit = 4;
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Fetch Limited Edition products
try {
    // Limited Edition has category_id = 2
    $countStmt = $db->prepare("SELECT COUNT(*) as total FROM products WHERE category_id = 2");
    $countStmt->execute();
    $totalProducts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $limit);

    $sql = "SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.category_id = 2
            ORDER BY p.created_at DESC 
            LIMIT $limit OFFSET $offset";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll();
} catch (Exception $e) {
    $totalProducts = 0;
    $totalPages = 1;
    $dbError = $e->getMessage();
}
?>

<style>
  .catalog-section {
    padding: 60px 0;
    background: #fdfcf9;
    min-height: 80vh;
  }
  .catalog-title {
    font-size: 32px;
    font-weight: 700;
    letter-spacing: 1px;
    color: #111;
  }
  .divider-gold {
    width: 60px;
    height: 3px;
    background: #b08d57;
    margin: 15px 0 30px 0;
  }
  
  /* PRODUCT CARD */
  .prod-card {
    background: #fff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    overflow: hidden;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
    box-shadow: 0 5px 20px rgba(0,0,0,0.02);
  }
  .prod-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.08);
    border-color: #b08d57;
  }
  .prod-img-wrap {
    height: 250px;
    background: linear-gradient(185deg, #ffffff, #faf7f2);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
  }
  .prod-img-wrap img {
    max-height: 100%;
    max-width: 100%;
    object-fit: contain;
    transition: 0.5s;
  }
  .prod-card:hover .prod-img-wrap img {
    transform: scale(1.08);
  }
  .prod-body {
    padding: 20px;
    flex: 1;
    display: flex;
    flex-direction: column;
  }
  .prod-category {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 2px;
    color: #b08d57;
    font-weight: 600;
    margin-bottom: 6px;
  }
  .prod-title {
    font-size: 18px;
    font-weight: 700;
    color: #111;
    margin-bottom: 4px;
    line-height: 1.3;
  }
  .prod-subtitle {
    font-size: 12px;
    color: #777;
    margin-bottom: 12px;
    height: 18px;
    overflow: hidden;
  }
  .prod-price {
    font-size: 18px;
    font-weight: 700;
    color: #b08d57;
    margin-bottom: 12px;
  }
  .prod-desc {
    font-size: 13px;
    color: #555;
    line-height: 1.6;
    margin-bottom: 20px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    height: 42px;
  }
  .prod-footer {
    margin-top: auto;
    display: flex;
    gap: 10px;
  }
  .btn-catalog-view {
    flex: 1;
    background: #111;
    color: #fff;
    font-weight: 600;
    font-size: 13px;
    padding: 10px;
    border-radius: 8px;
    border: none;
    text-align: center;
    transition: 0.3s;
  }
  .btn-catalog-view:hover {
    background: #b08d57;
    color: #fff;
    text-decoration: none;
  }
  .btn-catalog-cart {
    background: #f7f3ee;
    color: #111;
    border: 1px solid #eae5dc;
    border-radius: 8px;
    width: 42px;
    height: 42px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.3s;
  }
  .btn-catalog-cart:hover {
    background: #b08d57;
    color: #fff;
    border-color: #b08d57;
  }
</style>

<section class="catalog-section">
  <div class="container">
    
    <div class="text-center mb-5">
      <h2 class="catalog-title">LIMITED EDITION</h2>
      <div class="divider-gold mx-auto"></div>
      <p class="text-muted" style="max-width: 600px; margin: 0 auto;">Extremely rare and masterfully crafted timepieces. Designed for individuals who demand exclusivity and legacy.</p>
    </div>

    <?php if (isset($dbError)): ?>
      <div class="alert alert-danger">Error: <?= htmlspecialchars($dbError) ?></div>
    <?php elseif (empty($products)): ?>
      <div class="text-center py-5">
        <p class="text-muted">No limited edition watches found.</p>
      </div>
    <?php else: ?>
      <div class="row">
        <?php foreach ($products as $p): ?>
          <div class="col-md-6 col-lg-4 mb-4">
            <div class="prod-card">
              <div class="prod-img-wrap">
                <img src="<?= htmlspecialchars(asset_url(first_image_path($p['image']))) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
              </div>
              <div class="prod-body">
                <div class="prod-category"><?= htmlspecialchars($p['category_name']) ?></div>
                <h3 class="prod-title"><?= htmlspecialchars($p['name']) ?></h3>
                <div class="prod-subtitle"><?= htmlspecialchars($p['subtitle']) ?></div>
                <div class="prod-price">Rs. <?= number_format($p['price']) ?></div>
                <p class="prod-desc"><?= htmlspecialchars($p['description']) ?></p>
                
                <div class="prod-footer">
                  <a href="product-detail.php?id=<?= $p['id'] ?>" class="btn-catalog-view">VIEW DETAILS</a>
                  <form action="cart_action.php" method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                    <input type="hidden" name="qty" value="1">
                    <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                    <button type="submit" class="btn-catalog-cart"><i class="fas fa-cart-plus"></i></button>
                  </form>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Pagination -->
      <?php if ($totalPages > 1): ?>
        <nav class="mt-5">
          <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
              <a class="page-link" href="limited.php?page=<?= $page - 1 ?>">&laquo;</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
              <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                <a class="page-link" href="limited.php?page=<?= $i ?>"><?= $i ?></a>
              </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
              <a class="page-link" href="limited.php?page=<?= $page + 1 ?>">&raquo;</a>
            </li>
          </ul>
        </nav>
      <?php endif; ?>
    <?php endif; ?>

  </div>
</section>

<?php include 'includes/footer.php'; ?>
