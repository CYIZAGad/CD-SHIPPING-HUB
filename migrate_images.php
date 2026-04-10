<?php
/**
 * DATABASE MIGRATION SCRIPT
 * Converts existing image system to use database storage + filesystem
 * This handles ephemeral storage on Render
 * 
 * Run this ONCE after deploying the code changes
 * Visit: http://localhost/migrate_images.php
 */

require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Image Migration</title>";
echo "<style>body{font-family:Arial;margin:20px;background:#f5f5f5}";
echo ".section{background:white;padding:20px;margin:20px 0;border-radius:8px;box-shadow:0 2px 4px rgba(0,0,0,0.1)}";
echo "h2{color:#333;border-bottom:2px solid #007bff;padding-bottom:10px}";
echo ".success{background:#d4edda;color:#155724;padding:10px;border-radius:4px;margin:10px 0}";
echo ".error{background:#f8d7da;color:#721c24;padding:10px;border-radius:4px;margin:10px 0}";
echo ".warning{background:#fff3cd;color:#856404;padding:10px;border-radius:4px;margin:10px 0}";
echo ".info{background:#d1ecf1;color:#0c5460;padding:10px;border-radius:4px;margin:10px 0}";
echo "</style></head><body>";

echo "<h1>🖼️ Image System Migration</h1>";
echo "<p>This script converts your database to support ephemeral storage (Render)</p>";

try {
    $pdo = getDBConnection();
    
    // Step 1: Add columns if they don't exist
    echo "<div class='section'><h2>Step 1: Adding Database Columns</h2>";
    
    $columns = ['image_base64', 'image2_base64', 'image3_base64'];
    foreach ($columns as $col) {
        try {
            $pdo->exec("ALTER TABLE products ADD COLUMN $col LONGTEXT DEFAULT NULL");
            echo "<div class='success'>✓ Added column: $col</div>";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column') !== false) {
                echo "<div class='info'>ℹ️  Column already exists: $col</div>";
            } else {
                echo "<div class='error'>✗ Error adding $col: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    echo "</div>";
    
    // Step 2: Convert existing images to base64
    echo "<div class='section'><h2>Step 2: Converting Images to Base64</h2>";
    
    $products = $pdo->query("SELECT id, name, image, image2, image3 FROM products")->fetchAll();
    
    if (empty($products)) {
        echo "<div class='warning'>⚠️  No products found in database</div>";
    } else {
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        
        echo "<p>Processing " . count($products) . " products...</p>";
        
        foreach ($products as $prod) {
            $base64Data = ['image_base64' => null, 'image2_base64' => null, 'image3_base64' => null];
            $hasImages = false;
            
            // Convert each image
            for ($i = 1; $i <= 3; $i++) {
                $field = $i === 1 ? 'image' : "image$i";
                $base64Field = $i === 1 ? 'image_base64' : "image{$i}_base64";
                
                if (!empty($prod[$field])) {
                    $filePath = UPLOAD_DIR . $prod[$field];
                    if (file_exists($filePath)) {
                        $finfo = new finfo(FILEINFO_MIME_TYPE);
                        $mimeType = $finfo->file($filePath);
                        $imageData = file_get_contents($filePath);
                        $base64Data[$base64Field] = 'data:' . $mimeType . ';base64,' . base64_encode($imageData);
                        $hasImages = true;
                    }
                }
            }
            
            // Update database if we found images
            if ($hasImages) {
                try {
                    $stmt = $pdo->prepare("UPDATE products SET image_base64=?, image2_base64=?, image3_base64=? WHERE id=?");
                    $stmt->execute([$base64Data['image_base64'], $base64Data['image2_base64'], $base64Data['image3_base64'], $prod['id']]);
                    $updated++;
                } catch (PDOException $e) {
                    echo "<div class='error'>✗ Failed to update product {$prod['id']}: " . htmlspecialchars($e->getMessage()) . "</div>";
                    $failed++;
                }
            } else {
                $skipped++;
            }
        }
        
        echo "<div class='success'>✓ Updated: $updated products</div>";
        if ($skipped > 0) {
            echo "<div class='info'>ℹ️  Skipped: $skipped products (no images)</div>";
        }
        if ($failed > 0) {
            echo "<div class='error'>✗ Failed: $failed products</div>";
        }
    }
    echo "</div>";
    
    // Step 3: Verification
    echo "<div class='section'><h2>Step 3: Verification</h2>";
    
    $productsWithBase64 = $pdo->query("SELECT COUNT(*) FROM products WHERE image_base64 IS NOT NULL OR image2_base64 IS NOT NULL OR image3_base64 IS NOT NULL")->fetchColumn();
    
    echo "<p>Products with base64 data: <strong>$productsWithBase64</strong></p>";
    
    if ($productsWithBase64 > 0) {
        echo "<div class='success'>✓ Migration successful!</div>";
        echo "<p><strong>Next steps:</strong></p>";
        echo "<ul>";
        echo "<li>Images will now load from database if filesystem files are missing</li>";
        echo "<li>New image uploads will automatically store both filesystem and database copies</li>";
        echo "<li>On Render, if files expire, images will load from database</li>";
        echo "</ul>";
    } else {
        echo "<div class='warning'>⚠️  No base64 data was created. Check that image files exist in " . UPLOAD_DIR . "</div>";
    }
    echo "</div>";
    
    echo "<div class='section'>";
    echo "<p><strong>✅ Migration complete!</strong> You can now delete this file.</p>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
