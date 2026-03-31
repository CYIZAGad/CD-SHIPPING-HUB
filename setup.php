<?php
/**
 * Database Setup Script - CD SHIPPING HUB
 * Run this once to create the database and tables
 */

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv($key . '=' . $value);
        }
    }
}

// Get database configuration from environment or use defaults
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'cdshipping_hub';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

function execIgnoreErrors(PDO $pdo, $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        // Ignore duplicate/index-exists errors to keep setup idempotent.
        $code = (int)($e->errorInfo[1] ?? 0);
        if (!in_array($code, [1060, 1061, 1091], true)) {
            throw $e;
        }
    }
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // Create database
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $pdo->exec("USE `$dbName`");

    // Users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `users` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `full_name` VARCHAR(100) NOT NULL,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `password` VARCHAR(255) NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `address` TEXT NOT NULL,
        `role` ENUM('client','admin') DEFAULT 'client',
        `reset_token` VARCHAR(100) DEFAULT NULL,
        `reset_expires` DATETIME DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `icon` VARCHAR(50) DEFAULT 'bi-grid',
        `image` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Products table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `products` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `category_id` INT NOT NULL,
        `name` VARCHAR(200) NOT NULL,
        `slug` VARCHAR(200) NOT NULL UNIQUE,
        `description` TEXT,
        `specifications` TEXT,
        `price` DECIMAL(10,2) NOT NULL,
        `old_price` DECIMAL(10,2) DEFAULT NULL,
        `stock` INT DEFAULT 0,
        `image` VARCHAR(255) DEFAULT NULL,
        `image2` VARCHAR(255) DEFAULT NULL,
        `image3` VARCHAR(255) DEFAULT NULL,
        `featured` TINYINT(1) DEFAULT 0,
        `status` ENUM('active','inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Orders table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `orders` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `order_number` VARCHAR(20) NOT NULL UNIQUE,
        `total_amount` DECIMAL(10,2) NOT NULL,
        `shipping_address` TEXT NOT NULL,
        `phone` VARCHAR(20) NOT NULL,
        `payment_status` ENUM('pending','confirmed','rejected') DEFAULT 'pending',
        `order_status` ENUM('pending','approved','shipped','delivered','cancelled') DEFAULT 'pending',
        `payment_reference` VARCHAR(100) DEFAULT NULL,
        `notes` TEXT DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Order items table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `order_items` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `order_id` INT NOT NULL,
        `product_id` INT NOT NULL,
        `product_name` VARCHAR(200) NOT NULL,
        `price` DECIMAL(10,2) NOT NULL,
        `quantity` INT NOT NULL,
        `subtotal` DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (`order_id`) REFERENCES `orders`(`id`) ON DELETE CASCADE,
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB");

    // Newsletter subscribers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");

    // Index tuning for common lookups
    execIgnoreErrors($pdo, "CREATE INDEX idx_users_role ON users(role)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_categories_slug ON categories(slug)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_products_category_status ON products(category_id, status)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_products_slug ON products(slug)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_products_created_at ON products(created_at)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_orders_user_created ON orders(user_id, created_at)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_orders_payment_status ON orders(payment_status)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_orders_order_status ON orders(order_status)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_orders_order_number ON orders(order_number)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_order_items_order_id ON order_items(order_id)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_notifications_user_read ON notifications(user_id, is_read)");
    execIgnoreErrors($pdo, "CREATE INDEX idx_newsletter_email ON newsletter_subscribers(email)");

    // Insert default categories
    $pdo->exec("INSERT IGNORE INTO `categories` (`name`, `slug`, `icon`) VALUES
        ('Cars', 'cars', 'bi-car-front'),
        ('Laptops', 'laptops', 'bi-laptop'),
        ('Desktop Computers', 'desktops', 'bi-pc-display'),
        ('Smartphones', 'smartphones', 'bi-phone'),
        ('Stoves', 'stoves', 'bi-fire'),
        ('Other Electronics', 'other-electronics', 'bi-cpu')
    ");

    // Insert admin user (password: admin123)
    $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `phone`, `address`, `role`) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['Administrator', 'admin@cdshipping.com', $adminPassword, '+1234567890', 'Admin Office', 'admin']);

    // Insert sample products
    $sampleProducts = [
        [1, 'Toyota Camry 2024', 'toyota-camry-2024', 'Brand new Toyota Camry 2024 model with advanced safety features, hybrid engine, and premium interior.', 'Engine: 2.5L Hybrid|Power: 208 HP|Transmission: CVT|Fuel: Hybrid|Color: Pearl White', 35000.00, 38000.00, 5, 'car1.jpg', NULL, NULL, 1],
        [1, 'Honda Civic 2024', 'honda-civic-2024', 'Sleek and efficient Honda Civic with turbocharged engine and modern tech features.', 'Engine: 1.5L Turbo|Power: 180 HP|Transmission: CVT|Fuel: Gasoline|Color: Crystal Black', 28000.00, 30000.00, 8, 'car2.jpg', NULL, NULL, 0],
        [2, 'MacBook Pro 16\" M3', 'macbook-pro-16-m3', 'Apple MacBook Pro 16-inch with M3 Pro chip, stunning Liquid Retina XDR display.', 'Chip: Apple M3 Pro|RAM: 18GB|Storage: 512GB SSD|Display: 16.2\" Liquid Retina XDR|Battery: Up to 22 hours', 2499.00, 2699.00, 15, 'laptop1.jpg', NULL, NULL, 1],
        [2, 'Dell XPS 15', 'dell-xps-15', 'Premium Dell XPS 15 laptop with InfinityEdge display and powerful performance.', 'Processor: Intel i7-13700H|RAM: 16GB DDR5|Storage: 512GB SSD|Display: 15.6\" 3.5K OLED|GPU: RTX 4050', 1799.00, 1999.00, 20, 'laptop2.jpg', NULL, NULL, 1],
        [3, 'Gaming Desktop RTX 4080', 'gaming-desktop-rtx-4080', 'High-end gaming desktop with RTX 4080 graphics and liquid cooling system.', 'CPU: Intel i9-14900K|RAM: 32GB DDR5|Storage: 2TB NVMe SSD|GPU: RTX 4080 16GB|PSU: 850W Gold', 2999.00, 3299.00, 10, 'desktop1.jpg', NULL, NULL, 1],
        [3, 'HP Pavilion Desktop', 'hp-pavilion-desktop', 'Reliable HP Pavilion desktop perfect for home and office use.', 'CPU: Intel i5-13400|RAM: 16GB DDR4|Storage: 512GB SSD|GPU: Intel UHD 730|OS: Windows 11', 699.00, 799.00, 25, 'desktop2.jpg', NULL, NULL, 0],
        [4, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple iPhone 15 Pro Max with titanium design and A17 Pro chip.', 'Chip: A17 Pro|Display: 6.7\" Super Retina XDR|Camera: 48MP Triple|Storage: 256GB|Battery: All-day', 1199.00, NULL, 30, 'phone1.jpg', NULL, NULL, 1],
        [4, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Samsung flagship with Galaxy AI features and S Pen integration.', 'Chip: Snapdragon 8 Gen 3|Display: 6.8\" QHD+ AMOLED|Camera: 200MP Quad|Storage: 256GB|Battery: 5000mAh', 1099.00, 1199.00, 25, 'phone2.jpg', NULL, NULL, 1],
        [5, 'Samsung Smart Electric Range', 'samsung-smart-electric-range', 'Samsung smart electric range with Wi-Fi connectivity and air fry.', 'Type: Electric|Capacity: 6.3 cu ft|Burners: 5|Features: Air Fry, Wi-Fi|Color: Stainless Steel', 899.00, 1099.00, 12, 'stove1.jpg', NULL, NULL, 1],
        [5, 'LG Gas Double Oven Range', 'lg-gas-double-oven', 'LG gas range with double oven for versatile cooking.', 'Type: Gas|Capacity: 6.9 cu ft|Burners: 5|Features: ProBake, EasyClean|Color: Black Stainless', 1299.00, 1499.00, 8, 'stove2.jpg', NULL, NULL, 0],
        [6, 'Sony 65\" 4K OLED TV', 'sony-65-4k-oled-tv', 'Sony Bravia XR 65-inch 4K OLED TV with cognitive processor.', 'Display: 65\" 4K OLED|HDR: Dolby Vision, HDR10|Audio: Acoustic Surface Audio+|Smart: Google TV|Refresh: 120Hz', 1799.00, 2199.00, 10, 'other1.jpg', NULL, NULL, 1],
        [6, 'Bose QuietComfort Headphones', 'bose-quietcomfort-headphones', 'Premium noise-cancelling headphones with world-class ANC.', 'Type: Over-ear|ANC: Yes|Battery: 24 hours|Connectivity: Bluetooth 5.3|Driver: 35mm', 349.00, 399.00, 40, 'other2.jpg', NULL, NULL, 0],
    ];

    $stmt = $pdo->prepare("INSERT IGNORE INTO `products` (`category_id`, `name`, `slug`, `description`, `specifications`, `price`, `old_price`, `stock`, `image`, `image2`, `image3`, `featured`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($sampleProducts as $product) {
        $stmt->execute($product);
    }

    echo "<!DOCTYPE html><html><head><title>Setup Complete</title>";
    echo "<link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css' rel='stylesheet'>";
    echo "</head><body class='bg-light'>";
    echo "<div class='container mt-5'><div class='card shadow'><div class='card-body text-center p-5'>";
    echo "<h1 class='text-success mb-4'>&#10004; CD SHIPPING HUB - Setup Complete!</h1>";
    echo "<p class='lead'>Database and tables created successfully.</p>";
    echo "<hr>";
    echo "<h5>Admin Credentials:</h5>";
    echo "<p>Email: <strong>admin@cdshipping.com</strong><br>Password: <strong>admin123</strong></p>";
    echo "<a href='index.php' class='btn btn-primary btn-lg mt-3'>Go to Website</a> ";
    echo "<a href='admin/index.php' class='btn btn-dark btn-lg mt-3'>Go to Admin Dashboard</a>";
    echo "</div></div></div></body></html>";

} catch (PDOException $e) {
    die("Setup Error: " . $e->getMessage());
}
