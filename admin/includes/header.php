<?php
require_once __DIR__ . '/../../config/database.php';

if (!isAdmin()) {
    setFlash('error', 'Access denied.');
    redirect(SITE_URL . '/login.php');
}

$pdo = getDBConnection();

// Pending orders count
$pendingOrders = $pdo->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? sanitize($pageTitle) . ' - ' : '' ?>Admin | <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/admin.css" rel="stylesheet">
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="<?= ADMIN_URL ?>" class="text-white text-decoration-none">
                    <i class="bi bi-box-seam me-2"></i>
                    <span class="sidebar-text">CD SHIPPING</span>
                </a>
                <button class="btn btn-link text-white d-lg-none" onclick="toggleSidebar()">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
            <nav class="sidebar-nav">
                <a href="<?= ADMIN_URL ?>/index.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>">
                    <i class="bi bi-speedometer2"></i><span class="sidebar-text">Dashboard</span>
                </a>
                <a href="<?= ADMIN_URL ?>/products.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'products.php' || basename($_SERVER['PHP_SELF']) === 'product-form.php' ? 'active' : '' ?>">
                    <i class="bi bi-box"></i><span class="sidebar-text">Products</span>
                </a>
                <a href="<?= ADMIN_URL ?>/orders.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'orders.php' || basename($_SERVER['PHP_SELF']) === 'order-detail.php' ? 'active' : '' ?>">
                    <i class="bi bi-bag-check"></i><span class="sidebar-text">Orders</span>
                    <?php if ($pendingOrders > 0): ?><span class="badge bg-danger ms-auto"><?= $pendingOrders ?></span><?php endif; ?>
                </a>
                <a href="<?= ADMIN_URL ?>/customers.php" class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'customers.php' ? 'active' : '' ?>">
                    <i class="bi bi-people"></i><span class="sidebar-text">Customers</span>
                </a>
                <hr class="border-light mx-3">
                <a href="<?= SITE_URL ?>" class="nav-link" target="_blank">
                    <i class="bi bi-globe"></i><span class="sidebar-text">View Website</span>
                </a>
                <a href="<?= SITE_URL ?>/logout.php" class="nav-link text-danger">
                    <i class="bi bi-box-arrow-right"></i><span class="sidebar-text">Logout</span>
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <div class="admin-main">
            <!-- Top Bar -->
            <header class="admin-topbar">
                <div class="d-flex align-items-center">
                    <button class="btn btn-link text-dark d-lg-none me-2" onclick="toggleSidebar()">
                        <i class="bi bi-list fs-4"></i>
                    </button>
                    <h5 class="mb-0 fw-bold"><?= $pageTitle ?? 'Dashboard' ?></h5>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="text-muted d-none d-md-inline">
                        <i class="bi bi-person-circle me-1"></i> <?= sanitize($_SESSION['user_name']) ?>
                    </span>
                </div>
            </header>

            <!-- Content Area -->
            <div class="admin-content">
                <?php if ($success = getFlash('success')): ?>
                <div class="alert alert-success alert-dismissible fade show"><i class="bi bi-check-circle me-2"></i><?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
                <?php if ($error = getFlash('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                <?php endif; ?>
