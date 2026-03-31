<?php
$pageTitle = 'Customers';
require_once 'includes/header.php';

$pdo = getDBConnection();

$search = $_GET['search'] ?? '';
$where = "WHERE u.role = 'client'";
$params = [];

if (!empty($search)) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM users u $where");
$countStmt->execute($params);
$totalCustomers = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCustomers / $perPage));

$customers = $pdo->prepare("
    SELECT u.*, 
           COUNT(DISTINCT o.id) as order_count, 
           COALESCE(SUM(o.total_amount), 0) as total_spent
    FROM users u 
    LEFT JOIN orders o ON u.id = o.user_id 
    $where 
    GROUP BY u.id 
    ORDER BY u.created_at DESC
    LIMIT $perPage OFFSET $offset
");
$customers->execute($params);
$customers = $customers->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <p class="text-muted mb-0">Showing <?= count($customers) ?> of <?= $totalCustomers ?> registered customers</p>
</div>

<!-- Search -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-9">
                <input type="text" class="form-control" name="search" placeholder="Search by name, email, or phone..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Orders</th>
                        <th>Total Spent</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $c): ?>
                    <tr>
                        <td class="ps-3"><?= $c['id'] ?></td>
                        <td><strong><?= sanitize($c['full_name']) ?></strong></td>
                        <td><a href="mailto:<?= sanitize($c['email']) ?>"><?= sanitize($c['email']) ?></a></td>
                        <td><?= sanitize($c['phone']) ?></td>
                        <td><small class="text-muted"><?= sanitize(substr($c['address'], 0, 40)) ?><?= strlen($c['address']) > 40 ? '...' : '' ?></small></td>
                        <td><span class="badge bg-primary"><?= $c['order_count'] ?></span></td>
                        <td class="fw-bold"><?= formatPrice($c['total_spent']) ?></td>
                        <td class="small text-muted"><?= date('M d, Y', strtotime($c['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($customers)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">No customers found</td></tr>
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
