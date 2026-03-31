<?php
// Process all logic BEFORE including header (which outputs HTML)
require_once '../config/database.php';

// Check admin access FIRST
if (!isAdmin()) {
    setFlash('error', 'Access denied.');
    redirect(SITE_URL . '/login.php');
}

$pageTitle = 'Products';
$pdo = getDBConnection();

// Handle delete (POST only)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete_product') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('error', 'Invalid request.');
    } else {
        $deleteId = (int)($_POST['product_id'] ?? 0);
        if ($deleteId > 0) {
            $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$deleteId]);
            setFlash('success', 'Product deleted successfully.');
        }
    }
    redirect(ADMIN_URL . '/products.php');
}

// Now include header AFTER all processing and potential redirects
require_once './includes/header.php';

// Get products
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$where = [];
$params = [];

if (!empty($search)) {
    $where[] = "p.name LIKE ?";
    $params[] = "%$search%";
}
if (!empty($category)) {
    $where[] = "p.category_id = ?";
    $params[] = (int)$category;
}

$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 25;
$offset = ($page - 1) * $perPage;

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id $whereSQL");
$countStmt->execute($params);
$totalProducts = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalProducts / $perPage));

$products = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id $whereSQL ORDER BY p.created_at DESC LIMIT $perPage OFFSET $offset");
$products->execute($params);
$products = $products->fetchAll();

$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted mb-0">Showing <?= count($products) ?> of <?= $totalProducts ?> products</p>
    </div>
    <a href="product-form.php" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Product</a>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-5">
                <input type="text" class="form-control" name="search" placeholder="Search products..." value="<?= sanitize($search) ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="category">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>><?= sanitize($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100"><i class="bi bi-search me-1"></i>Search</button>
            </div>
        </form>
    </div>
</div>

<!-- Products Table -->
<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3" style="width:50px">ID</th>
                        <th style="width:60px">Image</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Featured</th>
                        <th style="width:120px">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $p):
                        $imgSrc = (!empty($p['image']) && file_exists(__DIR__ . '/../uploads/products/' . $p['image']))
                            ? UPLOAD_URL . $p['image']
                            : 'https://placehold.co/50x50/e3f2fd/1976d2?text=IMG';
                    ?>
                    <tr>
                        <td class="ps-3"><?= $p['id'] ?></td>
                        <td><img src="<?= $imgSrc ?>" class="rounded" width="45" height="45" style="object-fit:cover" alt=""></td>
                        <td>
                            <strong><?= sanitize($p['name']) ?></strong>
                            <br><small class="text-muted"><?= $p['slug'] ?></small>
                        </td>
                        <td><span class="badge bg-primary-subtle text-primary"><?= sanitize($p['category_name']) ?></span></td>
                        <td>
                            <strong><?= formatPrice($p['price']) ?></strong>
                            <?php if ($p['old_price']): ?><br><small class="text-decoration-line-through text-muted"><?= formatPrice($p['old_price']) ?></small><?php endif; ?>
                        </td>
                        <td>
                            <span class="badge <?= $p['stock'] > 0 ? 'bg-success' : 'bg-danger' ?>"><?= $p['stock'] ?></span>
                        </td>
                        <td><span class="badge bg-<?= $p['status'] === 'active' ? 'success' : 'secondary' ?>"><?= ucfirst($p['status']) ?></span></td>
                        <td><?= $p['featured'] ? '<i class="bi bi-star-fill text-warning"></i>' : '<i class="bi bi-star text-muted"></i>' ?></td>
                        <td>
                            <a href="product-form.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-primary me-1" title="Edit"><i class="bi bi-pencil"></i></a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this product?')">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete_product">
                                <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($products)): ?>
                    <tr><td colspan="9" class="text-center py-4 text-muted">No products found</td></tr>
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
