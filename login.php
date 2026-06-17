<?php
    session_start();
    require_once "./configs.php";
    require_once "./functions/user_function.php";
    require_once "./functions/totp_functions.php";
    $conn = openConn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= APP_NAME ?> - Sign In</title>
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
      <p class="login-box-msg">Sign in to start your session</p>
      <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post">
        <div class="input-group mb-3">
          <input type="email" name="email" class="form-control" placeholder="Email" required autofocus>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-envelope"></span>
            </div>
          </div>
        </div>
        <div class="input-group mb-3">
          <input type="password" name="password" class="form-control" placeholder="Password" required>
          <div class="input-group-append">
            <div class="input-group-text">
              <span class="fas fa-lock"></span>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-8">
            <div class="icheck-primary">
              <input type="checkbox" id="remember">
              <label for="remember">
                Remember Me
              </label>
            </div>
          </div>
          <div class="col-4">
            <button type="submit" name="login" class="btn btn-primary btn-block">Sign In</button>
          </div>
        </div>
      </form>
      <p class="mb-1 d-none">
        <a href="forgot-password.html">I forgot my password</a>
      </p>
    </div>
  </div>
</div>
<?php
    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])){
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $password = $_POST['password'];
        $user = selectUserByEmailOrPhone($conn, $email);
        $data = [
            "email"      => $email,
            "session_id" => session_id(),
            "ip_address" => $_SERVER['REMOTE_ADDR'],
            "user_agent" => $_SERVER['HTTP_USER_AGENT'],
        ];
        if($user && is_array($user)){
            $data['user_id'] = $user['id'];
            if(password_verify($password, $user['password'])){
                if($user['status'] === 'approved'){
                    $data['status'] = 'success';
                    $_SESSION['username'] = $user['name'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['usertype'] = $user['type'];
                    $_SESSION['userid'] = $user['id'];
                    $_SESSION['userlevel'] = $user['level'];
                    $_SESSION['branchid'] = $user['branch_id'];
                    recordUserLogin($conn, $data);
                    
                    // Check if MFA/TOTP is enabled
                    if (!empty($user['totp_enabled']) && $user['totp_enabled'] == 1) {
                        // Store secret temporarily and redirect to MFA page
                        $_SESSION['totp_secret'] = $user['totp_secret'];
                        requireMFAVerification();
                        echo "<script>window.location.href = './mfa_verify.php';</script>";
                    } else {
                        // Normal login without MFA
                        $_SESSION['mfa_verified'] = true;
                        echo "<script>window.location.href = './';</script>";
                    }
                } else{
                    $data['status'] = 'failed';
                    $data['failure_reason'] = 'user_inactive';
                    recordUserLogin($conn, $data);
                    $error = 'User is inactive. Please contact your admin.';
                }
            } else{
                $data['status'] = 'failed';
                $data['failure_reason'] = 'wrong_password';
                recordUserLogin($conn, $data);
                $error = 'Incorrect password.';
            }
        } else{
            $data['status'] = 'failed';
            $data['failure_reason'] = 'user_not_found';
            recordUserLogin($conn, $data);
            $error = 'Account not found with that email.';
        }
    }
    $conn->close();
?>
<script src="./plugins/jquery/jquery.min.js"></script>
<script src="./plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="./dist/js/adminlte.min.js"></script>
</body>
</html>