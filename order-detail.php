<?php
require_once 'config/database.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$orderNumber = $_GET['order'] ?? '';
if (empty($orderNumber)) redirect(SITE_URL . '/orders.php');

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id WHERE o.order_number = ? AND o.user_id = ?");
$stmt->execute([$orderNumber, $_SESSION['user_id']]);
$order = $stmt->fetch();

if (!$order) { setFlash('error', 'Order not found.'); redirect(SITE_URL . '/orders.php'); }

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$order['id']]);
$items = $items->fetchAll();

$statusColors = ['pending' => 'warning', 'approved' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
$paymentColors = ['pending' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'];
$steps = ['pending', 'approved', 'shipped', 'delivered'];
$currentStep = array_search($order['order_status'], $steps);
if ($currentStep === false) $currentStep = 0;

$pageTitle = 'Order #' . $order['order_number'];
require_once 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="orders.php">My Orders</a></li>
            <li class="breadcrumb-item active">#<?= sanitize($order['order_number']) ?></li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="bi bi-receipt me-2"></i>Order #<?= sanitize($order['order_number']) ?></h2>
        <span class="badge bg-<?= $statusColors[$order['order_status']] ?> fs-6 px-3 py-2"><?= ucfirst($order['order_status']) ?></span>
    </div>

    <!-- Order Tracking -->
    <?php if ($order['order_status'] !== 'cancelled'): ?>
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-4">
            <div class="order-tracker">
                <div class="d-flex justify-content-between position-relative">
                    <div class="tracker-line"></div>
                    <div class="tracker-progress" style="width: <?= ($currentStep / 3) * 100 ?>%"></div>
                    <?php foreach ($steps as $idx => $step): ?>
                    <div class="tracker-step text-center <?= $idx <= $currentStep ? 'active' : '' ?>">
                        <div class="tracker-dot <?= $idx <= $currentStep ? 'bg-primary' : 'bg-secondary' ?>">
                            <?php if ($idx < $currentStep): ?>
                            <i class="bi bi-check-lg text-white"></i>
                            <?php elseif ($idx === $currentStep): ?>
                            <i class="bi bi-circle-fill text-white small"></i>
                            <?php endif; ?>
                        </div>
                        <small class="d-block mt-2 fw-semibold <?= $idx <= $currentStep ? 'text-primary' : 'text-muted' ?>"><?= ucfirst($step) ?></small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Order Items -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h5 class="mb-0">Order Items</h5></div>
                <div class="card-body p-0">
                    <table class="table mb-0">
                        <thead class="bg-light">
                            <tr><th class="ps-3">Product</th><th>Price</th><th>Qty</th><th>Subtotal</th></tr>
                        </thead>
                        <tbody>
                        <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="ps-3"><?= sanitize($item['product_name']) ?></td>
                            <td><?= formatPrice($item['price']) ?></td>
                            <td><?= $item['quantity'] ?></td>
                            <td class="fw-bold"><?= formatPrice($item['subtotal']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Summary -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Order Date</span>
                        <span><?= date('M d, Y', strtotime($order['created_at'])) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Status</span>
                        <span class="badge bg-<?= $paymentColors[$order['payment_status']] ?>"><?= ucfirst($order['payment_status']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Payment Ref</span>
                        <span class="fw-semibold"><?= sanitize($order['payment_reference']) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="h5 mb-0">Total</span>
                        <span class="h5 mb-0 text-primary fw-bold"><?= formatPrice($order['total_amount']) ?></span>
                    </div>
                </div>
            </div>

            <!-- Shipping Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-geo-alt me-2"></i>Delivery Info</h5>
                    <p class="mb-1"><strong><?= sanitize($order['full_name']) ?></strong></p>
                    <p class="mb-1"><?= nl2br(sanitize($order['shipping_address'])) ?></p>
                    <p class="mb-0 text-muted"><i class="bi bi-telephone me-1"></i><?= sanitize($order['phone']) ?></p>
                    <?php if (!empty($order['notes'])): ?>
                    <hr>
                    <small class="text-muted"><strong>Notes:</strong> <?= sanitize($order['notes']) ?></small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
