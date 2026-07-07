<?php

$pageTitle = $pageTitle ?? 'Dashboard';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$initials = '';
$parts = explode(' ', $adminName);
foreach ($parts as $p) {
    $initials .= strtoupper(substr($p, 0, 1));
}
$initials = substr($initials, 0, 2);

// Fetch recent notifications (latest 3 orders)
$notifStmt = $db->query("SELECT o.order_code, c.full_name, o.order_date 
                          FROM orders o 
                          LEFT JOIN customers c ON o.customer_id = c.id 
                          ORDER BY o.order_date DESC LIMIT 3");
$notifications = $notifStmt->fetchAll();
?>
<!-- TOPBAR -->
<header class="topbar">
    <div class="topbar-left">
        <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>
        <h2><?= htmlspecialchars($pageTitle) ?></h2>
    </div>
    <div class="topbar-search">
        <i class="fas fa-search"></i>
        <input type="text" placeholder="Search anything…">
    </div>
    <div class="topbar-right">
        <div class="notif-wrapper">
            <a href="#" class="notif"
                onclick="event.preventDefault(); document.getElementById('notifDropdown').classList.toggle('show')">
                <i class="fas fa-bell"></i><span class="badge-dot"></span>
            </a>
            <div class="notif-dropdown" id="notifDropdown">
                <div class="notif-header">
                    <h4>Notifications</h4>
                    <span class="badge badge-pending"><?= count($notifications) ?> New</span>
                </div>
                <div class="notif-content">
                    <?php foreach ($notifications as $n): ?>
                    <div class="notif-item">
                        <div class="notif-icon" style="background:#fef3c7; color:#92400e;"><i
                                class="fas fa-shopping-cart"></i></div>
                        <div class="notif-body">
                            <p><strong>Order</strong> #<?= htmlspecialchars($n['order_code']) ?> by
                                <?= htmlspecialchars($n['full_name'] ?? 'Customer') ?></p>
                            <span><?= date('d M Y', strtotime($n['order_date'])) ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="notif-footer">
                    <a href="orders.php">View All Orders</a>
                </div>
            </div>
        </div>
        <a href="profile.php">
            <div class="topbar-avatar"><?= $initials ?></div>
        </a>
    </div>
</header>