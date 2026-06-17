<?php
/**
 * Notification Dashboard / Bell-drop panel
 * Shown when user visits ./?page=dashboard_notifications or embedded in navbar
 * Lists all in-app notifications with read/unread state, mark-all-read action
 */

$userId        = (int)$_SESSION['userid'];
$notifications = getUserNotifications($conn, $userId, 50);
$unreadCount   = countUnreadNotifications($conn, $userId);

// Icon + colour map per notification type
$typeConfig = [
    'info'    => ['icon' => 'fa-info-circle',      'color' => 'text-info'],
    'success' => ['icon' => 'fa-check-circle',     'color' => 'text-success'],
    'warning' => ['icon' => 'fa-exclamation-triangle', 'color' => 'text-warning'],
    'danger'  => ['icon' => 'fa-times-circle',     'color' => 'text-danger'],
    'loan'    => ['icon' => 'fa-hand-holding-usd', 'color' => 'text-primary'],
    'grantor' => ['icon' => 'fa-handshake',        'color' => 'text-purple'],
    'budget'  => ['icon' => 'fa-chart-pie',        'color' => 'text-success'],
    'meeting' => ['icon' => 'fa-file-alt',         'color' => 'text-secondary'],
    'system'  => ['icon' => 'fa-cog',              'color' => 'text-muted'],
];
$defaultCfg = ['icon' => 'fa-bell', 'color' => 'text-info'];
?>

<div class="card card-primary card-outline">
    <div class="card-header d-flex flex-wrap align-items-center justify-content-between">
        <h4 class="card-title mb-2 mb-sm-0">
            <i class="fas fa-bell mr-2"></i>Notifications
            <?php if ($unreadCount > 0): ?>
                <span class="badge badge-danger ml-1"><?= $unreadCount ?> new</span>
            <?php endif; ?>
        </h4>
        <div class="d-flex flex-wrap align-items-center">
            <?php if ($unreadCount > 0): ?>
            <a href="./?page=mark_notifications_read" class="btn btn-sm btn-outline-secondary mr-1 mb-1 mb-sm-0">
                <i class="fas fa-check-double mr-1"></i>Mark all read
            </a>
            <?php endif; ?>
            <a href="./?page=notification_settings" class="btn btn-sm btn-outline-primary mb-1 mb-sm-0">
                <i class="fas fa-sliders-h mr-1"></i>Settings
            </a>
        </div>
    </div>

    <div class="card-body p-0">
        <?php if (empty($notifications)): ?>
        <div class="text-center py-5 text-muted">
            <i class="fas fa-bell-slash fa-3x mb-3 d-block"></i>
            <p class="mb-0">You have no notifications yet.</p>
        </div>

        <?php else: ?>
        <ul class="list-group list-group-flush">
            <?php foreach ($notifications as $notif):
                $cfg   = $typeConfig[$notif['type']] ?? $defaultCfg;
                $unread = !(bool)$notif['is_read'];
                $bg     = $unread ? 'style="background:#f0f6ff;"' : '';
                $time   = date('d M Y, H:i', strtotime($notif['created_at']));
                $ago    = humanTimeAgo(strtotime($notif['created_at']));
            ?>
            <li class="list-group-item list-group-item-action px-4 py-3 <?= $unread ? 'font-weight-bold' : '' ?>" <?= $bg ?>>
                <div class="d-flex align-items-start flex-wrap">
                    <!-- Icon -->
                    <div class="mr-3 mt-1" style="min-width:28px;text-align:center;">
                        <i class="fas <?= $cfg['icon'] ?> fa-lg <?= $cfg['color'] ?>"></i>
                    </div>

                    <!-- Content -->
                    <div class="flex-grow-1 minw-0">
                        <div class="d-flex justify-content-between">
                            <span class="<?= $unread ? '' : 'text-muted' ?>" style="font-size:14px;">
                                <?= htmlspecialchars($notif['title'] ?? 'Notification') ?>
                                <?php if ($unread): ?>
                                    <span class="badge badge-primary ml-1" style="font-size:10px;">NEW</span>
                                <?php endif; ?>
                            </span>
                            <small class="text-muted ml-3 text-nowrap" title="<?= $time ?>"><?= $ago ?></small>
                        </div>
                        <p class="mb-0 mt-1 text-muted" style="font-size:13px; word-break:break-word; white-space:normal;">
                            <?= htmlspecialchars($notif['message'] ?? '') ?>
                        </p>
                        <?php if (!empty($notif['link'])): ?>
                        <a href="<?= htmlspecialchars($notif['link']) ?>" class="btn btn-xs btn-outline-info mt-2">
                            <i class="fas fa-arrow-right mr-1"></i>View
                        </a>
                        <?php endif; ?>
                    </div>

                    <!-- Mark read button -->
                    <?php if ($unread): ?>
                    <div class="ml-2 mt-2 mt-sm-0">
                        <a href="./?action=mark_notif_read&id=<?= $notif['id'] ?>&redirect=notifications"
                           class="btn btn-xs btn-light text-muted" title="Mark as read">
                            <i class="fas fa-check"></i>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>
            </li>
            <?php endforeach; ?>
        </ul>
        <?php endif; ?>
    </div>

    <?php if (count($notifications) >= 50): ?>
    <div class="card-footer text-center text-muted" style="font-size:13px;">
        Showing the 50 most recent notifications.
    </div>
    <?php endif; ?>
</div>

<?php
// ---- Helper: human-readable time ago ----
function humanTimeAgo(int $timestamp): string {
    $diff = time() - $timestamp;
    if ($diff < 60)        return 'just now';
    if ($diff < 3600)      return floor($diff / 60) . 'm ago';
    if ($diff < 86400)     return floor($diff / 3600) . 'h ago';
    if ($diff < 604800)    return floor($diff / 86400) . 'd ago';
    return date('d M Y', $timestamp);
}
?>
