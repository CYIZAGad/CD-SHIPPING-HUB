<?php
// Product card component - expects $product variable
// Trust the database: if image field is not empty, use it. If empty, use placeholder.
// Avoid file_exists check as it may not work reliably in containerized environments (e.g., Render)
$imgSrc = !empty($product['image'])
    ? UPLOAD_URL . $product['image']
    : 'https://placehold.co/400x300/e3f2fd/1976d2?text=' . urlencode($product['name']);
$inStock = $product['stock'] > 0;
?>
<div class="card product-card h-100 border-0 shadow-sm">
    <?php if ($product['old_price']): ?>
    <div class="product-badge">
        <span class="badge bg-danger">-<?= round((1 - $product['price']/$product['old_price']) * 100) ?>%</span>
    </div>
    <?php endif; ?>
    <a href="product.php?slug=<?= $product['slug'] ?>">
        <img src="<?= $imgSrc ?>" class="card-img-top product-img" alt="<?= sanitize($product['name']) ?>">
    </a>
    <div class="card-body d-flex flex-column">
        <small class="text-muted mb-1"><?= sanitize($product['category_name']) ?></small>
        <h6 class="card-title mb-2">
            <a href="product.php?slug=<?= $product['slug'] ?>" class="text-dark text-decoration-none product-title">
                <?= sanitize($product['name']) ?>
            </a>
        </h6>
        <div class="mt-auto">
            <div class="d-flex align-items-center mb-2">
                <span class="h5 text-primary mb-0 fw-bold"><?= formatPrice($product['price']) ?></span>
                <?php if ($product['old_price']): ?>
                <small class="text-muted text-decoration-line-through ms-2"><?= formatPrice($product['old_price']) ?></small>
                <?php endif; ?>
            </div>
            <div class="d-flex align-items-center justify-content-between">
                <span class="badge <?= $inStock ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                    <i class="bi <?= $inStock ? 'bi-check-circle' : 'bi-x-circle' ?> me-1"></i>
                    <?= $inStock ? 'In Stock' : 'Out of Stock' ?>
                </span>
                <a href="product.php?slug=<?= $product['slug'] ?>" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i>Details
                </a>
            </div>
        </div>
    </div>
</div>
