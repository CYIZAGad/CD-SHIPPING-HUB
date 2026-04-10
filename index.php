<?php
require_once 'config/database.php';
$pdo = getDBConnection();

$newsletterError = null;
$newsletterSuccess = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['form_action'] ?? '') === 'newsletter_subscribe') {
    $ipKey = 'newsletter_subscribe:' . getRequestIp();

    if (!rateLimitCheck($ipKey, 5, 300)) {
        $newsletterError = 'Too many subscription attempts. Please try again in a few minutes.';
    } elseif (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $newsletterError = 'Invalid form submission.';
    } else {
        $email = filter_var(trim($_POST['newsletter_email'] ?? ''), FILTER_VALIDATE_EMAIL);

        if (!$email) {
            $newsletterError = 'Please enter a valid email address.';
        } else {
            try {
                $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(150) NOT NULL UNIQUE,
                    `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                ) ENGINE=InnoDB");

                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email) VALUES (?)");
                $stmt->execute([$email]);
                $newsletterSuccess = 'Thank you for subscribing! You will now receive our latest deals.';
            } catch (PDOException $e) {
                $errorCode = (int)($e->errorInfo[1] ?? 0);
                if ($errorCode === 1062) {
                    $newsletterSuccess = 'This email is already subscribed. Thank you for staying with us!';
                } else {
                    $newsletterError = 'We could not process your subscription right now. Please try again shortly.';
                }
            }
        }
    }
}

// Get categories
$categories = $pdo->query("SELECT c.*, COUNT(p.id) as product_count FROM categories c LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active' GROUP BY c.id ORDER BY c.name")->fetchAll();

// Get featured products
$featured = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE COALESCE(p.featured, 0) = 1 AND p.status = 'active' ORDER BY p.id DESC LIMIT 8")->fetchAll();

// Get latest products
$latest = $pdo->query("SELECT p.*, c.name as category_name FROM products p JOIN categories c ON p.category_id = c.id WHERE p.status = 'active' ORDER BY p.created_at DESC LIMIT 8")->fetchAll();

// SEO Meta Tags for Homepage
$pageTitle = 'Home';
$pageDescription = 'Shop the latest electronics, cars, laptops, and smartphones with fast shipping and competitive prices. Discover the best deals at CD SHIPPING HUB.';
$pageKeywords = 'electronics, smartphones, laptops, cars, desktop computers, appliances, best deals, online shopping';
$canonicalUrl = SITE_URL;
$ogType = 'website';
require_once 'includes/header.php';
?>

<!-- Hero Banner -->
<section class="hero-banner">
    <div id="heroCarousel" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner">
            <div class="carousel-item active">
                <div class="hero-slide" style="background: linear-gradient(135deg, #0d47a1 0%, #1976d2 50%, #42a5f5 100%);">
                    <div class="container">
                        <div class="row align-items-center min-vh-50">
                            <div class="col-lg-6 text-white">
                                <span class="badge bg-warning text-dark mb-3 px-3 py-2">🔥 Hot Deals</span>
                                <h1 class="display-4 fw-bold mb-3">Latest Electronics<br>at Best Prices</h1>
                                <p class="lead mb-4">Discover the newest technology gadgets and electronics. Free shipping on orders over $500.</p>
                                <a href="products.php" class="btn btn-warning btn-lg px-5 me-2"><i class="bi bi-bag me-2"></i>Shop Now</a>
                                <a href="products.php?category=smartphones" class="btn btn-outline-light btn-lg px-4">View Phones</a>
                            </div>
                            <div class="col-lg-6 text-center d-none d-lg-block">
                                <i class="bi bi-phone display-1 text-white opacity-25" style="font-size:15rem"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="hero-slide" style="background: linear-gradient(135deg, #1b5e20 0%, #388e3c 50%, #66bb6a 100%);">
                    <div class="container">
                        <div class="row align-items-center min-vh-50">
                            <div class="col-lg-6 text-white">
                                <span class="badge bg-danger mb-3 px-3 py-2">🏷️ Up to 30% OFF</span>
                                <h1 class="display-4 fw-bold mb-3">Power Up Your<br>Home Office</h1>
                                <p class="lead mb-4">Laptops and desktops built for performance. Perfect for work and gaming.</p>
                                <a href="products.php?category=laptops" class="btn btn-warning btn-lg px-5 me-2"><i class="bi bi-laptop me-2"></i>Browse Laptops</a>
                                <a href="products.php?category=desktops" class="btn btn-outline-light btn-lg px-4">Desktops</a>
                            </div>
                            <div class="col-lg-6 text-center d-none d-lg-block">
                                <i class="bi bi-laptop display-1 text-white opacity-25" style="font-size:15rem"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="hero-slide" style="background: linear-gradient(135deg, #e65100 0%, #f57c00 50%, #ffb74d 100%);">
                    <div class="container">
                        <div class="row align-items-center min-vh-50">
                            <div class="col-lg-6 text-white">
                                <span class="badge bg-dark mb-3 px-3 py-2">🚗 Premium Selection</span>
                                <h1 class="display-4 fw-bold mb-3">Drive Your<br>Dream Car</h1>
                                <p class="lead mb-4">Explore our premium selection of vehicles with financing options available.</p>
                                <a href="products.php?category=cars" class="btn btn-dark btn-lg px-5 me-2"><i class="bi bi-car-front me-2"></i>View Cars</a>
                                <a href="products.php" class="btn btn-outline-light btn-lg px-4">All Products</a>
                            </div>
                            <div class="col-lg-6 text-center d-none d-lg-block">
                                <i class="bi bi-car-front display-1 text-white opacity-25" style="font-size:15rem"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
        </button>
    </div>
</section>

<!-- Features Bar -->
<section class="features-bar py-4 bg-light">
    <div class="container">
        <div class="row g-3 text-center">
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <i class="bi bi-truck display-6 text-primary"></i>
                    <h6 class="mt-2 mb-0">Free Shipping</h6>
                    <small class="text-muted">On orders $500+</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <i class="bi bi-shield-check display-6 text-success"></i>
                    <h6 class="mt-2 mb-0">Secure Payment</h6>
                    <small class="text-muted">100% Protected</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <i class="bi bi-arrow-repeat display-6 text-warning"></i>
                    <h6 class="mt-2 mb-0">Easy Returns</h6>
                    <small class="text-muted">30-Day Policy</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="feature-item">
                    <i class="bi bi-headset display-6 text-danger"></i>
                    <h6 class="mt-2 mb-0">24/7 Support</h6>
                    <small class="text-muted">Dedicated Help</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section class="py-5">
    <div class="container">
        <div class="section-header text-center mb-4">
            <h2 class="fw-bold">Shop by Category</h2>
            <p class="text-muted">Browse our wide range of electronics and technology</p>
        </div>
        <div class="row g-3">
            <?php foreach ($categories as $cat): ?>
            <div class="col-6 col-md-4 col-lg-2">
                <a href="products.php?category=<?= $cat['slug'] ?>" class="category-card text-decoration-none">
                    <div class="card text-center h-100 border-0 shadow-sm category-card-inner">
                        <div class="card-body p-3">
                            <i class="bi <?= $cat['icon'] ?> display-4 text-primary"></i>
                            <h6 class="mt-2 mb-1"><?= sanitize($cat['name']) ?></h6>
                            <small class="text-muted"><?= $cat['product_count'] ?> Products</small>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Featured Products -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Featured Products</h2>
                <p class="text-muted mb-0">Handpicked deals just for you</p>
            </div>
            <a href="products.php" class="btn btn-outline-primary">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($featured as $product): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Promo Banner -->
<section class="promo-banner py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-6">
                <div class="promo-card bg-primary text-white p-4 rounded-4">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <span class="badge bg-warning text-dark mb-2">Limited Time</span>
                            <h4 class="fw-bold">Smartphones Sale</h4>
                            <p class="mb-3 opacity-75">Get up to 25% off on the latest smartphones</p>
                            <a href="products.php?category=smartphones" class="btn btn-warning">Shop Now</a>
                        </div>
                        <div class="col-4 text-center">
                            <i class="bi bi-phone display-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="promo-card bg-dark text-white p-4 rounded-4">
                    <div class="row align-items-center">
                        <div class="col-8">
                            <span class="badge bg-danger mb-2">New Arrivals</span>
                            <h4 class="fw-bold">Premium Laptops</h4>
                            <p class="mb-3 opacity-75">Discover the latest laptops for work & play</p>
                            <a href="products.php?category=laptops" class="btn btn-light">Explore</a>
                        </div>
                        <div class="col-4 text-center">
                            <i class="bi bi-laptop display-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Products -->
<section class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0">Latest Arrivals</h2>
                <p class="text-muted mb-0">Newest additions to our store</p>
            </div>
            <a href="products.php" class="btn btn-outline-primary">View All <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php foreach ($latest as $product): ?>
            <div class="col-6 col-md-4 col-lg-3">
                <?php include 'includes/product-card.php'; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Newsletter -->
<section class="newsletter-section py-5 bg-primary text-white">
    <div class="container text-center">
        <h3 class="fw-bold mb-2">Subscribe to Our Newsletter</h3>
        <p class="mb-4 opacity-75">Get the latest deals and new arrivals directly in your inbox</p>
        <?php if ($newsletterSuccess): ?>
        <div class="alert alert-success d-inline-block text-start" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= e($newsletterSuccess) ?>
        </div>
        <?php endif; ?>
        <?php if ($newsletterError): ?>
        <div class="alert alert-danger d-inline-block text-start" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= e($newsletterError) ?>
        </div>
        <?php endif; ?>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <form method="POST" class="input-group input-group-lg">
                    <input type="hidden" name="form_action" value="newsletter_subscribe">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="email" class="form-control" name="newsletter_email" placeholder="Enter your email address" required>
                    <button type="submit" class="btn btn-warning px-4"><i class="bi bi-send me-2"></i>Subscribe</button>
                </form>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
