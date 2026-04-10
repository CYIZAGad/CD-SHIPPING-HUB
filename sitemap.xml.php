<?php
/**
 * XML Sitemap Generator
 * Dynamically generates sitemap for all pages and products
 * Access at: /sitemap.xml
 */

require_once 'config/database.php';

header('Content-Type: application/xml; charset=UTF-8');
header('Cache-Control: public, max-age=86400'); // Cache for 1 day

$pdo = getDBConnection();

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

// Static pages
$staticPages = [
    SITE_URL => '1.0', // Homepage
    SITE_URL . 'products.php' => '0.9',
    SITE_URL . 'login.php' => '0.7',
    SITE_URL . 'register.php' => '0.7',
];

foreach ($staticPages as $url => $priority) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars($url) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d') . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>' . $priority . '</priority>' . "\n";
    echo '  </url>' . "\n";
}

// Category pages
$categories = $pdo->query("SELECT slug, updated_at FROM categories ORDER BY updated_at DESC")->fetchAll();
foreach ($categories as $cat) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars(SITE_URL . 'products.php?category=' . urlencode($cat['slug'])) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($cat['updated_at'])) . '</lastmod>' . "\n";
    echo '    <changefreq>weekly</changefreq>' . "\n";
    echo '    <priority>0.8</priority>' . "\n";
    echo '  </url>' . "\n";
}

// Product pages (highest priority for SEO)
$products = $pdo->query("SELECT slug, updated_at FROM products WHERE status = 'active' ORDER BY updated_at DESC")->fetchAll();
foreach ($products as $prod) {
    echo '  <url>' . "\n";
    echo '    <loc>' . htmlspecialchars(SITE_URL . 'product.php?slug=' . urlencode($prod['slug'])) . '</loc>' . "\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($prod['updated_at'])) . '</lastmod>' . "\n";
    echo '    <changefreq>monthly</changefreq>' . "\n";
    echo '    <priority>0.9</priority>' . "\n";
    echo '  </url>' . "\n";
}

echo '</urlset>' . "\n";
