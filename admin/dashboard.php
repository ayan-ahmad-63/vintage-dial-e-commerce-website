<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
require_once '../includes/asset_helpers.php';
$currentPage = 'dashboard';
$pageTitle = 'Dashboard';

// Fetch stats from database
$totalSales = $db->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status != 'Cancelled'")->fetch()['total'];
$totalOrders = $db->query("SELECT COUNT(*) as total FROM orders")->fetch()['total'];
$totalCategories = $db->query("SELECT COUNT(*) as total FROM categories")->fetch()['total'];
$totalProducts = $db->query("SELECT COUNT(*) as total FROM products")->fetch()['total'];
$totalCustomers = $db->query("SELECT COUNT(*) as total FROM customers")->fetch()['total'];

// Recent orders
$recentOrders = $db->query("
    SELECT o.*, c.full_name as customer_name, p.name as product_name 
    FROM orders o 
    LEFT JOIN customers c ON o.customer_id = c.id 
    LEFT JOIN products p ON o.product_id = p.id 
    ORDER BY o.order_date DESC LIMIT 5
")->fetchAll();

// Top selling products
$topProducts = $db->query("
    SELECT p.name, p.image, COUNT(o.id) as sold, SUM(o.total_amount) as revenue
    FROM orders o
    JOIN products p ON o.product_id = p.id
    WHERE o.status != 'Cancelled'
    GROUP BY p.id
    ORDER BY sold DESC
    LIMIT 4
")->fetchAll();

// Monthly revenue for the last 6 months
$monthlySales = $db->query("
    SELECT YEAR(order_date) as year_num,
           MONTH(order_date) as month_num,
           SUM(total_amount) as rev
    FROM orders
    WHERE status != 'Cancelled'
      AND order_date >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH)
    GROUP BY YEAR(order_date), MONTH(order_date)
    ORDER BY YEAR(order_date), MONTH(order_date)
")->fetchAll();

$monthlyRevenue = [];
$monthlyChartRows = [];
for ($offset = 5; $offset >= 0; $offset--) {
    $month = (new DateTimeImmutable('first day of this month'))->modify("-$offset month");
    $key = $month->format('Y-m');
    $label = $month->format('M');
    $monthlyRevenue[$key] = 0.0;
    $monthlyChartRows[] = ['label' => $label, 'key' => $key, 'value' => 0.0];
}

foreach ($monthlySales as $sale) {
    $key = sprintf('%04d-%02d', (int) $sale['year_num'], (int) $sale['month_num']);
    $monthlyRevenue[$key] = (float) $sale['rev'];
    foreach ($monthlyChartRows as &$row) {
        if ($row['key'] === $key) {
            $row['value'] = (float) $sale['rev'];
            break;
        }
    }
    unset($row);
}
$maxMonthlyRevenue = !empty($monthlyRevenue) ? max($monthlyRevenue) : 1;

// Current month status counts for the donut chart
$currentMonthStatus = $db->query("
    SELECT status, COUNT(*) as cnt
    FROM orders
    WHERE YEAR(order_date) = YEAR(CURDATE())
      AND MONTH(order_date) = MONTH(CURDATE())
    GROUP BY status
")->fetchAll();
$statusMap = [];
foreach ($currentMonthStatus as $sc) {
    $statusMap[$sc['status']] = (int) $sc['cnt'];
}
$delivered = $statusMap['Delivered'] ?? 0;
$shipped = $statusMap['Shipped'] ?? 0;
$pending = $statusMap['Pending'] ?? 0;
$cancelled = $statusMap['Cancelled'] ?? 0;
$totalOrd = max($delivered + $shipped + $pending + $cancelled, 1);
$pctDelivered = round(($delivered / $totalOrd) * 100);
$pctShipped = round(($shipped / $totalOrd) * 100);
$pctPending = round(($pending / $totalOrd) * 100);
$pctCancelled = 100 - $pctDelivered - $pctShipped - $pctPending;

// New customers
$newCustomers = $db->query("SELECT full_name, city, created_at FROM customers ORDER BY created_at DESC LIMIT 5")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Vintage Dial Admin Dashboard – Overview of store performance">
    <title>Dashboard | Vintage Dial Admin</title>
    <link rel="icon" type="image/png" href="../images/footer.jpeg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>

<body>

    <?php include 'includes/sidebar.php'; ?>

    <!-- MAIN -->
    <div class="main">
        <?php include 'includes/topbar.php'; ?>

        <!-- CONTENT -->
        <section class="content">

            <!-- Welcome Banner -->
            <div class="card"
                style="background:linear-gradient(135deg,#0d0d0d 0%,#1a1a1a 100%); color:#fff; margin-bottom:28px;">
                <div class="card-body"
                    style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:16px;">
                    <div>
                        <h2 style="font-size:24px; margin-bottom:6px;">Welcome,
                            <?= htmlspecialchars($_SESSION['admin_name']) ?>!</h2>
                        <p style="color:rgba(255,255,255,.6); font-size:14px;">Today’s updates for your Vintage Dial.
                        </p>
                    </div>
                    <a href="analytics.php" class="btn btn-primary"><i class="fas fa-chart-bar"></i> View Analytics</a>
                </div>
            </div>

            <!-- STAT CARDS -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon gold"><i class="fas fa-coins"></i></div>
                    <div class="stat-info">
                        <h3>Rs. <?= number_format($totalSales) ?></h3>
                        <p>Total Sales</p>
                        <div class="trend up"><i class="fas fa-arrow-up"></i> 12.5% from last month</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div>
                    <div class="stat-info">
                        <h3><?= $totalOrders ?></h3>
                        <p>Total Orders</p>
                        <div class="trend up"><i class="fas fa-arrow-up"></i> 8.3% from last month</div>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon purple"><i class="fas fa-tags"></i></div>
                    <div class="stat-info">
                        <h3><?= $totalCategories ?></h3>
                        <p>Categories</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon green"><i class="fas fa-box-open"></i></div>
                    <div class="stat-info">
                        <h3><?= $totalProducts ?></h3>
                        <p>Total Products</p>
                    </div>
                </div>

                <div class="stat-card">
                    <div class="stat-icon red"><i class="fas fa-users"></i></div>
                    <div class="stat-info">
                        <h3><?= number_format($totalCustomers) ?></h3>
                        <p>Total Customers</p>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="grid-2">

                <!-- Sales Chart -->
                <div class="chart-container">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                        <h3 style="font-size:16px; font-weight:600;">Monthly Sales</h3>
                        <span class="text-muted" style="font-size:12px;">Last 6 months</span>
                    </div>
                    <div class="bar-chart">
                        <?php foreach ($monthlyChartRows as $row):
                            $barHeight = $maxMonthlyRevenue > 0 ? round(($row['value'] / $maxMonthlyRevenue) * 100) : 0;
                        ?>
                        <div class="bar" style="height:<?= $barHeight ?>%;" data-value="Rs. <?= number_format($row['value']) ?>"><span><?= $row['label'] ?></span></div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Orders by Status -->
                <div class="chart-container">
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                        <h3 style="font-size:16px; font-weight:600;">Orders by Status</h3>
                        <span class="text-muted" style="font-size:12px;">Current month</span>
                    </div>
                    <?php
          $d1 = $pctDelivered;
          $d2 = $d1 + $pctShipped;
          $d3 = $d2 + $pctPending;
          ?>
                    <div class="donut-chart"
                        style="background: conic-gradient(#10b981 0% <?=$d1?>%, #3b82f6 <?=$d1?>% <?=$d2?>%, #f59e0b <?=$d2?>% <?=$d3?>%, #ef4444 <?=$d3?>% 100%);">
                        <div class="donut-center">
                            <strong><?= $totalOrders ?></strong>
                            <span>Orders</span>
                        </div>
                    </div>
                    <div class="chart-legend">
                        <div class="legend-item"><span class="legend-dot" style="background:#10b981;"></span> Delivered
                            (<?=$pctDelivered?>%)</div>
                        <div class="legend-item"><span class="legend-dot" style="background:#3b82f6;"></span> Shipped
                            (<?=$pctShipped?>%)</div>
                        <div class="legend-item"><span class="legend-dot" style="background:#f59e0b;"></span> Pending
                            (<?=$pctPending?>%)</div>
                        <div class="legend-item"><span class="legend-dot" style="background:#ef4444;"></span> Cancelled
                            (<?=$pctCancelled?>%)</div>
                    </div>
                </div>

            </div>

            <!-- RECENT ORDERS TABLE -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent Orders</h3>
                    <a href="orders.php" class="btn btn-sm btn-outline">View All <i class="fas fa-arrow-right"></i></a>
                </div>
                <div class="card-body">
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Customer</th>
                                    <th>Product</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentOrders as $order): ?>
                                <tr>
                                    <td>#<?= htmlspecialchars($order['order_code']) ?></td>
                                    <td><?= htmlspecialchars($order['customer_name'] ?? 'N/A') ?></td>
                                    <td><?= htmlspecialchars($order['product_name'] ?? 'N/A') ?></td>
                                    <td>Rs. <?= number_format($order['total_amount']) ?></td>
                                    <td><span
                                            class="badge badge-<?= strtolower($order['status']) ?>"><?= $order['status'] ?></span>
                                    </td>
                                    <td><?= date('d M Y', strtotime($order['order_date'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- POPULAR PRODUCTS + RECENT CUSTOMERS -->
            <div class="grid-2">

                <!-- Popular Products -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top Selling Products</h3>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($topProducts as $tp): ?>
                                <tr>
                                    <td style="display:flex; align-items:center; gap:10px;">
                                        <img src="<?= htmlspecialchars(asset_url($tp['image'] ?? 'images/w1.png')) ?>"
                                            alt="<?= htmlspecialchars($tp['name']) ?>">
                                        <?= htmlspecialchars($tp['name']) ?>
                                    </td>
                                    <td><?= $tp['sold'] ?></td>
                                    <td>Rs. <?= number_format($tp['revenue']) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Recent Customers -->
                <div class="card">
                    <div class="card-header">
                        <h3>New Customers</h3>
                        <a href="customers.php" class="btn btn-sm btn-outline">View All</a>
                    </div>
                    <div class="card-body">
                        <table>
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>City</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($newCustomers as $nc): ?>
                                <tr>
                                    <td><?= htmlspecialchars($nc['full_name']) ?></td>
                                    <td><?= htmlspecialchars($nc['city']) ?></td>
                                    <td><?= date('d M', strtotime($nc['created_at'])) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </section>
    </div>

    <script>
    function toggleSidebar() {
        document.getElementById('sidebar').classList.toggle('open');
        document.getElementById('sidebarOverlay').classList.toggle('show');
    }

    window.onclick = function(event) {
        if (!event.target.closest('.notif-wrapper')) {
            document.getElementById('notifDropdown').classList.remove('show');
        }
    }
    </script>
</body>

</html>