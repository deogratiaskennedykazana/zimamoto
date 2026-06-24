<?php
// ============================================================
//  CHATBOT SETTINGS PAGE  (?page=chatbot_settings)
//  Admin only — enable/disable chatbot and set Gemini API key
// ============================================================

$allowedRoles = ['admin', 'superadmin', 'super admin'];
if (!in_array(strtolower($_SESSION['role'] ?? ''), $allowedRoles)) {
    echo '<div class="alert alert-danger m-3"><i class="fas fa-lock mr-1"></i> Access denied. Administrators only.</div>';
    return;
}

// Handle save
if (isset($_POST['save_chatbot_settings'])) {
    $enabled = isset($_POST['chatbot_enabled']) ? 1 : 0;
    $apiKey  = trim($_POST['gemini_api_key'] ?? '');
    $model   = in_array($_POST['model'] ?? '', ['gemini-1.5-flash', 'gemini-1.5-pro', 'gemini-2.0-flash-exp'])
               ? $_POST['model'] : 'gemini-1.5-flash';
    $updBy   = (int)$_SESSION['userid'];

    $existing = $conn->query("SELECT id FROM chatbot_settings LIMIT 1")->fetch_assoc();
    if ($existing) {
        $stmt = $conn->prepare("UPDATE chatbot_settings SET enabled=?, api_key=?, model=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("issii", $enabled, $apiKey, $model, $updBy, $existing['id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO chatbot_settings (enabled, api_key, model, updated_by) VALUES (?,?,?,?)");
        $stmt->bind_param("issi", $enabled, $apiKey, $model, $updBy);
        $stmt->execute();
    }
    echo '<script>alert("Chatbot settings saved successfully."); window.location.href="./?page=chatbot_settings";</script>';
    return;
}

$settings = $conn->query("SELECT * FROM chatbot_settings LIMIT 1")->fetch_assoc();

$auditRows = $conn->query("
    SELECT ca.*, u.name AS user_name
    FROM chatbot_audit ca
    LEFT JOIN users u ON u.id = ca.user_id
    ORDER BY ca.created_at DESC LIMIT 50
")->fetch_all(MYSQLI_ASSOC);
?>

<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-robot mr-1"></i> Chatbot Settings</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-info">
            <i class="fas fa-info-circle mr-1"></i>
            This chatbot uses <strong>Google Gemini API (free tier)</strong> —
            <strong>1,500 requests/day free, no credit card needed.</strong><br>
            Get your free API key at:
            <a href="https://aistudio.google.com/app/apikey" target="_blank">aistudio.google.com/app/apikey</a>
        </div>

        <form method="post">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Chatbot Status</label>
                        <div class="icheck-primary">
                            <input type="checkbox" id="chatbot_enabled" name="chatbot_enabled" value="1"
                                   <?= ($settings['enabled'] ?? 0) ? 'checked' : '' ?>>
                            <label for="chatbot_enabled">Enable Chatbot for all users</label>
                        </div>
                        <small class="text-muted">When disabled, the widget is hidden from all pages.</small>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Gemini Model</label>
                        <select name="model" class="form-control">
                            <?php
                            $models = [
                                'gemini-1.5-flash'     => 'Gemini 1.5 Flash (Recommended — fastest, free)',
                                'gemini-2.0-flash-exp' => 'Gemini 2.0 Flash Experimental (free)',
                                'gemini-1.5-pro'       => 'Gemini 1.5 Pro (slower, free tier limited)',
                            ];
                            foreach ($models as $val => $label) {
                                $sel = ($settings['model'] ?? 'gemini-1.5-flash') === $val ? 'selected' : '';
                                echo "<option value='{$val}' {$sel}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label>Google Gemini API Key</label>
                <input type="text" name="gemini_api_key" class="form-control"
                       value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>"
                       placeholder="AIza...">
                <small class="text-muted">Stored server-side only — never exposed to the browser.</small>
            </div>

            <button type="submit" name="save_chatbot_settings" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Save Settings
            </button>
        </form>
    </div>
</div>

<div class="card card-secondary mt-3">
    <div class="card-header">
        <h5 class="card-title"><i class="fas fa-history mr-1"></i> Recent Chatbot Usage (last 50)</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped mb-0">
                <thead>
                    <tr class="table-secondary">
                        <th>Time</th><th>User</th><th>Role</th><th>Message</th><th>Action</th><th>Navigated To</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($auditRows)): ?>
                        <?php foreach ($auditRows as $row): ?>
                            <tr>
                                <td class="text-nowrap"><?= htmlspecialchars($row['created_at']) ?></td>
                                <td><?= htmlspecialchars($row['user_name'] ?? '—') ?></td>
                                <td><small><?= htmlspecialchars($row['role_at_time'] ?? '') ?></small></td>
                                <td style="max-width:280px;"><?= htmlspecialchars(mb_substr($row['user_message'], 0, 100)) ?><?= strlen($row['user_message']) > 100 ? '…' : '' ?></td>
                                <td>
                                    <?php $b = ['answer'=>'badge-info','navigate'=>'badge-success','error'=>'badge-danger'][$row['bot_action']] ?? 'badge-secondary'; ?>
                                    <span class="badge <?= $b ?>"><?= htmlspecialchars($row['bot_action'] ?? '—') ?></span>
                                </td>
                                <td><?= htmlspecialchars($row['navigate_to'] ?? '—') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="6" class="text-center text-muted">No chatbot activity yet.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
