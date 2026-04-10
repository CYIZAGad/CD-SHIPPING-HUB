<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$slug = $_GET['slug'] ?? '';
if (empty($slug)) redirect(SITE_URL . '/products.php');

$stmt = $pdo->prepare("SELECT p.*, c.name as category_name, c.slug as category_slug FROM products p JOIN categories c ON p.category_id = c.id WHERE p.slug = ? AND p.status = 'active'");
$stmt->execute([$slug]);
$product = $stmt->fetch();

if (!$product) {
    setFlash('error', 'Product not found.');
    redirect(SITE_URL . '/products.php');
}

// Related products
$relStmt = $pdo->prepare("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' ORDER BY p.created_at DESC LIMIT 4");
$relStmt->execute([$product['category_id'], $product['id']]);
$related = $relStmt->fetchAll();

// Main image - use helper that gets from filesystem or database
$imgSrc = getProductImage($product, 'image');

// Build image gallery - try filesystem first, fallback to database
$images = [$imgSrc];
for ($i = 2; $i <= 3; $i++) {
    $key = "image$i";
    if (!empty($product[$key]) || !empty($product[$key . '_base64'])) {
        $images[] = getProductImage($product, $key);
    }
}

$specs = [];
if (!empty($product['specifications'])) {
    foreach (explode('|', $product['specifications']) as $spec) {
        $parts = explode(':', $spec, 2);
        if (count($parts) === 2) {
            $specs[trim($parts[0])] = trim($parts[1]);
        }
    }
}

$pageTitle = $product['name'];

// SEO Meta Tags
$pageDescription = substr(strip_tags($product['description']), 0, 160) ?: $product['name'] . ' - Premium quality product at CD SHIPPING HUB';
$pageKeywords = $product['name'] . ', ' . $product['category_name'] . ', ' . (isset($specs[0]) ? implode(', ', array_slice(array_keys($specs), 0, 3)) : 'shopping');
$canonicalUrl = SITE_URL . 'product.php?slug=' . urlencode($product['slug']);
$ogImage = getProductImage($product, 'image'); // Use actual product image
$ogType = 'product';

require_once 'includes/header.php';
?>

<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item"><a href="products.php?category=<?= $product['category_slug'] ?>"><?= sanitize($product['category_name']) ?></a></li>
            <li class="breadcrumb-item active"><?= sanitize($product['name']) ?></li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Product Images -->
        <div class="col-lg-6">
            <div class="product-gallery">
                <div class="main-image mb-3">
                    <img src="<?= $images[0] ?>" class="img-fluid rounded-3 w-100" id="mainImage" alt="<?= sanitize($product['name']) ?>">
                </div>
                <?php if (count($images) > 1): ?>
                <div class="d-flex gap-2 thumbnail-row">
                    <?php foreach ($images as $idx => $img): ?>
                    <img src="<?= $img ?>" class="img-thumbnail thumbnail-img <?= $idx === 0 ? 'active' : '' ?>" 
                         onclick="document.getElementById('mainImage').src=this.src; document.querySelectorAll('.thumbnail-img').forEach(t=>t.classList.remove('active')); this.classList.add('active');"
                         alt="Thumbnail <?= $idx + 1 ?>">
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Product Info -->
        <div class="col-lg-6">
            <div class="product-info">
                <span class="badge bg-primary mb-2"><?= sanitize($product['category_name']) ?></span>
                <h2 class="fw-bold mb-2"><?= sanitize($product['name']) ?></h2>
                
                <div class="d-flex align-items-center gap-3 mb-3">
                    <span class="h3 text-primary fw-bold mb-0"><?= formatPrice($product['price']) ?></span>
                    <?php if ($product['old_price']): ?>
                    <span class="h5 text-muted text-decoration-line-through mb-0"><?= formatPrice($product['old_price']) ?></span>
                    <span class="badge bg-danger">Save <?= formatPrice($product['old_price'] - $product['price']) ?></span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <?php if ($product['stock'] > 0): ?>
                    <span class="badge bg-success-subtle text-success px-3 py-2">
                        <i class="bi bi-check-circle me-1"></i> In Stock (<?= $product['stock'] ?> available)
                    </span>
                    <?php else: ?>
                    <span class="badge bg-danger-subtle text-danger px-3 py-2">
                        <i class="bi bi-x-circle me-1"></i> Out of Stock
                    </span>
                    <?php endif; ?>
                </div>

                <p class="text-muted mb-4"><?= nl2br(sanitize($product['description'])) ?></p>

                <?php if ($product['stock'] > 0): ?>
                <form action="cart-action.php" method="POST" class="mb-4">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <label class="form-label mb-0 fw-semibold">Quantity:</label>
                        <div class="input-group" style="width:140px">
                            <button type="button" class="btn btn-outline-secondary" onclick="changeQty(-1)">-</button>
                            <input type="number" class="form-control text-center" name="quantity" id="qty" value="1" min="1" max="<?= $product['stock'] ?>">
                            <button type="button" class="btn btn-outline-secondary" onclick="changeQty(1)">+</button>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary btn-lg flex-grow-1">
                            <i class="bi bi-cart-plus me-2"></i>Add to Cart
                        </button>
                    </div>
                </form>
                <?php endif; ?>

                <!-- Specifications -->
                <?php if (!empty($specs)): ?>
                <div class="specifications mt-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-list-check me-2"></i>Specifications</h5>
                    <table class="table table-striped">
                        <tbody>
                        <?php foreach ($specs as $key => $val): ?>
                        <tr>
                            <th class="text-muted" style="width:40%"><?= sanitize($key) ?></th>
                            <td><?= sanitize($val) ?></td>
                        </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>

                <!-- Guarantees -->
                <div class="row g-2 mt-3">
                    <div class="col-4 text-center">
                        <div class="p-2 bg-light rounded"><i class="bi bi-truck text-primary"></i><br><small>Free Shipping</small></div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="p-2 bg-light rounded"><i class="bi bi-shield-check text-success"></i><br><small>Warranty</small></div>
                    </div>
                    <div class="col-4 text-center">
                        <div class="p-2 bg-light rounded"><i class="bi bi-arrow-repeat text-warning"></i><br><small>Easy Returns</small></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Related Products -->
    <?php if (!empty($related)): ?>
    <section class="mt-5">
        <h3 class="fw-bold mb-4">Related Products</h3>
        <div class="row g-4">
            <?php foreach ($related as $product): ?>
            <div class="col-6 col-md-3">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
function changeQty(delta) {
    const qty = document.getElementById('qty');
    const newVal = parseInt(qty.value) + delta;
    if (newVal >= parseInt(qty.min) && newVal <= parseInt(qty.max)) {
        qty.value = newVal;
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>
