<?php
session_start();
require_once 'admin/config/db.php';

// If already logged in
if (isset($_SESSION['customer_id'])) {
    header('Location: index.php');
    exit;
}

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$error = '';
$success = '';
$isValidToken = false;
$cust = [];

if (empty($token)) {
    $error = "Reset token is missing.";
} else {
    try {
        // Validate Token and Expiry (must be greater than NOW)
        $stmt = $db->prepare("SELECT * FROM customers WHERE reset_token = ? AND reset_token_expiry > NOW()");
        $stmt->execute([$token]);
        $cust = $stmt->fetch();
        
        if ($cust) {
            $isValidToken = true;
        } else {
            $error = "Reset token is invalid or has expired.";
        }
    } catch (Exception $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle Form Submission
if ($isValidToken && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($password)) {
        $error = "Password cannot be empty.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirmPassword) {
        $error = "Passwords do not match.";
    } else {
        try {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            
            // Update password and clear token
            $up = $db->prepare("UPDATE customers SET password = ?, reset_token = NULL, reset_token_expiry = NULL WHERE id = ?");
            $up->execute([$hashed, $cust['id']]);
            
            $success = "Password has been reset successfully! You can now log in.";
            $isValidToken = false; // Hide form
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
  <title>Reset Password | Vintage Dial</title>
  <link rel="icon" type="image/png" href="./images/footer.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: "Noto Sans", sans-serif;
      background: #fdfcf9;
    }
    .auth-box {
      width: 100%;
      max-width: 500px;
      margin: 100px auto;
      background: #fff;
      box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #eae5dc;
      padding: 40px;
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
      box-shadow: 0 0 0 0.2rem rgba(201,162,39,0.15);
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
  </style>
</head>

<body>
  <div class="container">
    <div class="auth-box">
      <h3 class="text-center font-weight-bold mb-4" style="color:#111;">Reset Password</h3>

      <?php if ($error): ?>
        <div class="alert alert-danger text-center" style="font-size: 13px; border-radius: 8px;">
          <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>
      
      <?php if ($success): ?>
        <div class="alert alert-success text-center" style="font-size: 13px; border-radius: 8px;">
          <i class="fas fa-check-circle mr-2"></i> <?= htmlspecialchars($success) ?><br>
          <a href="login.php" class="btn btn-dark btn-sm font-weight-bold px-4 py-2 mt-3" style="border-radius:20px;">Log In Now</a>
        </div>
      <?php endif; ?>

      <?php if ($isValidToken): ?>
        <form method="POST" action="reset-password.php?token=<?= htmlspecialchars($token) ?>">
          <div class="form-group">
            <label class="font-weight-bold" style="font-size:12px;">NEW PASSWORD</label>
            <input type="password" name="password" class="form-control" placeholder="Minimum 6 characters" required />
          </div>
          <div class="form-group">
            <label class="font-weight-bold" style="font-size:12px;">CONFIRM NEW PASSWORD</label>
            <input type="password" name="confirm_password" class="form-control" placeholder="Re-enter new password" required />
          </div>
          <button type="submit" class="btn-auth mt-2">Reset Password</button>
        </form>
      <?php endif; ?>

      <div class="text-center mt-4">
        <a href="login.php" style="color:#b08d57; font-weight:600; font-size:13px;">Back to Login</a>
      </div>
    </div>
  </div>
</body>

</html>
