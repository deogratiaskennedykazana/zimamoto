<?php
/**
 * MFA Setup Page — embedded in the user profile section
 * Handles: setup (show QR), verify & enable, disable, recovery codes
 */
require_once __DIR__ . '/../../functions/totp_functions.php';

$userId  = (int)$_SESSION['userid'];
$message = '';
$msgType = 'info';

// Fetch fresh user data
$user = $conn->query("SELECT id, name, email, totp_enabled, totp_secret, totp_recovery_codes FROM users WHERE id = $userId")->fetch_assoc();
$totpEnabled = !empty($user['totp_enabled']) && $user['totp_enabled'] == 1;

// --- ACTIONS ---

// 1. Start Setup: generate a new secret and store it temporarily in session
if (isset($_POST['start_setup'])) {
    $secret = generateTOTPSecret();
    $_SESSION['pending_totp_secret'] = $secret;
    echo "<script>window.location.href='./?page=mfa_setup';</script>";
    exit;
}

// 2. Verify & Enable
if (isset($_POST['enable_totp'])) {
    $code   = preg_replace('/[^0-9]/', '', $_POST['totp_code'] ?? '');
    $secret = $_SESSION['pending_totp_secret'] ?? '';
    if ($secret && verifyTOTP($secret, $code)) {
        enableUserTOTP($conn, $userId, $secret);
        $recoveryCodes = generateRecoveryCodes(8);
        saveRecoveryCodes($conn, $userId, $recoveryCodes);
        unset($_SESSION['pending_totp_secret']);
        $_SESSION['show_recovery_codes'] = $recoveryCodes;
        $message = 'Two-Factor Authentication enabled successfully!';
        $msgType = 'success';
        $totpEnabled = true;
        $user = $conn->query("SELECT id, name, email, totp_enabled, totp_secret FROM users WHERE id = $userId")->fetch_assoc();
    } else {
        $message = 'Invalid code. Please try again — make sure your device clock is correct.';
        $msgType = 'danger';
    }
}

// 3. Disable
if (isset($_POST['disable_totp'])) {
    disableUserTOTP($conn, $userId);
    $message  = 'Two-Factor Authentication has been disabled.';
    $msgType  = 'warning';
    $totpEnabled = false;
    unset($_SESSION['pending_totp_secret'], $_SESSION['show_recovery_codes']);
}

// 4. Regenerate recovery codes
if (isset($_POST['regen_recovery'])) {
    if ($totpEnabled) {
        $recoveryCodes = generateRecoveryCodes(8);
        saveRecoveryCodes($conn, $userId, $recoveryCodes);
        $_SESSION['show_recovery_codes'] = $recoveryCodes;
        $message = 'Recovery codes regenerated. Save these now!';
        $msgType = 'warning';
    }
}

// Pending secret in session (setup in progress)
$pendingSecret = $_SESSION['pending_totp_secret'] ?? null;
$totpUri       = $pendingSecret ? generateTOTPURI($user['email'], $pendingSecret) : null;
$showRecovery  = $_SESSION['show_recovery_codes'] ?? null;
if ($showRecovery) unset($_SESSION['show_recovery_codes']);
?>

<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-shield-alt mr-2"></i>Two-Factor Authentication (2FA)</h4>
    </div>
    <div class="card-body">

        <?php if ($message): ?>
        <div class="alert alert-<?= $msgType ?>"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <!-- Status Banner -->
        <div class="alert <?= $totpEnabled ? 'alert-success' : 'alert-warning' ?>">
            <i class="fas <?= $totpEnabled ? 'fa-lock' : 'fa-unlock' ?> mr-2"></i>
            <strong>2FA is currently <?= $totpEnabled ? 'ENABLED' : 'DISABLED' ?></strong>
            <?php if ($totpEnabled): ?>
                — Your account is protected with an authenticator app.
            <?php else: ?>
                — We recommend enabling 2FA to protect your account.
            <?php endif; ?>
        </div>

        <?php if ($totpEnabled): ?>
        <!-- === ENABLED STATE === -->
        <div class="row">
            <div class="col-md-6">
                <div class="card card-outline card-danger">
                    <div class="card-header"><h5 class="card-title">Disable 2FA</h5></div>
                    <div class="card-body">
                        <p class="text-muted">Disabling 2FA will make your account less secure.</p>
                        <form method="post" onsubmit="return confirm('Are you sure you want to disable 2FA?')">
                            <button type="submit" name="disable_totp" class="btn btn-danger btn-block">
                                <i class="fas fa-unlock mr-1"></i> Disable Two-Factor Authentication
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-outline card-warning">
                    <div class="card-header"><h5 class="card-title">Recovery Codes</h5></div>
                    <div class="card-body">
                        <p class="text-muted">Recovery codes let you log in if you lose access to your authenticator app.</p>
                        <form method="post" onsubmit="return confirm('This will invalidate your current recovery codes. Continue?')">
                            <button type="submit" name="regen_recovery" class="btn btn-warning btn-block">
                                <i class="fas fa-redo mr-1"></i> Regenerate Recovery Codes
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($showRecovery): ?>
        <div class="alert alert-warning mt-3">
            <h5><i class="fas fa-key mr-2"></i>Save Your Recovery Codes</h5>
            <p>Store these codes in a safe place. Each can only be used once. If you lose your authenticator, these are your only backup.</p>
            <div class="row">
                <?php foreach ($showRecovery as $i => $code): ?>
                <div class="col-md-3 mb-2">
                    <code class="d-block text-center p-2 bg-dark text-light rounded" style="font-size:14px;"><?= htmlspecialchars($code) ?></code>
                </div>
                <?php endforeach; ?>
            </div>
            <button onclick="window.print()" class="btn btn-sm btn-secondary mt-2">
                <i class="fas fa-print mr-1"></i> Print Codes
            </button>
        </div>
        <?php endif; ?>

        <?php else: ?>
        <!-- === SETUP STATE === -->
        <?php if ($pendingSecret && $totpUri): ?>
        <!-- Step 2: Scan QR -->
        <div class="card card-outline card-info">
            <div class="card-header"><h5 class="card-title">Step 2: Scan QR Code</h5></div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-4 text-center">
                        <div id="qrcode" style="display:inline-block;padding:10px;background:#fff;border-radius:8px;"></div>
                        <p class="mt-2 text-muted small">Scan with Google Authenticator, Authy, or any TOTP app.</p>
                    </div>
                    <div class="col-md-8">
                        <p>Can't scan? Enter this key manually:</p>
                        <div class="input-group mb-3">
                            <input type="text" class="form-control" value="<?= htmlspecialchars($pendingSecret) ?>" id="secretKey" readonly>
                            <div class="input-group-append">
                                <button class="btn btn-secondary" onclick="copySecret()"><i class="fas fa-copy"></i></button>
                            </div>
                        </div>
                        <form method="post">
                            <div class="form-group">
                                <label><strong>Step 3: Enter the 6-digit code from your app</strong></label>
                                <input type="text" name="totp_code" class="form-control form-control-lg text-center"
                                    placeholder="000 000" maxlength="6" pattern="[0-9]{6}" inputmode="numeric"
                                    autocomplete="one-time-code" autofocus required style="font-size:24px;letter-spacing:6px;">
                            </div>
                            <button type="submit" name="enable_totp" class="btn btn-success btn-block">
                                <i class="fas fa-check mr-1"></i> Verify and Enable 2FA
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
        <script>
        new QRCode(document.getElementById('qrcode'), {
            text: <?= json_encode($totpUri) ?>,
            width: 180, height: 180,
            colorDark: '#000', colorLight: '#fff',
            correctLevel: QRCode.CorrectLevel.M
        });
        function copySecret() {
            document.getElementById('secretKey').select();
            document.execCommand('copy');
            alert('Secret key copied!');
        }
        </script>

        <?php else: ?>
        <!-- Step 1: Start Setup -->
        <div class="card card-outline card-primary">
            <div class="card-header"><h5 class="card-title">Enable Two-Factor Authentication</h5></div>
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col-md-2 text-center">
                        <i class="fas fa-shield-alt text-primary" style="font-size:60px;"></i>
                    </div>
                    <div class="col-md-10">
                        <h5>What you need:</h5>
                        <ol>
                            <li>Install an authenticator app:
                                <strong>Google Authenticator</strong> or <strong>Authy</strong>
                                (available on Android &amp; iOS)
                            </li>
                            <li>Click <strong>Set Up</strong> to get your QR code</li>
                            <li>Scan the QR code with the app</li>
                            <li>Enter the 6-digit code to confirm</li>
                        </ol>
                        <form method="post">
                            <button type="submit" name="start_setup" class="btn btn-primary">
                                <i class="fas fa-shield-alt mr-1"></i> Set Up Two-Factor Authentication
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endif; ?>

    </div>
</div>
