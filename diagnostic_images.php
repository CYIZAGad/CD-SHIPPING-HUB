<?php
/**
 * IMAGE DEBUGGING TOOL - Check database and filesystem discrepancies
 * Visit: http://localhost/diagnostic_images.php
 */
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>";
echo "<title>Image Diagnostic Tool</title>";
echo "<style>body { font-family: Arial; margin: 20px; background: #f5f5f5; }";
echo ".section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }";
echo "h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }";
echo ".error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".success { background: #d4edda; color: #155724; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".warning { background: #fff3cd; color: #856404; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo ".info { background: #d1ecf1; color: #0c5460; padding: 10px; border-radius: 4px; margin: 10px 0; }";
echo "table { width: 100%; border-collapse: collapse; }";
echo "td, th { border: 1px solid #ddd; padding: 10px; text-align: left; }";
echo "th { background: #007bff; color: white; }";
echo "tr:nth-child(even) { background: #f9f9f9; }";
echo "code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New'; }";
echo "</style></head><body>";

echo "<h1>🖼️ Image Diagnostic Tool</h1>";

try {
    $pdo = getDBConnection();
    
    // 1. Database Connection Status
    echo "<div class='section'>";
    echo "<h2>1. Database Connection Status</h2>";
    $dbTest = $pdo->query("SELECT 1")->fetch();
    if ($dbTest) {
        echo "<div class='success'>✓ Database connection successful</div>";
    } else {
        echo "<div class='error'>✗ Database connection failed</div>";
    }
    echo "</div>";
    
    // 2. Products Table Check
    echo "<div class='section'>";
    echo "<h2>2. Products Table Structure</h2>";
    $schema = $pdo->query("DESCRIBE products")->fetchAll();
    echo "<table>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Default</th><th>Key</th></tr>";
    foreach ($schema as $col) {
        if (strpos($col['Field'], 'image') !== false) {
            echo "<tr style='background: #fff3cd;'>";
        } else {
            echo "<tr>";
        }
        echo "<td><strong>" . $col['Field'] . "</strong></td>";
        echo "<td>" . $col['Type'] . "</td>";
        echo "<td>" . $col['Null'] . "</td>";
        echo "<td>" . ($col['Default'] ?? 'N/A') . "</td>";
        echo "<td>" . ($col['Key'] ?? '') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "</div>";
    
    // 3. All Products with Image Status
    echo "<div class='section'>";
    echo "<h2>3. All Products - Image Field Status</h2>";
    $allProducts = $pdo->query("SELECT id, name, image, image2, image3, featured, status FROM products ORDER BY created_at DESC")->fetchAll();
    
    if (empty($allProducts)) {
        echo "<div class='warning'>⚠️ No products found in database</div>";
    } else {
        echo "<p>Total Products: <strong>" . count($allProducts) . "</strong></p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Image</th><th>Image2</th><th>Image3</th><th>Featured</th><th>Status</th></tr>";
        
        $hasNullImages = 0;
        foreach ($allProducts as $prod) {
            $hasNull = empty($prod['image']) || empty($prod['image2']) || empty($prod['image3']);
            echo "<tr style='background: " . ($hasNull ? '#fff3cd' : '#f9f9f9') . ";'>";
            echo "<td>" . $prod['id'] . "</td>";
            echo "<td>" . $prod['name'] . "</td>";
            echo "<td>" . ($prod['image'] ?: '<span style="color:red;">NULL/EMPTY</span>') . "</td>";
            echo "<td>" . ($prod['image2'] ?: '<span style="color:red;">NULL/EMPTY</span>') . "</td>";
            echo "<td>" . ($prod['image3'] ?: '<span style="color:red;">NULL/EMPTY</span>') . "</td>";
            echo "<td>" . $prod['featured'] . "</td>";
            echo "<td>" . $prod['status'] . "</td>";
            echo "</tr>";
            if ($hasNull) $hasNullImages++;
        }
        echo "</table>";
        
        if ($hasNullImages > 0) {
            echo "<div class='warning'>⚠️ <strong>" . $hasNullImages . "</strong> products have missing/empty image fields</div>";
        }
    }
    echo "</div>";
    
    // 4. Filesystem Check
    echo "<div class='section'>";
    echo "<h2>4. Upload Directory Status</h2>";
    echo "<p>Upload Directory: <code>" . UPLOAD_DIR . "</code></p>";
    
    if (!is_dir(UPLOAD_DIR)) {
        echo "<div class='error'>✗ Upload directory does NOT exist</div>";
    } else {
        echo "<div class='success'>✓ Upload directory exists</div>";
        
        $files = scandir(UPLOAD_DIR);
        $imageFiles = array_filter($files, function($f) {
            return !in_array($f, ['.', '..', '.htaccess']) && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f);
        });
        
        // Get file sizes
        $totalSize = 0;
        $fileInfo = [];
        foreach ($imageFiles as $file) {
            $size = filesize(UPLOAD_DIR . $file);
            $totalSize += $size;
            $fileInfo[$file] = $size;
        }
        
        echo "<p>Total image files: <strong>" . count($imageFiles) . "</strong></p>";
        echo "<p>Total size: <strong>" . round($totalSize / 1024 / 1024, 2) . " MB</strong></p>";
        
        if (count($imageFiles) > 0) {
            echo "<h3>Sample Files (first 20):</h3>";
            echo "<ul>";
            foreach (array_slice($imageFiles, 0, 20) as $file) {
                $size = filesize(UPLOAD_DIR . $file);
                echo "<li><code>" . $file . "</code> (" . round($size / 1024, 2) . " KB)</li>";
            }
            echo "</ul>";
        }
    }
    echo "</div>";
    
    // 5. Database vs Filesystem Mismatch
    echo "<div class='section'>";
    echo "<h2>5. Database vs Filesystem Verification</h2>";
    
    $filesInDB = [];
    foreach ($allProducts as $prod) {
        if (!empty($prod['image'])) $filesInDB[] = $prod['image'];
        if (!empty($prod['image2'])) $filesInDB[] = $prod['image2'];
        if (!empty($prod['image3'])) $filesInDB[] = $prod['image3'];
    }
    
    $filesInDB = array_unique($filesInDB);
    $filesInFilesystem = array_filter(scandir(UPLOAD_DIR), function($f) {
        return !in_array($f, ['.', '..', '.htaccess']) && preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f);
    });
    
    $missingFiles = array_diff($filesInDB, $filesInFilesystem);
    $orphanedFiles = array_diff($filesInFilesystem, $filesInDB);
    
    echo "<p>Files referenced in DB: <strong>" . count($filesInDB) . "</strong></p>";
    echo "<p>Files in filesystem: <strong>" . count($filesInFilesystem) . "</strong></p>";
    
    if (empty($missingFiles)) {
        echo "<div class='success'>✓ All images in database exist on filesystem</div>";
    } else {
        echo "<div class='error'>✗ <strong>" . count($missingFiles) . "</strong> images referenced in database are MISSING from filesystem!</div>";
        echo "<p>Missing files:</p><ul>";
        foreach (array_slice($missingFiles, 0, 10) as $file) {
            echo "<li><code>" . $file . "</code></li>";
        }
        if (count($missingFiles) > 10) {
            echo "<li>... and " . (count($missingFiles) - 10) . " more</li>";
        }
        echo "</ul>";
    }
    
    if (!empty($orphanedFiles)) {
        echo "<div class='warning'>⚠️ <strong>" . count($orphanedFiles) . "</strong> files exist on filesystem but are NOT in database</div>";
    } else {
        echo "<div class='success'>✓ No orphaned files on filesystem</div>";
    }
    echo "</div>";
    
    // 6. Query Test
    echo "<div class='section'>";
    echo "<h2>6. Direct Query Test</h2>";
    $testQuery = "SELECT id, name, image FROM products WHERE image IS NOT NULL AND image != '' LIMIT 1";
    $testResult = $pdo->query($testQuery)->fetch();
    
    if ($testResult) {
        echo "<div class='success'>✓ Query returned data</div>";
        echo "<p>Sample product:</p>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Image</th></tr>";
        echo "<tr>";
        echo "<td>" . $testResult['id'] . "</td>";
        echo "<td>" . $testResult['name'] . "</td>";
        echo "<td><code>" . $testResult['image'] . "</code></td>";
        echo "</tr>";
        echo "</table>";
        
        // Check if file exists
        $imagePath = UPLOAD_DIR . $testResult['image'];
        if (file_exists($imagePath)) {
            echo "<div class='success'>✓ Image file exists: " . $imagePath . "</div>";
            echo "<p>File size: " . filesize($imagePath) . " bytes</p>";
        } else {
            echo "<div class='error'>✗ Image file does NOT exist: " . $imagePath . "</div>";
        }
    } else {
        echo "<div class='warning'>⚠️ No products with non-empty image field found</div>";
    }
    echo "</div>";
    
    // 7. Featured Products Test
    echo "<div class='section'>";
    echo "<h2>7. Featured Products Query</h2>";
    $featuredQuery = "SELECT id, name, image FROM products WHERE featured = 1 AND status = 'active'";
    $featured = $pdo->query($featuredQuery)->fetchAll();
    echo "<p>Featured products found: <strong>" . count($featured) . "</strong></p>";
    if (!empty($featured)) {
        echo "<table>";
        echo "<tr><th>ID</th><th>Name</th><th>Image</th></tr>";
        foreach (array_slice($featured, 0, 5) as $prod) {
            echo "<tr>";
            echo "<td>" . $prod['id'] . "</td>";
            echo "<td>" . $prod['name'] . "</td>";
            echo "<td><code>" . ($prod['image'] ?: 'NULL') . "</code></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'><strong>ERROR:</strong> " . htmlspecialchars($e->getMessage()) . "</div>";
}

echo "</body></html>";
