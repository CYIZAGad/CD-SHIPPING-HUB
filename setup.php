<?php
/**
 * Database Setup Script - CD SHIPPING HUB
 * MySQL/MariaDB Version with Production Data
 * Run this once to create the database tables and populate with production data
 */

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv($key . '=' . $value);
        }
    }
}

// Get database configuration from environment
$host = getenv('DB_HOST') ?: 'localhost';
$port = getenv('DB_PORT') ?: '3306';
$dbName = getenv('DB_NAME') ?: 'cdshipping_hub';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';

function execIgnoreErrors(PDO $pdo, $sql) {
    try {
        $pdo->exec($sql);
    } catch (PDOException $e) {
        echo "<!-- Notice: " . htmlspecialchars($e->getMessage()) . " -->\n";
    }
}

try {
    // Connect to MySQL/MariaDB
    $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Connected to MySQL/MariaDB database successfully.\n";

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
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        KEY `idx_users_role` (`role`),
        KEY `idx_users_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Categories table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `name` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `icon` VARCHAR(50) DEFAULT 'bi-grid',
        `image` VARCHAR(255) DEFAULT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `idx_categories_slug` (`slug`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
        `image_base64` LONGTEXT DEFAULT NULL,
        `image2_base64` LONGTEXT DEFAULT NULL,
        `image3_base64` LONGTEXT DEFAULT NULL,
        `featured` TINYINT(1) DEFAULT 0,
        `status` ENUM('active','inactive') DEFAULT 'active',
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE,
        KEY `idx_products_category_status` (`category_id`, `status`),
        UNIQUE KEY `idx_products_slug` (`slug`),
        KEY `idx_products_created_at` (`created_at`),
        KEY `idx_products_featured` (`featured`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        UNIQUE KEY `idx_orders_order_number` (`order_number`),
        KEY `idx_orders_user_created` (`user_id`, `created_at`),
        KEY `idx_orders_payment_status` (`payment_status`),
        KEY `idx_orders_order_status` (`order_status`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

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
        FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
        KEY `idx_order_items_order_id` (`order_id`),
        KEY `idx_order_items_product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Notifications table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `notifications` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `title` VARCHAR(200) NOT NULL,
        `message` TEXT NOT NULL,
        `is_read` TINYINT(1) DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
        KEY `idx_notifications_user_read` (`user_id`, `is_read`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Newsletter subscribers table
    $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `email` VARCHAR(150) NOT NULL UNIQUE,
        `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `idx_newsletter_email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo "✓ Created all database tables\n\n";

    // Insert production users
    $stmt = $pdo->prepare("INSERT INTO users (id, full_name, email, password, phone, address, role, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?) ON CONFLICT (id) DO NOTHING");
    $stmt->execute([1, 'Administrator', 'admin@cdshipping.com', '$2y$10$fE/PSSEDm/0GBAvqWPZcU.nQ4/N0T.dVQAcndkAkPPmDFwLnIOGb6', '+1234567890', 'Admin Office', 'admin', '2026-03-14 15:41:50', '2026-03-14 15:41:50']);
    $stmt->execute([3, 'CYIZA Gad', 'cyizagad@gmail.com', '$2y$10$ddKzrIcai7Rfeexw/8H7k.53yrwtAcE2YHAwDYae9bK5tevCPt1C.', '+250728178335', 'kigali', 'client', '2026-03-14 16:23:54', '2026-03-14 16:23:54']);
    echo "✓ Inserted 2 users\n";

    // Insert production categories
    $stmt = $pdo->prepare("INSERT INTO `categories` (id, name, slug, icon, created_at) 
        VALUES (?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $categories = [
        [1, 'Cars', 'cars', 'bi-car-front', '2026-03-14 15:41:50'],
        [2, 'Laptops', 'laptops', 'bi-laptop', '2026-03-14 15:41:50'],
        [3, 'Desktop Computers', 'desktops', 'bi-pc-display', '2026-03-14 15:41:50'],
        [4, 'Smartphones', 'smartphones', 'bi-phone', '2026-03-14 15:41:50'],
        [5, 'Stoves', 'stoves', 'bi-fire', '2026-03-14 15:41:50'],
        [6, 'Other Electronics', 'other-electronics', 'bi-cpu', '2026-03-14 15:41:50']
    ];
    foreach ($categories as $cat) {
        $stmt->execute($cat);
    }
    echo "✓ Inserted 6 categories\n";

    // Insert production products
    $stmt = $pdo->prepare("INSERT INTO `products` (id, category_id, name, slug, description, specifications, price, old_price, stock, image, image2, image3, featured, status, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    
    $products = [
        [1, 1, 'Toyota Camry 2024', 'toyota-camry-2024', 'Brand new Toyota Camry 2024 model with advanced safety features, hybrid engine, and premium interior.', 'Engine: 2.5L Hybrid|Power: 208 HP|Transmission: CVT|Fuel: Hybrid|Color: Pearl White', 35000.00, 38000.00, 4, 'toyota-camry-2024-image-1773505125.webp', 'toyota-camry-2024-image2-1773505125.jpg', 'toyota-camry-2024-image3-1773505125.jpg', true, 'active', '2026-03-14 15:41:50', '2026-03-15 11:46:35'],
        [2, 1, 'Honda Civic 2024', 'honda-civic-2024', 'Sleek and efficient Honda Civic with turbocharged engine and modern tech features.', 'Engine: 1.5L Turbo|Power: 180 HP|Transmission: CVT|Fuel: Gasoline|Color: Crystal Black', 28000.00, 30000.00, 8, 'honda-civic-2024-image-1773571553.jpg', 'honda-civic-2024-image2-1773571553.jpg', 'honda-civic-2024-image3-1773571553.jpg', false, 'active', '2026-03-14 15:41:50', '2026-03-15 10:45:53'],
        [3, 2, 'MacBook Pro 16" M3', 'macbook-pro-16-quot-m3', 'Apple MacBook Pro 16-inch with M3 Pro chip, stunning Liquid Retina XDR display.', 'Chip: Apple M3 Pro|RAM: 18GB|Storage: 512GB SSD|Display: 16.2" Liquid Retina XDR|Battery: Up to 22 hours', 2499.00, 2699.00, 15, 'macbook-pro-16-quot-m3-image-1773571657.webp', 'macbook-pro-16-quot-m3-image2-1773571657.jpg', 'macbook-pro-16-quot-m3-image3-1773571657.png', true, 'active', '2026-03-14 15:41:50', '2026-03-15 10:47:37'],
        [4, 2, 'Dell XPS 15', 'dell-xps-15', 'Premium Dell XPS 15 laptop with InfinityEdge display and powerful performance.', 'Processor: Intel i7-13700H|RAM: 16GB DDR5|Storage: 512GB SSD|Display: 15.6" 3.5K OLED|GPU: RTX 4050', 1799.00, 1999.00, 20, 'dell-xps-15-image-1773571719.jpg', 'dell-xps-15-image2-1773571719.jpg', 'dell-xps-15-image3-1773571719.jpg', true, 'active', '2026-03-14 15:41:50', '2026-03-15 10:48:39'],
        [5, 3, 'Gaming Desktop RTX 4080', 'gaming-desktop-rtx-4080', 'High-end gaming desktop with RTX 4080 graphics and liquid cooling system.', 'CPU: Intel i9-14900K|RAM: 32GB DDR5|Storage: 2TB NVMe SSD|GPU: RTX 4080 16GB|PSU: 850W Gold', 2999.00, 3299.00, 9, 'gaming-desktop-rtx-4080-image-1773571786.jpg', 'gaming-desktop-rtx-4080-image2-1773571786.jpg', 'gaming-desktop-rtx-4080-image3-1773571786.jpg', true, 'active', '2026-03-14 15:41:50', '2026-03-15 11:46:35'],
        [6, 3, 'HP Pavilion Desktop', 'hp-pavilion-desktop', 'Reliable HP Pavilion desktop perfect for home and office use.', 'CPU: Intel i5-13400|RAM: 16GB DDR4|Storage: 512GB SSD|GPU: Intel UHD 730|OS: Windows 11', 699.00, 799.00, 25, 'hp-pavilion-desktop-image-1773573904.jpg', 'hp-pavilion-desktop-image2-1773573904.jpg', 'hp-pavilion-desktop-image3-1773573904.jpg', false, 'active', '2026-03-14 15:41:50', '2026-03-15 11:25:04'],
        [7, 4, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple iPhone 15 Pro Max with titanium design and A17 Pro chip.', 'Chip: A17 Pro|Display: 6.7" Super Retina XDR|Camera: 48MP Triple|Storage: 256GB|Battery: All-day', 1199.00, null, 30, 'iphone-15-pro-max-image-1773573955.png', 'iphone-15-pro-max-image2-1773573955.webp', 'iphone-15-pro-max-image3-1773573955.png', true, 'active', '2026-03-14 15:41:50', '2026-03-15 11:25:55'],
        [8, 4, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Samsung flagship with Galaxy AI features and S Pen integration.', 'Chip: Snapdragon 8 Gen 3|Display: 6.8" QHD+ AMOLED|Camera: 200MP Quad|Storage: 256GB|Battery: 5000mAh', 1099.00, 1199.00, 25, 'samsung-galaxy-s24-ultra-image-1773574022.jpg', 'samsung-galaxy-s24-ultra-image2-1773574022.jpg', 'samsung-galaxy-s24-ultra-image3-1773574022.jpg', true, 'active', '2026-03-14 15:41:50', '2026-03-15 11:27:02'],
        [9, 5, 'Samsung Smart Electric Range', 'samsung-smart-electric-range', 'Samsung smart electric range with Wi-Fi connectivity and air fry.', 'Type: Electric|Capacity: 6.3 cu ft|Burners: 5|Features: Air Fry, Wi-Fi|Color: Stainless Steel', 899.00, 1099.00, 12, 'samsung-smart-electric-range-image-1773574121.jpg', 'samsung-smart-electric-range-image2-1773574121.jpg', 'samsung-smart-electric-range-image3-1773574121.jpg', true, 'active', '2026-03-14 15:41:50', '2026-03-15 11:28:41'],
        [10, 5, 'LG Gas Double Oven Range', 'lg-gas-double-oven-range', 'LG gas range with double oven for versatile cooking.', 'Type: Gas|Capacity: 6.9 cu ft|Burners: 5|Features: ProBake, EasyClean|Color: Black Stainless', 1299.00, 1499.00, 8, 'lg-gas-double-oven-range-image-1773574231.jpg', 'lg-gas-double-oven-range-image2-1773574231.jpg', 'lg-gas-double-oven-range-image3-1773574231.jpg', false, 'active', '2026-03-14 15:41:50', '2026-03-15 11:30:31'],
        [11, 6, 'Sony 65" 4K OLED TV', 'sony-65-quot-4k-oled-tv', 'Sony Bravia XR 65-inch 4K OLED TV with cognitive processor.', 'Display: 65" 4K OLED|HDR: Dolby Vision, HDR10|Audio: Acoustic Surface Audio+|Smart: Google TV|Refresh: 120Hz', 1799.00, 2199.00, 10, 'sony-65-quot-4k-oled-tv-image-1773574287.webp', 'sony-65-quot-4k-oled-tv-image2-1773574287.webp', 'sony-65-quot-4k-oled-tv-image3-1773574287.jpg', true, 'active', '2026-03-14 15:41:50', '2026-03-15 11:31:27'],
        [12, 6, 'Bose QuietComfort Headphones', 'bose-quietcomfort-headphones', 'Premium noise-cancelling headphones with world-class ANC.', 'Type: Over-ear|ANC: Yes|Battery: 24 hours|Connectivity: Bluetooth 5.3|Driver: 35mm', 349.00, 399.00, 40, 'bose-quietcomfort-headphones-image-1773574346.webp', 'bose-quietcomfort-headphones-image2-1773574346.png', 'bose-quietcomfort-headphones-image3-1773574346.jpg', false, 'active', '2026-03-14 15:41:50', '2026-03-15 11:32:26'],
        [25, 1, 'Rivian R2', 'rivian-r2', 'The R2 is slightly larger than the Model Y in almost every metric', 'it\'s 0.9 in wider, 3.1 in taller, and its wheelbase is 1.8 in longer. However, Tesla\'s crossover trumps the R2\'s overall length by 2.8 inches. In terms of cargo capacity, the R2 shines', 45000.00, 48000.00, 1, 'rivian-r2-image-1774550348.jpg', 'rivian-r2-image2-1774550348.jpg', 'rivian-r2-image3-1774550348.webp', true, 'active', '2026-03-26 18:39:08', '2026-03-26 18:39:08'],
        [26, 1, 'Jeep Recon', 'jeep-recon', 'The 2026 Jeep Recon is an all-electric, "trail-rated" SUV designed for rugged off-road capability, featuring 650 horsepower, a ~250-mile range, and removable doors/windows', 'Expected in 2026, it is a boxy, unibody vehicle with 33-inch tires, electric locking differentials, and a 100 kWh battery, offering a 0-60 mph time of 3.6 seconds', 65000.00, 65500.00, 4, 'jeep-recon-image-1774551065.webp', 'jeep-recon-image2-1774551065.webp', 'jeep-recon-image3-1774551065.jpg', true, 'active', '2026-03-26 18:51:05', '2026-03-26 18:51:05'],
        [27, 1, 'BMW', 'bmw', 'BMW 7 Series combined with the BMW N57/M57 diesel engine family, which are frequently used in both 7 Series models (e.g., 730d, 750d) and for high-performance engine swaps', 'Reliability: The M57 is considered highly reliable, with many engines reaching over 300,000 miles, although high-mileage engines may face turbo or EGR issues. Tuning Capability: The M57, particularly the M57N (204–218 HP), is popular for tuning, easily achieving 240–250 HP with a simple remap. Swap Popularity: M57 engines are popular for engine swaps into vehicles like Land Rover Defenders due to their high torque and reliability.', 97300.00, 168500.00, 5, 'bmw-image-1774551564.jpg', 'bmw-image2-1774551564.jpg', 'bmw-image3-1774551564.webp', true, 'active', '2026-03-26 18:59:24', '2026-03-26 18:59:24'],
        [28, 3, 'desktop computer', 'desktop-computer', 'New 2026 desktop models emphasize AI-accelerated performance, featuring Intel Core Ultra processors, NVIDIA RTX 50-series graphics, and dedicated NPUs for advanced productivity.', 'Key releases include the powerful Dell XPS 8960, the compact HP Envy TE02, and the efficient M4-chip Apple Mac mini, designed for AI tasks, gaming, and 4K editing.', 1500.00, 3000.00, 23, 'desktop-computer-image-1774552196.webp', 'desktop-computer-image2-1774552196.jpg', 'desktop-computer-image3-1774552196.jpg', true, 'active', '2026-03-26 19:09:56', '2026-03-26 19:09:56'],
        [29, 4, 'iPhone 17 Pro Max', 'iphone-17-pro-max', 'We start 2026 with the Apple iPhone 17 Pro Max at the top spot as our best phone overall', 'Weight: 199g Dimensions: 149.6 x 71.5 x 8.25mm OS: iOS 18 Screen size: 6.3-inch Resolution: 2622 x 1206 pixels CPU: A18 Pro Storage: 128GB / 256GB / 512GB / 1TB Rear cameras: 48MP main (24mm, f/1.78), 48MP ultra-wide (13mm, f/2.2), 12MP telephoto with 5x optical zoom (120mm, f/2.8) Front camera: 12MP (f/1.9)', 2200000.00, 2500000.00, 12, 'iphone-17-pro-max-image-1774552988.webp', 'iphone-17-pro-max-image2-1774552988.webp', 'iphone-17-pro-max-image3-1774552988.webp', true, 'active', '2026-03-26 19:23:08', '2026-03-26 19:23:08'],
        [30, 2, 'Lenovo laptops', 'lenovo-laptops', 'Lenovo laptops are recognized for their robust build quality, premium keyboards, and diverse range catering to business, gaming, and everyday use, often featuring AI-enhanced performance and OLED displays.', 'Intel Core (up to i7/i9) or AMD Ryzen (up to 8000 series) processors, Windows 11, 8GB-32GB DDR5 RAM, and 512GB-1TB SSDs.', 700.00, 1500.00, 10, 'lenovo-laptops-image-1774894699.jpg', 'lenovo-laptops-image2-1774894699.jpg', 'lenovo-laptops-image3-1774894699.jpg', true, 'active', '2026-03-30 18:18:19', '2026-03-30 18:18:19']
    ];
    
    foreach ($products as $product) {
        $stmt->execute($product);
    }
    echo "✓ Inserted 19 products\n";

    // Insert production orders
    $stmt = $pdo->prepare("INSERT INTO `orders` (id, user_id, order_number, total_amount, shipping_address, phone, payment_status, order_status, payment_reference, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute([1, 3, 'CD202603146286B8', 175000.00, 'kigali', '+250728178335', 'pending', 'pending', '34455', '2026-03-14 16:34:38', '2026-03-14 16:34:38']);
    $stmt->execute([2, 1, 'CD202603158E87D5', 37999.00, 'Admin Office', '+1234567890', 'confirmed', 'delivered', '34455', '2026-03-15 11:45:27', '2026-03-15 11:46:45']);
    echo "✓ Inserted 2 orders\n";

    // Insert production order items
    $stmt = $pdo->prepare("INSERT INTO `order_items` (id, order_id, product_id, product_name, price, quantity, subtotal) 
        VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute([1, 1, 1, 'Toyota Camry 2024', 35000.00, 5, 175000.00]);
    $stmt->execute([2, 2, 1, 'Toyota Camry 2024', 35000.00, 1, 35000.00]);
    $stmt->execute([3, 2, 5, 'Gaming Desktop RTX 4080', 2999.00, 1, 2999.00]);
    echo "✓ Inserted 3 order items\n";

    // Insert production notifications
    $stmt = $pdo->prepare("INSERT INTO `notifications` (id, user_id, title, message, is_read, created_at) 
        VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute([1, 3, 'Order Placed Successfully', 'Your order #CD202603146286B8 has been placed. Total: $175,000.00. We will process your payment shortly.', false, '2026-03-14 16:34:38']);
    $stmt->execute([2, 1, 'Order Placed Successfully', 'Your order #CD202603158E87D5 has been placed. Total: $37,999.00. We will process your payment shortly.', false, '2026-03-15 11:45:27']);
    $stmt->execute([3, 1, 'Payment Confirmed', 'Your payment for order #CD202603158E87D5 has been confirmed!', false, '2026-03-15 11:46:35']);
    $stmt->execute([4, 1, 'Order Delivered', 'Your order #CD202603158E87D5 has been delivered.', false, '2026-03-15 11:46:45']);
    echo "✓ Inserted 4 notifications\n";

    // Insert newsletter subscribers
    $stmt = $pdo->prepare("INSERT INTO `newsletter_subscribers` (id, email, subscribed_at) 
        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE id=id");
    $stmt->execute([1, 'cyizagad69@gmail.com', '2026-03-30 18:31:00']);
    echo "\n✅ Database setup completed successfully with all production data!\n";
    echo "\n🔐 Admin Credentials:\n";
    echo "  Email: admin@cdshipping.com\n";
    echo "  Password: admin123\n";
    echo "\n⚠️  WARNING: Change admin password immediately in production!\n";;

} catch (PDOException $e) {
    die("MySQL Setup Error: " . htmlspecialchars($e->getMessage()));
}
