<?php
require_once 'config/database.php';
if (!isLoggedIn()) { setFlash('error', 'Please login to view orders.'); redirect(SITE_URL . '/login.php'); }

$pdo = getDBConnection();
$stmt = $pdo->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$pageTitle = 'My Orders';
require_once 'includes/header.php';

$statusColors = [
    'pending' => 'warning', 'approved' => 'info', 'shipped' => 'primary', 
    'delivered' => 'success', 'cancelled' => 'danger'
];
$paymentColors = ['pending' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'];
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">My Orders</li>
        </ol>
    </nav>

    <h2 class="fw-bold mb-4"><i class="bi bi-bag-check me-2"></i>My Orders</h2>

    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bag-x display-1 text-muted"></i>
        <h4 class="mt-3">No Orders Yet</h4>
        <p class="text-muted">You haven't placed any orders yet.</p>
        <a href="products.php" class="btn btn-primary btn-lg"><i class="bi bi-bag me-2"></i>Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <?php foreach ($orders as $order): 
            $itemStmt = $pdo->prepare("SELECT COUNT(*) as count FROM order_items WHERE order_id = ?");
            $itemStmt->execute([$order['id']]);
            $itemCount = $itemStmt->fetch()['count'];
        ?>
        <div class="col-12">
            <div class="card border-0 shadow-sm order-card">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-3">
                            <small class="text-muted">Order Number</small>
                            <h6 class="mb-0 fw-bold text-primary"><?= sanitize($order['order_number']) ?></h6>
                            <small class="text-muted"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></small>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Items</small>
                            <h6 class="mb-0"><?= $itemCount ?> item(s)</h6>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Total</small>
                            <h6 class="mb-0 fw-bold"><?= formatPrice($order['total_amount']) ?></h6>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Payment</small><br>
                            <span class="badge bg-<?= $paymentColors[$order['payment_status']] ?>">
                                <?= ucfirst($order['payment_status']) ?>
                            </span>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Status</small><br>
                            <span class="badge bg-<?= $statusColors[$order['order_status']] ?>">
                                <?= ucfirst($order['order_status']) ?>
                            </span>
                        </div>
                        <div class="col-md-1 text-end">
                            <a href="order-detail.php?order=<?= $order['order_number'] ?>" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-eye"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
