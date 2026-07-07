<?php
/**
 * Sidebar Component
 * Usage: include 'includes/sidebar.php';
 * Set $currentPage before including, e.g. $currentPage = 'dashboard';
 */
$currentPage = $currentPage ?? '';
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$initials = '';
$parts = explode(' ', $adminName);
foreach ($parts as $p) {
    $initials .= strtoupper(substr($p, 0, 1));
}
$initials = substr($initials, 0, 2);
?>
<!-- Sidebar Overlay (mobile) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- SIDEBAR -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
        <h1>Vintage Dial</h1>
    </div>
    <nav class="sidebar-nav">
        <a href="dashboard.php" <?= $currentPage === 'dashboard' ? 'class="active"' : '' ?>><i
                class="fas fa-th-large"></i> Dashboard</a>
        <a href="orders.php" <?= $currentPage === 'orders' ? 'class="active"' : '' ?>><i
                class="fas fa-shopping-bag"></i> Orders</a>
        <a href="contact_messages.php" <?= $currentPage === 'contacts' ? 'class="active"' : '' ?>><i
                class="fas fa-envelope"></i> Contact Messages</a>
        <a href="categories.php" <?= $currentPage === 'categories' ? 'class="active"' : '' ?>><i
                class="fas fa-tags"></i> Categories</a>
        <a href="products.php" <?= $currentPage === 'products' ? 'class="active"' : '' ?>><i
                class="fas fa-box-open"></i> Products</a>
        <a href="content.php" <?= $currentPage === 'content' ? 'class="active"' : '' ?>><i class="fas fa-newspaper"></i>
            Content</a>
        <a href="customers.php" <?= $currentPage === 'customers' ? 'class="active"' : '' ?>><i class="fas fa-users"></i>
            Customers</a>
        <a href="analytics.php" <?= $currentPage === 'analytics' ? 'class="active"' : '' ?>><i
                class="fas fa-chart-line"></i> Analytics</a>
        <a href="reviews.php" <?= $currentPage === 'reviews' ? 'class="active"' : '' ?>><i class="fas fa-star"></i>
            Reviews</a>
        <a href="admins.php" <?= $currentPage === 'admins' ? 'class="active"' : '' ?>><i class="fas fa-user-shield"></i>
            Admins</a>
        <a href="profile.php" <?= $currentPage === 'profile' ? 'class="active"' : '' ?>><i class="fas fa-user-cog"></i>
            Profile</a>
    </nav>
    <div class="sidebar-footer">
        <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i> Logout
        </a>
    </div>
</aside>