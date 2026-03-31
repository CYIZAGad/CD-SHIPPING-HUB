<?php
require_once 'config/database.php';

if (isLoggedIn()) redirect(SITE_URL);

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = sanitize($_POST['full_name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (empty($name)) $errors[] = 'Full name is required.';
        if (!$email) $errors[] = 'Valid email is required.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (empty($address)) $errors[] = 'Delivery address is required.';

        if (empty($errors)) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Email already registered.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, address) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $email, $hash, $phone, $address]);
                setFlash('success', 'Account created successfully! Please log in.');
                redirect(SITE_URL . '/login.php');
            }
        }
    }
}

$pageTitle = 'Create Account';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <i class="bi bi-person-plus display-4 text-primary"></i>
                    <h3 class="mt-2">Create Your Account</h3>
                    <p class="text-muted">Join CD SHIPPING HUB and start shopping</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-person me-1"></i>Full Name</label>
                        <input type="text" class="form-control form-control-lg" name="full_name" 
                               value="<?= sanitize($_POST['full_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>Email Address</label>
                        <input type="email" class="form-control form-control-lg" name="email" 
                               value="<?= sanitize($_POST['email'] ?? '') ?>" required>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                            <input type="password" class="form-control form-control-lg" name="password" required minlength="6">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label"><i class="bi bi-lock-fill me-1"></i>Confirm Password</label>
                            <input type="password" class="form-control form-control-lg" name="confirm_password" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-telephone me-1"></i>Phone Number</label>
                        <input type="tel" class="form-control form-control-lg" name="phone" 
                               value="<?= sanitize($_POST['phone'] ?? '') ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-geo-alt me-1"></i>Delivery Address</label>
                        <textarea class="form-control form-control-lg" name="address" rows="2" required><?= sanitize($_POST['address'] ?? '') ?></textarea>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="bi bi-person-plus me-2"></i>Create Account
                    </button>
                    
                    <p class="text-center text-muted">
                        Already have an account? <a href="login.php" class="text-primary fw-bold">Login here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
