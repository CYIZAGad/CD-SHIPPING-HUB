<?php
require_once 'config/database.php';

if (isLoggedIn()) redirect(SITE_URL);

$errors = [];
$success = false;
$captchaQuestion = getCaptchaQuestion('forgot_password');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipKey = 'forgot_password:' . getRequestIp();
    if (!rateLimitCheck($ipKey, 5, 300)) {
        $errors[] = 'Too many requests. Please wait a few minutes and try again.';
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $captcha = $_POST['captcha_answer'] ?? '';

        if (!verifyCaptchaChallenge('forgot_password', $captcha)) {
            $errors[] = 'Captcha verification failed.';
        }

        if (!$email) {
            $errors[] = 'Valid email is required.';
        } elseif (empty($errors)) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                $token = bin2hex(random_bytes(32));
                $tokenHash = hashResetToken($token);
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expires = ? WHERE id = ?");
                $stmt->execute([$tokenHash, $expires, $user['id']]);
                $resetUrl = SITE_URL . "/reset-password.php?token=$token";
                sendPasswordResetEmail($email, $resetUrl);
            }
            // Always show success to prevent email enumeration
            $success = true;
        }
    }
    $captchaQuestion = getCaptchaQuestion('forgot_password');
}

$pageTitle = 'Forgot Password';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <i class="bi bi-key display-4 text-warning"></i>
                    <h3 class="mt-2">Forgot Password?</h3>
                    <p class="text-muted">Enter your email to receive a reset link</p>
                </div>

                <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="bi bi-check-circle me-2"></i>If an account with that email exists, a reset link has been sent.
                </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>Email Address</label>
                        <input type="email" class="form-control form-control-lg" name="email" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield-lock me-1"></i>Captcha: Solve <?= e($captchaQuestion) ?></label>
                        <input type="text" class="form-control form-control-lg" name="captcha_answer" required>
                    </div>
                    <button type="submit" class="btn btn-warning btn-lg w-100 mb-3">
                        <i class="bi bi-send me-2"></i>Send Reset Link
                    </button>
                    <p class="text-center">
                        <a href="login.php" class="text-primary"><i class="bi bi-arrow-left me-1"></i>Back to Login</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
