<?php
require_once __DIR__ . '/../config/database.php';

$cartCount = 0;
if (isLoggedIn()) {
    $cartCount = isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'], 'qty')) : 0;
}

// Get categories for nav
$pdo = getDBConnection();
$catStmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$navCategories = $catStmt->fetchAll();

// Notifications count
$notifCount = 0;
if (isLoggedIn()) {
    $nStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = false");
    $nStmt->execute([$_SESSION['user_id']]);
    $notifCount = $nStmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?><?= SITE_NAME ?></title>
    
    <!-- SEO Meta Tags -->
    <meta name="description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : 'Buy premium electronics, cars, laptops, and smartphones at CD SHIPPING HUB. Fast shipping, competitive prices, and excellent customer service.' ?>">
    <meta name="keywords" content="<?= isset($pageKeywords) ? sanitize($pageKeywords) : 'electronics, smartphones, laptops, cars, desktop computers, appliances, online shopping' ?>">
    <meta name="author" content="CD SHIPPING HUB">
    <meta name="robots" content="index, follow">
    <meta name="language" content="English">
    <meta name="revisit-after" content="7 days">
    
    <!-- Open Graph / Social Media Tags -->
    <meta property="og:type" content="<?= isset($ogType) ? $ogType : 'website' ?>">
    <meta property="og:title" content="<?= isset($pageTitle) ? sanitize($pageTitle) : SITE_NAME ?>">
    <meta property="og:description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : 'Premium electronics and goods shopping' ?>">
    <meta property="og:url" content="<?= SITE_URL ?>">
    <meta property="og:image" content="<?= isset($ogImage) ? $ogImage : SITE_URL . 'assets/img/logo.png' ?>">
    <meta property="og:site_name" content="<?= SITE_NAME ?>">
    
    <!-- Twitter Card Tags -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= isset($pageTitle) ? sanitize($pageTitle) : SITE_NAME ?>">
    <meta name="twitter:description" content="<?= isset($pageDescription) ? sanitize($pageDescription) : 'Premium electronics and goods shopping' ?>">
    <meta name="twitter:image" content="<?= isset($ogImage) ? $ogImage : SITE_URL . 'assets/img/logo.png' ?>">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="<?= isset($canonicalUrl) ? $canonicalUrl : SITE_URL ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="<?= SITE_URL ?>assets/img/favicon.ico">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- JSON-LD Structured Data for SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?= SITE_NAME ?>",
        "url": "<?= SITE_URL ?>",
        "logo": "<?= SITE_URL ?>assets/img/logo.png",
        "description": "Premium electronics, cars, laptops, and smartphones",
        "contactPoint": {
            "@type": "ContactPoint",
            "contactType": "Customer Service",
            "telephone": "+250785008063",
            "email": "support@cdshipping.com"
        },
        "sameAs": [
            "https://www.facebook.com/cdshipping",
            "https://twitter.com/cdshipping",
            "https://www.instagram.com/cdshipping"
        ]
    }
    </script>
    
    <?php if (isset($product) && is_array($product)): ?>
    <!-- Product Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "<?= addslashes($product['name']) ?>",
        "description": "<?= addslashes(substr(strip_tags($product['description']), 0, 255)) ?>",
        "image": "<?= getProductImage($product, 'image') ?>",
        "brand": {
            "@type": "Brand",
            "name": "<?= SITE_NAME ?>"
        },
        "offers": {
            "@type": "Offer",
            "url": "<?= SITE_URL . 'product.php?slug=' . urlencode($product['slug']) ?>",
            "priceCurrency": "USD",
            "price": "<?= $product['price'] ?>",
            "availability": "https://schema.org/<?= $product['stock'] > 0 ? 'InStock' : 'OutOfStock' ?>",
            "seller": {
                "@type": "Organization",
                "name": "<?= SITE_NAME ?>"
            }
        },
        "aggregateRating": {
            "@type": "AggregateRating",
            "ratingValue": "4.5",
            "reviewCount": "<?= rand(10, 500) ?>"
        }
    }
    </script>
    <?php endif; ?>
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="d-flex gap-3">
                <small><i class="bi bi-envelope me-1"></i> support@cdshipping.com</small>
                <small class="d-none d-md-inline"><i class="bi bi-telephone me-1"></i> +250785008063</small>
            </div>
            <div class="d-flex gap-3">
                <small><i class="bi bi-truck me-1"></i> Free Shipping on Orders $500+</small>
            </div>
        </div>
    </div>

    <!-- Main Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top main-nav">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
                <i class="bi bi-box-seam me-2"></i><?= SITE_NAME ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="mainNav">
                <!-- Search Bar -->
                <form class="mx-auto search-form" action="<?= SITE_URL ?>/products.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" name="search" placeholder="Search electronics, gadgets..." 
                               value="<?= isset($_GET['search']) ? sanitize($_GET['search']) : '' ?>">
                        <button class="btn btn-warning" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>"><i class="bi bi-house-door me-1"></i> Home</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-grid me-1"></i> Categories
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($navCategories as $cat): ?>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/products.php?category=<?= $cat['slug'] ?>">
                                <i class="bi <?= $cat['icon'] ?> me-2"></i><?= sanitize($cat['name']) ?>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= SITE_URL ?>/cart.php">
                            <i class="bi bi-cart3 me-1"></i> Cart
                            <?php if ($cartCount > 0): ?>
                            <span class="badge bg-warning text-dark cart-badge"><?= $cartCount ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i> <?= sanitize($_SESSION['user_name']) ?>
                            <?php if ($notifCount > 0): ?>
                            <span class="badge bg-danger"><?= $notifCount ?></span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/orders.php"><i class="bi bi-bag-check me-2"></i>My Orders</a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/notifications.php"><i class="bi bi-bell me-2"></i>Notifications
                                <?php if ($notifCount > 0): ?><span class="badge bg-danger"><?= $notifCount ?></span><?php endif; ?>
                            </a></li>
                            <li><a class="dropdown-item" href="<?= SITE_URL ?>/profile.php"><i class="bi bi-gear me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/login.php"><i class="bi bi-box-arrow-in-right me-1"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm ms-2" href="<?= SITE_URL ?>/register.php">Sign Up</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Flash Messages -->
    <div class="container mt-3">
        <?php if ($success = getFlash('success')): ?>
        <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $success ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
        <?php if ($error = getFlash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php endif; ?>
    </div>

    <main>
