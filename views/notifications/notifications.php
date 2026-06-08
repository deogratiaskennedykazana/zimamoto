<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title">Notifications</h4>
        <div class="card-tools">
            <button class="btn btn-sm btn-info" onclick="markAllRead()"><i class="fas fa-check-double"></i> Mark All Read</button>
        </div>
    </div>
    <div class="card-body">
        <?php
        $notifications = getUserNotifications($conn, $_SESSION['userid'], 50);
        if($notifications && is_array($notifications) && count($notifications) > 0):
        ?>
        <div class="list-group">
            <?php foreach($notifications as $n): ?>
            <a href="<?= $n['link'] ?? '#' ?>" class="list-group-item list-group-item-action <?= !$n['is_read'] ? 'list-group-item-info' : '' ?>">
                <div class="d-flex w-100 justify-content-between">
                    <h5 class="mb-1">
                        <?php if(!$n['is_read']): ?><span class="badge badge-info mr-1">NEW</span><?php endif; ?>
                        <?= $n['title'] ?>
                    </h5>
                    <small><?= date('d/m/Y H:i', strtotime($n['created_at'])) ?></small>
                </div>
                <p class="mb-1"><?= $n['message'] ?></p>
                <small class="text-muted">Type: <?= ucfirst($n['type']) ?></small>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info">No notifications yet.</div>
        <?php endif; ?>
    </div>
</div>
<script>
function markAllRead() {
    $.post('./?page=mark_notifications_read', function() {
        location.reload();
    });
}
</script>
