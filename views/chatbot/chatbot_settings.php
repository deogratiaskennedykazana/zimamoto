<?php
// ============================================================
//  CHATBOT SETTINGS PAGE  (?page=chatbot_settings)
//  Admin only — enable/disable chatbot, set Gemini API key,
//  and control which roles/users can access the chatbot.
// ============================================================

$allowedRoles = ['admin', 'superadmin', 'super admin'];
if (!in_array(strtolower($_SESSION['role'] ?? ''), $allowedRoles)) {
    echo '<div class="alert alert-danger m-3"><i class="fas fa-lock mr-1"></i> Access denied. Administrators only.</div>';
    return;
}

// ── All system roles for the access-control checkboxes ───────
$systemRoles = [
    'admin'        => 'Admin',
    'superadmin'   => 'Super Admin',
    'accountant'   => 'Accountant',
    'manager'      => 'Manager',
    'loan comitee' => 'Loan Committee',
    'chairman'     => 'Chairman',
    'member'       => 'Member',
];

// ── Handle save ───────────────────────────────────────────────
if (isset($_POST['save_chatbot_settings'])) {
    $enabled     = isset($_POST['chatbot_enabled']) ? 1 : 0;
    $provider    = in_array($_POST['provider'] ?? '', ['gemini', 'grok'], true)
                   ? $_POST['provider'] : 'gemini';
    $geminiApiKey = trim($_POST['gemini_api_key'] ?? '');
    $grokApiKey   = trim($_POST['grok_api_key'] ?? '');
    $geminiModel  = in_array($_POST['gemini_model'] ?? '', ['gemini-3.5-flash', 'gemini-3.1-flash-lite', 'gemini-3.1-pro-preview'], true)
                   ? $_POST['gemini_model'] : 'gemini-3.5-flash';
    $grokModel    = in_array($_POST['grok_model'] ?? '', ['grok-4.3'], true)
                   ? $_POST['grok_model'] : 'grok-4.3';
    $updBy       = (int)$_SESSION['userid'];

    // Collect allowed roles — admin/superadmin always included
    $chosenRoles = (array)($_POST['allowed_roles'] ?? []);
    // Always keep admins in the list regardless of checkbox state
    foreach (['admin', 'superadmin', 'super admin'] as $ar) {
        if (!in_array($ar, $chosenRoles)) $chosenRoles[] = $ar;
    }
    $allowedRolesStr = implode(',', array_unique(array_filter($chosenRoles)));

    $existing = $conn->query("SELECT id FROM chatbot_settings LIMIT 1")->fetch_assoc();
    if ($existing) {
        $stmt = $conn->prepare("UPDATE chatbot_settings SET enabled=?, provider=?, api_key=?, model=?, grok_api_key=?, grok_model=?, allowed_roles=?, updated_by=?, updated_at=NOW() WHERE id=?");
        $stmt->bind_param("issssssii", $enabled, $provider, $geminiApiKey, $geminiModel, $grokApiKey, $grokModel, $allowedRolesStr, $updBy, $existing['id']);
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("INSERT INTO chatbot_settings (enabled, provider, api_key, model, grok_api_key, grok_model, allowed_roles, updated_by) VALUES (?,?,?,?,?,?,?,?)");
        $stmt->bind_param("issssssi", $enabled, $provider, $geminiApiKey, $geminiModel, $grokApiKey, $grokModel, $allowedRolesStr, $updBy);
        $stmt->execute();
    }
    echo '<script>alert("Chatbot settings saved successfully."); window.location.href="./?page=chatbot_settings";</script>';
    return;
}

$settings  = $conn->query("SELECT enabled, provider, api_key, model, grok_api_key, grok_model, allowed_roles FROM chatbot_settings LIMIT 1")->fetch_assoc();
$savedRoles = array_map('trim', explode(',', $settings['allowed_roles'] ?? 'admin,superadmin,super admin'));

$auditRows = $conn->query("
    SELECT ca.*, u.name AS user_name
    FROM chatbot_audit ca
    LEFT JOIN users u ON u.id = ca.user_id
    ORDER BY ca.created_at DESC LIMIT 50
")->fetch_all(MYSQLI_ASSOC);
?>

<!-- ── Main settings card ──────────────────────────────────── -->
<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-robot mr-1"></i> Chatbot Settings</h4>
    </div>
    <div class="card-body">
        <div class="alert alert-info mb-3">
            <i class="fas fa-info-circle mr-1"></i>
            This chatbot supports both <strong>Google Gemini</strong> and <strong>xAI Grok</strong>.
            For Gemini, use an API key from Google AI Studio. For Grok, use your xAI bearer key.
        </div>

        <form method="post">

            <!-- ── Row 1: Enable + Model ───────────────────── -->
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Chatbot Status</label>
                        <div class="icheck-primary">
                            <input type="checkbox" id="chatbot_enabled" name="chatbot_enabled" value="1"
                                   <?= ($settings['enabled'] ?? 0) ? 'checked' : '' ?>>
                            <label for="chatbot_enabled">Enable Chatbot</label>
                        </div>
                        <small class="text-muted">When disabled, the widget is completely hidden from all pages.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">AI Provider</label>
                        <select name="provider" class="form-control">
                            <?php
                            $providers = [
                                'gemini' => 'Google Gemini',
                                'grok'   => 'xAI Grok',
                            ];
                            foreach ($providers as $val => $label) {
                                $sel = ($settings['provider'] ?? 'gemini') === $val ? 'selected' : '';
                                echo "<option value='{$val}' {$sel}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Gemini Model</label>
                        <select name="gemini_model" class="form-control">
                            <?php
                            $models = [
                                'gemini-3.5-flash'      => 'Gemini 3.5 Flash (current stable)',
                                'gemini-3.1-flash-lite' => 'Gemini 3.1 Flash Lite',
                                'gemini-3.1-pro-preview'=> 'Gemini 3.1 Pro Preview',
                            ];
                            foreach ($models as $val => $label) {
                                $sel = ($settings['model'] ?? 'gemini-3.5-flash') === $val ? 'selected' : '';
                                echo "<option value='{$val}' {$sel}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Google Gemini API Key</label>
                        <input type="text" name="gemini_api_key" class="form-control"
                               value="<?= htmlspecialchars($settings['api_key'] ?? '') ?>"
                               placeholder="AIza...">
                        <small class="text-muted">Stored server-side only — never sent to the browser.</small>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Grok Model</label>
                        <select name="grok_model" class="form-control">
                            <?php
                            $grokModels = [
                                'grok-4.3' => 'Grok 4.3 (xAI flagship)',
                            ];
                            foreach ($grokModels as $val => $label) {
                                $sel = ($settings['grok_model'] ?? 'grok-4.3') === $val ? 'selected' : '';
                                echo "<option value='{$val}' {$sel}>{$label}</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Grok API Key</label>
                        <input type="text" name="grok_api_key" class="form-control"
                               value="<?= htmlspecialchars($settings['grok_api_key'] ?? '') ?>"
                               placeholder="sk-...">
                        <small class="text-muted">Stored server-side only — never sent to the browser.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="alert alert-info mt-4">
                        <strong>Note:</strong> When using Grok, the Grok API key is required. Gemini settings remain available for fallback.
                    </div>
                </div>
            </div>

            <hr>

            <!-- ── Row 2: Access control ───────────────────── -->
            <div class="row">
                <div class="col-12">
                    <label class="font-weight-bold">
                        <i class="fas fa-user-shield mr-1 text-primary"></i>
                        Who Can Use the Chatbot?
                    </label>
                    <p class="text-muted small mb-2">
                        Admins always have access. Tick the additional roles you want to grant.
                        Users whose role is not ticked will <strong>not</strong> see the chat widget.
                    </p>
                </div>

                <?php foreach ($systemRoles as $roleKey => $roleLabel):
                    $isAdmin    = in_array($roleKey, ['admin', 'superadmin', 'super admin']);
                    $isChecked  = in_array($roleKey, $savedRoles);
                    $isDisabled = $isAdmin; // admins are always on
                ?>
                <div class="col-md-3 col-sm-6 mb-2">
                    <div class="card card-outline <?= $isAdmin ? 'card-primary' : 'card-secondary' ?> p-2">
                        <div class="icheck-<?= $isAdmin ? 'primary' : 'info' ?>">
                            <input type="checkbox"
                                   id="role_<?= htmlspecialchars($roleKey) ?>"
                                   name="allowed_roles[]"
                                   value="<?= htmlspecialchars($roleKey) ?>"
                                   <?= $isChecked  ? 'checked'  : '' ?>
                                   <?= $isDisabled ? 'disabled' : '' ?>>
                            <?php if ($isDisabled): ?>
                                <!-- Hidden field so admin roles are always submitted -->
                                <input type="hidden" name="allowed_roles[]" value="<?= htmlspecialchars($roleKey) ?>">
                            <?php endif; ?>
                            <label for="role_<?= htmlspecialchars($roleKey) ?>">
                                <?= htmlspecialchars($roleLabel) ?>
                                <?php if ($isAdmin): ?>
                                    <span class="badge badge-primary badge-sm ml-1">Always on</span>
                                <?php endif; ?>
                            </label>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Quick-select helpers -->
            <div class="mb-3">
                <small class="text-muted mr-2">Quick select:</small>
                <button type="button" class="btn btn-xs btn-outline-success mr-1" onclick="setAllRoles(true)">
                    <i class="fas fa-check-double mr-1"></i>Grant All
                </button>
                <button type="button" class="btn btn-xs btn-outline-danger mr-1" onclick="setAllRoles(false)">
                    <i class="fas fa-times mr-1"></i>Revoke All (except Admin)
                </button>
                <button type="button" class="btn btn-xs btn-outline-info" onclick="setStaffOnly()">
                    <i class="fas fa-users-cog mr-1"></i>Staff Only (no Members)
                </button>
            </div>

            <hr>

            <button type="submit" name="save_chatbot_settings" class="btn btn-primary">
                <i class="fas fa-save mr-1"></i> Save Settings
            </button>
        </form>
    </div>
</div>

<!-- ── Audit log card ───────────────────────────────────────── -->
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

<script>
// Non-admin role checkboxes only (disabled ones are always admin)
var nonAdminBoxes = document.querySelectorAll('input[name="allowed_roles[]"]:not([disabled])');

function setAllRoles(state) {
    nonAdminBoxes.forEach(function(cb) { cb.checked = state; });
}
function setStaffOnly() {
    nonAdminBoxes.forEach(function(cb) {
        cb.checked = (cb.value !== 'member');
    });
}
</script>
