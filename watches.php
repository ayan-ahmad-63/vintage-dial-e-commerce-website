<?php
$pageTitle = "Watches Catalog";
include 'includes/header.php';

// Pagination setup
$limit = 4; // Products per page
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($page - 1) * $limit;

// Filters
$categoryFilter = isset($_GET['category']) ? trim($_GET['category']) : '';
$searchFilter = isset($_GET['search']) ? trim($_GET['search']) : '';

$where = [];
$params = [];

if ($categoryFilter) {
    $where[] = "c.name = ?";
    $params[] = $categoryFilter;
}

if ($searchFilter) {
    $where[] = "(p.name LIKE ? OR p.subtitle LIKE ? OR p.description LIKE ?)";
    $params[] = "%$searchFilter%";
    $params[] = "%$searchFilter%";
    $params[] = "%$searchFilter%";
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total products for pagination
try {
    $countSql = "SELECT COUNT(*) as total FROM products p LEFT JOIN categories c ON p.category_id = c.id $whereSQL";
    $countStmt = $db->prepare($countSql);
    $countStmt->execute($params);
    $totalProducts = $countStmt->fetch()['total'];
    $totalPages = ceil($totalProducts / $limit);
} catch (Exception $e) {
    $totalProducts = 0;
    $totalPages = 1;
    $dbError = $e->getMessage();
}

// Fetch products for current page
$products = [];
if ($totalProducts > 0) {
    try {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                $whereSQL 
                ORDER BY p.created_at DESC 
                LIMIT $limit OFFSET $offset";
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
    } catch (Exception $e) {
        $dbError = $e->getMessage();
    }
}

// Fetch all categories for filter dropdown/sidebar
try {
    $categories = $db->query("SELECT DISTINCT name FROM categories WHERE status='Active' ORDER BY name")->fetchAll(DB_FETCH_COLUMN);
} catch (Exception $e) {
    $categories = [];
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
  .sidebar-card {
    background: #fff;
    border: 1px solid #eae5dc;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.02);
  }
  .sidebar-card h4 {
    font-size: 16px;
    font-weight: 700;
    border-bottom: 2px solid #b08d57;
    padding-bottom: 10px;
    margin-bottom: 15px;
    letter-spacing: 1px;
    color: #111;
  }
  .category-list {
    list-style: none;
    padding: 0;
    margin: 0;
  }
  .category-list li {
    margin-bottom: 10px;
  }
  .category-list a {
    color: #555;
    font-size: 14px;
    text-decoration: none;
    transition: 0.2s;
    display: block;
    padding: 6px 10px;
    border-radius: 6px;
  }
  .category-list a:hover, .category-list a.active {
    background: #fffdf5;
    color: #b08d57;
    font-weight: 600;
    padding-left: 15px;
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
    position: relative;
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
    text-overflow: ellipsis;
    white-space: nowrap;
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
  
  /* PAGINATION */
  .pagination .page-item .page-link {
    color: #111;
    border-color: #eae5dc;
    margin: 0 3px;
    border-radius: 8px;
    transition: 0.3s;
  }
  .pagination .page-item.active .page-link {
    background: #b08d57;
    border-color: #b08d57;
    color: #fff;
  }
  .pagination .page-item .page-link:hover {
    background: #fffdf5;
    color: #b08d57;
    border-color: #b08d57;
  }
</style>

<section class="catalog-section">
  <div class="container-fluid px-5">
    
    <!-- Title Area -->
    <div class="row mb-5 align-items-center">
      <div class="col-md-6">
        <h2 class="catalog-title">
          <?php
          if ($categoryFilter) {
              echo htmlspecialchars($categoryFilter);
          } elseif ($searchFilter) {
              echo "Search Results for \"" . htmlspecialchars($searchFilter) . "\"";
          } else {
              echo "The Complete Collection";
          }
          ?>
        </h2>
        <p class="text-muted mb-0">Showing <?= count($products) ?> of <?= $totalProducts ?> exceptional watches</p>
      </div>
      <div class="col-md-6 text-md-right mt-3 mt-md-0">
        <!-- Breadcrumb / Back button -->
        <a href="watches.php" class="btn btn-sm btn-outline-secondary px-3 py-2" style="border-radius:8px;">Clear Filters</a>
      </div>
    </div>

    <div class="row">
      <!-- Sidebar Filters -->
      <div class="col-lg-3 mb-4">
        <div class="sidebar-card">
          <!-- Categories Filter -->
          <h4>COLLECTIONS</h4>
          <ul class="category-list">
            <li>
              <a href="watches.php" class="<?= !$categoryFilter ? 'active' : '' ?>">All Watches</a>
            </li>
            <?php foreach ($categories as $cat): ?>
              <li>
                <a href="watches.php?category=<?= urlencode($cat) ?>" class="<?= $categoryFilter === $cat ? 'active' : '' ?>">
                  <?= htmlspecialchars($cat) ?>
                </a>
              </li>
            <?php endforeach; ?>
          </ul>
          
          <!-- Search Box -->
          <h4 class="mt-4">SEARCH</h4>
          <form action="watches.php" method="GET">
            <?php if ($categoryFilter): ?>
              <input type="hidden" name="category" value="<?= htmlspecialchars($categoryFilter) ?>">
            <?php endif; ?>
            <div class="input-group">
              <input type="text" name="search" class="form-control" placeholder="Search catalog..." value="<?= htmlspecialchars($searchFilter) ?>" style="border-radius:8px 0 0 8px; border-color:#eae5dc;">
              <div class="input-group-append">
                <button class="btn btn-outline-secondary" type="submit" style="border-radius:0 8px 8px 0; border-color:#eae5dc; background:#f7f3ee;">
                  <i class="fas fa-search"></i>
                </button>
              </div>
            </div>
          </form>
        </div>
      </div>

      <!-- Products Grid -->
      <div class="col-lg-9">
        <?php if (isset($dbError)): ?>
          <div class="alert alert-danger">Error retrieving products: <?= htmlspecialchars($dbError) ?></div>
        <?php elseif (empty($products)): ?>
          <div class="text-center py-5 sidebar-card">
            <i class="fas fa-search-minus mb-3" style="font-size: 48px; color: #b08d57;"></i>
            <h3>No Products Found</h3>
            <p class="text-muted">We couldn't find any watches matching your filter criteria. Try clearing filters or searching for another term.</p>
            <a href="watches.php" class="btn btn-primary px-4 py-2 mt-2" style="background:#b08d57; border:none; border-radius:30px;">Show All Watches</a>
          </div>
        <?php else: ?>
          <div class="row">
            <?php foreach ($products as $p): ?>
              <div class="col-md-6 col-xl-4 mb-4">
                <div class="prod-card">
                  <div class="prod-img-wrap">
                    <img src="<?= htmlspecialchars(asset_url($p['image'])) ?>" alt="<?= htmlspecialchars($p['name']) ?>">
                  </div>
                  <div class="prod-body">
                    <div class="prod-category"><?= htmlspecialchars($p['category_name']) ?></div>
                    <h3 class="prod-title"><?= htmlspecialchars($p['name']) ?></h3>
                    <div class="prod-subtitle"><?= htmlspecialchars($p['subtitle']) ?></div>
                    <div class="prod-price">Rs. <?= number_format($p['price']) ?></div>
                    <p class="prod-desc"><?= htmlspecialchars($p['description']) ?></p>
                    
                    <div class="prod-footer">
                      <a href="product-detail.php?id=<?= $p['id'] ?>" class="btn-catalog-view">VIEW DETAILS</a>
                      <!-- Quick Add to Cart Form -->
                      <form action="cart_action.php" method="POST" style="margin:0;">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                        <input type="hidden" name="qty" value="1">
                        <input type="hidden" name="return_url" value="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
                        <button type="submit" class="btn-catalog-cart" title="Quick Add to Cart"><i class="fas fa-cart-plus"></i></button>
                      </form>
                    </div>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>

          <!-- Pagination Navigation -->
          <?php if ($totalPages > 1): ?>
            <nav class="mt-5">
              <ul class="pagination justify-content-center">
                <!-- Previous Button -->
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link" href="watches.php?page=<?= $page - 1 ?><?= $categoryFilter ? '&category='.urlencode($categoryFilter) : '' ?><?= $searchFilter ? '&search='.urlencode($searchFilter) : '' ?>" aria-label="Previous">
                    <span aria-hidden="true">&laquo;</span>
                  </a>
                </li>
                
                <!-- Page Numbers -->
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $page === $i ? 'active' : '' ?>">
                    <a class="page-link" href="watches.php?page=<?= $i ?><?= $categoryFilter ? '&category='.urlencode($categoryFilter) : '' ?><?= $searchFilter ? '&search='.urlencode($searchFilter) : '' ?>">
                      <?= $i ?>
                    </a>
                  </li>
                <?php endfor; ?>

                <!-- Next Button -->
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link" href="watches.php?page=<?= $page + 1 ?><?= $categoryFilter ? '&category='.urlencode($categoryFilter) : '' ?><?= $searchFilter ? '&search='.urlencode($searchFilter) : '' ?>" aria-label="Next">
                    <span aria-hidden="true">&raquo;</span>
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>
