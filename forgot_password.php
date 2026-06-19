<?php
/**
 * forgot_password.php
 * Step 1 of password reset: user enters their email.
 * A signed, time-limited token is inserted into password_resets
 * and a link is emailed to the user.
 */
session_start();
require_once "./configs.php";
require_once "./functions/user_function.php";
require_once "./functions/email_functions.php";

$conn   = openConn();
$error  = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['forgot'])) {
    $email = trim(filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $user = selectUserByEmail($conn, $email);

        // Always show the same success message regardless of whether the
        // email exists — this prevents user enumeration.
        if ($user && is_array($user)) {
            // Generate a cryptographically secure token
            $token     = bin2hex(random_bytes(48)); // 96 hex chars
            $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 hour

            // Delete any old unused tokens for this user first
            $del = $conn->prepare("DELETE FROM password_resets WHERE user_id = ? AND used_at IS NULL");
            $del->bind_param("i", $user['id']);
            $del->execute();
            $del->close();

            // Insert new token
            $ins = $conn->prepare(
                "INSERT INTO password_resets (user_id, email, token, expires_at) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param("isss", $user['id'], $email, $token, $expiresAt);
            $inserted = $ins->execute();
            $ins->close();

            if ($inserted) {
                $resetLink = APP_URL . '/zimamoto/reset_password.php?token=' . urlencode($token);

                $subject = APP_NAME . ' — Password Reset Request';
                $body    = '
<!DOCTYPE html>
<html>
<body style="font-family:Arial,sans-serif;background:#f4f4f4;padding:30px;">
  <div style="max-width:560px;margin:auto;background:#fff;border-radius:8px;padding:32px;box-shadow:0 2px 8px rgba(0,0,0,.08);">
    <h2 style="color:#007bff;margin-top:0;">Password Reset</h2>
    <p>Hello <strong>' . htmlspecialchars($user['name']) . '</strong>,</p>
    <p>We received a request to reset your password for your <strong>' . APP_NAME . '</strong> account.</p>
    <p>Click the button below to choose a new password. This link expires in <strong>1 hour</strong>.</p>
    <p style="text-align:center;margin:32px 0;">
      <a href="' . $resetLink . '"
         style="background:#007bff;color:#fff;padding:12px 28px;border-radius:5px;text-decoration:none;font-size:16px;">
        Reset My Password
      </a>
    </p>
    <p>Or copy this link into your browser:</p>
    <p style="word-break:break-all;color:#555;font-size:13px;">' . $resetLink . '</p>
    <hr style="border:none;border-top:1px solid #eee;margin:24px 0;">
    <p style="font-size:12px;color:#999;">
      If you did not request a password reset, you can safely ignore this email.
      Your password will not change until you click the link above and create a new one.
    </p>
    <p style="font-size:12px;color:#999;">&copy; ' . date('Y') . ' ' . APP_NAME . '</p>
  </div>
</body>
</html>';

                sendEmail($email, $subject, $body);
            }
        }

        // Always show this — even if email not found (prevents enumeration)
        $success = 'If an account with that email exists, a password reset link has been sent. Please check your inbox (and spam folder).';
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> — Forgot Password</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="./plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="./plugins/icheck-bootstrap/icheck-bootstrap.min.css">
  <link rel="stylesheet" href="./dist/css/adminlte.min.css">
</head>
<body class="hold-transition login-page">
<div class="login-box">
  <div class="card card-outline card-primary">
    <div class="card-header text-center">
      <a href="./" class="h1"><b>TELLIC</b>ERP</a>
    </div>
    <div class="card-body">
      <p class="login-box-msg">
        <i class="fas fa-lock mr-1"></i> Forgot your password?<br>
        <small class="text-muted">Enter your email and we'll send you a reset link.</small>
      </p>

      <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible">
          <button type="button" class="close" data-dismiss="alert">&times;</button>
          <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <?php if ($success): ?>
        <div class="alert alert-success">
          <i class="fas fa-check-circle mr-1"></i> <?= htmlspecialchars($success) ?>
        </div>
        <div class="text-center mt-3">
          <a href="./login.php" class="btn btn-primary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Back to Login
          </a>
        </div>
      <?php else: ?>
        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
          <div class="input-group mb-3">
            <input type="email" name="email" class="form-control" placeholder="Your account email"
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autofocus>
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-envelope"></span>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <button type="submit" name="forgot" class="btn btn-primary btn-block">
                <i class="fas fa-paper-plane mr-1"></i> Send Reset Link
              </button>
            </div>
          </div>
        </form>
        <p class="mt-3 mb-1 text-center">
          <a href="./login.php"><i class="fas fa-arrow-left mr-1"></i>Back to Login</a>
        </p>
      <?php endif; ?>
    </div>
  </div>
</div>
<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./dist/js/adminlte.min.js"></script>
</body>
</html>
