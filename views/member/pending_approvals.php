<?php
// Guard: only admins can access this page
$allowedRoles = ['admin', 'superadmin', 'super admin'];
if (!in_array(strtolower($_SESSION['role'] ?? ''), $allowedRoles)) {
    echo '<div class="alert alert-danger m-3">
            <i class="fas fa-lock mr-1"></i> Access denied. Admins only.
          </div>';
    return;
}

// ── Handle approve / reject actions ─────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $user_id = (int) ($_POST['user_id'] ?? 0);

    if ($user_id > 0 && in_array($action, ['approved', 'rejected'])) {
        $ok = setUserStatus($conn, $user_id, $action);

        if ($ok && $action === 'approved') {
            // Create the 4 min_sub accounts for the newly approved member
            $userRow = $conn->query("SELECT * FROM users WHERE id = $user_id LIMIT 1")->fetch_assoc();
            if ($userRow) {
                $uname     = $conn->real_escape_string($userRow['name']);
                $branch_id = (int) $userRow['branch_id'];

                require_once "./functions/min_sub_functions.php";
                createMinsub($conn, $uname . ' Amana Account',  $user_id, 9,  $branch_id, 'person', 'amana');
                createMinsub($conn, $uname . ' Share Account',  $user_id, 8,  $branch_id, 'person', 'share');
                createMinsub($conn, $uname . ' Saving Account', $user_id, 7,  $branch_id, 'person', 'saving');
                createMinsub($conn, $uname . ' Loan Account',   $user_id, 59, $branch_id, 'person', 'loan');

                // Notify the member they're approved
                $msg = 'Your membership application has been approved. You can now log in.';
                $conn->query("INSERT INTO system_notifications
                    (user_id, type, title, message, link, is_read, created_at)
                    VALUES ($user_id, 'success', 'Account Approved', '$msg', './', 0, NOW())");
            }
        } elseif ($ok && $action === 'rejected') {
            $msg = 'Unfortunately, your membership application was not approved. Please contact the branch for more information.';
            $conn->query("INSERT INTO system_notifications
                (user_id, type, title, message, link, is_read, created_at)
                VALUES ($user_id, 'danger', 'Application Not Approved', '$msg', './', 0, NOW())");
        }

        $label = ($action === 'approved') ? 'Approved' : 'Rejected';
        echo "<script>alert('$label successfully'); window.location.href='./?page=pending_approvals';</script>";
        return;
    }
}

// ── Fetch all pending users ───────────────────────────────────
$pending = selectPendingUsers($conn);
?>

<div class="card card-warning">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-user-clock mr-2"></i>Pending Member Approvals
    </h4>
    <div class="card-tools">
      <span class="badge badge-warning badge-lg"><?= count($pending) ?> pending</span>
    </div>
  </div>
  <div class="card-body p-0">
    <?php if (empty($pending)): ?>
      <div class="p-4 text-center text-muted">
        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i><br>
        No pending applications — all caught up!
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm mb-0 table-search">
          <thead class="thead-dark">
            <tr>
              <th>#</th>
              <th>Name</th>
              <th>Email</th>
              <th>Phone</th>
              <th>Branch</th>
              <th>Applied</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($pending as $i => $u): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= htmlspecialchars($u['name']) ?></td>
              <td><?= htmlspecialchars($u['email']) ?></td>
              <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
              <td><?= htmlspecialchars($u['branch_name'] ?? '—') ?></td>
              <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
              <td>
                <!-- Approve -->
                <form method="post" action="./?page=pending_approvals" style="display:inline"
                      onsubmit="return confirm('Approve <?= addslashes(htmlspecialchars($u['name'])) ?>?')">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="approved">
                  <button type="submit" class="btn btn-xs btn-success">
                    <i class="fas fa-check mr-1"></i>Approve
                  </button>
                </form>
                <!-- Reject -->
                <form method="post" action="./?page=pending_approvals" style="display:inline"
                      onsubmit="return confirm('Reject <?= addslashes(htmlspecialchars($u['name'])) ?>? This cannot be undone.')">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <input type="hidden" name="action" value="rejected">
                  <button type="submit" class="btn btn-xs btn-danger">
                    <i class="fas fa-times mr-1"></i>Reject
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>
