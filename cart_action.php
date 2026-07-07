<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'admin/config/db.php';
require_once 'includes/asset_helpers.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // 1. ADD TO CART
    if ($action === 'add') {
        $productId = isset($_POST['product_id']) ? intval($_POST['product_id']) : 0;
        $qty = isset($_POST['qty']) ? max(1, intval($_POST['qty'])) : 1;
        $size = isset($_POST['variation_size']) ? trim($_POST['variation_size']) : 'Standard';
        $wrap = isset($_POST['variation_wrap']) ? trim($_POST['variation_wrap']) : 'No';
        
        if ($productId > 0) {
            // Get product details
            $stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->execute([$productId]);
            $p = $stmt->fetch();
            
            if ($p) {
                // Unique key for item including variations
                $cartKey = $productId . '_' . strtolower($size) . '_' . strtolower($wrap);
                
                if (isset($_SESSION['cart'][$cartKey])) {
                    $_SESSION['cart'][$cartKey]['qty'] += $qty;
                } else {
                    $_SESSION['cart'][$cartKey] = [
                        'id' => $p['id'],
                        'name' => $p['name'],
                        'subtitle' => $p['subtitle'],
                        'price' => floatval($p['price']),
                        'image' => first_image_path($p['image']),
                        'size' => $size,
                        'wrap' => $wrap,
                        'qty' => $qty
                    ];
                }
                
                // If Buy Now, redirect to checkout
                if (isset($_POST['buy_now']) && $_POST['buy_now'] == '1') {
                    header('Location: checkout.php');
                    exit;
                }
            }
        }

        $returnUrl = $_POST['return_url'] ?? $_SERVER['HTTP_REFERER'] ?? '';
        if ($returnUrl) {
            $parsedUrl = parse_url($returnUrl);
            $redirectPath = $parsedUrl['path'] ?? 'index.php';
            if (!empty($parsedUrl['query'])) {
                $redirectPath .= '?' . $parsedUrl['query'];
            }
            header('Location: ' . $redirectPath);
        } else {
            header('Location: index.php');
        }
        exit;
    }
    
    // 2. UPDATE QUANTITY
    if ($action === 'update') {
        $key = isset($_POST['cart_key']) ? trim($_POST['cart_key']) : '';
        $qty = isset($_POST['qty']) ? intval($_POST['qty']) : 0;
        
        if ($key && isset($_SESSION['cart'][$key])) {
            if ($qty > 0) {
                // Check stock
                $prodId = $_SESSION['cart'][$key]['id'];
                $stmt = $db->prepare("SELECT stock FROM products WHERE id = ?");
                $stmt->execute([$prodId]);
                $stock = $stmt->fetchColumn();
                
                $_SESSION['cart'][$key]['qty'] = min($qty, $stock);
            } else {
                unset($_SESSION['cart'][$key]);
            }
        }
        header('Location: cart.php?msg=updated');
        exit;
    }
    
    // 3. REMOVE ITEM
    if ($action === 'remove') {
        $key = isset($_POST['cart_key']) ? trim($_POST['cart_key']) : '';
        
        if ($key && isset($_SESSION['cart'][$key])) {
            unset($_SESSION['cart'][$key]);
        }
        header('Location: cart.php?msg=removed');
        exit;
    }
    
    // 4. APPLY PROMO CODE
    if ($action === 'apply_promo') {
        $promo = strtoupper(trim($_POST['promo_code'] ?? ''));
        
        if ($promo === 'WELCOME10') {
            $_SESSION['discount_pct'] = 10;
            $_SESSION['promo_code'] = $promo;
            header('Location: cart.php?msg=promo_applied');
        } elseif ($promo === 'VINTAGE20') {
            $_SESSION['discount_pct'] = 20;
            $_SESSION['promo_code'] = $promo;
            header('Location: cart.php?msg=promo_applied');
        } else {
            $_SESSION['discount_pct'] = 0;
            $_SESSION['promo_code'] = '';
            header('Location: cart.php?msg=promo_invalid');
        }
        exit;
    }
}

// Redirect back if hit directly
header('Location: cart.php');
exit;
