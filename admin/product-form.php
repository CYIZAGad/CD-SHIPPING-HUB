<?php
$pageTitle = 'Product Form';
require_once 'includes/header.php';

$pdo = getDBConnection();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

$product = null;
$isEdit = false;

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $product = $stmt->fetch();
    if ($product) {
        $isEdit = true;
        $pageTitle = 'Edit Product';
    }
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid form submission.';
    } else {
        $name = sanitize($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $description = sanitize($_POST['description'] ?? '');
        $specifications = sanitize($_POST['specifications'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $oldPrice = !empty($_POST['old_price']) ? (float)$_POST['old_price'] : null;
        $stock = (int)($_POST['stock'] ?? 0);
        $status = in_array($_POST['status'] ?? '', ['active', 'inactive']) ? $_POST['status'] : 'active';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $slug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $name));

        if (empty($name)) $errors[] = 'Product name is required.';
        if ($categoryId < 1) $errors[] = 'Category is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';

        // Handle image uploads
        $imageFields = ['image', 'image2', 'image3'];
        $imageNames = [];
        
        foreach ($imageFields as $field) {
            $imageNames[$field] = $isEdit ? $product[$field] : null;

            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $mimeToExt = [
                    'image/jpeg' => 'jpg',
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    'image/gif' => 'gif',
                ];
                $finfo = new finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->file($file['tmp_name']);

                if (!in_array($mimeType, $allowed)) {
                    $errors[] = "Invalid file type for $field. Allowed: JPG, PNG, WebP, GIF.";
                } elseif ($file['size'] > 5 * 1024 * 1024) {
                    $errors[] = "File $field is too large. Max 5MB.";
                } else {
                    $ext = $mimeToExt[$mimeType] ?? null;
                    if ($ext === null) {
                        $errors[] = "Unsupported image format for $field.";
                        continue;
                    }
                    $newName = $slug . '-' . $field . '-' . time() . '.' . $ext;
                    
                    if (!is_dir(UPLOAD_DIR)) {
                        mkdir(UPLOAD_DIR, 0755, true);
                    }

                    if (move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $newName)) {
                        // Remove old image
                        if ($isEdit && !empty($product[$field]) && file_exists(UPLOAD_DIR . $product[$field])) {
                            unlink(UPLOAD_DIR . $product[$field]);
                        }
                        $imageNames[$field] = $newName;
                    } else {
                        $errors[] = "Failed to upload $field.";
                    }
                }
            }
        }

        if (empty($errors)) {
            if ($isEdit) {
                $stmt = $pdo->prepare("UPDATE products SET category_id=?, name=?, slug=?, description=?, specifications=?, price=?, old_price=?, stock=?, image=?, image2=?, image3=?, featured=?, status=? WHERE id=?");
                $stmt->execute([$categoryId, $name, $slug, $description, $specifications, $price, $oldPrice, $stock, $imageNames['image'], $imageNames['image2'], $imageNames['image3'], $featured, $status, $product['id']]);
                setFlash('success', 'Product updated successfully!');
            } else {
                // Check slug uniqueness
                $check = $pdo->prepare("SELECT id FROM products WHERE slug = ?");
                $check->execute([$slug]);
                if ($check->fetch()) {
                    $slug .= '-' . time();
                }

                $stmt = $pdo->prepare("INSERT INTO products (category_id, name, slug, description, specifications, price, old_price, stock, image, image2, image3, featured, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$categoryId, $name, $slug, $description, $specifications, $price, $oldPrice, $stock, $imageNames['image'], $imageNames['image2'], $imageNames['image3'], $featured, $status]);
                setFlash('success', 'Product created successfully!');
            }
            redirect(ADMIN_URL . '/products.php');
        }
    }
}
?>

<div class="mb-3">
    <a href="products.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to Products</a>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger">
    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?></ul>
</div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    
    <div class="row g-4">
        <div class="col-lg-8">
            <!-- Basic Info -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Product Information</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name *</label>
                        <input type="text" class="form-control" name="name" value="<?= sanitize($_POST['name'] ?? $product['name'] ?? '') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category *</label>
                        <select class="form-select" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= ($product['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>>
                                <?= sanitize($cat['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="4"><?= sanitize($_POST['description'] ?? $product['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Specifications</label>
                        <textarea class="form-control" name="specifications" rows="3" placeholder="Format: Key:Value|Key:Value"><?= sanitize($_POST['specifications'] ?? $product['specifications'] ?? '') ?></textarea>
                        <small class="text-muted">Separate specs with | (e.g., CPU:Intel i7|RAM:16GB|Storage:512GB SSD)</small>
                    </div>
                </div>
            </div>

            <!-- Images -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Product Images</h6></div>
                <div class="card-body">
                    <div class="row g-3">
                        <?php for ($i = 1; $i <= 3; $i++): 
                            $field = $i === 1 ? 'image' : "image$i";
                            $current = $product[$field] ?? null;
                        ?>
                        <div class="col-md-4">
                            <label class="form-label">Image <?= $i ?> <?= $i === 1 ? '(Main)' : '' ?></label>
                            <?php if ($current && file_exists(UPLOAD_DIR . $current)): ?>
                            <div class="mb-2"><img src="<?= UPLOAD_URL . $current ?>" class="img-thumbnail" style="max-height:120px" alt=""></div>
                            <?php endif; ?>
                            <input type="file" class="form-control" name="<?= $field ?>" accept="image/*">
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Pricing -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Pricing & Stock</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Price ($) *</label>
                        <input type="number" class="form-control" name="price" step="0.01" min="0" 
                               value="<?= $_POST['price'] ?? $product['price'] ?? '' ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Old Price ($)</label>
                        <input type="number" class="form-control" name="old_price" step="0.01" min="0" 
                               value="<?= $_POST['old_price'] ?? $product['old_price'] ?? '' ?>">
                        <small class="text-muted">Set to show a discount badge</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Stock Quantity *</label>
                        <input type="number" class="form-control" name="stock" min="0" 
                               value="<?= $_POST['stock'] ?? $product['stock'] ?? 0 ?>" required>
                    </div>
                </div>
            </div>

            <!-- Status -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white"><h6 class="mb-0 fw-bold">Status</h6></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Visibility</label>
                        <select class="form-select" name="status">
                            <option value="active" <?= ($product['status'] ?? 'active') === 'active' ? 'selected' : '' ?>>Active</option>
                            <option value="inactive" <?= ($product['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>
                    <div class="form-check mb-3">
                        <input type="checkbox" class="form-check-input" name="featured" id="featured" 
                               <?= ($product['featured'] ?? 0) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="featured">Featured Product</label>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="bi bi-check-lg me-2"></i><?= $isEdit ? 'Update Product' : 'Create Product' ?>
                </button>
                <a href="products.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </div>
    </div>
</form>

<?php require_once 'includes/footer.php'; ?>
