<?php
/**
 * reset_password.php
 * Step 2 of password reset: user clicks email link, enters new password.
 * Token is validated (exists, not expired, not used), then password updated.
 */
session_start();
require_once "./configs.php";
require_once "./functions/user_function.php";

$conn  = openConn();
$error = null;
$success = null;
$tokenRow = null;

// ── Validate the token from the URL ──────────────────────────────────────────
$token = trim($_GET['token'] ?? '');

if (empty($token)) {
    $error = 'Invalid or missing reset token. Please request a new password reset.';
} else {
    $stmt = $conn->prepare(
        "SELECT pr.*, u.name AS user_name, u.email AS user_email
         FROM password_resets pr
         JOIN users u ON u.id = pr.user_id
         WHERE pr.token = ?
           AND pr.used_at IS NULL
           AND pr.expires_at > NOW()
         LIMIT 1"
    );
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $tokenRow = stmt_fetch_assoc($stmt);
    $stmt->close();

    if (!$tokenRow) {
        $error = 'This reset link is invalid or has expired. Please <a href="./forgot_password.php">request a new one</a>.';
    }
}

// ── Handle new password submission ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password']) && $tokenRow) {
    $newPassword  = $_POST['new_password']     ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (strlen($newPassword) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match.';
    } else {
        $hashed = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update the user's password
        $upd = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $upd->bind_param("si", $hashed, $tokenRow['user_id']);
        $updated = $upd->execute();
        $upd->close();

        if ($updated) {
            // Mark the token as used so it cannot be reused
            $mark = $conn->prepare("UPDATE password_resets SET used_at = NOW() WHERE token = ?");
            $mark->bind_param("s", $token);
            $mark->execute();
            $mark->close();

            $success = 'Your password has been reset successfully. You can now log in with your new password.';
            $tokenRow = null; // hide the form
        } else {
            $error = 'Failed to update your password. Please try again.';
        }
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> — Reset Password</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./dist/css/adminlte.min.css">
  <style>
    .password-strength { height: 6px; border-radius: 3px; transition: all .3s; }
    .strength-weak   { background: #dc3545; width: 33%; }
    .strength-medium { background: #ffc107; width: 66%; }
    .strength-strong { background: #28a745; width: 100%; }
  </style>
</head>
<body class="hold-transition login-page">
<div class="login-box" style="width:420px;">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="./" class="h1"><b>TELLIC</b>ERP</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">
        <i class="fas fa-key mr-1"></i> Reset Your Password
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger">
          <i class="fas fa-exclamation-circle mr-1"></i>
          <?= $error /* may contain a safe HTML link */ ?>
        </div>
        <div class="text-center">
          <a href="./login.php" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
          </a>
        </div>
      <?php elseif ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($success) ?>
        </div>
        <div class="text-center mt-3">
          <a href="./login.php" class="btn btn-primary">
            <i class="fas fa-sign-in-alt mr-1"></i> Log In Now
          </a>
        </div>
      <?php elseif ($tokenRow): ?>
        <p class="text-muted text-center mb-3" style="font-size:13px;">
          Resetting password for <strong><?= htmlspecialchars($tokenRow['user_email']) ?></strong>
        </p>
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>?token=<?= urlencode($token) ?>" method="post" id="resetForm">
          <div class="form-group mb-3">
            <label class="text-sm">New Password</label>
            <div class="input-group">
              <input type="password" name="new_password" id="new_password"
                     class="form-control" placeholder="Min 8 characters" required minlength="8"
                     oninput="checkStrength(this.value)">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="new_password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <div class="password-strength mt-1" id="strengthBar"></div>
            <small id="strengthLabel" class="text-muted"></small>
          </div>

          <div class="form-group mb-3">
            <label class="text-sm">Confirm New Password</label>
            <div class="input-group">
              <input type="password" name="confirm_password" id="confirm_password"
                     class="form-control" placeholder="Repeat password" required minlength="8">
              <div class="input-group-append">
                <button type="button" class="btn btn-outline-secondary toggle-pw" data-target="confirm_password">
                  <i class="fas fa-eye"></i>
                </button>
              </div>
            </div>
            <small id="matchLabel" class="text-muted"></small>
          </div>

          <button type="submit" name="reset_password" class="btn btn-primary btn-block">
            <i class="fas fa-save mr-1"></i> Set New Password
          </button>
        </form>
      <?php endif; ?>

    </div>
  </div>
</div>

<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./dist/js/adminlte.min.js"></script>
<script>
// Toggle password visibility
document.querySelectorAll('.toggle-pw').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var input = document.getElementById(this.dataset.target);
        var icon  = this.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('fa-eye', 'fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.replace('fa-eye-slash', 'fa-eye');
        }
    });
});

// Password strength indicator
function checkStrength(val) {
    var bar   = document.getElementById('strengthBar');
    var label = document.getElementById('strengthLabel');
    bar.className = 'password-strength mt-1';
    if (val.length === 0) { label.textContent = ''; return; }
    var strong = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).{8,}$/.test(val);
    var medium = /^(?=.*[a-zA-Z])(?=.*\d).{8,}$/.test(val);
    if (strong) {
        bar.classList.add('strength-strong');
        label.textContent = 'Strong'; label.className = 'text-success';
    } else if (medium) {
        bar.classList.add('strength-medium');
        label.textContent = 'Medium'; label.className = 'text-warning';
    } else {
        bar.classList.add('strength-weak');
        label.textContent = 'Weak'; label.className = 'text-danger';
    }
}

// Live match check
document.getElementById('confirm_password') && document.getElementById('confirm_password').addEventListener('input', function() {
    var pw1 = document.getElementById('new_password').value;
    var lbl = document.getElementById('matchLabel');
    if (this.value === '') { lbl.textContent = ''; return; }
    if (this.value === pw1) {
        lbl.textContent = '✔ Passwords match'; lbl.className = 'text-success';
    } else {
        lbl.textContent = '✘ Passwords do not match'; lbl.className = 'text-danger';
    }
});
</script>
</body>
</html>
