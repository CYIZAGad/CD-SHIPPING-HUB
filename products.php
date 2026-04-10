<?php
require_once 'config/database.php';
$pdo = getDBConnection();

// Get categories for filtering
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Build query
$where = ["p.status = 'active'"];
$params = [];

// Category filter
if (!empty($_GET['category'])) {
    $where[] = "c.slug = ?";
    $params[] = $_GET['category'];
}

// Search filter
if (!empty($_GET['search'])) {
    $where[] = "(p.name LIKE ? OR p.description LIKE ?)";
    $searchTerm = '%' . $_GET['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Price filter
if (!empty($_GET['min_price'])) {
    $where[] = "p.price >= ?";
    $params[] = (float)$_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $where[] = "p.price <= ?";
    $params[] = (float)$_GET['max_price'];
}

// Stock filter
if (!empty($_GET['in_stock'])) {
    $where[] = "p.stock > 0";
}

// Sort
$sortOptions = [
    'newest' => 'p.created_at DESC',
    'price_low' => 'p.price ASC',
    'price_high' => 'p.price DESC',
    'name' => 'p.name ASC',
];
$sort = $sortOptions[$_GET['sort'] ?? 'newest'] ?? 'p.created_at DESC';

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;
$offset = ($page - 1) * $perPage;

$whereSQL = implode(' AND ', $where);

// Count total
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereSQL");
$countStmt->execute($params);
$totalProducts = $countStmt->fetchColumn();
$totalPages = ceil($totalProducts / $perPage);

// Get products
$stmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE $whereSQL ORDER BY $sort LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$products = $stmt->fetchAll();

$pageTitle = 'Products';
$pageDescription = 'Browse our wide selection of products. Find electronics, cars, laptops, and more with the best prices.';
$pageKeywords = 'products, electronics, shopping, deals';
$canonicalUrl = SITE_URL . 'products.php';

if (!empty($_GET['category'])) {
    foreach ($categories as $cat) {
        if ($cat['slug'] === $_GET['category']) {
            $pageTitle = $cat['name'];
            $pageDescription = 'Browse ' . $cat['name']  . ' products at CD SHIPPING HUB. Find the best prices and quality products.';
            $pageKeywords = strtolower($cat['name']) . ', products, electronics, shopping';
            $canonicalUrl = SITE_URL . 'products.php?category=' . urlencode($cat['slug']);
            break;
        }
    }
}
if (!empty($_GET['search'])) {
    $searchQuery = sanitize($_GET['search']);
    $pageTitle = 'Search: ' . $searchQuery;
    $pageDescription = 'Search results for "' . $searchQuery . '" at CD SHIPPING HUB';
    $pageKeywords = $searchQuery . ', products, search results';
    $canonicalUrl = SITE_URL . 'products.php?search=' . urlencode($_GET['search']);
}

require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active"><?= $pageTitle ?></li>
        </ol>
    </nav>

    <div class="row">
        <!-- Sidebar Filters -->
        <div class="col-lg-3 mb-4">
            <div class="filter-card card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3"><i class="bi bi-funnel me-2"></i>Filters</h5>
                    <form method="GET" id="filterForm">
                        <?php if (!empty($_GET['search'])): ?>
                        <input type="hidden" name="search" value="<?= sanitize($_GET['search']) ?>">
                        <?php endif; ?>

                        <!-- Categories -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-2">Categories</h6>
                            <div class="list-group list-group-flush">
                                <a href="products.php" class="list-group-item list-group-item-action border-0 px-0 <?= empty($_GET['category']) ? 'fw-bold text-primary' : '' ?>">
                                    All Categories
                                </a>
                                <?php foreach ($categories as $cat): ?>
                                <a href="products.php?category=<?= $cat['slug'] ?>" 
                                   class="list-group-item list-group-item-action border-0 px-0 <?= ($_GET['category'] ?? '') === $cat['slug'] ? 'fw-bold text-primary' : '' ?>">
                                    <i class="bi <?= $cat['icon'] ?> me-2"></i><?= sanitize($cat['name']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Price Range -->
                        <div class="mb-4">
                            <h6 class="fw-semibold mb-2">Price Range</h6>
                            <div class="row g-2">
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" name="min_price" 
                                           placeholder="Min" value="<?= sanitize($_GET['min_price'] ?? '') ?>">
                                </div>
                                <div class="col-6">
                                    <input type="number" class="form-control form-control-sm" name="max_price" 
                                           placeholder="Max" value="<?= sanitize($_GET['max_price'] ?? '') ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Availability -->
                        <div class="mb-4">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="in_stock" value="1" id="inStock"
                                       <?= !empty($_GET['in_stock']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="inStock">In Stock Only</label>
                            </div>
                        </div>

                        <?php if (!empty($_GET['category'])): ?>
                        <input type="hidden" name="category" value="<?= sanitize($_GET['category']) ?>">
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-100"><i class="bi bi-filter me-2"></i>Apply Filters</button>
                        <a href="products.php" class="btn btn-outline-secondary w-100 mt-2">Clear All</a>
                    </form>
                </div>
            </div>
        </div>

        <!-- Product Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <p class="mb-0 text-muted">Showing <?= count($products) ?> of <?= $totalProducts ?> products</p>
                <div class="d-flex align-items-center gap-2">
                    <label class="form-label mb-0 text-nowrap">Sort by:</label>
                    <select class="form-select form-select-sm" id="sortSelect" style="width:auto">
                        <option value="newest" <?= ($_GET['sort'] ?? '') === 'newest' ? 'selected' : '' ?>>Newest</option>
                        <option value="price_low" <?= ($_GET['sort'] ?? '') === 'price_low' ? 'selected' : '' ?>>Price: Low to High</option>
                        <option value="price_high" <?= ($_GET['sort'] ?? '') === 'price_high' ? 'selected' : '' ?>>Price: High to Low</option>
                        <option value="name" <?= ($_GET['sort'] ?? '') === 'name' ? 'selected' : '' ?>>Name A-Z</option>
                    </select>
                </div>
            </div>

            <?php if (empty($products)): ?>
            <div class="text-center py-5">
                <i class="bi bi-search display-1 text-muted"></i>
                <h4 class="mt-3">No Products Found</h4>
                <p class="text-muted">Try adjusting your filters or search terms.</p>
                <a href="products.php" class="btn btn-primary">Browse All Products</a>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                <div class="col-6 col-md-4">
                    <?php include 'includes/product-card.php'; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <nav class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.getElementById('sortSelect').addEventListener('change', function() {
    const url = new URL(window.location);
    url.searchParams.set('sort', this.value);
    url.searchParams.delete('page');
    window.location = url;
});
</script>

<?php require_once 'includes/footer.php'; ?>
