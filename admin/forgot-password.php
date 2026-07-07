<?php
session_start();
require_once 'config/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$success = '';
$resetLink = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = 'Please enter your email address.';
    } else {
        try {
            $stmt = $db->prepare("SELECT id, full_name FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin) {
                $db->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) NULL");
                $db->query("ALTER TABLE admins ADD COLUMN IF NOT EXISTS reset_token_expiry DATETIME NULL");

                $token = bin2hex(random_bytes(16));
                $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

                $up = $db->prepare("UPDATE admins SET reset_token = ?, reset_token_expiry = ? WHERE id = ?");
                $up->execute([$token, $expiry, $admin['id']]);

                $success = "Password recovery token has been generated. For testing locally, click the link below to reset your password:";
                $resetLink = "reset-password.php?token=" . $token;
            } else {
                $error = "No admin account found with that email address.";
            }
        } catch (Exception $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Forgot Password | Vintage Dial</title>
    <link rel="icon" type="image/png" href="../images/footer.jpeg">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
    <style>
        body { font-family: "Noto Sans", sans-serif; background: #fdfcf9; }
        .auth-box { width: 100%; max-width: 500px; margin: 100px auto; background: #fff; box-shadow: 0 15px 40px rgba(0,0,0,0.08); border-radius: 12px; overflow: hidden; border: 1px solid #eae5dc; padding: 40px; }
        .form-control { border-radius: 8px; padding: 12px 14px; height: auto; margin-bottom: 15px; border: 1px solid #eae5dc; font-size: 14px; }
        .form-control:focus { border-color: #c9a227; box-shadow: 0 0 0 0.2rem rgba(201,162,39,0.15); }
        .btn-auth { width: 100%; background: #111; color: white; padding: 12px; border: none; border-radius: 30px; font-weight: bold; transition: 0.3s; font-size: 15px; letter-spacing: 1px; }
        .btn-auth:hover { background: #c9a227; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <div class="auth-box">
            <h3 class="text-center font-weight-bold mb-3" style="color:#111;">Admin Password Recovery</h3>
            <p class="text-muted text-center mb-4" style="font-size:13px;">Enter your admin email below and we will help you reset your password.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger text-center" style="font-size: 13px; border-radius: 8px;">
                    <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success" style="font-size: 13px; border-radius: 8px;">
                    <i class="fas fa-check-circle mr-2"></i> <?= $success ?><br>
                    <div class="mt-3 text-center">
                        <a href="<?= htmlspecialchars($resetLink) ?>" class="btn btn-dark btn-sm font-weight-bold px-3" style="border-radius:20px;">Reset Password Now</a>
                    </div>
                </div>
            <?php endif; ?>

            <form method="POST" action="forgot-password.php">
                <div class="form-group">
                    <label class="font-weight-bold" style="font-size:12px;">EMAIL ADDRESS</label>
                    <input type="email" name="email" class="form-control" placeholder="Enter your admin email" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
                </div>
                <button type="submit" class="btn-auth mt-2">Recover Password</button>
            </form>

            <div class="text-center mt-4">
                <a href="login.php" style="color:#b08d57; font-weight:600; font-size:13px;">Back to Admin Login</a>
            </div>
        </div>
    </div>
</body>
</html>
