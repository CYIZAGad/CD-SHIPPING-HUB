<?php
require_once 'config/database.php';

if (!isLoggedIn()) {
    setFlash('error', 'Please login to checkout.');
    redirect(SITE_URL . '/login.php');
}

$cart = $_SESSION['cart'] ?? [];
if (empty($cart)) redirect(SITE_URL . '/cart.php');

$pdo = getDBConnection();
$user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}
$shipping = $total >= 500 ? 0 : 25;
$grandTotal = $total + $shipping;

$errors = [];
$captchaQuestion = getCaptchaQuestion('checkout_payment');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ipKey = 'checkout_payment:' . getRequestIp();
    if (!rateLimitCheck($ipKey, 8, 300)) {
        $errors[] = 'Too many payment submissions. Please wait a few minutes and try again.';
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $address = sanitize($_POST['shipping_address'] ?? '');
        $phone = sanitize($_POST['phone'] ?? '');
        $paymentRef = sanitize($_POST['payment_reference'] ?? '');
        $notes = sanitize($_POST['notes'] ?? '');
        $captcha = $_POST['captcha_answer'] ?? '';

        if (empty($address)) $errors[] = 'Shipping address is required.';
        if (empty($phone)) $errors[] = 'Phone number is required.';
        if (empty($paymentRef)) $errors[] = 'Payment reference is required.';
        if (!verifyCaptchaChallenge('checkout_payment', $captcha)) $errors[] = 'Captcha verification failed.';

        if (empty($errors)) {
            $pdo->beginTransaction();
            try {
                // Generate order number
                $orderNumber = 'CD' . date('Ymd') . strtoupper(bin2hex(random_bytes(3)));

                // Create order
                $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, shipping_address, phone, payment_reference, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$_SESSION['user_id'], $orderNumber, $grandTotal, $address, $phone, $paymentRef, $notes]);
                $orderId = $pdo->lastInsertId();

                // Create order items and reduce stock
                foreach ($cart as $item) {
                    $subtotal = $item['price'] * $item['qty'];
                    $stmt = $pdo->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity, subtotal) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$orderId, $item['id'], $item['name'], $item['price'], $item['qty'], $subtotal]);
                }

                // Create notification
                $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_SESSION['user_id'],
                    'Order Placed Successfully',
                    "Your order #$orderNumber has been placed. Total: " . formatPrice($grandTotal) . ". We will process your payment shortly."
                ]);

                $pdo->commit();

                // Clear cart
                $_SESSION['cart'] = [];

                // Redirect to confirmation
                redirect(SITE_URL . '/order-confirmation.php?order=' . $orderNumber);

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Failed to place order. Please try again.';
            }
        }
    }
    $captchaQuestion = getCaptchaQuestion('checkout_payment');
}

$pageTitle = 'Checkout';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="cart.php">Cart</a></li>
            <li class="breadcrumb-item active">Checkout</li>
        </ol>
    </nav>

    <h2 class="fw-bold mb-4"><i class="bi bi-credit-card me-2"></i>Checkout</h2>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <form method="POST">
        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
        <div class="row g-4">
            <div class="col-lg-7">
                <!-- Delivery Info -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Delivery Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Full Name</label>
                            <input type="text" class="form-control" value="<?= sanitize($user['full_name']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" value="<?= sanitize($user['email']) ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" name="phone" 
                                   value="<?= sanitize($_POST['phone'] ?? $user['phone']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Shipping Address *</label>
                            <textarea class="form-control" name="shipping_address" rows="3" required><?= sanitize($_POST['shipping_address'] ?? $user['address']) ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Order Notes (optional)</label>
                            <textarea class="form-control" name="notes" rows="2" placeholder="Special delivery instructions..."><?= sanitize($_POST['notes'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Payment -->
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-wallet2 me-2 text-primary"></i>Payment Confirmation</h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <h6><i class="bi bi-info-circle me-2"></i>Payment Instructions</h6>
                            <p class="mb-2">Please transfer the total amount to one of our payment accounts:</p>
                            <ul class="mb-2">
                                <li><strong>Bank Transfer:</strong> Mucunguzi Deus - Account #: 100212186378</li>
                                <li><strong>PayPal:</strong> cdshipping@gmail.com</li>
                                <li><strong>Mobile Money:</strong> +250785008063</li>
                            </ul>
                            <p class="mb-0 small">Enter the transaction/reference number below after making payment.</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Payment Reference / Transaction ID *</label>
                            <input type="text" class="form-control" name="payment_reference" 
                                   value="<?= sanitize($_POST['payment_reference'] ?? '') ?>" 
                                   placeholder="Enter your payment reference number" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Captcha: Solve <?= e($captchaQuestion) ?></label>
                            <input type="text" class="form-control" name="captcha_answer" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Summary -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-lg-top" style="top:80px">
                    <div class="card-header bg-white">
                        <h5 class="mb-0"><i class="bi bi-receipt me-2 text-primary"></i>Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($cart as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom">
                            <div>
                                <h6 class="mb-0 small"><?= sanitize($item['name']) ?></h6>
                                <small class="text-muted">Qty: <?= $item['qty'] ?> x <?= formatPrice($item['price']) ?></small>
                            </div>
                            <span class="fw-semibold"><?= formatPrice($item['price'] * $item['qty']) ?></span>
                        </div>
                        <?php endforeach; ?>
                        
                        <div class="d-flex justify-content-between mb-2 mt-3">
                            <span class="text-muted">Subtotal</span>
                            <span><?= formatPrice($total) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Shipping</span>
                            <span class="<?= $shipping === 0 ? 'text-success' : '' ?>"><?= $shipping === 0 ? 'FREE' : formatPrice($shipping) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <span class="h5 mb-0 fw-bold">Total</span>
                            <span class="h5 mb-0 fw-bold text-primary"><?= formatPrice($grandTotal) ?></span>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-lg w-100">
                            <i class="bi bi-check-circle me-2"></i>Place Order
                        </button>
                        <a href="cart.php" class="btn btn-outline-secondary w-100 mt-2">
                            <i class="bi bi-arrow-left me-2"></i>Back to Cart
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<?php require_once 'includes/footer.php'; ?>
