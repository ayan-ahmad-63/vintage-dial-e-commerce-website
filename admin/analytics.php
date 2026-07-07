<?php
require_once 'includes/auth_check.php';
require_once 'config/db.php';
$currentPage = 'analytics';
$pageTitle = 'Analytics & Reporting';

$totalRevenue = $db->query("SELECT COALESCE(SUM(total_amount),0) as t FROM orders WHERE status!='Cancelled'")->fetch()['t'];
$totalOrders = $db->query("SELECT COUNT(*) as c FROM orders")->fetch()['c'];
$paidOrders = $db->query("SELECT COUNT(*) as c FROM orders WHERE status!='Cancelled'")->fetch()['c'];
$totalCustomers = $db->query("SELECT COUNT(*) as c FROM customers")->fetch()['c'];
$avgOrderValue = $paidOrders > 0 ? round($totalRevenue / $paidOrders) : 0;

$statusCounts = $db->query("SELECT status, COUNT(*) as cnt FROM orders GROUP BY status")->fetchAll();
$statusMap = [];
foreach ($statusCounts as $sc) $statusMap[$sc['status']] = $sc['cnt'];

$topProducts = $db->query("SELECT p.name, SUM(o.total_amount) as revenue, COUNT(o.id) as orders FROM orders o JOIN products p ON o.product_id=p.id WHERE o.status!='Cancelled' GROUP BY p.id ORDER BY revenue DESC LIMIT 5")->fetchAll();
$maxRev = !empty($topProducts) ? $topProducts[0]['revenue'] : 1;

$topCities = $db->query("SELECT c.city, COUNT(o.id) as orders, SUM(o.total_amount) as revenue FROM orders o JOIN customers c ON o.customer_id=c.id WHERE o.status!='Cancelled' AND c.city IS NOT NULL GROUP BY c.city ORDER BY revenue DESC LIMIT 5")->fetchAll();

$monthlySales = $db->query("SELECT DATE_FORMAT(order_date,'%b') as mn, SUM(total_amount) as rev FROM orders WHERE status!='Cancelled' AND order_date>=DATE_SUB(CURDATE(),INTERVAL 6 MONTH) GROUP BY YEAR(order_date),MONTH(order_date) ORDER BY YEAR(order_date),MONTH(order_date)")->fetchAll();
$maxM = 1;
foreach ($monthlySales as $ms) if ($ms['rev'] > $maxM) $maxM = $ms['rev'];

$recentOrders = $db->query("SELECT o.*,c.full_name as cname,p.name as pname FROM orders o LEFT JOIN customers c ON o.customer_id=c.id LEFT JOIN products p ON o.product_id=p.id ORDER BY o.order_date DESC LIMIT 10")->fetchAll();

$totalOrd = max(array_sum(array_values($statusMap)), 1);
$del = round(($statusMap['Delivered'] ?? 0) / $totalOrd * 100);
$shp = round(($statusMap['Shipped'] ?? 0) / $totalOrd * 100);
$pen = round(($statusMap['Pending'] ?? 0) / $totalOrd * 100);
$can = 100 - $del - $shp - $pen;
$s1=$del; $s2=$s1+$shp; $s3=$s2+$pen;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Analytics | Vintage Dial Admin</title>
  <link rel="icon" type="image/png" href="../images/footer.jpeg">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="admin.css">
</head>
<body>
  <?php include 'includes/sidebar.php'; ?>
  <div class="main">
    <?php include 'includes/topbar.php'; ?>
    <section class="content">
      <div class="stats-grid">
        <div class="stat-card"><div class="stat-icon gold"><i class="fas fa-coins"></i></div><div class="stat-info"><h3>Rs. <?=number_format($totalRevenue)?></h3><p>Total Revenue</p></div></div>
        <div class="stat-card"><div class="stat-icon blue"><i class="fas fa-shopping-bag"></i></div><div class="stat-info"><h3><?=$totalOrders?></h3><p>Total Orders</p></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="fas fa-users"></i></div><div class="stat-info"><h3><?=$totalCustomers?></h3><p>Total Customers</p></div></div>
        <div class="stat-card"><div class="stat-icon purple"><i class="fas fa-receipt"></i></div><div class="stat-info"><h3>Rs. <?=number_format($avgOrderValue)?></h3><p>Avg Order Value</p></div></div>
      </div>
      <div class="grid-2">
        <div class="chart-container">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;"><h3 style="font-size:16px;font-weight:600;">Monthly Revenue</h3><span class="text-muted" style="font-size:12px;">Last 6 months</span></div>
          <div class="bar-chart">
            <?php foreach($monthlySales as $ms): $pct=round(($ms['rev']/$maxM)*100); ?>
            <div class="bar" style="height:<?=$pct?>%;" data-value="<?=number_format($ms['rev']/1000)?>K"><span><?=$ms['mn']?></span></div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="chart-container">
          <h3 style="font-size:16px;font-weight:600;margin-bottom:20px;">Orders by Status</h3>
          <div class="donut-chart" style="background:conic-gradient(#10b981 0% <?=$s1?>%,#3b82f6 <?=$s1?>% <?=$s2?>%,#f59e0b <?=$s2?>% <?=$s3?>%,#ef4444 <?=$s3?>% 100%);">
            <div class="donut-center"><strong><?=$totalOrders?></strong><span>Total</span></div>
          </div>
          <div class="chart-legend" style="margin-top:16px;">
            <div class="legend-item"><span class="legend-dot" style="background:#10b981;"></span> Delivered (<?=$statusMap['Delivered']??0?>)</div>
            <div class="legend-item"><span class="legend-dot" style="background:#3b82f6;"></span> Shipped (<?=$statusMap['Shipped']??0?>)</div>
            <div class="legend-item"><span class="legend-dot" style="background:#f59e0b;"></span> Pending (<?=$statusMap['Pending']??0?>)</div>
            <div class="legend-item"><span class="legend-dot" style="background:#ef4444;"></span> Cancelled (<?=$statusMap['Cancelled']??0?>)</div>
          </div>
        </div>
      </div>
      <div class="grid-2">
        <div class="card"><div class="card-header"><h3>Top Products by Revenue</h3></div><div class="card-body">
          <?php foreach($topProducts as $tp): $pct=round(($tp['revenue']/$maxRev)*100); ?>
          <div style="margin-bottom:16px;"><div style="display:flex;justify-content:space-between;margin-bottom:6px;"><strong style="font-size:13px;"><?=htmlspecialchars($tp['name'])?></strong><span class="text-muted" style="font-size:12px;">Rs. <?=number_format($tp['revenue'])?></span></div><div style="height:8px;background:#f0f0f0;border-radius:4px;overflow:hidden;"><div style="height:100%;width:<?=$pct?>%;background:linear-gradient(90deg,var(--clr-gold),#d4af37);border-radius:4px;"></div></div></div>
          <?php endforeach; ?>
        </div></div>
        <div class="card"><div class="card-header"><h3>Sales by City</h3></div><div class="card-body"><table><thead><tr><th>City</th><th>Orders</th><th>Revenue</th></tr></thead><tbody>
          <?php foreach($topCities as $tc): ?>
          <tr><td><i class="fas fa-map-marker-alt" style="color:var(--clr-gold);margin-right:6px;"></i><?=htmlspecialchars($tc['city'])?></td><td><?=$tc['orders']?></td><td>Rs. <?=number_format($tc['revenue'])?></td></tr>
          <?php endforeach; ?>
        </tbody></table></div></div>
      </div>
      <div class="card"><div class="card-header"><h3>Sales Report — Recent Orders</h3></div><div class="card-body"><div class="table-wrap"><table><thead><tr><th>Order ID</th><th>Customer</th><th>Product</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead><tbody>
        <?php foreach($recentOrders as $o): ?>
        <tr><td>#<?=htmlspecialchars($o['order_code'])?></td><td><?=htmlspecialchars($o['cname']??'N/A')?></td><td><?=htmlspecialchars($o['pname']??'N/A')?></td><td>Rs. <?=number_format($o['total_amount'])?></td><td><span class="badge badge-<?=strtolower($o['status'])?>"><?=$o['status']?></span></td><td><?=date('d M Y',strtotime($o['order_date']))?></td></tr>
        <?php endforeach; ?>
      </tbody></table></div></div></div>
    </section>
  </div>
  <script>
    function toggleSidebar(){document.getElementById('sidebar').classList.toggle('open');document.getElementById('sidebarOverlay').classList.toggle('show');}
    window.onclick=function(e){if(!e.target.closest('.notif-wrapper')){var d=document.getElementById('notifDropdown');if(d)d.classList.remove('show');}}
  </script>
</body>
</html>
