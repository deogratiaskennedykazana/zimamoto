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

$conn = openConn();

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

    // ── Validation ───────────────────────────────────────────
    if (!$name || !$email || !$phone || !$password || !$branch_id) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        // Check if email already taken
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

        // Insert user with status = 'pending' so admin must approve
        $newUserId = registerUser(
            $conn, $name, $email, 'member', 'member',
            $hashed, 'branch', $branch_id, $phone, 'pending'
        );

        if (is_numeric($newUserId) && $newUserId > 0) {
            // Create the members row (minimal — admin fills details later if needed)
            $reg_no    = 'PENDING-' . $newUserId;
            $district_id = 1; // default; admin can update on approval
            registerMember(
                $conn, $newUserId, $phone, $address,
                $reg_no, $birthdate ?: '1990-01-01',
                $district_id, $branch_id, $gender ?: 'other', $nida, ''
            );

            // Notify all admins
            $admins = selectUsersByRole($conn, 'admin');
            if ($admins && is_array($admins)) {
                foreach ($admins as $admin) {
                    $msg = "New member signup awaiting approval: $name ($email). Review in Pending Approvals.";
                    $link = './?page=pending_approvals';
                    $conn->query("INSERT INTO system_notifications
                        (user_id, type, title, message, link, is_read, created_at)
                        VALUES ({$admin['id']}, 'info', 'New Member Signup', '$msg', '$link', 0, NOW())");
                }
            }

            $success = true;
        } else {
            $error = 'Registration failed — the email may already be in use. Please try again.';
        }
    }
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
  </style>
</head>
<body class="hold-transition">
<div class="signup-box">

  <div class="text-center mb-3">
    <span class="brand-text"><i class="fas fa-university mr-2 text-primary"></i>SBI SACCOS — Member Registration</span>
  </div>

  <?php if ($success): ?>
    <div class="card card-success">
      <div class="card-header"><h4 class="card-title">Application Submitted</h4></div>
      <div class="card-body">
        <p class="mb-1"><i class="fas fa-check-circle text-success mr-2"></i>
          Your registration has been submitted and is <strong>pending approval</strong> by an administrator.</p>
        <p class="text-muted">You will be able to log in once your account is approved. Please check back later.</p>
      </div>
      <div class="card-footer">
        <a href="./login.php" class="btn btn-success btn-block">Back to Login</a>
      </div>
    </div>
  <?php else: ?>
    <div class="card card-primary">
      <div class="card-header"><h4 class="card-title">Create Member Account</h4></div>

      <?php if ($error): ?>
        <div class="alert alert-danger mx-3 mt-3 mb-0">
          <i class="fas fa-exclamation-circle mr-1"></i> <?= htmlspecialchars($error) ?>
        </div>
      <?php endif; ?>

      <form action="./signup.php" method="post" class="was-validated">
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
