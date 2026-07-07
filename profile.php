<?php
$pageTitle = "My Profile";
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Force Login
if (!isset($_SESSION['customer_id'])) {
    header('Location: login.php');
    exit;
}

include 'includes/header.php';

$customerId = $_SESSION['customer_id'];
$error = '';
$success = '';

// Handle Profile Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['dob'] ?? '';
    $city = trim($_POST['city'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($fullName)) {
        $error = 'Full Name is a required field.';
    } elseif (!empty($password) && strlen($password) < 6) {
        $error = 'New password must be at least 6 characters long.';
    } elseif (!empty($password) && $password !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $db->beginTransaction();
            
            // Update personal details
            $stmt = $db->prepare("UPDATE customers SET full_name = ?, phone = ?, gender = ?, dob = ?, city = ?, address = ? WHERE id = ?");
            $stmt->execute([$fullName, $phone, $gender, $dob, $city, $address, $customerId]);
            
            // Update password if filled
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $upPass = $db->prepare("UPDATE customers SET password = ? WHERE id = ?");
                $upPass->execute([$hashed, $customerId]);
            }
            
            $db->commit();
            
            $_SESSION['customer_name'] = $fullName;
            $success = 'Profile updated successfully!';
        } catch (Exception $e) {
            $db->rollBack();
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch current details
try {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customerId]);
    $cust = $stmt->fetch();
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}
?>

<style>
.profile-section {
    padding: 60px 0;
    background: #fdfcf9;
    min-height: 80vh;
}

.profile-container {
    background: #ffffff;
    border: 1px solid #eae5dc;
    border-radius: 15px;
    padding: 40px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
}

.section-title {
    font-size: 28px;
    font-weight: 700;
    color: #111;
    margin-bottom: 25px;
}

.profile-input {
    border-radius: 8px !important;
    border-color: #eae5dc !important;
    padding: 12px !important;
    height: auto !important;
    font-size: 14px !important;
}

.profile-input:focus {
    border-color: #b08d57 !important;
    box-shadow: 0 0 0 0.2rem rgba(176, 141, 87, 0.15) !important;
}

.sidebar-menu {
    border: 1px solid #eae5dc;
    border-radius: 12px;
    overflow: hidden;
    background: #fff;
}

.sidebar-menu a {
    display: block;
    padding: 15px 20px;
    color: #555;
    font-weight: 600;
    text-decoration: none;
    border-bottom: 1px solid #eae5dc;
    transition: 0.2s;
}

.sidebar-menu a:last-child {
    border-bottom: none;
}

.sidebar-menu a:hover,
.sidebar-menu a.active {
    background: #fffdf5;
    color: #b08d57;
    padding-left: 25px;
}
</style>

<section class="profile-section">
    <div class="container">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-4 mb-4 mb-md-0">
                <div class="sidebar-menu">
                    <a href="profile.php" class="active"><i class="fas fa-user-circle mr-2"></i> Account Details</a>
                    <a href="order.php"><i class="fas fa-shopping-bag mr-2"></i> Order History</a>
                    <a href="logout.php" class="text-danger"><i class="fas fa-sign-out-alt mr-2"></i> Logout</a>
                </div>
            </div>

            <!-- Profile Form -->
            <div class="col-md-8">
                <div class="profile-container">
                    <div class="profile-avatar"
                        style="width:70px; height:70px; background:#c9a227; color:#fff; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:32px; font-weight:700; margin:0 auto 18px auto;">
                        <?php
              $initial = '';
              if (!empty($cust['full_name'])) {
                $initial = strtoupper(substr(trim($cust['full_name']), 0, 1));
              }
              echo $initial;
            ?>
                    </div>
                    <h2 class="section-title">Account Details</h2>

                    <?php if($success): ?>
                    <div class="alert alert-success"><i class="fas fa-check-circle mr-2"></i> <?= $success ?></div>
                    <?php endif; ?>
                    <?php if($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-2"></i> <?= $error ?></div>
                    <?php endif; ?>

                    <form action="profile.php" method="POST">
                        <!-- Email (readonly) -->
                        <div class="form-group">
                            <label class="font-weight-bold" style="font-size:12px;">EMAIL ADDRESS (Cannot be
                                changed)</label>
                            <input type="email" class="form-control profile-input"
                                value="<?= htmlspecialchars($cust['email']) ?>" readonly
                                style="background:#f9f9f9; color:#777;">
                        </div>

                        <div class="form-row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">FULL NAME *</label>
                                <input type="text" name="full_name" class="form-control profile-input" required
                                    value="<?= htmlspecialchars($cust['full_name']) ?>">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">PHONE NUMBER</label>
                                <input type="text" name="phone" class="form-control profile-input"
                                    value="<?= htmlspecialchars($cust['phone'] ?? '') ?>"
                                    placeholder="e.g. 0300-1234567">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">GENDER</label>
                                <select name="gender" class="form-control profile-input">
                                    <option value="">Select Gender</option>
                                    <option <?= $cust['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                    <option <?= $cust['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                                    <option <?= $cust['gender'] === 'Other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">DATE OF BIRTH</label>
                                <input type="date" name="dob" class="form-control profile-input"
                                    value="<?= htmlspecialchars($cust['dob'] ?? '') ?>">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="col-md-8 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">SHIPPING ADDRESS</label>
                                <input type="text" name="address" class="form-control profile-input"
                                    value="<?= htmlspecialchars($cust['address'] ?? '') ?>"
                                    placeholder="Street Address">
                            </div>
                            <div class="col-md-4 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">CITY</label>
                                <input type="text" name="city" class="form-control profile-input"
                                    value="<?= htmlspecialchars($cust['city'] ?? '') ?>" placeholder="e.g. Lahore">
                            </div>
                        </div>

                        <!-- Password Updates -->
                        <hr class="my-4" style="border-color:#eae5dc;">
                        <h4 class="mb-3 font-weight-bold" style="font-size:16px; color:#111;">Change Password (Leave
                            blank to keep current)</h4>

                        <div class="form-row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">NEW PASSWORD</label>
                                <input type="password" name="password" class="form-control profile-input"
                                    placeholder="Min 6 characters">
                            </div>
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold" style="font-size:12px;">CONFIRM NEW PASSWORD</label>
                                <input type="password" name="confirm_password" class="form-control profile-input"
                                    placeholder="Re-enter password">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-dark px-4 py-3 mt-3"
                            style="border-radius:30px; font-weight:600; font-size:14px; background:#111;">
                            SAVE CHANGES
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>