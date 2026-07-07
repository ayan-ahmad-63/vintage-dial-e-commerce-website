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
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');

    // Server-Side Validations
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'Full Name, Email, and both password fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($fullName) < 3) {
        $error = 'Full name must be at least 3 characters long.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters long.';
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Password must contain at least one uppercase letter, one lowercase letter, and one number.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } elseif (!empty($phone) && !preg_match('/^[0-9\s()+-]{7,20}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif (!empty($dob) && strtotime($dob) > time()) {
        $error = 'Date of birth cannot be in the future.';
    } else {
        try {
            // Check if email already exists
            $stmt = $db->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $ins = $db->prepare("INSERT INTO customers (full_name, email, password, gender, dob, phone, address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $ins->execute([$fullName, $email, $hashedPassword, $gender, $dob, $phone, $address]);
                
                $customerId = $db->lastInsertId();
                
                // Set Login Sessions
                $_SESSION['customer_id'] = $customerId;
                $_SESSION['customer_name'] = $fullName;
                $_SESSION['customer_email'] = $email;
                
                $success = 'Account created successfully!';
                
                // Redirect if came from checkout flow
                header('Location: index.php');
                exit;
            }
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
  <title>Sign Up | Vintage Dial</title>
  <link rel="icon" type="image/png" href="./images/footer.jpeg">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Noto+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <style>
    body {
      font-family: "Noto Sans", sans-serif;
      background: #fdfcf9;
      margin: 0;
      padding: 0;
    }
    .auth-box {
      width: 100%;
      max-width: 950px;
      min-height: 600px;
      display: flex;
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
      color: #fff;
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
      flex: 1.2;
      padding: 40px;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }
    .form-control {
      border-radius: 8px;
      padding: 10px 14px;
      height: auto;
      margin-bottom: 12px;
      border: 1px solid #eae5dc;
      font-size: 13px;
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
      cursor: pointer;
      transition: 0.3s;
      font-size: 14px;
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
      <!-- Left Info Panel -->
      <div class="auth-left d-none d-md-flex">
        <h1 class="logo">Vintage Dial</h1>
        <p class="tagline">Where Time Becomes Legacy</p>
      </div>

      <!-- Right Form Panel -->
      <div class="auth-right">
        <h3 class="text-center font-weight-bold mb-4" style="color:#111;">Create Account</h3>

        <?php if ($error): ?>
          <div class="alert alert-danger" style="font-size: 13px; border-radius: 8px;">
            <i class="fas fa-exclamation-circle mr-2"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="signup.php" id="signupForm">
          <div class="form-row">
            <div class="col-md-6">
              <input type="text" name="full_name" class="form-control" placeholder="Full Name *" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>" />
            </div>
            <div class="col-md-6">
              <input type="email" name="email" class="form-control" placeholder="Email Address *" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
            </div>
          </div>

          <div class="form-row">
            <div class="col-md-6">
              <input type="password" name="password" class="form-control" placeholder="Password *" required pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}" title="Use at least 8 characters, including uppercase, lowercase, and a number." />
            </div>
            <div class="col-md-6">
              <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password *" required />
            </div>
          </div>

          <div class="form-row">
            <div class="col-md-6">
              <select name="gender" class="form-control">
                <option value="">Gender (Optional)</option>
                <option <?= (($_POST['gender'] ?? '') === 'Male') ? 'selected' : '' ?>>Male</option>
                <option <?= (($_POST['gender'] ?? '') === 'Female') ? 'selected' : '' ?>>Female</option>
                <option <?= (($_POST['gender'] ?? '') === 'Other') ? 'selected' : '' ?>>Other</option>
              </select>
            </div>
            <div class="col-md-6">
              <input type="date" name="dob" class="form-control" title="Date of Birth" value="<?= htmlspecialchars($_POST['dob'] ?? '') ?>" />
            </div>
          </div>

          <input type="text" name="phone" class="form-control" placeholder="Phone Number" pattern="[0-9\s()+-]{7,20}" title="Enter a valid phone number." value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
          <textarea name="address" class="form-control" placeholder="Shipping Address" rows="2"><?= htmlspecialchars($_POST['address'] ?? '') ?></textarea>

          <button type="submit" class="btn-auth mt-2">
            Create Account
          </button>
        </form>

        <div class="link">
          Already have an account? <a href="login.php">Login</a>
        </div>
        <div class="text-center mt-3">
          <a href="index.php" class="text-muted" style="font-size: 11px;"><i class="fas fa-arrow-left"></i> Back to Store</a>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
