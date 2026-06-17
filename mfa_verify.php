<?php
session_start();
if (!isset($_SESSION['mfa_required']) || $_SESSION['mfa_required'] !== true) {
    header('Location: ./');
    exit;
}

require_once "./configs.php";
require_once "./functions/totp_functions.php";
$conn = openConn();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['verify_code'])) {
        $code = preg_replace('/[^0-9]/', '', $_POST['totp_code']);
        
        if (verifyTOTP($_SESSION['totp_secret'], $code)) {
            completeMFAVerification();
            // Remove used secret from session
            unset($_SESSION['totp_secret']);
            echo "<script>window.location.href='./';</script>";
            exit;
        } else {
            // Try recovery code
            if (isset($_POST['use_recovery']) && $_POST['use_recovery'] == '1') {
                if (verifyRecoveryCode($conn, $_SESSION['userid'], $code)) {
                    completeMFAVerification();
                    unset($_SESSION['totp_secret']);
                    echo "<script>window.location.href='./';</script>";
                    exit;
                }
            }
            $error = 'Invalid verification code. Please try again.';
        }
    }
}

$userId = $_SESSION['userid'];
$userSql = "SELECT email, totp_enabled FROM users WHERE id = ?";
$stmt = $conn->prepare($userSql);
$stmt->bind_param("i", $userId);
$stmt->execute();
$user = stmt_fetch_assoc($stmt);
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Two-Factor Authentication - <?= APP_NAME ?></title>
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
      <p class="login-box-msg">Two-Factor Authentication Required</p>
      
      <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <p class="text-muted">Please enter the 6-digit code from your authenticator app.</p>
      
      <form action="" method="post">
        <div class="input-group mb-3">
          <input type="text" name="totp_code" class="form-control text-center" placeholder="000000" 
                 maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code" required autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-shield-alt"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-12">
            <button type="submit" name="verify_code" class="btn btn-primary btn-block">Verify</button>
          </div>
        </div>
      </form>
      
      <hr>
      
      <p class="mb-1">
        <a href="#" onclick="showRecovery()">Use a recovery code instead</a>
      </p>
      
      <div id="recoverySection" style="display:none;" class="mt-3">
        <p class="text-muted">Enter one of your recovery codes.</p>
        <form action="" method="post">
          <div class="input-group mb-3">
            <input type="text" name="totp_code" class="form-control text-center" placeholder="XXXX-XXXX" 
                   pattern="[A-Za-z0-9]{4}-[A-Za-z0-9]{4}" autocomplete="off">
            <input type="hidden" name="use_recovery" value="1">
            <div class="input-group-append">
              <div class="input-group-text">
                <span class="fas fa-key"></span>
              </div>
            </div>
          </div>
          <button type="submit" name="verify_code" class="btn btn-warning btn-block">Verify Recovery Code</button>
        </form>
      </div>
      
      <p class="mb-0 mt-3">
        <a href="./?page=logout" class="text-center">Cancel and log out</a>
      </p>
    </div>
  </div>
</div>

<script>
function showRecovery() {
    document.getElementById('recoverySection').style.display = 'block';
    return false;
}
</script>

<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./dist/js/adminlte.min.js"></script>
</body>
</html>
