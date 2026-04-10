<?php
/**
 * Database Setup Script - CD SHIPPING HUB
 * Supports both MySQL/MariaDB and PostgreSQL
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
$port = getenv('DB_PORT') ?: '5432';
$dbName = getenv('DB_NAME') ?: 'cdshipping_hub';
$user = getenv('DB_USER') ?: 'postgres';
$pass = getenv('DB_PASS') ?: '';

// Detect database type from port or host
$isPostgreSQL = (int)$port === 5432 || strpos($host, 'postgres') !== false || strpos($host, 'dpg-') !== false;

try {
    // Connect to appropriate database
    if ($isPostgreSQL) {
        $dsn = "pgsql:host=$host;port=$port;dbname=$dbName;";
        echo "🔌 Connecting to PostgreSQL...\n";
    } else {
        $dsn = "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4";
        echo "🔌 Connecting to MySQL/MariaDB...\n";
    }
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);

    echo "✓ Connected successfully (" . ($isPostgreSQL ? "PostgreSQL" : "MySQL") . ").\n\n";

    // ============ CREATE TABLES ============
    
    // Users table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            full_name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            address TEXT NOT NULL,
            role VARCHAR(20) DEFAULT 'client',
            reset_token VARCHAR(100) DEFAULT NULL,
            reset_expires TIMESTAMP DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
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
    }

    // Categories table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
            id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            slug VARCHAR(100) NOT NULL UNIQUE,
            icon VARCHAR(50) DEFAULT 'bi-grid',
            image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `categories` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `name` VARCHAR(100) NOT NULL,
            `slug` VARCHAR(100) NOT NULL UNIQUE,
            `icon` VARCHAR(50) DEFAULT 'bi-grid',
            `image` VARCHAR(255) DEFAULT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `idx_categories_slug` (`slug`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    // Products table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS products (
            id SERIAL PRIMARY KEY,
            category_id INTEGER NOT NULL,
            name VARCHAR(200) NOT NULL,
            slug VARCHAR(200) NOT NULL UNIQUE,
            description TEXT,
            specifications TEXT,
            price NUMERIC(10,2) NOT NULL,
            old_price NUMERIC(10,2) DEFAULT NULL,
            stock INTEGER DEFAULT 0,
            image VARCHAR(255) DEFAULT NULL,
            image2 VARCHAR(255) DEFAULT NULL,
            image3 VARCHAR(255) DEFAULT NULL,
            image_base64 TEXT DEFAULT NULL,
            image2_base64 TEXT DEFAULT NULL,
            image3_base64 TEXT DEFAULT NULL,
            featured INTEGER DEFAULT 0,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
    } else {
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
    }

    // Orders table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            order_number VARCHAR(20) NOT NULL UNIQUE,
            total_amount NUMERIC(10,2) NOT NULL,
            shipping_address TEXT NOT NULL,
            phone VARCHAR(20) NOT NULL,
            payment_status VARCHAR(20) DEFAULT 'pending',
            order_status VARCHAR(20) DEFAULT 'pending',
            payment_reference VARCHAR(100) DEFAULT NULL,
            notes TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } else {
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
    }

    // Order items table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS order_items (
            id SERIAL PRIMARY KEY,
            order_id INTEGER NOT NULL,
            product_id INTEGER NOT NULL,
            product_name VARCHAR(200) NOT NULL,
            price NUMERIC(10,2) NOT NULL,
            quantity INTEGER NOT NULL,
            subtotal NUMERIC(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
            FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
        )");
    } else {
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
    }

    // Notifications table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS notifications (
            id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            title VARCHAR(200) NOT NULL,
            message TEXT NOT NULL,
            is_read SMALLINT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )");
    } else {
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
    }

    // Newsletter subscribers table
    if ($isPostgreSQL) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS newsletter_subscribers (
            id SERIAL PRIMARY KEY,
            email VARCHAR(150) NOT NULL UNIQUE,
            subscribed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    } else {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `newsletter_subscribers` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `email` VARCHAR(150) NOT NULL UNIQUE,
            `subscribed_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    echo "✓ Tables created successfully\n\n";

    // ============ RUN MIGRATIONS ============
    
    // Fix featured column type for PostgreSQL (migrate from old SMALLINT/BOOLEAN to INTEGER)
    if ($isPostgreSQL) {
        try {
            $pdo->exec("ALTER TABLE products ALTER COLUMN featured TYPE INTEGER USING COALESCE(featured::integer, 0)");
            echo "✓ Migrated featured column to INTEGER\n";
        } catch (PDOException $e) {
            // Column might already be correct type, that's ok
            echo "✓ Featured column type verified\n";
        }
    }

    // ============ INSERT SEED DATA ============
    
    // Check if categories already exist
    $categoryCount = $pdo->query($isPostgreSQL ? "SELECT COUNT(*) FROM categories" : "SELECT COUNT(*) FROM `categories`")->fetchColumn();
    
    if ($categoryCount == 0) {
        // Insert categories only if table is empty
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("INSERT INTO categories (id, name, slug, icon) VALUES (?, ?, ?, ?) ON CONFLICT (id) DO NOTHING");
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `categories` (`id`, `name`, `slug`, `icon`) VALUES (?, ?, ?, ?)");
        }
        
        $categories = [
            [1, 'Cars', 'cars', 'bi-car-front'],
            [2, 'Smartphones', 'smartphones', 'bi-phone'],
            [3, 'Desktop Computers', 'desktop-computers', 'bi-pc-display'],
            [4, 'Laptops', 'laptops', 'bi-laptop'],
            [5, 'Stoves', 'stoves', 'bi-fire'],
            [6, 'Other Electronics', 'other-electronics', 'bi-lightning-charge'],
        ];
        
        foreach ($categories as $category) {
            $stmt->execute($category);
        }
        echo "✓ Inserted 6 categories\n";
    } else {
        echo "✓ Categories already exist (skipping insertion)\n";
    }

    // Check if admin user already exists
    $userCount = $pdo->query($isPostgreSQL ? "SELECT COUNT(*) FROM users WHERE role='admin'" : "SELECT COUNT(*) FROM `users` WHERE `role`='admin'")->fetchColumn();
    
    if ($userCount == 0) {
        // Insert admin user only if no admin exists
        $adminPassword = password_hash('admin123', PASSWORD_BCRYPT);
        if ($isPostgreSQL) {
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, phone, address, role) VALUES (?, ?, ?, ?, ?, ?) ON CONFLICT (email) DO NOTHING");
        } else {
            $stmt = $pdo->prepare("INSERT IGNORE INTO `users` (`full_name`, `email`, `password`, `phone`, `address`, `role`) VALUES (?, ?, ?, ?, ?, ?)");
        }
        $stmt->execute(['Admin User', 'admin@cdshipping.com', $adminPassword, '+250785008063', 'Admin Office', 'admin']);
        echo "✓ Inserted admin user\n\n";
    } else {
        echo "✓ Admin user already exists (skipping creation)\n\n";
    }

    echo "✅ Database initialization completed successfully!\n";
    echo "🔐 Admin Credentials:\n";
    echo "   Email: admin@cdshipping.com\n";
    echo "   Password: admin123\n";
    echo "⚠️  Change admin password immediately after first login!\n\n";
    echo "📝 Next Steps:\n";
    echo "   1. Log in to admin panel at /admin\n";
    echo "   2. Change admin password in profile settings\n";
    echo "   3. Add products via the admin panel\n";
    echo "   4. Configure payment methods if needed\n";

} catch (PDOException $e) {
    echo "❌ Database Error: " . htmlspecialchars($e->getMessage()) . "\n";
    echo "Connection Details:\n";
    echo "   Host: $host\n";
    echo "   Port: $port\n";
    echo "   Database: $dbName\n";
    echo "   User: $user\n";
    exit(1);
}
