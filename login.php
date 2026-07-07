<?php
session_start();
require_once 'admin/config/db.php';

// If already logged in, redirect to homepage
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please fill in both email and password.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        try {
            $stmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            $cust = $stmt->fetch();

            if ($cust && password_verify($password, $cust['password'])) {
                $_SESSION['customer_id'] = $cust['id'];
                $_SESSION['customer_name'] = $cust['full_name'];
                $_SESSION['customer_email'] = $cust['email'];

                // Redirect to checkout if cart has items, otherwise homepage
                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    header('Location: checkout.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }

            $adminStmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
            $adminStmt->execute([$email]);
            $admin = $adminStmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $customerStmt = $db->prepare("SELECT * FROM customers WHERE email = ?");
                $customerStmt->execute([$email]);
                $customer = $customerStmt->fetch();

                if (!$customer) {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $createCustomer = $db->prepare("INSERT INTO customers (full_name, email, password, gender, dob, phone, address) VALUES (?, ?, ?, '', '', '', '')");
                    $createCustomer->execute([$admin['full_name'], $email, $hashedPassword]);
                    $customerId = $db->lastInsertId();
                    $customerName = $admin['full_name'];
                } else {
                    $customerId = $customer['id'];
                    $customerName = $customer['full_name'];
                }

                $_SESSION['customer_id'] = $customerId;
                $_SESSION['customer_name'] = $customerName;
                $_SESSION['customer_email'] = $email;

                if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
                    header('Location: checkout.php');
                } else {
                    header('Location: index.php');
                }
                exit;
            }

            $error = 'Invalid email or password.';
        } catch (Exception $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login | Vintage Dial</title>
    <link rel="icon" type="image/png" href="./images/footer.jpeg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link
        href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Noto+Sans:wght@300;400;500;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
    body {
        font-family: "Noto Sans", sans-serif;
        background: #fdfcf9;
    }

    .auth-box {
        width: 100%;
        max-width: 950px;
        height: 600px;
        display: flex;
        text-align: center;
        margin: 60px auto;
        background: #fff;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        border-radius: 12px;
        overflow: hidden;
        border: 1px solid #eae5dc;
    }

    .auth-left {
        flex: 1;
        background: url("./images/login-bg.jpg") center/cover no-repeat;
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        padding: 30px;
    }

    .auth-left::after {
        content: "";
        position: absolute;
        inset: 0;
        background: rgba(0, 0, 0, 0.6);
    }

    .auth-left .logo,
    .auth-left .tagline {
        position: relative;
        z-index: 1;
    }

    .logo {
        font-family: "Great Vibes", cursive;
        font-size: 56px;
        margin-bottom: 10px;
    }

    .tagline {
        font-size: 14px;
        letter-spacing: 2px;
        text-transform: uppercase;
        color: #c9a227 !important;
    }

    .auth-right {
        flex: 1;
        padding: 40px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: left;
    }

    .form-control {
        border-radius: 8px;
        padding: 12px 14px;
        height: auto;
        margin-bottom: 15px;
        border: 1px solid #eae5dc;
        font-size: 14px;
    }

    .form-control:focus {
        border-color: #c9a227;
        box-shadow: 0 0 0 0.2rem rgba(201, 162, 39, 0.15);
    }

    .btn-auth {
        width: 100%;
        background: #111;
        color: white;
        padding: 12px;
        border: none;
        border-radius: 30px;
        font-weight: bold;
        transition: 0.3s;
        font-size: 15px;
        letter-spacing: 1px;
    }

    .btn-auth:hover {
        background: #c9a227;
        color: black;
    }

    .link {
        text-align: center;
        margin-top: 15px;
        font-size: 13px;
    }

    .link a {
        color: #b08d57;
        font-weight: bold;
        text-decoration: none;
    }

    .link a:hover {
        text-decoration: underline;
    }
    </style>
</head>

<body>
    <div class="container d-flex" style="min-height: 100vh; align-items: center;">
        <div class="auth-box">
            <!-- Left Panel -->
            <div class="auth-left d-none d-md-flex">
                <h1 class="logo">Vintage Dial</h1>
                <p class="tagline">Where Time Becomes Legacy</p>
            </div>

            <!-- Right Panel Form -->
            <div class="auth-right">
                <h3 class="text-center font-weight-bold mb-4" style="color:#111;">Sign In</h3>

                <?php if ($error): ?>
                <div class="alert alert-danger text-center" style="font-size: 13px; border-radius: 8px;">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                </div>
                <?php endif; ?>

                <form method="POST" action="login.php">
                    <div class="form-group">
                        <label class="font-weight-bold" style="font-size:12px;">EMAIL ADDRESS</label>
                        <input type="email" name="email" class="form-control" placeholder="Enter your email" required
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                    </div>

                    <div class="form-group">
                        <div class="d-flex justify-content-between">
                            <label class="font-weight-bold" style="font-size:12px;">PASSWORD</label>
                            <a href="forgot-password.php" style="font-size:11px; color:#b08d57; font-weight:600;">Forgot
                                Password?</a>
                        </div>
                        <input type="password" name="password" class="form-control" placeholder="Enter your password"
                            required />
                    </div>

                    <button type="submit" class="btn-auth mt-2">Login</button>
                </form>

                <div class="link">
                    Don't have an account? <a href="signup.php">Sign Up</a>
                </div>
                <div class="text-center mt-3">
                    <a href="index.php" class="text-muted" style="font-size: 11px;"><i class="fas fa-arrow-left"></i>
                        Back to Store</a>
                </div>
            </div>
        </div>
    </div>
</body>

</html>