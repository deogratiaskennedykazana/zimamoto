<?php
session_start();
// Redirect already-logged-in users back to dashboard
if (isset($_SESSION['userid'])) {
    header('Location: ./');
    exit;
}

$error   = null;
$success = null;

// Always open DB so the branch dropdown works on GET requests too
require_once "./configs.php";
require_once "./functions/user_function.php";
require_once "./functions/member_functions.php";
require_once "./functions/min_sub_functions.php";
require_once "./functions/branch_functions.php";
require_once "./functions/sms_functions.php";

$conn = openConn();

// ── Step 1: Registration form submission ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['signup'])) {

    $name       = htmlspecialchars(trim($_POST['name'] ?? ''));
    $email      = $conn->real_escape_string(trim($_POST['email'] ?? ''));
    $phone      = $conn->real_escape_string(trim($_POST['phone'] ?? ''));
    $password   = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';
    $branch_id  = (int) ($_POST['branch_id'] ?? 0);
    $nida       = $conn->real_escape_string(trim($_POST['nida'] ?? ''));
    $address    = $conn->real_escape_string(trim($_POST['address'] ?? ''));
    $gender     = $conn->real_escape_string($_POST['gender'] ?? '');
    $birthdate  = $conn->real_escape_string($_POST['birthdate'] ?? '');

    // Validation
    if (!$name || !$email || !$phone || !$password || !$branch_id) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $chk = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $chk->bind_param("s", $email);
        $chk->execute();
        $chk->store_result();
        if ($chk->num_rows > 0) {
            $error = 'An account with this email already exists. Please log in.';
        }
        $chk->close();
    }

    if (!$error) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);

        $newUserId = registerUser(
            $conn, $name, $email, 'member', 'member',
            $hashed, 'branch', $branch_id, $phone, 'pending'
        );

        if (is_numeric($newUserId) && $newUserId > 0) {
            $reg_no      = 'PENDING-' . $newUserId;
            $district_id = 1;

            // Optional document uploads — failures here must not block registration
            $photoFile  = null;
            $idCardFile = null;
            if (isset($_FILES['photo_file'])) {
                $result = handleOptionalMemberUpload($_FILES['photo_file'], 'member_photos', 'photo');
                $photoFile = ($result === false) ? null : $result;
            }
            if (isset($_FILES['id_card_file'])) {
                $result = handleOptionalMemberUpload($_FILES['id_card_file'], 'member_ids', 'idcard');
                $idCardFile = ($result === false) ? null : $result;
            }

            registerMember(
                $conn, $newUserId, $phone, $address,
                $reg_no, $birthdate ?: '1990-01-01',
                $district_id, $branch_id, $gender ?: 'other', $nida, '',
                $photoFile, $idCardFile
            );

            // Notify admins
            $admins = selectUsersByRole($conn, 'admin');
            if ($admins && is_array($admins)) {
                foreach ($admins as $admin) {
                    $msg  = "New member signup awaiting approval: $name ($email). Review in Pending Approvals.";
                    $link = './?page=pending_approvals';
                    $conn->query("INSERT INTO system_notifications
                        (user_id, type, title, message, link, is_read, created_at)
                        VALUES ({$admin['id']}, 'info', 'New Member Signup', '$msg', '$link', 0, NOW())");
                }
            }

            // Store for MFA opt-in step
            $_SESSION['signup_pending_mfa'] = [
                'user_id' => $newUserId,
                'name'    => $name,
                'phone'   => $phone,
            ];
            $success = 'registered';
        } else {
            $error = 'Registration failed — the email may already be in use. Please try again.';
        }
    }
}

// ── Step 2a: User chose to enable SMS MFA — send OTP ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['setup_mfa'])) {
    $pending = $_SESSION['signup_pending_mfa'] ?? null;
    if ($pending) {
        $userId = (int) $pending['user_id'];
        $phone  = $pending['phone'];
        $result = sendMfaOTP($conn, $userId, $phone);
        if ($result === true) {
            $_SESSION['signup_mfa_verify'] = $userId;
            $success = 'otp_sent';
        } else {
            $error   = 'Could not send SMS: ' . $result . ' — you can enable MFA later from your profile.';
            $success = 'registered';
        }
    }
}

// ── Step 2b: User submitted OTP to confirm their phone ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_mfa_otp'])) {
    $userId = (int) ($_SESSION['signup_mfa_verify'] ?? 0);
    $otp    = preg_replace('/[^0-9]/', '', $_POST['otp_code'] ?? '');
    if ($userId && verifySmsOTP($conn, $userId, $otp)) {
        enableSmsMFA($conn, $userId);
        unset($_SESSION['signup_pending_mfa'], $_SESSION['signup_mfa_verify']);
        $success = 'mfa_enabled';
    } else {
        $error   = 'Invalid or expired code. Please try again.';
        $success = 'otp_sent';
    }
}

// ── Step 2c: User chose to skip MFA ──────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['skip_mfa'])) {
    unset($_SESSION['signup_pending_mfa'], $_SESSION['signup_mfa_verify']);
    $success = 'done';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Member Sign Up — SBI SACCOS</title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="dist/css/adminlte.min.css">
  <style>
    body { background: #f4f6f9; }
    .signup-box { max-width: 520px; margin: 60px auto; }
    .brand-text { font-size: 1.4rem; font-weight: 600; color: #343a40; }
    .otp-input { font-size: 28px; letter-spacing: 10px; text-align: center; }
  </style>
</head>
<body class="hold-transition">
<div class="signup-box">

  <div class="text-center mb-3">
    <span class="brand-text"><i class="fas fa-university mr-2 text-primary"></i>SBI SACCOS — Member Registration</span>
  </div>

  <?php if (in_array($success, ['registered','otp_sent','mfa_enabled','done'])): ?>

    <?php if ($success === 'registered'): ?>
    <!-- ── Card: Registration done → ask about SMS MFA ── -->
    <div class="card card-success">
      <div class="card-header"><h4 class="card-title"><i class="fas fa-check-circle mr-2"></i>Application Submitted</h4></div>
      <div class="card-body">
        <p class="mb-2">Your registration has been submitted and is <strong>pending approval</strong> by an administrator.</p>
        <hr>
        <h5 class="mb-1"><i class="fas fa-mobile-alt mr-2 text-primary"></i>Secure Your Account with SMS Verification</h5>
        <p class="text-muted">Enable two-factor authentication so a one-time code is sent to your phone
          <strong><?= htmlspecialchars($_SESSION['signup_pending_mfa']['phone'] ?? '') ?></strong>
          each time you log in. This keeps your account safe even if your password is compromised.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="row mt-3">
          <div class="col-7">
            <form method="post">
              <button type="submit" name="setup_mfa" class="btn btn-primary btn-block">
                <i class="fas fa-shield-alt mr-1"></i> Yes, enable SMS 2FA
              </button>
            </form>
          </div>
          <div class="col-5">
            <form method="post">
              <button type="submit" name="skip_mfa" class="btn btn-outline-secondary btn-block">
                <i class="fas fa-forward mr-1"></i> Skip for now
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <?php elseif ($success === 'otp_sent'): ?>
    <!-- ── Card: OTP sent → verify phone ── -->
    <div class="card card-primary">
      <div class="card-header"><h4 class="card-title"><i class="fas fa-sms mr-2"></i>Verify Your Phone Number</h4></div>
      <div class="card-body">
        <p>A 6-digit code has been sent to
          <strong><?= htmlspecialchars($_SESSION['signup_pending_mfa']['phone'] ?? '') ?></strong>.
          Enter it below to confirm and activate SMS 2FA.</p>

        <?php if ($error): ?>
          <div class="alert alert-danger"><i class="fas fa-exclamation-circle mr-1"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post">
          <div class="form-group">
            <label>Verification Code</label>
            <input type="text" name="otp_code" class="form-control otp-input"
                   placeholder="000000" maxlength="6" pattern="[0-9]{6}"
                   inputmode="numeric" autocomplete="one-time-code" autofocus required>
            <small class="text-muted">Code expires in 10 minutes.</small>
          </div>
          <button type="submit" name="verify_mfa_otp" class="btn btn-primary btn-block">
            <i class="fas fa-check mr-1"></i> Verify &amp; Enable 2FA
          </button>
        </form>

        <div class="text-center mt-3">
          <form method="post" style="display:inline;">
            <button type="submit" name="skip_mfa" class="btn btn-link btn-sm text-muted">
              Skip and continue without 2FA
            </button>
          </form>
        </div>
      </div>
    </div>

    <?php elseif ($success === 'mfa_enabled'): ?>
    <!-- ── Card: MFA enabled ── -->
    <div class="card card-success">
      <div class="card-header"><h4 class="card-title"><i class="fas fa-shield-alt mr-2"></i>SMS 2FA Enabled</h4></div>
      <div class="card-body">
        <p><i class="fas fa-check-circle text-success mr-2"></i>
          Phone verification is now active on your account. You will be asked for a code each time you log in.</p>
        <p class="text-muted">Your account is still <strong>pending admin approval</strong>. You will be able to log in once it is approved.</p>
      </div>
      <div class="card-footer">
        <a href="./login.php" class="btn btn-success btn-block">Back to Login</a>
      </div>
    </div>

    <?php else: ?>
    <!-- ── Card: Skipped MFA ── -->
    <div class="card card-success">
      <div class="card-header"><h4 class="card-title">Application Submitted</h4></div>
      <div class="card-body">
        <p class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>
          Your registration has been submitted and is <strong>pending approval</strong> by an administrator.</p>
        <p class="text-muted">You can enable SMS 2FA anytime from your profile after logging in.</p>
      </div>
      <div class="card-footer">
        <a href="./login.php" class="btn btn-success btn-block">Back to Login</a>
      </div>
    </div>
    <?php endif; ?>

  <?php else: ?>
    <!-- ── Registration Form ── -->
    <div class="card card-primary">
      <div class="card-header"><h4 class="card-title">Create Member Account</h4></div>

      <?php if ($error): ?>
        <div class="alert alert-danger mx-3 mt-3 mb-0">
          <i class="fas fa-exclamation-circle mr-1"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="./signup.php" method="post" enctype="multipart/form-data" class="was-validated">
        <div class="card-body">

          <div class="form-group">
            <label>Full Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control" required
                   value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Email Address <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required
                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Phone Number <span class="text-danger">*</span></label>
            <input type="text" name="phone" class="form-control" required
                   placeholder="e.g. 0712345678"
                   value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Branch <span class="text-danger">*</span></label>
            <select name="branch_id" class="form-control" required>
              <option value="">— Select Branch —</option>
              <?php
                $branches = selectAllBranches($conn);
                if ($branches && is_array($branches)) {
                    foreach ($branches as $b) {
                        $sel = (isset($_POST['branch_id']) && $_POST['branch_id'] == $b['id']) ? 'selected' : '';
                        echo "<option value='{$b['id']}' $sel>{$b['name']}</option>";
                    }
                }
              ?>
            </select>
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group">
                <label>Gender</label>
                <select name="gender" class="form-control">
                  <option value="">— Select —</option>
                  <?php foreach (['male','female','other'] as $g):
                    $sel = (isset($_POST['gender']) && $_POST['gender'] === $g) ? 'selected' : ''; ?>
                    <option value="<?= $g ?>" <?= $sel ?>><?= ucfirst($g) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group">
                <label>Date of Birth</label>
                <input type="date" name="birthdate" class="form-control"
                       max="<?= date('Y-m-d', strtotime('-16 years')) ?>"
                       value="<?= htmlspecialchars($_POST['birthdate'] ?? '') ?>">
              </div>
            </div>
          </div>

          <div class="form-group">
            <label>NIDA Number</label>
            <input type="text" name="nida" class="form-control"
                   value="<?= htmlspecialchars($_POST['nida'] ?? '') ?>">
          </div>

          <div class="form-group">
            <label>Address</label>
            <input type="text" name="address" class="form-control"
                   value="<?= htmlspecialchars($_POST['address'] ?? '') ?>">
          </div>

          <hr>

          <div class="form-group">
            <label>Upload Your Photo / Passport Picture <span class="text-muted">(optional)</span></label>
            <input type="file" name="photo_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            <small class="text-muted">JPG, PNG or PDF. You may also add this later from your profile.</small>
          </div>

          <div class="form-group">
            <label>Upload Your ID Card / NIDA Card <span class="text-muted">(optional)</span></label>
            <input type="file" name="id_card_file" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
            <small class="text-muted">JPG, PNG or PDF. You may also add this later from your profile.</small>
          </div>

          <hr>

          <div class="form-group">
            <label>Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required minlength="6">
            <small class="text-muted">Minimum 6 characters.</small>
          </div>

          <div class="form-group">
            <label>Confirm Password <span class="text-danger">*</span></label>
            <input type="password" name="confirm_password" class="form-control" required minlength="6">
          </div>

        </div>
        <div class="card-footer">
          <button type="submit" name="signup" class="btn btn-primary btn-block">
            <i class="fas fa-user-plus mr-1"></i> Submit Application
          </button>
          <div class="text-center mt-2">
            <small>Already have an account? <a href="./login.php">Log in here</a></small>
          </div>
        </div>
      </form>
    </div>
  <?php endif; ?>
</div>
<script src="plugins/jquery/jquery.min.js"></script>
<script src="plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>
