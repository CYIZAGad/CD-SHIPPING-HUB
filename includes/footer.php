    </main>

    <!-- Footer -->
    <footer class="site-footer mt-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <h5 class="text-white"><i class="bi bi-box-seam me-2"></i><?= SITE_NAME ?></h5>
                    <p class="text-light opacity-75">Your trusted online marketplace for electronics, gadgets, and technology. We deliver quality products right to your doorstep.</p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="social-link"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-link"><i class="bi bi-youtube"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white mb-3">Quick Links</h6>
                    <ul class="list-unstyled footer-links">
                        <li><a href="<?= SITE_URL ?>">Home</a></li>
                        <li><a href="<?= SITE_URL ?>/products.php">Products</a></li>
                        <li><a href="<?= SITE_URL ?>/cart.php">Cart</a></li>
                        <li><a href="<?= SITE_URL ?>/orders.php">My Orders</a></li>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white mb-3">Categories</h6>
                    <ul class="list-unstyled footer-links">
                        <?php foreach ($navCategories as $cat): ?>
                        <li><a href="<?= SITE_URL ?>/products.php?category=<?= $cat['slug'] ?>"><?= sanitize($cat['name']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white mb-3">Contact Us</h6>
                    <ul class="list-unstyled text-light opacity-75">
                        <li class="mb-2"><i class="bi bi-geo-alt me-2"></i>Kigali Rwanda , kigali Nyarugenge</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>+250785008063</li>
                        <li class="mb-2"><i class="bi bi-telephone me-2"></i>0780335155</li>
                        <li class="mb-2"><i class="bi bi-envelope me-2"></i>cdshipping@gmail.com</li>
                        <li class="mb-2"><i class="bi bi-clock me-2"></i>Mon-Sunday: 9AM - 8PM</li>
                    </ul>
                </div>
            </div>
            <hr class="border-light opacity-25 my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="text-light opacity-50 mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All Rights Reserved.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <img src="https://img.shields.io/badge/Visa-003087?style=flat&logo=visa&logoColor=white" alt="Visa" class="me-1">
                    <img src="https://img.shields.io/badge/MasterCard-EB001B?style=flat&logo=mastercard&logoColor=white" alt="MC" class="me-1">
                    <img src="https://img.shields.io/badge/PayPal-00457C?style=flat&logo=paypal&logoColor=white" alt="PayPal">
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="<?= SITE_URL ?>/assets/js/main.js"></script>
</body>
</html>
