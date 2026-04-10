<?php
require_once 'config/database.php';

$pdo = getDBConnection();

echo "=== DATABASE IMAGE CHECK ===\n\n";

// Check total products
$total = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
echo "Total products in database: $total\n\n";

// Check how many have images
echo "--- IMAGE FIELD STATUS ---\n";
$hasImage = $pdo->query("SELECT COUNT(*) FROM products WHERE image IS NOT NULL AND image != ''")->fetchColumn();
$nullImage = $pdo->query("SELECT COUNT(*) FROM products WHERE image IS NULL OR image = ''")->fetchColumn();

echo "Products WITH image data: $hasImage\n";
echo "Products WITHOUT image data (NULL/EMPTY): $nullImage\n\n";

// Get first 10 products with all their data
echo "--- FIRST 10 PRODUCTS ---\n";
$products = $pdo->query("SELECT id, name, image, image2, image3, featured, status FROM products LIMIT 10")->fetchAll();

foreach ($products as $p) {
    echo "\n[ID: {$p['id']}] {$p['name']}\n";
    echo "  Featured: {$p['featured']} | Status: {$p['status']}\n";
    echo "  image:   " . ($p['image'] ?: 'NULL/EMPTY') . "\n";
    echo "  image2:  " . ($p['image2'] ?: 'NULL/EMPTY') . "\n";
    echo "  image3:  " . ($p['image3'] ?: 'NULL/EMPTY') . "\n";
}

// Check file system
echo "\n\n--- FILESYSTEM CHECK ---\n";
echo "Upload directory: " . UPLOAD_DIR . "\n";
if (is_dir(UPLOAD_DIR)) {
    $files = scandir(UPLOAD_DIR);
    $imageCount = 0;
    foreach ($files as $f) {
        if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f)) {
            $imageCount++;
        }
    }
    echo "Image files on disk: $imageCount\n";
    
    // Show first 10 files
    echo "\nFirst 10 files:\n";
    $count = 0;
    foreach ($files as $f) {
        if (preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f)) {
            $size = filesize(UPLOAD_DIR . $f);
            echo "  - " . $f . " (" . round($size/1024, 2) . " KB)\n";
            if (++$count >= 10) break;
        }
    }
} else {
    echo "ERROR: Upload directory does not exist!\n";
}

echo "\n\n=== FEATURED PRODUCTS QUERY ===\n";
$featured = $pdo->query("SELECT id, name, image, featured, status FROM products WHERE featured = 1 AND status = 'active' LIMIT 5")->fetchAll();
echo "Featured products: " . count($featured) . "\n";
foreach ($featured as $p) {
    echo "  - {$p['name']}: image=" . ($p['image'] ?: 'NULL') . "\n";
}
