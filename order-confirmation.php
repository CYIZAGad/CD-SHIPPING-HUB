<?php
require_once 'config/database.php';

if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) redirect(SITE_URL . '/orders.php');

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_number = ? AND o.user_id = ?");
$stmt->execute([$orderNumber, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) {
    setFlash('error', 'Order not found.');
    redirect(SITE_URL . '/orders.php');
}

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$pageTitle = 'Order Confirmation';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="text-center mb-4">
                <div class="success-checkmark mb-3">
                    <i class="bi bi-check-circle-fill display-1 text-success"></i>
                </div>
                <h2 class="fw-bold text-success">Order Placed Successfully!</h2>
                <p class="lead text-muted">Thank you for your order. We'll process your payment shortly.</p>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="row text-center g-3">
                        <div class="col-md-3 col-6">
                            <small class="text-muted d-block">Order Number</small>
                            <strong class="text-primary"><?= sanitize($order['order_number']) ?></strong>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-muted d-block">Date</small>
                            <strong><?= date('M d, Y', strtotime($order['created_at'])) ?></strong>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-muted d-block">Total</small>
                            <strong class="text-primary"><?= formatPrice($order['total_amount']) ?></strong>
                        </div>
                        <div class="col-md-3 col-6">
                            <small class="text-muted d-block">Payment Status</small>
                            <span class="badge bg-warning text-dark">Pending</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Order Items</h5></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr><th>Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td><?= sanitize($item['product_name']) ?></td>
                            <td><?= formatPrice($item['price']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="fw-bold"><?= formatPrice($item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr class="table-primary">
                            <td colspan="3" class="text-end fw-bold">Grand Total</td>
                            <td class="fw-bold text-primary"><?= formatPrice($order['total_amount']) ?></td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="fw-bold"><i class="bi bi-geo-alt me-2 text-primary"></i>Shipping Address</h6>
                            <p class="mb-0"><?= nl2br(sanitize($order['shipping_address'])) ?></p>
                            <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= sanitize($order['phone']) ?></small>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <h6 class="fw-bold"><i class="bi bi-wallet2 me-2 text-primary"></i>Payment Info</h6>
                            <p class="mb-0">Reference: <strong><?= sanitize($order['payment_reference']) ?></strong></p>
                            <small class="text-muted">Our team will verify your payment within 24 hours.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="alert alert-info">
                <h6><i class="bi bi-info-circle me-2"></i>What's Next?</h6>
                <ul class="mb-0">
                    <li>Our team will verify your payment within 24 hours.</li>
                    <li>You'll receive a notification once your payment is confirmed.</li>
                    <li>Track your order status in the <a href="orders.php">My Orders</a> section.</li>
                    <li>An email & SMS confirmation will be sent to your registered details.</li>
                </ul>
            </div>

            <div class="text-center">
                <a href="orders.php" class="btn btn-primary me-2"><i class="bi bi-bag-check me-2"></i>View My Orders</a>
                <a href="<?= SITE_URL ?>" class="btn btn-outline-primary"><i class="bi bi-house me-2"></i>Continue Shopping</a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
