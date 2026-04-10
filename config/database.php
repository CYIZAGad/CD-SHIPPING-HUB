<?php
/**
 * Database Configuration - CD SHIPPING HUB
 */

// Load environment variables from .env file if it exists
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Skip comments
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (!getenv($key)) {
            putenv($key . '=' . $value);
        }
    }
}

// Database configuration with fallbacks (PostgreSQL for production, MySQL for local dev)
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('DB_PORT') ?: '5432');
define('DB_NAME', getenv('DB_NAME') ?: 'cdshipping_hub');
define('DB_USER', getenv('DB_USER') ?: 'postgres');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// Environment detection
define('APP_ENV', getenv('APP_ENV') ?: 'development');
define('IS_PRODUCTION', APP_ENV === 'production');

// Site configuration - dynamically built from environment
define('SITE_NAME', 'CD SHIPPING HUB');
// Detect protocol from environment or current request (accounting for proxies)
$detectedProtocol = 'http';
if (getenv('SITE_PROTOCOL')) {
    $detectedProtocol = getenv('SITE_PROTOCOL');
} elseif (!empty($_SERVER['HTTP_X_FORWARDED_PROTO'])) {
    $detectedProtocol = $_SERVER['HTTP_X_FORWARDED_PROTO'];
} elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
    $detectedProtocol = 'https';
} elseif (($_SERVER['SERVER_PORT'] ?? null) == 443) {
    $detectedProtocol = 'https';
}
$siteProtocol = $detectedProtocol;
$siteDomain = getenv('SITE_DOMAIN') ?: ($_SERVER['HTTP_HOST'] ?? 'localhost');
$sitePath = getenv('SITE_PATH') ?: '/';
define('SITE_URL', $siteProtocol . '://' . $siteDomain . $sitePath);
define('ADMIN_URL', SITE_URL . '/admin');
define('UPLOAD_DIR', __DIR__ . '/../uploads/products/');
define('UPLOAD_URL', SITE_URL . '/uploads/products/');

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Detect HTTPS - check both direct HTTPS and X-Forwarded-Proto header (for reverse proxies like Render)
$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
    || (($_SERVER['SERVER_PORT'] ?? null) == 443)
    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

ini_set('session.cookie_secure', $isHttps ? '1' : '0');

if (PHP_VERSION_ID >= 70300) {
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $isHttps,
        'samesite' => 'Strict',
    ]);
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (PHP_SAPI !== 'cli' && APP_ENV === 'production' && !$isHttps) {
    $host = $_SERVER['HTTP_HOST'] ?? '';
    $uri = $_SERVER['REQUEST_URI'] ?? '/';
    if (!empty($host)) {
        header('Location: https://' . $host . $uri, true, 301);
        exit;
    }
}

// Database connection
function getDBConnection() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            // Detect database type from port or host
            $isPostgreSQL = (int)DB_PORT === 5432 || 
                           strpos(DB_HOST, 'postgres') !== false || 
                           strpos(DB_HOST, 'dpg-') !== false;
            
            if ($isPostgreSQL) {
                // PostgreSQL connection
                $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
            } else {
                // MySQL/MariaDB connection
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            }
            
            $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
            
            // Log successful connection (for debugging)
            error_log("Connected to " . ($isPostgreSQL ? "PostgreSQL" : "MySQL") . " at " . DB_HOST . ":" . DB_PORT);
        } catch (PDOException $e) {
            $dbType = ($isPostgreSQL ? "PostgreSQL" : "MySQL/MariaDB");
            die("Database connection failed (using $dbType at " . DB_HOST . ":" . DB_PORT . "): " . $e->getMessage());
        }
    }
    return $pdo;
}

// CSRF Token
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Escaped output helper
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

// Lightweight in-session rate limiting
function rateLimitCheck($key, $maxAttempts, $windowSeconds) {
    $now = time();
    if (!isset($_SESSION['rate_limits'])) {
        $_SESSION['rate_limits'] = [];
    }
    if (!isset($_SESSION['rate_limits'][$key])) {
        $_SESSION['rate_limits'][$key] = [];
    }

    $_SESSION['rate_limits'][$key] = array_values(array_filter(
        $_SESSION['rate_limits'][$key],
        function($ts) use ($now, $windowSeconds) {
            return ($now - $ts) < $windowSeconds;
        }
    ));

    if (count($_SESSION['rate_limits'][$key]) >= $maxAttempts) {
        return false;
    }

    $_SESSION['rate_limits'][$key][] = $now;
    return true;
}

function getRequestIp() {
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

// Session-backed account lockout by email
function isAccountLocked($email) {
    $emailKey = strtolower(trim($email));
    if (empty($_SESSION['auth_lockouts'][$emailKey])) {
        return false;
    }
    return $_SESSION['auth_lockouts'][$emailKey]['until'] > time();
}

function registerFailedLogin($email) {
    $emailKey = strtolower(trim($email));
    if (!isset($_SESSION['auth_lockouts'])) {
        $_SESSION['auth_lockouts'] = [];
    }
    if (!isset($_SESSION['auth_lockouts'][$emailKey])) {
        $_SESSION['auth_lockouts'][$emailKey] = ['count' => 0, 'until' => 0];
    }

    $_SESSION['auth_lockouts'][$emailKey]['count']++;
    if ($_SESSION['auth_lockouts'][$emailKey]['count'] >= 5) {
        $_SESSION['auth_lockouts'][$emailKey]['until'] = time() + (15 * 60);
    }
}

function clearFailedLogin($email) {
    $emailKey = strtolower(trim($email));
    if (isset($_SESSION['auth_lockouts'][$emailKey])) {
        unset($_SESSION['auth_lockouts'][$emailKey]);
    }
}

function generateCaptchaChallenge($formKey) {
    $a = random_int(1, 9);
    $b = random_int(1, 9);
    $_SESSION['captcha'][$formKey] = [
        'answer' => (string)($a + $b),
        'expires' => time() + 900,
        'question' => "$a + $b",
    ];
    return $_SESSION['captcha'][$formKey]['question'];
}

function verifyCaptchaChallenge($formKey, $answer) {
    if (empty($_SESSION['captcha'][$formKey])) {
        return false;
    }
    $record = $_SESSION['captcha'][$formKey];
    $ok = ($record['expires'] > time()) && hash_equals($record['answer'], trim((string)$answer));
    unset($_SESSION['captcha'][$formKey]);
    return $ok;
}

function getCaptchaQuestion($formKey) {
    if (empty($_SESSION['captcha'][$formKey]) || $_SESSION['captcha'][$formKey]['expires'] <= time()) {
        return generateCaptchaChallenge($formKey);
    }
    return $_SESSION['captcha'][$formKey]['question'];
}

function hashResetToken($token) {
    return hash('sha256', $token);
}

function sendPasswordResetEmail($toEmail, $resetUrl) {
    $subject = 'CD SHIPPING HUB Password Reset';
    $message = "A password reset was requested for your account.\n\n" .
               "Use the link below within 1 hour:\n$resetUrl\n\n" .
               "If you did not request this, please ignore this email.";
    $headers = 'From: no-reply@cdshipping.com' . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8';

    $sent = @mail($toEmail, $subject, $message, $headers);
    if (!$sent) {
        // Fallback for environments without mail transport.
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logLine = date('c') . " RESET_EMAIL to=$toEmail url=$resetUrl" . PHP_EOL;
        @file_put_contents($logDir . '/mail.log', $logLine, FILE_APPEND);
    }
    return $sent;
}

// Flash messages
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

function getFlash($type) {
    if (isset($_SESSION['flash'][$type])) {
        $msg = $_SESSION['flash'][$type];
        unset($_SESSION['flash'][$type]);
        return e($msg);
    }
    return null;
}

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if admin is logged in
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

// Redirect helper - Prevent open redirect attacks
function redirect($url) {
    // Only allow redirects to URLs starting with SITE_URL or absolute paths
    if (!empty($url)) {
        $parsed = parse_url($url);
        $baseHost = parse_url(SITE_URL, PHP_URL_HOST);
        
        // Allow relative paths and same-host redirects
        if ((isset($parsed['host']) && $parsed['host'] === $baseHost) || 
            (empty($parsed['host']) && strpos($url, '/') === 0)) {
            header("Location: $url");
            exit;
        }
    }
    
    // Fallback to site URL if redirect is invalid
    header("Location: " . SITE_URL);
    exit;
}

// Format currency
function formatPrice($price) {
    return '$' . number_format((float)$price, 2);
}

// Get image: from filesystem first, fallback to database base64 (for ephemeral storage like Render)
function getProductImage($product, $imageField = 'image') {
    $fileFieldName = $imageField; // e.g. 'image', 'image2', 'image3'
    $base64FieldName = $imageField . '_base64'; // e.g. 'image_base64', 'image2_base64'
    
    // Try filesystem first
    if (!empty($product[$fileFieldName])) {
        $filePath = UPLOAD_DIR . $product[$fileFieldName];
        if (file_exists($filePath)) {
            return UPLOAD_URL . $product[$fileFieldName] . '?t=' . time(); // Cache busting
        }
    }
    
    // Fall back to database base64 if file doesn't exist
    if (!empty($product[$base64FieldName])) {
        return $product[$base64FieldName]; // Returns data URL like: data:image/jpeg;base64,...
    }
    
    // If no image found, return placeholder
    return 'https://placehold.co/400x300/e3f2fd/1976d2?text=' . urlencode($product['name'] ?? 'Product');
}
