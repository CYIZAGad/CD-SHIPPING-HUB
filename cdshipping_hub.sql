-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Mar 31, 2026 at 10:54 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `cdshipping_hub`
--

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE `categories` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `slug` varchar(100) NOT NULL,
  `icon` varchar(50) DEFAULT 'bi-grid',
  `image` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `categories`
--

INSERT INTO `categories` (`id`, `name`, `slug`, `icon`, `image`, `created_at`) VALUES
(1, 'Cars', 'cars', 'bi-car-front', NULL, '2026-03-14 15:41:50'),
(2, 'Laptops', 'laptops', 'bi-laptop', NULL, '2026-03-14 15:41:50'),
(3, 'Desktop Computers', 'desktops', 'bi-pc-display', NULL, '2026-03-14 15:41:50'),
(4, 'Smartphones', 'smartphones', 'bi-phone', NULL, '2026-03-14 15:41:50'),
(5, 'Stoves', 'stoves', 'bi-fire', NULL, '2026-03-14 15:41:50'),
(6, 'Other Electronics', 'other-electronics', 'bi-cpu', NULL, '2026-03-14 15:41:50');

-- --------------------------------------------------------

--
-- Table structure for table `newsletter_subscribers`
--

CREATE TABLE `newsletter_subscribers` (
  `id` int(11) NOT NULL,
  `email` varchar(150) NOT NULL,
  `subscribed_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `newsletter_subscribers`
--

INSERT INTO `newsletter_subscribers` (`id`, `email`, `subscribed_at`) VALUES
(1, 'cyizagad69@gmail.com', '2026-03-30 18:31:00');

-- --------------------------------------------------------

--
-- Table structure for table `notifications`
--

CREATE TABLE `notifications` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `title` varchar(200) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `notifications`
--

INSERT INTO `notifications` (`id`, `user_id`, `title`, `message`, `is_read`, `created_at`) VALUES
(1, 3, 'Order Placed Successfully', 'Your order #CD202603146286B8 has been placed. Total: $175,000.00. We will process your payment shortly.', 0, '2026-03-14 16:34:38'),
(2, 1, 'Order Placed Successfully', 'Your order #CD202603158E87D5 has been placed. Total: $37,999.00. We will process your payment shortly.', 0, '2026-03-15 11:45:27'),
(3, 1, 'Payment Confirmed', 'Your payment for order #CD202603158E87D5 has been confirmed!', 0, '2026-03-15 11:46:35'),
(4, 1, 'Order Delivered', 'Your order #CD202603158E87D5 has been delivered.', 0, '2026-03-15 11:46:45');

-- --------------------------------------------------------

--
-- Table structure for table `orders`
--

CREATE TABLE `orders` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `order_number` varchar(20) NOT NULL,
  `total_amount` decimal(10,2) NOT NULL,
  `shipping_address` text NOT NULL,
  `phone` varchar(20) NOT NULL,
  `payment_status` enum('pending','confirmed','rejected') DEFAULT 'pending',
  `order_status` enum('pending','approved','shipped','delivered','cancelled') DEFAULT 'pending',
  `payment_reference` varchar(100) DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `orders`
--

INSERT INTO `orders` (`id`, `user_id`, `order_number`, `total_amount`, `shipping_address`, `phone`, `payment_status`, `order_status`, `payment_reference`, `notes`, `created_at`, `updated_at`) VALUES
(1, 3, 'CD202603146286B8', 175000.00, 'kigali', '+250728178335', 'pending', 'pending', '34455', '', '2026-03-14 16:34:38', '2026-03-14 16:34:38'),
(2, 1, 'CD202603158E87D5', 37999.00, 'Admin Office', '+1234567890', 'confirmed', 'delivered', '34455', '', '2026-03-15 11:45:27', '2026-03-15 11:46:45');

-- --------------------------------------------------------

--
-- Table structure for table `order_items`
--

CREATE TABLE `order_items` (
  `id` int(11) NOT NULL,
  `order_id` int(11) NOT NULL,
  `product_id` int(11) NOT NULL,
  `product_name` varchar(200) NOT NULL,
  `price` decimal(10,2) NOT NULL,
  `quantity` int(11) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `order_items`
--

INSERT INTO `order_items` (`id`, `order_id`, `product_id`, `product_name`, `price`, `quantity`, `subtotal`) VALUES
(1, 1, 1, 'Toyota Camry 2024', 35000.00, 5, 175000.00),
(2, 2, 1, 'Toyota Camry 2024', 35000.00, 1, 35000.00),
(3, 2, 5, 'Gaming Desktop RTX 4080', 2999.00, 1, 2999.00);

-- --------------------------------------------------------

--
-- Table structure for table `products`
--

CREATE TABLE `products` (
  `id` int(11) NOT NULL,
  `category_id` int(11) NOT NULL,
  `name` varchar(200) NOT NULL,
  `slug` varchar(200) NOT NULL,
  `description` text DEFAULT NULL,
  `specifications` text DEFAULT NULL,
  `price` decimal(10,2) NOT NULL,
  `old_price` decimal(10,2) DEFAULT NULL,
  `stock` int(11) DEFAULT 0,
  `image` varchar(255) DEFAULT NULL,
  `image2` varchar(255) DEFAULT NULL,
  `image3` varchar(255) DEFAULT NULL,
  `featured` tinyint(1) DEFAULT 0,
  `status` enum('active','inactive') DEFAULT 'active',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `products`
--

INSERT INTO `products` (`id`, `category_id`, `name`, `slug`, `description`, `specifications`, `price`, `old_price`, `stock`, `image`, `image2`, `image3`, `featured`, `status`, `created_at`, `updated_at`) VALUES
(1, 1, 'Toyota Camry 2024', 'toyota-camry-2024', 'Brand new Toyota Camry 2024 model with advanced safety features, hybrid engine, and premium interior.', 'Engine: 2.5L Hybrid|Power: 208 HP|Transmission: CVT|Fuel: Hybrid|Color: Pearl White', 35000.00, 38000.00, 4, 'toyota-camry-2024-image-1773505125.webp', 'toyota-camry-2024-image2-1773505125.jpg', 'toyota-camry-2024-image3-1773505125.jpg', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 11:46:35'),
(2, 1, 'Honda Civic 2024', 'honda-civic-2024', 'Sleek and efficient Honda Civic with turbocharged engine and modern tech features.', 'Engine: 1.5L Turbo|Power: 180 HP|Transmission: CVT|Fuel: Gasoline|Color: Crystal Black', 28000.00, 30000.00, 8, 'honda-civic-2024-image-1773571553.jpg', 'honda-civic-2024-image2-1773571553.jpg', 'honda-civic-2024-image3-1773571553.jpg', 0, 'active', '2026-03-14 15:41:50', '2026-03-15 10:45:53'),
(3, 2, 'MacBook Pro 16\\&quot; M3', 'macbook-pro-16-quot-m3', 'Apple MacBook Pro 16-inch with M3 Pro chip, stunning Liquid Retina XDR display.', 'Chip: Apple M3 Pro|RAM: 18GB|Storage: 512GB SSD|Display: 16.2\\&quot; Liquid Retina XDR|Battery: Up to 22 hours', 2499.00, 2699.00, 15, 'macbook-pro-16-quot-m3-image-1773571657.webp', 'macbook-pro-16-quot-m3-image2-1773571657.jpg', 'macbook-pro-16-quot-m3-image3-1773571657.png', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 10:47:37'),
(4, 2, 'Dell XPS 15', 'dell-xps-15', 'Premium Dell XPS 15 laptop with InfinityEdge display and powerful performance.', 'Processor: Intel i7-13700H|RAM: 16GB DDR5|Storage: 512GB SSD|Display: 15.6\\&quot; 3.5K OLED|GPU: RTX 4050', 1799.00, 1999.00, 20, 'dell-xps-15-image-1773571719.jpg', 'dell-xps-15-image2-1773571719.jpg', 'dell-xps-15-image3-1773571719.jpg', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 10:48:39'),
(5, 3, 'Gaming Desktop RTX 4080', 'gaming-desktop-rtx-4080', 'High-end gaming desktop with RTX 4080 graphics and liquid cooling system.', 'CPU: Intel i9-14900K|RAM: 32GB DDR5|Storage: 2TB NVMe SSD|GPU: RTX 4080 16GB|PSU: 850W Gold', 2999.00, 3299.00, 9, 'gaming-desktop-rtx-4080-image-1773571786.jpg', 'gaming-desktop-rtx-4080-image2-1773571786.jpg', 'gaming-desktop-rtx-4080-image3-1773571786.jpg', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 11:46:35'),
(6, 3, 'HP Pavilion Desktop', 'hp-pavilion-desktop', 'Reliable HP Pavilion desktop perfect for home and office use.', 'CPU: Intel i5-13400|RAM: 16GB DDR4|Storage: 512GB SSD|GPU: Intel UHD 730|OS: Windows 11', 699.00, 799.00, 25, 'hp-pavilion-desktop-image-1773573904.jpg', 'hp-pavilion-desktop-image2-1773573904.jpg', 'hp-pavilion-desktop-image3-1773573904.jpg', 0, 'active', '2026-03-14 15:41:50', '2026-03-15 11:25:04'),
(7, 4, 'iPhone 15 Pro Max', 'iphone-15-pro-max', 'Apple iPhone 15 Pro Max with titanium design and A17 Pro chip.', 'Chip: A17 Pro|Display: 6.7\\&quot; Super Retina XDR|Camera: 48MP Triple|Storage: 256GB|Battery: All-day', 1199.00, NULL, 30, 'iphone-15-pro-max-image-1773573955.png', 'iphone-15-pro-max-image2-1773573955.webp', 'iphone-15-pro-max-image3-1773573955.png', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 11:25:55'),
(8, 4, 'Samsung Galaxy S24 Ultra', 'samsung-galaxy-s24-ultra', 'Samsung flagship with Galaxy AI features and S Pen integration.', 'Chip: Snapdragon 8 Gen 3|Display: 6.8\\&quot; QHD+ AMOLED|Camera: 200MP Quad|Storage: 256GB|Battery: 5000mAh', 1099.00, 1199.00, 25, 'samsung-galaxy-s24-ultra-image-1773574022.jpg', 'samsung-galaxy-s24-ultra-image2-1773574022.jpg', 'samsung-galaxy-s24-ultra-image3-1773574022.jpg', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 11:27:02'),
(9, 5, 'Samsung Smart Electric Range', 'samsung-smart-electric-range', 'Samsung smart electric range with Wi-Fi connectivity and air fry.', 'Type: Electric|Capacity: 6.3 cu ft|Burners: 5|Features: Air Fry, Wi-Fi|Color: Stainless Steel', 899.00, 1099.00, 12, 'samsung-smart-electric-range-image-1773574121.jpg', 'samsung-smart-electric-range-image2-1773574121.jpg', 'samsung-smart-electric-range-image3-1773574121.jpg', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 11:28:41'),
(10, 5, 'LG Gas Double Oven Range', 'lg-gas-double-oven-range', 'LG gas range with double oven for versatile cooking.', 'Type: Gas|Capacity: 6.9 cu ft|Burners: 5|Features: ProBake, EasyClean|Color: Black Stainless', 1299.00, 1499.00, 8, 'lg-gas-double-oven-range-image-1773574231.jpg', 'lg-gas-double-oven-range-image2-1773574231.jpg', 'lg-gas-double-oven-range-image3-1773574231.jpg', 0, 'active', '2026-03-14 15:41:50', '2026-03-15 11:30:31'),
(11, 6, 'Sony 65\\&quot; 4K OLED TV', 'sony-65-quot-4k-oled-tv', 'Sony Bravia XR 65-inch 4K OLED TV with cognitive processor.', 'Display: 65\\&quot; 4K OLED|HDR: Dolby Vision, HDR10|Audio: Acoustic Surface Audio+|Smart: Google TV|Refresh: 120Hz', 1799.00, 2199.00, 10, 'sony-65-quot-4k-oled-tv-image-1773574287.webp', 'sony-65-quot-4k-oled-tv-image2-1773574287.webp', 'sony-65-quot-4k-oled-tv-image3-1773574287.jpg', 1, 'active', '2026-03-14 15:41:50', '2026-03-15 11:31:27'),
(12, 6, 'Bose QuietComfort Headphones', 'bose-quietcomfort-headphones', 'Premium noise-cancelling headphones with world-class ANC.', 'Type: Over-ear|ANC: Yes|Battery: 24 hours|Connectivity: Bluetooth 5.3|Driver: 35mm', 349.00, 399.00, 40, 'bose-quietcomfort-headphones-image-1773574346.webp', 'bose-quietcomfort-headphones-image2-1773574346.png', 'bose-quietcomfort-headphones-image3-1773574346.jpg', 0, 'active', '2026-03-14 15:41:50', '2026-03-15 11:32:26'),
(25, 1, 'Rivian R2', 'rivian-r2', 'The R2 is slightly larger than the Model Y in almost every metric', 'it&#039;s 0.9 in wider, 3.1 in taller, and its wheelbase is 1.8 in longer. However, Tesla&#039;s crossover trumps the R2&#039;s overall length by 2.8 inches. In terms of cargo capacity, the R2 shines', 45000.00, 48000.00, 1, 'rivian-r2-image-1774550348.jpg', 'rivian-r2-image2-1774550348.jpg', 'rivian-r2-image3-1774550348.webp', 1, 'active', '2026-03-26 18:39:08', '2026-03-26 18:39:08'),
(26, 1, 'Jeep Recon', 'jeep-recon', 'The 2026 Jeep Recon is an all-electric, &quot;trail-rated&quot; SUV designed for rugged off-road capability, featuring 650 horsepower, a ~250-mile range, and removable doors/windows', 'Expected in 2026, it is a boxy, unibody vehicle with 33-inch tires, electric locking differentials, and a 100 kWh battery, offering a 0-60 mph time of 3.6 seconds', 65000.00, 65500.00, 4, 'jeep-recon-image-1774551065.webp', 'jeep-recon-image2-1774551065.webp', 'jeep-recon-image3-1774551065.jpg', 1, 'active', '2026-03-26 18:51:05', '2026-03-26 18:51:05'),
(27, 1, 'BMW', 'bmw', 'BMW 7 Series combined with the BMW N57/M57 diesel engine family, which are frequently used in both 7 Series models (e.g., 730d, 750d) and for high-performance engine swaps', 'Reliability: The M57 is considered highly reliable, with many engines reaching over 300,000 miles, although high-mileage engines may face turbo or EGR issues.\r\nTuning Capability: The M57, particularly the M57N (204–218 HP), is popular for tuning, easily achieving 240–250 HP with a simple remap.\r\nSwap Popularity: M57 engines are popular for engine swaps into vehicles like Land Rover Defenders due to their high torque and reliability.', 97300.00, 168500.00, 5, 'bmw-image-1774551564.jpg', 'bmw-image2-1774551564.jpg', 'bmw-image3-1774551564.webp', 1, 'active', '2026-03-26 18:59:24', '2026-03-26 18:59:24'),
(28, 3, 'desktop computer', 'desktop-computer', 'New 2026 desktop models emphasize AI-accelerated performance, featuring Intel Core Ultra processors, NVIDIA RTX 50-series graphics, and dedicated NPUs for advanced productivity.', 'Key releases include the powerful Dell XPS 8960, the compact HP Envy TE02, and the efficient M4-chip Apple Mac mini, designed for AI tasks, gaming, and 4K editing.', 1500.00, 3000.00, 23, 'desktop-computer-image-1774552196.webp', 'desktop-computer-image2-1774552196.jpg', 'desktop-computer-image3-1774552196.jpg', 1, 'active', '2026-03-26 19:09:56', '2026-03-26 19:09:56'),
(29, 4, 'iPhone 17 Pro Max', 'iphone-17-pro-max', 'We start 2026 with the Apple iPhone 17 Pro Max at the top spot as our best phone overall', 'Weight: 199gDimensions: 149.6 x 71.5 x 8.25mmOS: iOS 18Screen size: 6.3-inchResolution: 2622 x 1206 pixelsCPU: A18 ProStorage: 128GB / 256GB / 512GB / 1TBRear cameras: 48MP main (24mm, f/1.78), 48MP ultra-wide (13mm, f/2.2), 12MP telephoto with 5x optical zoom (120mm, f/2.8)Front camera: 12MP (f/1.9)', 2200000.00, 2500000.00, 12, 'iphone-17-pro-max-image-1774552988.webp', 'iphone-17-pro-max-image2-1774552988.webp', 'iphone-17-pro-max-image3-1774552988.webp', 1, 'active', '2026-03-26 19:23:08', '2026-03-26 19:23:08'),
(30, 2, 'Lenovo laptops', 'lenovo-laptops', 'Lenovo laptops are recognized for their robust build quality, premium keyboards, and diverse range catering to business, gaming, and everyday use, often featuring AI-enhanced performance and OLED displays.', 'Intel Core (up to i7/i9) or AMD Ryzen (up to 8000 series) processors, Windows 11, 8GB-32GB DDR5 RAM, and 512GB-1TB SSDs.', 700.00, 1500.00, 10, 'lenovo-laptops-image-1774894699.jpg', 'lenovo-laptops-image2-1774894699.jpg', 'lenovo-laptops-image3-1774894699.jpg', 1, 'active', '2026-03-30 18:18:19', '2026-03-30 18:18:19');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `password` varchar(255) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `address` text NOT NULL,
  `role` enum('client','admin') DEFAULT 'client',
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_expires` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `full_name`, `email`, `password`, `phone`, `address`, `role`, `reset_token`, `reset_expires`, `created_at`, `updated_at`) VALUES
(1, 'Administrator', 'admin@cdshipping.com', '$2y$10$fE/PSSEDm/0GBAvqWPZcU.nQ4/N0T.dVQAcndkAkPPmDFwLnIOGb6', '+1234567890', 'Admin Office', 'admin', NULL, NULL, '2026-03-14 15:41:50', '2026-03-14 15:41:50'),
(3, 'CYIZA Gad', 'cyizagad@gmail.com', '$2y$10$ddKzrIcai7Rfeexw/8H7k.53yrwtAcE2YHAwDYae9bK5tevCPt1C.', '+250728178335', 'kigali', 'client', NULL, NULL, '2026-03-14 16:23:54', '2026-03-14 16:23:54');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `categories`
--
ALTER TABLE `categories`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_categories_slug` (`slug`);

--
-- Indexes for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- Indexes for table `notifications`
--
ALTER TABLE `notifications`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_notifications_user_read` (`user_id`,`is_read`);

--
-- Indexes for table `orders`
--
ALTER TABLE `orders`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `order_number` (`order_number`),
  ADD KEY `idx_orders_user_created` (`user_id`,`created_at`),
  ADD KEY `idx_orders_payment_status` (`payment_status`),
  ADD KEY `idx_orders_order_status` (`order_status`),
  ADD KEY `idx_orders_order_number` (`order_number`);

--
-- Indexes for table `order_items`
--
ALTER TABLE `order_items`
  ADD PRIMARY KEY (`id`),
  ADD KEY `product_id` (`product_id`),
  ADD KEY `idx_order_items_order_id` (`order_id`);

--
-- Indexes for table `products`
--
ALTER TABLE `products`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`),
  ADD KEY `idx_products_category_status` (`category_id`,`status`),
  ADD KEY `idx_products_slug` (`slug`),
  ADD KEY `idx_products_created_at` (`created_at`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_users_role` (`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `categories`
--
ALTER TABLE `categories`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT for table `newsletter_subscribers`
--
ALTER TABLE `newsletter_subscribers`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `notifications`
--
ALTER TABLE `notifications`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `orders`
--
ALTER TABLE `orders`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `order_items`
--
ALTER TABLE `order_items`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `products`
--
ALTER TABLE `products`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `notifications`
--
ALTER TABLE `notifications`
  ADD CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `orders`
--
ALTER TABLE `orders`
  ADD CONSTRAINT `orders_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `order_items`
--
ALTER TABLE `order_items`
  ADD CONSTRAINT `order_items_ibfk_1` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `order_items_ibfk_2` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `products`
--
ALTER TABLE `products`
  ADD CONSTRAINT `products_ibfk_1` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
