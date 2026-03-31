<?php
$pageTitle = 'Dashboard';
require_once 'includes/header.php';

$pdo = getDBConnection();

// Dashboard stats
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalOrders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingPayments = $pdo->query("SELECT COUNT(*) FROM orders WHERE payment_status = 'pending'")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM orders WHERE payment_status = 'confirmed'")->fetchColumn();
$totalCustomers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'client'")->fetchColumn();

// Recent orders
$recentOrders = $pdo->query("SELECT o.*, u.full_name FROM orders o JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 10")->fetchAll();

// Monthly revenue for chart (last 6 months)
$monthlyRevenue = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, 
           SUM(CASE WHEN payment_status = 'confirmed' THEN total_amount ELSE 0 END) as revenue,
           COUNT(*) as order_count 
    FROM orders 
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m') 
    ORDER BY month
")->fetchAll();

// Order status distribution
$orderStatuses = $pdo->query("SELECT order_status, COUNT(*) as count FROM orders GROUP BY order_status")->fetchAll();

$statusColors = ['pending' => 'warning', 'approved' => 'info', 'shipped' => 'primary', 'delivered' => 'success', 'cancelled' => 'danger'];
$paymentColors = ['pending' => 'warning', 'confirmed' => 'success', 'rejected' => 'danger'];
?>

<!-- Stats Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-6 col-xl-3">
        <div class="stat-card bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-75">Total Products</p>
                    <h3 class="mb-0 fw-bold"><?= number_format($totalProducts) ?></h3>
                </div>
                <i class="bi bi-box display-6 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card bg-success text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-75">Total Orders</p>
                    <h3 class="mb-0 fw-bold"><?= number_format($totalOrders) ?></h3>
                </div>
                <i class="bi bi-bag-check display-6 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card bg-warning text-dark">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-75">Pending Payments</p>
                    <h3 class="mb-0 fw-bold"><?= number_format($pendingPayments) ?></h3>
                </div>
                <i class="bi bi-clock-history display-6 opacity-50"></i>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-xl-3">
        <div class="stat-card bg-info text-white">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <p class="mb-1 opacity-75">Revenue</p>
                    <h3 class="mb-0 fw-bold"><?= formatPrice($totalRevenue) ?></h3>
                </div>
                <i class="bi bi-currency-dollar display-6 opacity-50"></i>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold">Revenue Overview (Last 6 Months)</h6>
            </div>
            <div class="card-body">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0 fw-bold">Order Status</h6>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <canvas id="statusChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Quick Stats -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <i class="bi bi-people display-5 text-primary"></i>
            <h4 class="fw-bold mt-2 mb-0"><?= number_format($totalCustomers) ?></h4>
            <small class="text-muted">Registered Customers</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <i class="bi bi-graph-up-arrow display-5 text-success"></i>
            <h4 class="fw-bold mt-2 mb-0"><?= number_format($totalOrders > 0 ? $totalRevenue / $totalOrders : 0, 2) ?></h4>
            <small class="text-muted">Avg Order Value</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm text-center p-3">
            <i class="bi bi-truck display-5 text-warning"></i>
            <h4 class="fw-bold mt-2 mb-0"><?= $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'shipped'")->fetchColumn() ?></h4>
            <small class="text-muted">In Transit</small>
        </div>
    </div>
</div>

<!-- Recent Orders -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold">Recent Orders</h6>
        <a href="orders.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Order #</th>
                        <th>Customer</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th>Date</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td class="ps-3 fw-semibold"><?= sanitize($order['order_number']) ?></td>
                        <td><?= sanitize($order['full_name']) ?></td>
                        <td class="fw-bold"><?= formatPrice($order['total_amount']) ?></td>
                        <td><span class="badge bg-<?= $paymentColors[$order['payment_status']] ?>"><?= ucfirst($order['payment_status']) ?></span></td>
                        <td><span class="badge bg-<?= $statusColors[$order['order_status']] ?>"><?= ucfirst($order['order_status']) ?></span></td>
                        <td class="text-muted small"><?= date('M d, H:i', strtotime($order['created_at'])) ?></td>
                        <td><a href="order-detail.php?id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                    <tr><td colspan="7" class="text-center py-4 text-muted">No orders yet</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const months = <?= json_encode(array_map(function($m) { return date('M Y', strtotime($m['month'] . '-01')); }, $monthlyRevenue)) ?>;
    const revenues = <?= json_encode(array_map(function($m) { return (float)$m['revenue']; }, $monthlyRevenue)) ?>;
    const orderCounts = <?= json_encode(array_map(function($m) { return (int)$m['order_count']; }, $monthlyRevenue)) ?>;

    if (document.getElementById('revenueChart')) {
        new Chart(document.getElementById('revenueChart'), {
            type: 'bar',
            data: {
                labels: months.length ? months : ['No Data'],
                datasets: [{
                    label: 'Revenue ($)',
                    data: revenues.length ? revenues : [0],
                    backgroundColor: 'rgba(13, 110, 253, 0.8)',
                    borderRadius: 8
                }, {
                    label: 'Orders',
                    data: orderCounts.length ? orderCounts : [0],
                    type: 'line',
                    borderColor: '#198754',
                    tension: 0.3,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, title: { display: true, text: 'Revenue ($)' }},
                    y1: { position: 'right', beginAtZero: true, title: { display: true, text: 'Orders' }, grid: { drawOnChartArea: false }}
                }
            }
        });
    }

    // Status Chart
    const statuses = <?= json_encode(array_column($orderStatuses, 'order_status')) ?>;
    const counts = <?= json_encode(array_map('intval', array_column($orderStatuses, 'count'))) ?>;
    const colors = { pending: '#ffc107', approved: '#0dcaf0', shipped: '#0d6efd', delivered: '#198754', cancelled: '#dc3545' };

    if (document.getElementById('statusChart')) {
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: statuses.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
                datasets: [{
                    data: counts.length ? counts : [1],
                    backgroundColor: statuses.length ? statuses.map(s => colors[s] || '#6c757d') : ['#e9ecef']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' }}}
        });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
