<?php
require_once 'config/database.php';

echo "<h2>Quick Product Check</h2>";
$pdo = getDBConnection();

// Get the first product
$product = $pdo->query("SELECT * FROM products LIMIT 1")->fetch();

if (!$product) {
    echo "No products in database.";
} else {
    echo "<h3>Product: " . htmlspecialchars($product['name']) . "</h3>";
    echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
    print_r($product);
    echo "</pre>";
    
    echo "<h3>Image Field Values:</h3>";
    echo "<ul>";
    echo "<li>image: <code>" . ($product['image'] ?: '[NULL/EMPTY]') . "</code></li>";
    echo "<li>image2: <code>" . ($product['image2'] ?: '[NULL/EMPTY]') . "</code></li>";
    echo "<li>image3: <code>" . ($product['image3'] ?: '[NULL/EMPTY]') . "</code></li>";
    echo "</ul>";
    
    echo "<h3>File System Check:</h3>";
    if (!empty($product['image'])) {
        $path = UPLOAD_DIR . $product['image'];
        echo "<p>Checking: <code>" . $path . "</code>:</p>";
        if (file_exists($path)) {
            echo "<span style='color: green;'>✓ FILE EXISTS (" . filesize($path) . " bytes)</span>";
        } else {
            echo "<span style='color: red;'>✗ FILE NOT FOUND</span>";
        }
    } else {
        echo "<p><span style='color: orange;'>⚠️ image field is NULL/EMPTY in database</span></p>";
    }
}
?>
