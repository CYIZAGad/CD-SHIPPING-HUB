<?php
require_once 'config/database.php';

if (isLoggedIn()) redirect(SITE_URL);

$errors = [];
$token = $_GET['token'] ?? '';
$captchaQuestion = getCaptchaQuestion('reset_password');

if (empty($token)) {
    redirect(SITE_URL . '/forgot-password.php');
}

// Validate token
$pdo = getDBConnection();
$tokenHash = hashResetToken($token);
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = ? AND reset_expires > NOW()");
$stmt->execute([$tokenHash]);
$user = $stmt->fetch();

if (!$user) {
    setFlash('error', 'Invalid or expired reset token.');
    redirect(SITE_URL . '/forgot-password.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipKey = 'reset_password:' . getRequestIp();
    if (!rateLimitCheck($ipKey, 5, 300)) {
        $errors[] = 'Too many attempts. Please wait a few minutes and try again.';
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        $captcha = $_POST['captcha_answer'] ?? '';

        if (!verifyCaptchaChallenge('reset_password', $captcha)) {
            $errors[] = 'Captcha verification failed.';
        }

        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $confirm) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expires = NULL WHERE id = ?");
            $stmt->execute([$hash, $user['id']]);
            setFlash('success', 'Password reset successfully! Please log in.');
            redirect(SITE_URL . '/login.php');
        }
    }
    $captchaQuestion = getCaptchaQuestion('reset_password');
}

$pageTitle = 'Reset Password';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-check display-4 text-success"></i>
                    <h3 class="mt-2">Reset Password</h3>
                    <p class="text-muted">Enter your new password</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" class="form-control form-control-lg" name="password" required minlength="6">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Confirm New Password</label>
                        <input type="password" class="form-control form-control-lg" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Captcha: Solve <?= e($captchaQuestion) ?></label>
                        <input type="text" class="form-control form-control-lg" name="captcha_answer" required>
                    </div>
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        <i class="bi bi-check-lg me-2"></i>Reset Password
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
