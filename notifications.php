<?php
require_once 'config/database.php';
if (!isLoggedIn()) redirect(SITE_URL . '/login.php');

$pdo = getDBConnection();

// Mark all as read if requested
if (isset($_GET['mark_read'])) {
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$_SESSION['user_id']]);
    redirect(SITE_URL . '/notifications.php');
}

$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
$stmt->execute([$_SESSION['user_id']]);
$notifications = $stmt->fetchAll();

$pageTitle = 'Notifications';
require_once 'includes/header.php';
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold mb-0"><i class="bi bi-bell me-2"></i>Notifications</h2>
        <?php if (!empty($notifications)): ?>
        <a href="?mark_read=1" class="btn btn-outline-primary btn-sm"><i class="bi bi-check-all me-1"></i>Mark All as Read</a>
        <?php endif; ?>
    </div>

    <?php if (empty($notifications)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bell-slash display-1 text-muted"></i>
        <h4 class="mt-3">No Notifications</h4>
        <p class="text-muted">You're all caught up!</p>
    </div>
    <?php else: ?>
    <div class="list-group">
        <?php foreach ($notifications as $notif): ?>
        <div class="list-group-item border-0 shadow-sm mb-2 rounded <?= !$notif['is_read'] ? 'border-start border-primary border-3' : '' ?>">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-1 <?= !$notif['is_read'] ? 'fw-bold' : '' ?>">
                        <i class="bi bi-bell-fill me-2 <?= !$notif['is_read'] ? 'text-primary' : 'text-muted' ?>"></i>
                        <?= sanitize($notif['title']) ?>
                    </h6>
                    <p class="mb-0 text-muted"><?= sanitize($notif['message']) ?></p>
                </div>
                <small class="text-muted text-nowrap ms-3"><?= date('M d, H:i', strtotime($notif['created_at'])) ?></small>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
