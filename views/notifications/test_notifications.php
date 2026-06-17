<?php
/**
 * Admin Notification Test Panel
 * Accessible via: ./?page=test_notifications
 * Only admins should access this page (enforced in index.php switch case).
 */

$testResult = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_type'])) {

    $testType    = $_POST['test_type'];           // email | sms | in_app | queue_process
    $recipient   = trim($_POST['recipient'] ?? '');
    $subject     = trim($_POST['subject']  ?? 'Zima-Moto Test Notification');
    $body        = trim($_POST['body']     ?? 'This is a test notification from the Zima-Moto admin panel.');

    switch ($testType) {

        // ── EMAIL ──────────────────────────────────────────────────────────
        case 'email':
            if (empty($recipient)) {
                $testResult = ['type' => 'danger', 'msg' => 'Email address is required.'];
                break;
            }
            $htmlBody = "
                <div style='font-family:sans-serif;max-width:600px;margin:auto;border:1px solid #ddd;border-radius:8px;overflow:hidden'>
                    <div style='background:#007bff;padding:20px;color:#fff'>
                        <h2 style='margin:0'>".htmlspecialchars(APP_NAME)."</h2>
                        <p style='margin:4px 0 0'>Notification Test</p>
                    </div>
                    <div style='padding:24px'>
                        <h3>".htmlspecialchars($subject)."</h3>
                        <p>".nl2br(htmlspecialchars($body))."</p>
                        <hr>
                        <small style='color:#888'>Sent from admin test panel &mdash; ".date('Y-m-d H:i:s')."</small>
                    </div>
                </div>";
            $result = sendEmail($recipient, $subject, $htmlBody);
            $testResult = $result['success']
                ? ['type' => 'success', 'msg' => '✅ Email sent successfully to <strong>'.htmlspecialchars($recipient).'</strong>']
                : ['type' => 'danger',  'msg' => '❌ Email failed: '.htmlspecialchars($result['message'])];
            break;

        // ── SMS ────────────────────────────────────────────────────────────
        case 'sms':
            if (empty($recipient)) {
                $testResult = ['type' => 'danger', 'msg' => 'Phone number is required.'];
                break;
            }
            $smsText = APP_NAME . ": " . $body;
            $result  = sendSMS($recipient, $smsText);
            $testResult = $result['success']
                ? ['type' => 'success', 'msg' => '✅ SMS sent successfully to <strong>'.htmlspecialchars($recipient).'</strong>']
                : ['type' => 'danger',  'msg' => '❌ SMS failed: '.htmlspecialchars($result['message'])];
            break;

        // ── IN-APP ─────────────────────────────────────────────────────────
        case 'in_app':
            $targetUser = (int) ($_POST['target_user_id'] ?? $_SESSION['userid']);
            $result     = createSystemNotification($conn, $targetUser, $subject, $body, 'info', './?page=notifications');
            $testResult = $result
                ? ['type' => 'success', 'msg' => '✅ In-app notification created for user ID <strong>'.$targetUser.'</strong>']
                : ['type' => 'danger',  'msg' => '❌ Failed to create in-app notification. Check DB connection.'];
            break;

        // ── PROCESS QUEUE ──────────────────────────────────────────────────
        case 'queue_process':
            $limit  = max(1, min(50, (int) ($_POST['queue_limit'] ?? 10)));
            $result = processNotificationQueue($conn, $limit);
            $testResult = $result
                ? ['type' => 'success', 'msg' => '✅ Queue processed (up to '.$limit.' items). Check the queue table for updated statuses.']
                : ['type' => 'danger',  'msg' => '❌ Queue processing failed.'];
            break;

        default:
            $testResult = ['type' => 'warning', 'msg' => 'Unknown test type.'];
    }
}

// ── Fetch queue stats ──────────────────────────────────────────────────────
$queueStats = [];
$statsQuery = $conn->query("SELECT status, COUNT(*) AS cnt FROM notification_queue GROUP BY status");
if ($statsQuery) {
    while ($row = $statsQuery->fetch_assoc()) {
        $queueStats[$row['status']] = $row['cnt'];
    }
}

// ── Fetch recent queue rows ────────────────────────────────────────────────
$recentQueue = [];
$recentQuery = $conn->query("SELECT * FROM notification_queue ORDER BY created_at DESC LIMIT 15");
if ($recentQuery) {
    while ($row = $recentQuery->fetch_assoc()) {
        $recentQueue[] = $row;
    }
}

// ── Fetch system users for in-app target selector ─────────────────────────
$systemUsers  = [];
$usersQuery   = $conn->query("SELECT id, name, email FROM users WHERE status = 'approved' ORDER BY name LIMIT 200");
if ($usersQuery) {
    while ($row = $usersQuery->fetch_assoc()) {
        $systemUsers[] = $row;
    }
}

// ── Config check ──────────────────────────────────────────────────────────
$smtpOk  = (SMTP_USERNAME !== 'your-email@gmail.com');
$smsOk   = (SMS_API_KEY   !== 'your-api-key');
$smsSandbox = (SMS_USERNAME === 'sandbox');
?>

<!-- =====================================================================
     ADMIN NOTIFICATION TEST PANEL
     ===================================================================== -->

<div class="content-header">
    <div class="container-fluid">
        <h1 class="m-0"><i class="fas fa-flask mr-2 text-primary"></i>Notification Test Panel</h1>
        <small class="text-muted">Admin only &mdash; test Email, SMS and In-App notifications</small>
    </div>
</div>

<div class="container-fluid">

    <!-- ── Result alert ─────────────────────────────────────────────── -->
    <?php if ($testResult): ?>
    <div class="alert alert-<?= $testResult['type'] ?> alert-dismissible fade show" role="alert">
        <?= $testResult['msg'] ?>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php endif; ?>

    <!-- ── Config status cards ──────────────────────────────────────── -->
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="info-box <?= $smtpOk ? 'bg-success' : 'bg-warning' ?>">
                <span class="info-box-icon"><i class="fas fa-envelope"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">SMTP Config</span>
                    <span class="info-box-number"><?= $smtpOk ? 'Configured' : 'Not configured' ?></span>
                    <small><?= htmlspecialchars(SMTP_HOST) ?>:<?= SMTP_PORT ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box <?= $smsOk ? ($smsSandbox ? 'bg-warning' : 'bg-success') : 'bg-danger' ?>">
                <span class="info-box-icon"><i class="fas fa-sms"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">SMS Config (Africa's Talking)</span>
                    <span class="info-box-number">
                        <?php if (!$smsOk): ?>Not configured
                        <?php elseif ($smsSandbox): ?>Sandbox mode
                        <?php else: ?>Production mode
                        <?php endif; ?>
                    </span>
                    <small>Username: <?= htmlspecialchars(SMS_USERNAME) ?> &mdash; From: <?= htmlspecialchars(SMS_FROM) ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="info-box bg-info">
                <span class="info-box-icon"><i class="fas fa-bell"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Queue</span>
                    <span class="info-box-number">
                        Pending: <?= $queueStats['pending'] ?? 0 ?>
                        &nbsp;|&nbsp; Sent: <?= $queueStats['sent'] ?? 0 ?>
                        &nbsp;|&nbsp; Failed: <?= $queueStats['failed'] ?? 0 ?>
                    </span>
                    <small>notification_queue table</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">

        <!-- ── LEFT COLUMN: Test forms ─────────────────────────────── -->
        <div class="col-md-7">

            <!-- Tabs -->
            <div class="card card-primary card-tabs">
                <div class="card-header p-0 pt-1">
                    <ul class="nav nav-tabs" id="notifTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="email-tab" data-toggle="pill" href="#tab-email" role="tab">
                                <i class="fas fa-envelope mr-1"></i>Email
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="sms-tab" data-toggle="pill" href="#tab-sms" role="tab">
                                <i class="fas fa-sms mr-1"></i>SMS
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="inapp-tab" data-toggle="pill" href="#tab-inapp" role="tab">
                                <i class="fas fa-bell mr-1"></i>In-App
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="queue-tab" data-toggle="pill" href="#tab-queue" role="tab">
                                <i class="fas fa-list mr-1"></i>Process Queue
                            </a>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="notifTabsContent">

                        <!-- EMAIL form -->
                        <div class="tab-pane fade show active" id="tab-email">
                            <?php if (!$smtpOk): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                SMTP is not configured. Update <code>SMTP_USERNAME</code> and <code>SMTP_PASSWORD</code> in <code>configs.php</code> first.
                            </div>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="test_type" value="email">
                                <div class="form-group">
                                    <label>Recipient Email <span class="text-danger">*</span></label>
                                    <input type="email" name="recipient" class="form-control"
                                           placeholder="test@example.com"
                                           value="<?= htmlspecialchars($_POST['recipient'] ?? '') ?>" required>
                                </div>
                                <div class="form-group">
                                    <label>Subject</label>
                                    <input type="text" name="subject" class="form-control"
                                           value="<?= htmlspecialchars($_POST['subject'] ?? 'Zima-Moto Test Email') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Message Body</label>
                                    <textarea name="body" class="form-control" rows="4"><?= htmlspecialchars($_POST['body'] ?? 'This is a test email from the Zima-Moto SACCOS admin panel. If you received this, email notifications are working correctly!') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block">
                                    <i class="fas fa-paper-plane mr-1"></i>Send Test Email
                                </button>
                            </form>
                        </div>

                        <!-- SMS form -->
                        <div class="tab-pane fade" id="tab-sms">
                            <?php if (!$smsOk): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Africa's Talking API key is not configured. Update <code>SMS_API_KEY</code> in <code>configs.php</code>.
                            </div>
                            <?php endif; ?>
                            <?php if ($smsSandbox): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-1"></i>
                                Running in <strong>sandbox</strong> mode. SMS will be sent to AT Simulator, not real phones.
                                Change <code>SMS_USERNAME</code> in <code>configs.php</code> to your real username for production.
                            </div>
                            <?php endif; ?>
                            <form method="post">
                                <input type="hidden" name="test_type" value="sms">
                                <div class="form-group">
                                    <label>Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="recipient" class="form-control"
                                           placeholder="07XXXXXXXX or 255XXXXXXXXX"
                                           value="<?= htmlspecialchars($_POST['recipient'] ?? '') ?>" required>
                                    <small class="text-muted">Tanzanian format: 07XXXXXXXX or 255XXXXXXXXX</small>
                                </div>
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea name="body" class="form-control" rows="3" maxlength="160"><?= htmlspecialchars($_POST['body'] ?? 'Zima-Moto test SMS. If you received this, SMS notifications are working!') ?></textarea>
                                    <small class="text-muted">Max 160 characters for a single SMS.</small>
                                </div>
                                <button type="submit" class="btn btn-success btn-block">
                                    <i class="fas fa-mobile-alt mr-1"></i>Send Test SMS
                                </button>
                            </form>
                        </div>

                        <!-- IN-APP form -->
                        <div class="tab-pane fade" id="tab-inapp">
                            <form method="post">
                                <input type="hidden" name="test_type" value="in_app">
                                <div class="form-group">
                                    <label>Target User</label>
                                    <select name="target_user_id" class="form-control select2-form select2bs4-form">
                                        <option value="<?= (int)$_SESSION['userid'] ?>">Myself (ID <?= (int)$_SESSION['userid'] ?>)</option>
                                        <?php foreach ($systemUsers as $u): ?>
                                            <?php if ($u['id'] != $_SESSION['userid']): ?>
                                            <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> &lt;<?= htmlspecialchars($u['email']) ?>&gt;</option>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>Notification Title</label>
                                    <input type="text" name="subject" class="form-control"
                                           value="<?= htmlspecialchars($_POST['subject'] ?? 'Test In-App Notification') ?>">
                                </div>
                                <div class="form-group">
                                    <label>Message</label>
                                    <textarea name="body" class="form-control" rows="3"><?= htmlspecialchars($_POST['body'] ?? 'This is a test in-app notification from the admin panel.') ?></textarea>
                                </div>
                                <button type="submit" class="btn btn-info btn-block">
                                    <i class="fas fa-bell mr-1"></i>Create In-App Notification
                                </button>
                            </form>
                        </div>

                        <!-- QUEUE PROCESS -->
                        <div class="tab-pane fade" id="tab-queue">
                            <p class="text-muted">
                                Notifications that use email or SMS go through the <code>notification_queue</code> table.
                                Use this to manually process pending items (normally done by a cron job).
                            </p>
                            <form method="post">
                                <input type="hidden" name="test_type" value="queue_process">
                                <div class="form-group">
                                    <label>Batch Size (max items to process)</label>
                                    <input type="number" name="queue_limit" class="form-control" value="10" min="1" max="50">
                                </div>
                                <button type="submit" class="btn btn-warning btn-block">
                                    <i class="fas fa-cogs mr-1"></i>Process Queue Now
                                </button>
                            </form>

                            <div class="mt-3">
                                <h6>Queue Summary</h6>
                                <table class="table table-sm table-bordered">
                                    <thead class="thead-light">
                                        <tr><th>Status</th><th>Count</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (['pending','sent','failed'] as $s): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $badge = ['pending'=>'warning','sent'=>'success','failed'=>'danger'];
                                                echo '<span class="badge badge-'.$badge[$s].'">'.$s.'</span>';
                                                ?>
                                            </td>
                                            <td><?= $queueStats[$s] ?? 0 ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                    </div><!-- /.tab-content -->
                </div>
            </div><!-- /.card -->

        </div><!-- /.col-md-7 -->

        <!-- ── RIGHT COLUMN: Config hints + recent queue ────────────── -->
        <div class="col-md-5">

            <!-- configs.php hints -->
            <div class="card card-outline card-secondary">
                <div class="card-header"><h5 class="card-title"><i class="fas fa-cog mr-1"></i>configs.php Settings</h5></div>
                <div class="card-body p-2">
                    <table class="table table-sm table-striped mb-0">
                        <tbody>
                            <tr><td><code>SMTP_HOST</code></td><td><?= htmlspecialchars(SMTP_HOST) ?></td></tr>
                            <tr><td><code>SMTP_PORT</code></td><td><?= SMTP_PORT ?></td></tr>
                            <tr><td><code>SMTP_USERNAME</code></td>
                                <td><?= $smtpOk ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-danger">Placeholder</span>' ?></td></tr>
                            <tr><td><code>SMTP_FROM_EMAIL</code></td><td><?= htmlspecialchars(SMTP_FROM_EMAIL) ?></td></tr>
                            <tr><td><code>SMS_PROVIDER</code></td><td><?= htmlspecialchars(SMS_PROVIDER) ?></td></tr>
                            <tr><td><code>SMS_USERNAME</code></td><td><?= htmlspecialchars(SMS_USERNAME) ?></td></tr>
                            <tr><td><code>SMS_API_KEY</code></td>
                                <td><?= $smsOk ? '<span class="badge badge-success">Set</span>' : '<span class="badge badge-danger">Placeholder</span>' ?></td></tr>
                            <tr><td><code>SMS_FROM</code></td><td><?= htmlspecialchars(SMS_FROM) ?></td></tr>
                            <tr><td><code>APP_URL</code></td><td><?= htmlspecialchars(APP_URL) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent queue -->
            <div class="card card-outline card-info">
                <div class="card-header"><h5 class="card-title"><i class="fas fa-list mr-1"></i>Recent Queue (last 15)</h5></div>
                <div class="card-body p-0" style="max-height:380px;overflow-y:auto">
                    <?php if (empty($recentQueue)): ?>
                        <p class="p-3 text-muted">Queue is empty.</p>
                    <?php else: ?>
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th>Type</th>
                                <th>Recipient</th>
                                <th>Status</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentQueue as $q): ?>
                            <?php
                            $sb = ['pending'=>'warning','sent'=>'success','failed'=>'danger'];
                            $b  = $sb[$q['status']] ?? 'secondary';
                            ?>
                            <tr>
                                <td><span class="badge badge-<?= $q['type']==='email'?'primary':'success' ?>"><?= htmlspecialchars($q['type']) ?></span></td>
                                <td style="max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="<?= htmlspecialchars($q['recipient']) ?>"><?= htmlspecialchars($q['recipient']) ?></td>
                                <td><span class="badge badge-<?= $b ?>"><?= $q['status'] ?></span></td>
                                <td><small><?= date('d/m H:i', strtotime($q['created_at'])) ?></small></td>
                            </tr>
                            <?php if (!empty($q['error_message'])): ?>
                            <tr class="table-danger">
                                <td colspan="4"><small class="text-danger"><?= htmlspecialchars($q['error_message']) ?></small></td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.col-md-5 -->

    </div><!-- /.row -->

</div><!-- /.container-fluid -->
