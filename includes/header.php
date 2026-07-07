<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../admin/config/db.php';
require_once __DIR__ . '/asset_helpers.php';

// Calculate cart count
$cartCount = 0;
if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += isset($item['qty']) ? intval($item['qty']) : 1;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Noto+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <link rel="stylesheet" href="navbar.css">
    <link rel="stylesheet" href="footer.css">
    <link rel="stylesheet" href="carousel.css">
    <link rel="stylesheet" href="brands.css">
    <link rel="stylesheet" href="moment.css">
    <link rel="stylesheet" href="insta.css">
    <link rel="stylesheet" href="press.css">

    <title><?php RouteTitle(); ?></title>
    <link rel="icon" type="image/png" href="./images/footer.jpeg">

    <style>
    /* Styling adjustments for navbar search and dynamic items */
    .navbar {
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
    }

    .logo {
        font-family: "Great Vibes", cursive !important;
        font-size: 38px !important;
        font-weight: normal;
        color: #111 !important;
        margin: 0;
        line-height: 1;
    }

    .nav-search-form {
        display: flex;
        align-items: center;
        margin-left: 15px;
        border: 1px solid #ddd;
        border-radius: 20px;
        padding: 2px 12px;
        background: #fdfdfd;
        transition: 0.3s;
    }

    .nav-search-form:focus-within {
        border-color: #c9a227;
        box-shadow: 0 0 5px rgba(201, 162, 39, 0.2);
    }

    .nav-search-form input {
        border: none;
        background: none;
        outline: none;
        font-size: 13px;
        padding: 4px 8px;
        width: 150px;
        transition: 0.3s;
    }

    .nav-search-form input:focus {
        width: 200px;
    }

    .nav-search-form button {
        background: none;
        border: none;
        outline: none;
        color: #666;
        cursor: pointer;
        padding: 0;
    }

    .nav-search-form button:hover {
        color: #c9a227;
    }

    .store-btn {
        position: relative;
        transition: 0.3s;
    }

    .store-btn .badge-cart {
        position: absolute;
        top: -6px;
        right: -6px;
        background: #c9a227;
        color: #000;
        border-radius: 50%;
        padding: 3px 6px;
        font-size: 10px;
        font-weight: bold;
    }

    .nav-link {
        font-family: "Noto Sans", sans-serif;
        font-weight: 500;
        color: #333 !important;
        transition: 0.3s;
    }

    .nav-link:hover {
        color: #c9a227 !important;
    }

    .user-menu-dropdown {
        font-family: "Noto Sans", sans-serif;
        font-size: 14px;
    }

    .user-menu-dropdown .dropdown-item {
        font-size: 13px;
        padding: 8px 16px;
        transition: 0.2s;
    }

    .user-menu-dropdown .dropdown-item:hover {
        background: #f8f9fa;
        color: #c9a227;
    }

    /* Alert styles for status messages */
    .alert-vintage {
        background: #fffdf5;
        border-left: 4px solid #c9a227;
        color: #856404;
        border-radius: 4px;
        padding: 15px;
        margin-bottom: 20px;
    }
    </style>
</head>

<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom sticky-top">
        <div class="container-fluid">
            <!-- Logo (Left) -->
            <a href="index.php" style="text-decoration: none;">
                <h1 class="logo">Vintage Dial</h1>
            </a>

            <!-- Toggle for mobile -->
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#mainNavbar"
                aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Center Menu -->
            <div class="collapse navbar-collapse justify-content-center" id="mainNavbar">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="collectionDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            Collection
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm" aria-labelledby="collectionDropdown">
                            <a class="dropdown-item" href="watches.php">Watches</a>
                            <a class="dropdown-item" href="limited.php">Limited Edition</a>
                        </div>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="heritage.php">Our Heritage</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="order.php">My Orders</a>
                    </li>

                    <?php if (isset($_SESSION['customer_id'])): ?>
                    <li class="nav-item dropdown user-menu-dropdown">
                        <a class="nav-link dropdown-toggle font-weight-bold" href="#" id="userDropdown" role="button"
                            data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['customer_name']) ?>
                        </a>
                        <div class="dropdown-menu border-0 shadow-sm" aria-labelledby="userDropdown">
                            <a class="dropdown-item" href="profile.php"><i class="fas fa-id-card mr-2"></i> My
                                Profile</a>
                            <a class="dropdown-item" href="order.php"><i class="fas fa-shopping-bag mr-2"></i> Order
                                History</a>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item text-danger" href="logout.php"><i
                                    class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                        </div>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"> Login</a>
                    </li>
                    <?php endif; ?>
                </ul>

                <!-- Search bar directly in navbar collapse for responsiveness -->
                <form action=" watches.php" method="GET" class="nav-search-form my-2 my-lg-0">
                    <input type="text" name="search" placeholder="Search watches..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <!-- Right Section -->
            <div class="ml-auto d-flex align-items-center">
                <a href="cart.php">
                    <button class="store-btn">
                        My Cart <i class="fas fa-shopping-cart"></i>
                        <?php if ($cartCount > 0): ?>
                        <span class="badge-cart"><?= $cartCount ?></span>
                        <?php endif; ?>
                    </button>
                </a>
            </div>
        </div>
    </nav>

    <?php
// Page title helper function
function RouteTitle() {
    global $pageTitle;
    echo isset($pageTitle) ? $pageTitle . " | Vintage Dial" : "Vintage Dial — Where Time Becomes Legacy";
}
?>