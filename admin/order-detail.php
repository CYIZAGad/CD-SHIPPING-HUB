<?php
$pageTitle = 'Order Details';
require_once 'includes/header.php';

$pdo = getDBConnection();

$orderId = (int)($_GET['id'] ?? 0);
if (!$orderId) redirect(ADMIN_URL . '/orders.php');

$order = $pdo->prepare("SELECT o.*, u.full_name, u.email, u.phone as user_phone, u.address as user_address FROM orders o JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$order->execute([$orderId]);
$order = $order->fetch();

if (!$order) { setFlash('error', 'Order not found.'); redirect(ADMIN_URL . '/orders.php'); }

$items = $pdo->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items->execute([$orderId]);
$items = $items->fetchAll();

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'update_payment') {
        $paymentStatus = in_array($_POST['payment_status'], ['pending', 'confirmed', 'rejected']) ? $_POST['payment_status'] : 'pending';
        try {
            $pdo->beginTransaction();
            $currentStmt = $pdo->prepare("SELECT payment_status FROM orders WHERE id = ? FOR UPDATE");
            $currentStmt->execute([$orderId]);
            $current = $currentStmt->fetch();

            if (!$current) {
                throw new Exception('Order no longer exists.');
            }

            $currentStatus = $current['payment_status'];
            if ($currentStatus === 'confirmed' && $paymentStatus !== 'confirmed') {
                throw new Exception('Confirmed payments cannot be reverted.');
            }

            // Deduct stock only once, when payment is first confirmed.
            if ($currentStatus !== 'confirmed' && $paymentStatus === 'confirmed') {
                $stockItems = $pdo->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
                $stockItems->execute([$orderId]);
                $stockItems = $stockItems->fetchAll();

                foreach ($stockItems as $stockItem) {
                    $updateStock = $pdo->prepare("UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?");
                    $updateStock->execute([(int)$stockItem['quantity'], (int)$stockItem['product_id'], (int)$stockItem['quantity']]);
                    if ($updateStock->rowCount() === 0) {
                        throw new Exception('Insufficient stock to confirm this payment.');
                    }
                }
            }

            $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ?")->execute([$paymentStatus, $orderId]);
            $pdo->commit();
        } catch (Exception $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            setFlash('error', $ex->getMessage());
            redirect(ADMIN_URL . "/order-detail.php?id=$orderId");
        }
        
        // Notify customer
        $msg = $paymentStatus === 'confirmed' 
            ? "Your payment for order #{$order['order_number']} has been confirmed!" 
            : ($paymentStatus === 'rejected' ? "Your payment for order #{$order['order_number']} was rejected. Please contact support." : "");
        if ($msg) {
            $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")
                ->execute([$order['user_id'], 'Payment ' . ucfirst($paymentStatus), $msg]);
        }
        setFlash('success', 'Payment status updated.');
        redirect(ADMIN_URL . "/order-detail.php?id=$orderId");
    }
    
    if ($action === 'update_status') {
        $orderStatus = in_array($_POST['order_status'], ['pending', 'approved', 'shipped', 'delivered', 'cancelled']) ? $_POST['order_status'] : 'pending';
        $pdo->prepare("UPDATE orders SET order_status = ? WHERE id = ?")->execute([$orderStatus, $orderId]);
        
        // Notify customer
        $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")
            ->execute([$order['user_id'], 'Order ' . ucfirst($orderStatus), "Your order #{$order['order_number']} has been " . $orderStatus . "."]);
        
        setFlash('success', 'Order status updated.');
        redirect(ADMIN_URL . "/order-detail.php?id=$orderId");
    }
}

$statusColors = ['pending' => 'warning', 'approved' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
$paymentColors = ['pending' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'];
?>

<div class="mb-3">
    <a href="orders.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Orders</a>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <!-- Order Info -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Order #<?= sanitize($order['order_number']) ?></h6>
                <small class="text-muted"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></small>
            </div>
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
                    <tr class="table-primary">
                        <td colspan="3" class="text-end fw-bold ps-3">Grand Total</td>
                        <td class="fw-bold text-primary"><?= formatPrice($order['total_amount']) ?></td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Customer & Shipping -->
        <div class="row g-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="bi bi-person me-2"></i>Customer</h6></div>
                    <div class="card-body">
                        <p class="mb-1"><strong><?= sanitize($order['full_name']) ?></strong></p>
                        <p class="mb-1"><i class="bi bi-envelope me-1"></i><?= sanitize($order['email']) ?></p>
                        <p class="mb-0"><i class="bi bi-telephone me-1"></i><?= sanitize($order['user_phone']) ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="bi bi-geo-alt me-2"></i>Shipping</h6></div>
                    <div class="card-body">
                        <p class="mb-1"><?= nl2br(sanitize($order['shipping_address'])) ?></p>
                        <p class="mb-0"><i class="bi bi-telephone me-1"></i><?= sanitize($order['phone']) ?></p>
                        <?php if ($order['notes']): ?><hr><p class="mb-0 small text-muted"><strong>Notes:</strong> <?= sanitize($order['notes']) ?></p><?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <!-- Payment Status -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-2"></i>Payment</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Current Status</small><br>
                    <span class="badge bg-<?= $paymentColors[$order['payment_status']] ?> fs-6"><?= ucfirst($order['payment_status']) ?></span>
                </div>
                <div class="mb-3">
                    <small class="text-muted">Reference</small><br>
                    <strong><?= sanitize($order['payment_reference'] ?? 'N/A') ?></strong>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="update_payment">
                    <div class="mb-2">
                        <select class="form-select" name="payment_status">
                            <option value="pending" <?= $order['payment_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $order['payment_status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="rejected" <?= $order['payment_status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100 btn-sm"><i class="bi bi-check me-1"></i>Update Payment</button>
                </form>
            </div>
        </div>

        <!-- Order Status -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white"><h6 class="mb-0 fw-bold"><i class="bi bi-truck me-2"></i>Order Status</h6></div>
            <div class="card-body">
                <div class="mb-3">
                    <small class="text-muted">Current Status</small><br>
                    <span class="badge bg-<?= $statusColors[$order['order_status']] ?> fs-6"><?= ucfirst($order['order_status']) ?></span>
                </div>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="update_status">
                    <div class="mb-2">
                        <select class="form-select" name="order_status">
                            <option value="pending" <?= $order['order_status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="approved" <?= $order['order_status'] === 'approved' ? 'selected' : '' ?>>Approved</option>
                            <option value="shipped" <?= $order['order_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                            <option value="delivered" <?= $order['order_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                            <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 btn-sm"><i class="bi bi-check me-1"></i>Update Status</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
