<?php
$pageTitle = 'Orders';
require_once 'includes/header.php';

$pdo = getDBConnection();

// Filters
$status = $_GET['status'] ?? '';
$payment = $_GET['payment'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if (!empty($status)) { $where[] = "o.order_status = ?"; $params[] = $status; }
if (!empty($payment)) { $where[] = "o.payment_status = ?"; $params[] = $payment; }
if (!empty($search)) { $where[] = "(o.order_number LIKE ? OR u.full_name LIKE ?)"; $params[] = "%$search%"; $params[] = "%$search%"; }

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM orders o JOIN users u ON o.user_id = u.id $whereSQL");
$countStmt->execute($params);
$totalOrders = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalOrders / $perPage));

$orders = $pdo->prepare("SELECT o.*, u.full_name, u.email FROM orders o JOIN users u ON o.user_id = u.id $whereSQL ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset");
$orders->execute($params);
$orders = $orders->fetchAll();

$statusColors = ['pending' => 'warning', 'approved' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
$paymentColors = ['pending' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Showing <?= count($orders) ?> of <?= $totalOrders ?> orders</p>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" class="form-control" name="search" placeholder="Search order # or name..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <option value="">All Statuses</option>
                    <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                    <option value="shipped" <?= $status === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                    <option value="delivered" <?= $status === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                </select>
            </div>
            <div class="col-md-3">
                <select class="form-select" name="payment">
                    <option value="">All Payments</option>
                    <option value="pending" <?= $payment === 'pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="confirmed" <?= $payment === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                    <option value="rejected" <?= $payment === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment Ref</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="ps-3 fw-bold"><?= sanitize($order['order_number']) ?></td>
                        <td>
                            <?= sanitize($order['full_name']) ?>
                            <br><small class="text-muted"><?= sanitize($order['email']) ?></small>
                        </td>
                        <td class="fw-bold"><?= formatPrice($order['total_amount']) ?></td>
                        <td><small><?= sanitize($order['payment_reference'] ?? 'N/A') ?></small></td>
                        <td><span class="badge bg-<?= $paymentColors[$order['payment_status']] ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                        <td><span class="badge bg-<?= $statusColors[$order['order_status']] ?>"><?= ucfirst($order['order_status']) ?></span></td>
                        <td class="small text-muted"><?= date('M d, Y H:i', strtotime($order['created_at'])) ?></td>
                        <td>
                            <a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i>View</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($orders)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No orders found</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php if ($totalPages > 1): ?>
<nav class="mt-4">
    <ul class="pagination justify-content-center">
        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">&laquo;</a>
        </li>
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
        </li>
        <?php endfor; ?>
        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">&raquo;</a>
        </li>
    </ul>
</nav>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>
