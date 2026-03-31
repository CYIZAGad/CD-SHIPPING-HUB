<?php
require_once 'config/database.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = sanitize($_POST['full_name'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $address = sanitize($_POST['address'] ?? '');

        if (empty($name)) $errors[] = 'Full name is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';

        // Password change (optional)
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';
        if (!empty($newPass)) {
            if (strlen($newPass) < 6) $errors[] = 'New password must be at least 6 characters.';
            if ($newPass !== $confirmPass) $errors[] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, phone = ?, address = ? WHERE id = ?");
            $stmt->execute([$name, $phone, $address, $_SESSION['user_id']]);

            if (!empty($newPass)) {
                $hash = password_hash($newPass, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hash, $_SESSION['user_id']]);
            }

            $_SESSION['user_name'] = $name;
            setFlash('success', 'Profile updated successfully!');
            redirect(SITE_URL . '/profile.php');
        }
    }
}

$pageTitle = 'My Profile';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <h2 class="fw-bold mb-4"><i class="bi bi-gear me-2"></i>My Profile</h2>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h5 class="mb-0">Personal Information</h5></div>
                <div class="card-body">
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" name="full_name" value="<?= sanitize($user['full_name']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email (cannot be changed)</label>
                            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" name="phone" value="<?= sanitize($user['phone']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="2"><?= sanitize($user['address']) ?></textarea>
                        </div>
                        <hr>
                        <h6 class="fw-bold">Change Password (optional)</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" minlength="6">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" name="confirm_password">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-2"></i>Save Changes</button>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="bi bi-person-circle display-1 text-primary"></i>
                    <h5 class="mt-2"><?= sanitize($user['full_name']) ?></h5>
                    <p class="text-muted"><?= sanitize($user['email']) ?></p>
                    <p class="text-muted small">Member since <?= date('F Y', strtotime($user['created_at'])) ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
