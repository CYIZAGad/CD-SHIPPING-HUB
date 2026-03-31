<?php
require_once 'config/database.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
    $total += $item['price'] * $item['qty'];
}

$pageTitle = 'Shopping Cart';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="<?= SITE_URL ?>">Home</a></li>
            <li class="breadcrumb-item active">Shopping Cart</li>
        </ol>
    </nav>

    <h2 class="fw-bold mb-4"><i class="bi bi-cart3 me-2"></i>Shopping Cart</h2>

    <?php if (empty($cart)): ?>
    <div class="text-center py-5">
        <i class="bi bi-cart-x display-1 text-muted"></i>
        <h4 class="mt-3">Your Cart is Empty</h4>
        <p class="text-muted">Browse our products and add items to your cart.</p>
        <a href="products.php" class="btn btn-primary btn-lg"><i class="bi bi-bag me-2"></i>Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="ps-3">Product</th>
                                    <th>Price</th>
                                    <th style="width:150px">Quantity</th>
                                    <th>Subtotal</th>
                                    <th style="width:50px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cart as $item):
                                    $imgSrc = (!empty($item['image']) && file_exists(__DIR__ . '/uploads/products/' . $item['image']))
                                        ? UPLOAD_URL . $item['image']
                                        : 'https://placehold.co/80x80/e3f2fd/1976d2?text=IMG';
                                    $subtotal = $item['price'] * $item['qty'];
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <div class="d-flex align-items-center">
                                            <img src="<?= $imgSrc ?>" class="rounded me-3" width="60" height="60" style="object-fit:cover" alt="">
                                            <div>
                                                <h6 class="mb-0"><?= sanitize($item['name']) ?></h6>
                                                <small class="text-muted">ID: <?= $item['id'] ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="fw-semibold"><?= formatPrice($item['price']) ?></td>
                                    <td>
                                        <form action="cart-action.php" method="POST" class="d-flex align-items-center">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <div class="input-group input-group-sm" style="width:120px">
                                                <button type="submit" name="quantity" value="<?= max(1, $item['qty'] - 1) ?>" class="btn btn-outline-secondary">-</button>
                                                <input type="text" class="form-control text-center" value="<?= $item['qty'] ?>" readonly>
                                                <button type="submit" name="quantity" value="<?= min($item['stock'], $item['qty'] + 1) ?>" class="btn btn-outline-secondary">+</button>
                                            </div>
                                        </form>
                                    </td>
                                    <td class="fw-bold text-primary"><?= formatPrice($subtotal) ?></td>
                                    <td>
                                        <form action="cart-action.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                            <input type="hidden" name="action" value="remove">
                                            <input type="hidden" name="product_id" value="<?= $item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="d-flex justify-content-between mt-3">
                <a href="products.php" class="btn btn-outline-primary"><i class="bi bi-arrow-left me-2"></i>Continue Shopping</a>
                <form action="cart-action.php" method="POST">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="clear">
                    <button type="submit" class="btn btn-outline-danger"><i class="bi bi-trash me-2"></i>Clear Cart</button>
                </form>
            </div>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <h5 class="fw-bold mb-3">Order Summary</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal (<?= array_sum(array_column($cart, 'qty')) ?> items)</span>
                        <span class="fw-semibold"><?= formatPrice($total) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Shipping</span>
                        <span class="fw-semibold <?= $total >= 500 ? 'text-success' : '' ?>"><?= $total >= 500 ? 'FREE' : formatPrice(25) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between mb-3">
                        <span class="h5 mb-0">Total</span>
                        <span class="h5 mb-0 text-primary fw-bold"><?= formatPrice($total >= 500 ? $total : $total + 25) ?></span>
                    </div>
                    <?php if ($total >= 500): ?>
                    <div class="alert alert-success small py-2"><i class="bi bi-truck me-1"></i> You qualify for free shipping!</div>
                    <?php else: ?>
                    <div class="alert alert-info small py-2"><i class="bi bi-info-circle me-1"></i> Add <?= formatPrice(500 - $total) ?> more for free shipping</div>
                    <?php endif; ?>
                    <a href="checkout.php" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-lock me-2"></i>Proceed to Checkout
                    </a>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
