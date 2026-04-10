<?php
require_once 'config/database.php';

$pdo = getDBConnection();

// Check products table schema
echo "==== PRODUCTS TABLE SCHEMA ====\n";
$schema = $pdo->query("DESCRIBE products")->fetchAll();
foreach ($schema as $col) {
    echo $col['Field'] . " (" . $col['Type'] . ") - Null: " . $col['Null'] . " - Default: " . $col['Default'] . "\n";
}

echo "\n==== FIRST 5 PRODUCTS (IMAGE DATA) ====\n";
$products = $pdo->query("SELECT id, name, image, image2, image3, created_at, updated_at FROM products LIMIT 5")->fetchAll();
foreach ($products as $p) {
    echo "ID: " . $p['id'] . " | Name: " . $p['name'] . "\n";
    echo "  image: " . ($p['image'] ?: 'NULL/EMPTY') . "\n";
    echo "  image2: " . ($p['image2'] ?: 'NULL/EMPTY') . "\n";
    echo "  image3: " . ($p['image3'] ?: 'NULL/EMPTY') . "\n";
    echo "  created_at: " . $p['created_at'] . "\n";
    echo "  updated_at: " . $p['updated_at'] . "\n";
    echo "\n";
}

// Check for products with NULL images
echo "==== PRODUCTS WITH NULL/EMPTY IMAGES ====\n";
$nullProducts = $pdo->query("SELECT id, name, image FROM products WHERE image IS NULL OR image = ''")->fetchAll();
echo "Count: " . count($nullProducts) . "\n";
foreach ($nullProducts as $p) {
    echo "  ID: " . $p['id'] . " | Name: " . $p['name'] . " | image: NULL/EMPTY\n";
}

// Check the actual SQL query being used to retrieve products (like in index.php)
echo "\n==== TESTING FEATURED PRODUCTS QUERY ====\n";
$query = "SELECT p.*, c.name as category FROM products p 
          LEFT JOIN categories c ON p.category_id = c.id 
          WHERE p.featured = 1 AND p.status = 'active' 
          LIMIT 1";
$test = $pdo->query($query)->fetch();
if ($test) {
    echo "Featured Product Found:\n";
    echo "  ID: " . $test['id'] . "\n";
    echo "  Name: " . $test['name'] . "\n";
    echo "  Image: " . ($test['image'] ?: 'NULL/EMPTY') . "\n";
} else {
    echo "No featured products found\n";
}

echo "\n==== CHECKING ALL COLUMNS IN PRODUCTS TABLE ====\n";
$firstProduct = $pdo->query("SELECT * FROM products LIMIT 1")->fetch();
if ($firstProduct) {
    echo "Columns in first product:\n";
    foreach ($firstProduct as $key => $val) {
        echo "  $key: " . ($val ?: '[NULL/EMPTY]') . "\n";
    }
}
