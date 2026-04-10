<?php
require_once 'config/database.php';

echo "=== SITE CONFIGURATION DIAGNOSTIC ===\n\n";

// Display all key configuration
echo "SITE_URL: " . SITE_URL . "\n";
echo "UPLOAD_URL: " . UPLOAD_URL . "\n";
echo "UPLOAD_DIR: " . UPLOAD_DIR . "\n";
echo "ADMIN_URL: " . ADMIN_URL . "\n\n";

// Check if paths look correct
echo "--- VERIFICATION ---\n";
$expectedUploadUrl = "http://localhost/cdshipping/uploads/products/";
if (UPLOAD_URL === $expectedUploadUrl) {
    echo "✓ UPLOAD_URL looks correct\n";
} else {
    echo "✗ UPLOAD_URL might be wrong!\n";
    echo "  Current: " . UPLOAD_URL . "\n";
    echo "  Expected: " . $expectedUploadUrl . "\n";
}

// Check environment
echo "\n--- ENVIRONMENT ---\n";
echo "APP_ENV: " . APP_ENV . "\n";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'not set') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'not set') . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'not set') . "\n";

echo "\n--- .env FILE ---\n";
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    echo "✓ .env file exists\n";
    $content = file_get_contents($envFile);
    echo "Content:\n" . $content . "\n";
} else {
    echo "✗ .env file NOT FOUND\n";
}

echo "\n--- CHECKING FILESYSTEM ---\n";
if (is_dir(UPLOAD_DIR)) {
    $files = array_filter(scandir(UPLOAD_DIR), function($f) {
        return preg_match('/\.(jpg|jpeg|png|webp|gif)$/i', $f);
    });
    echo "✓ Upload directory exists\n";
    echo "  Image files: " . count($files) . "\n";
} else {
    echo "✗ Upload directory NOT FOUND: " . UPLOAD_DIR . "\n";
}
