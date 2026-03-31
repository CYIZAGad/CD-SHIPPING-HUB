<?php
require_once 'config/database.php';

if (isLoggedIn()) redirect(SITE_URL);

$errors = [];
$captchaQuestion = getCaptchaQuestion('login');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipKey = 'login:' . getRequestIp();
    if (!rateLimitCheck($ipKey, 10, 300)) {
        $errors[] = 'Too many login attempts. Please wait a few minutes and try again.';
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'] ?? '';
        $captcha = $_POST['captcha_answer'] ?? '';

        if (!$email) $errors[] = 'Valid email is required.';
        if (empty($password)) $errors[] = 'Password is required.';
        if (!verifyCaptchaChallenge('login', $captcha)) $errors[] = 'Captcha verification failed.';

        if ($email && isAccountLocked($email)) {
            $errors[] = 'Account temporarily locked due to failed attempts. Try again in 15 minutes.';
        }

        if (empty($errors)) {
            $pdo = getDBConnection();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['full_name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];

                session_regenerate_id(true);
                clearFailedLogin($email);

                if ($user['role'] === 'admin') {
                    redirect(ADMIN_URL . '/index.php');
                } else {
                    setFlash('success', 'Welcome back, ' . sanitize($user['full_name']) . '!');
                    redirect(SITE_URL);
                }
            } else {
                if ($email) {
                    registerFailedLogin($email);
                }
                $errors[] = 'Invalid email or password.';
            }
        }
    }
    $captchaQuestion = getCaptchaQuestion('login');
}

$pageTitle = 'Login';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5 col-md-7">
            <div class="auth-card">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock display-4 text-primary"></i>
                    <h3 class="mt-2">Welcome Back</h3>
                    <p class="text-muted">Login to your CD SHIPPING HUB account</p>
                </div>

                <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>

                <form method="POST" novalidate>
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-envelope me-1"></i>Email Address</label>
                        <input type="email" class="form-control form-control-lg" name="email" 
                               value="<?= sanitize($_POST['email'] ?? '') ?>" required autofocus>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-lock me-1"></i>Password</label>
                        <input type="password" class="form-control form-control-lg" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><i class="bi bi-shield-lock me-1"></i>Captcha: Solve <?= e($captchaQuestion) ?></label>
                        <input type="text" class="form-control form-control-lg" name="captcha_answer" required>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input type="checkbox" class="form-check-input" id="remember">
                            <label class="form-check-label" for="remember">Remember me</label>
                        </div>
                        <a href="forgot-password.php" class="text-primary">Forgot Password?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                        <i class="bi bi-box-arrow-in-right me-2"></i>Login
                    </button>
                    
                    <p class="text-center text-muted">
                        Don't have an account? <a href="register.php" class="text-primary fw-bold">Sign up here</a>
                    </p>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
