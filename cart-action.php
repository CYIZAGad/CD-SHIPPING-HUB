<?php
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') redirect(SITE_URL);
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Invalid form submission.');
    redirect(SITE_URL);
}

$action = $_POST['action'] ?? '';
$productId = (int)($_POST['product_id'] ?? 0);

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

$pdo = getDBConnection();

switch ($action) {
    case 'add':
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        $stmt = $pdo->prepare("SELECT id, name, price, stock, image FROM products WHERE id = ? AND status = 'active'");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            setFlash('error', 'Product not found.');
            break;
        }
        if ($product['stock'] < 1) {
            setFlash('error', 'Product is out of stock.');
            break;
        }

        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $productId) {
                $newQty = $item['qty'] + $qty;
                $item['qty'] = min($newQty, $product['stock']);
                $found = true;
                break;
            }
        }
        unset($item);

        if (!$found) {
            $_SESSION['cart'][] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'image' => $product['image'],
                'qty' => min($qty, $product['stock']),
                'stock' => $product['stock']
            ];
        }
        setFlash('success', sanitize($product['name']) . ' added to cart!');
        break;

    case 'update':
        $qty = max(1, (int)($_POST['quantity'] ?? 1));
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['id'] === $productId) {
                $item['qty'] = min($qty, $item['stock']);
                break;
            }
        }
        unset($item);
        break;

    case 'remove':
        $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use ($productId) {
            return $item['id'] !== $productId;
        }));
        setFlash('success', 'Item removed from cart.');
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        setFlash('success', 'Cart cleared.');
        break;
}

$redirect = $_POST['redirect'] ?? SITE_URL . '/cart.php';
// Validate redirect is local
if (strpos($redirect, SITE_URL) !== 0) {
    $redirect = SITE_URL . '/cart.php';
}
redirect($redirect);
