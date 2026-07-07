<?php
session_start();

// If already logged in, redirect to dashboard
if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'config/db.php';
    
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        $stmt = $db->prepare("SELECT * FROM admins WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();
        
        if ($admin && password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            $_SESSION['admin_email'] = $admin['email'];
            $_SESSION['admin_role'] = $admin['role'];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid email or password';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Vintage Dial Admin Panel – Secure Login">
    <title>Admin Login | Vintage Dial</title>
    <link rel="icon" type="image/png" href="../images/footer.jpeg">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="admin.css">
</head>

<body class="login-page">

    <div class="login-card">
        <!-- Banner -->
        <div class="login-banner">
            <h1>Vintage Dial</h1>
        </div>

        <!-- Form -->
        <div class="login-form">
            <h2>Welcome Back</h2>

            <?php if ($error): ?>
            <div class="login-error" style="display:block;">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="login.php">
                <div class="form-group">
                    <label for="adminEmail">Email / Username / Mobile</label>
                    <input type="text" id="adminEmail" name="email" placeholder="admin@vintagedial.com"
                        value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                </div>

                <div class="form-group">
                    <label for="adminPassword">Password</label>
                    <input type="password" id="adminPassword" name="password" placeholder="Enter your password"
                        required>
                </div>

                <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="remember"> Remember me
                    </label>
                    <a href="forgot-password.php" style="font-size:13px; color:black; font-weight:500;">Forgot
                        password?</a>
                </div>

                <button type="submit" class="btn btn-primary">
                    Login
                </button>
            </form>

            <p style="text-align:center; margin-top:20px; font-size:13px; color:var(--clr-text-muted);">
                <a href="../index.php" style="color: black;"><i class="fas fa-arrow-left"></i> Back to
                    Store</a>
            </p>
        </div>
    </div>

    <script>
    // Allow Enter key to submit
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            document.querySelector('form').submit();
        }
    });
    </script>
</body>

</html>