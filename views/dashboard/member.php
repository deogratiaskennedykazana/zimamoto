<?php
/**
 * Member Dashboard — Chart.js graphs
 * - No get_result() — uses stmt_fetch_assoc() / stmt_fetch_all() polyfill only
 * - Correct column names: min_transactions.date_  |  loans.approve_date  |  transaction_voucher.dr_ammount
 */

$memberId = (int)$_SESSION['userid'];

// ---- Member MFA status ----
$totpEnabled = false;
$smsMfaEnabled = false;
$stmtMfa = $conn->prepare("SELECT totp_enabled, sms_mfa_enabled FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1");
if ($stmtMfa) {
    $stmtMfa->bind_param("i", $memberId);
    $stmtMfa->execute();
    $mfaRow = stmt_fetch_assoc($stmtMfa);
    if ($mfaRow) {
        $totpEnabled = !empty($mfaRow['totp_enabled']) && $mfaRow['totp_enabled'] == 1;
        $smsMfaEnabled = !empty($mfaRow['sms_mfa_enabled']) && $mfaRow['sms_mfa_enabled'] == 1;
    }
    $stmtMfa->close();
}

// ---- Helper: member account balance ----
function getMemberBalance(mysqli $conn, int $userId, string $category): float {
    $stmt = $conn->prepare(
        "SELECT id FROM min_subs WHERE user_id = ? AND category = ? AND deleted_at IS NULL LIMIT 1"
    );
    if (!$stmt) return 0.0;
    $stmt->bind_param("is", $userId, $category);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    $stmt->close();
    if (!$row) return 0.0;

    $accId = (int)$row['id'];
    $txns  = getMinTransactionByMinSubId($conn, $accId);   // returns array via fetch_all
    $bal   = 0.0;
    if (is_array($txns)) {
        foreach ($txns as $t) {
            if ((int)$t['dr_account'] === $accId) $bal += (float)$t['amount'];
            elseif ((int)$t['cr_account'] === $accId) $bal -= (float)$t['amount'];
        }
    }
    return abs($bal);
}

$shareBalance  = getMemberBalance($conn, $memberId, 'share');
$savingBalance = getMemberBalance($conn, $memberId, 'saving');
$loanBalance   = getMemberBalance($conn, $memberId, 'loan');

// ---- Monthly contribution trend — column is `date_` not `transaction_date` ----
$monthlyLabels  = [];
$monthlySavings = [];
$monthlyShares  = [];

$stmtTrend = $conn->prepare(
    "SELECT COALESCE(SUM(mt.amount),0) AS total
     FROM min_transactions mt
     JOIN min_subs ms ON mt.dr_account = ms.id
     WHERE ms.user_id = ? AND ms.category = ? AND ms.deleted_at IS NULL
     AND DATE_FORMAT(mt.date_,'%Y-%m') = ?"
);

for ($i = 5; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $monthlyLabels[] = date('M Y', strtotime("-$i months"));

    if ($stmtTrend) {
        $cat = 'saving';
        $stmtTrend->bind_param("iss", $memberId, $cat, $month);
        $stmtTrend->execute();
        $r = stmt_fetch_assoc($stmtTrend);
        $monthlySavings[] = (float)($r['total'] ?? 0);

        $cat = 'share';
        $stmtTrend->bind_param("iss", $memberId, $cat, $month);
        $stmtTrend->execute();
        $r = stmt_fetch_assoc($stmtTrend);
        $monthlyShares[] = (float)($r['total'] ?? 0);
    } else {
        $monthlySavings[] = 0;
        $monthlyShares[]  = 0;
    }
}
if ($stmtTrend) $stmtTrend->close();

// ---- My approved loans ----
$myLoans   = selectLoansByStatusAndUserId($conn, 'approved', $memberId);
$loanCount = is_array($myLoans) ? count($myLoans) : 0;

// ---- Admin / system-wide data ----
$role    = $_SESSION['role']      ?? '';
$level   = $_SESSION['userlevel'] ?? '';
$isAdmin = !($role === 'member' && $level === 'branch');

$loanCounts      = [0, 0, 0, 0];
$disburseAmounts = [];
$branchLabels    = [];
$branchCounts    = [];
$vData           = ['cnt' => 0, 'total' => 0];
$totalMembers    = 0;

if ($isAdmin) {

    // Loan counts by status
    foreach (['pending', 'approved', 'rejected', 'processing'] as $idx => $st) {
        $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM loans WHERE status = ? AND deleted_at IS NULL");
        if ($stmt) {
            $stmt->bind_param("s", $st);
            $stmt->execute();
            $r = stmt_fetch_assoc($stmt);
            $loanCounts[$idx] = (int)($r['cnt'] ?? 0);
            $stmt->close();
        }
    }

    // Monthly disbursements — column is `approve_date`
    $stmtDisb = $conn->prepare(
        "SELECT COALESCE(SUM(principle),0) AS total FROM loans
         WHERE status = 'approved' AND DATE_FORMAT(approve_date,'%Y-%m') = ? AND deleted_at IS NULL"
    );
    for ($i = 5; $i >= 0; $i--) {
        $month = date('Y-m', strtotime("-$i months"));
        if ($stmtDisb) {
            $stmtDisb->bind_param("s", $month);
            $stmtDisb->execute();
            $r = stmt_fetch_assoc($stmtDisb);
            $disburseAmounts[] = (float)($r['total'] ?? 0);
        } else {
            $disburseAmounts[] = 0;
        }
    }
    if ($stmtDisb) $stmtDisb->close();

    // Members per branch (top 8) — uses query() so fetch_assoc() is fine here
    $r = $conn->query(
        "SELECT b.name, COUNT(m.id) AS cnt
         FROM members m JOIN branches b ON m.branch_id = b.id
         GROUP BY b.id ORDER BY cnt DESC LIMIT 8"
    );
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $branchLabels[] = $row['name'];
            $branchCounts[] = (int)$row['cnt'];
        }
    }

    // Vouchers this month — column is `dr_ammount` (note double-m), date is `date_`
    $thisMonth = date('Y-m');
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt, COALESCE(SUM(dr_ammount),0) AS total
         FROM transaction_voucher WHERE DATE_FORMAT(date_,'%Y-%m') = ? AND deleted_at IS NULL"
    );
    if ($stmt) {
        $stmt->bind_param("s", $thisMonth);
        $stmt->execute();
        $r2 = stmt_fetch_assoc($stmt);
        $vData = $r2 ?: ['cnt' => 0, 'total' => 0];
        $stmt->close();
    }

    // Total members
    $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM members WHERE deleted_at IS NULL");
    if ($stmt) {
        $stmt->execute();
        $r3 = stmt_fetch_assoc($stmt);
        $totalMembers = (int)($r3['c'] ?? 0);
        $stmt->close();
    }
}
?>

<div class="row">

    <!-- ===== MEMBER PERSONAL SECTION ===== -->
    <div class="col-12">
        <div class="d-flex flex-wrap align-items-center justify-content-between mb-3">
            <div class="mb-2 mb-sm-0">
                <h5 class="mb-0">
                    <i class="fas fa-user-circle mr-2 text-primary"></i>
                    My Account Summary — <?= date('F Y') ?>
                </h5>
            </div>
            <div class="d-flex flex-wrap align-items-center">
                <?php if ($totpEnabled || $smsMfaEnabled): ?>
                    <span class="badge badge-success mb-2 mb-sm-0">MFA Enabled</span>
                <?php else: ?>
                    <span class="badge badge-warning mb-2 mb-sm-0">MFA Disabled</span>
                <?php endif; ?>
                <a href="./?page=mfa_setup" class="btn btn-sm btn-outline-secondary ml-0 ml-sm-2 mb-2 mb-sm-0">
                    <i class="fas fa-shield-alt mr-1"></i> Manage 2FA
                </a>
            </div>
        </div>
    </div>

    <!-- Balance Doughnut -->
    <div class="col-md-4">
        <div class="card card-outline card-primary">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-wallet mr-1"></i>My Balances</h6></div>
            <div class="card-body">
                <canvas id="myBalanceChart" height="220"></canvas>
                <div class="mt-2 text-center" style="font-size:13px;">
                    <span class="mr-2"><span style="color:#007bff;">●</span> Shares: <strong>TZS <?= number_format($shareBalance, 0) ?></strong></span><br>
                    <span class="mr-2"><span style="color:#28a745;">●</span> Savings: <strong>TZS <?= number_format($savingBalance, 0) ?></strong></span><br>
                    <span><span style="color:#ffc107;">●</span> Loan: <strong>TZS <?= number_format($loanBalance, 0) ?></strong></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Monthly Contribution Trend -->
    <div class="col-md-8">
        <div class="card card-outline card-success">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-chart-line mr-1"></i>My Contribution Trend (Last 6 Months)</h6></div>
            <div class="card-body">
                <canvas id="myTrendChart" height="175"></canvas>
            </div>
        </div>
    </div>

    <!-- My Loans Table -->
    <div class="col-12">
        <div class="card card-outline card-info">
            <div class="card-header">
                <h6 class="card-title"><i class="fas fa-hand-holding-usd mr-1"></i>My Approved Loans (<?= $loanCount ?>)</h6>
                <div class="card-tools">
                    <a href="./?page=apply_loan" class="btn btn-sm btn-success"><i class="fas fa-plus mr-1"></i>Apply for Loan</a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-striped mb-0">
                        <thead><tr class="table-primary">
                            <th>#</th><th>Type</th><th>Principle (TZS)</th><th>Interest (TZS)</th><th>Period</th><th>Date Approved</th><th></th>
                        </tr></thead>
                        <tbody>
                        <?php if ($myLoans && is_array($myLoans) && count($myLoans)): ?>
                            <?php foreach ($myLoans as $i => $loan): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($loan['product'] ?? '—') ?></td>
                                <td><?= number_format($loan['principle'], 2) ?></td>
                                <td><?= number_format($loan['interest_amount'] ?? 0, 2) ?></td>
                                <td><?= $loan['period'] ?> mo</td>
                                <td><?= $loan['approve_date'] ?? '—' ?></td>
                                <td>
                                    <a href="./?page=view_loan_details&loan_id=<?= $loan['id'] ?>&user_id=<?= $loan['user_id'] ?>"
                                       class="btn btn-xs btn-outline-primary"><i class="fas fa-eye"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center text-muted py-3">No approved loans found.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- ===== SYSTEM OVERVIEW ===== -->
    <div class="col-12 mt-3">
        <h5 class="mb-3"><i class="fas fa-chart-bar mr-2 text-success"></i>System Overview</h5>
    </div>

    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-info">
            <div class="inner"><h3><?= number_format($totalMembers) ?></h3><p>Total Active Members</p></div>
            <div class="icon"><i class="fas fa-users"></i></div>
            <a href="./?page=all_member_list" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-warning">
            <div class="inner"><h3><?= number_format($loanCounts[0]) ?></h3><p>Pending Loan Requests</p></div>
            <div class="icon"><i class="fas fa-hourglass-half"></i></div>
            <a href="./?page=Pending_loan_list_form" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-success">
            <div class="inner"><h3><?= number_format($loanCounts[1]) ?></h3><p>Approved Loans</p></div>
            <div class="icon"><i class="fas fa-thumbs-up"></i></div>
            <a href="./?page=approved_loan_list_form" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>
    <div class="col-md-3 col-sm-6">
        <div class="small-box bg-danger">
            <div class="inner">
                <h3>TZS <?= number_format($vData['total'] ?? 0, 0) ?></h3>
                <p>Vouchers This Month (<?= $vData['cnt'] ?? 0 ?>)</p>
            </div>
            <div class="icon"><i class="fas fa-file-invoice-dollar"></i></div>
            <a href="./?page=transaction_list" class="small-box-footer">View <i class="fas fa-arrow-circle-right"></i></a>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card card-outline card-warning">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-chart-pie mr-1"></i>Loans by Status</h6></div>
            <div class="card-body"><canvas id="loanStatusChart" height="240"></canvas></div>
        </div>
    </div>
    <div class="col-md-8">
        <div class="card card-outline card-danger">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-chart-bar mr-1"></i>Monthly Loan Disbursements — Last 6 Months</h6></div>
            <div class="card-body"><canvas id="disburseChart" height="200"></canvas></div>
        </div>
    </div>
    <div class="col-12">
        <div class="card card-outline card-secondary">
            <div class="card-header"><h6 class="card-title"><i class="fas fa-code-branch mr-1"></i>Members per Branch</h6></div>
            <div class="card-body"><canvas id="branchChart" height="90"></canvas></div>
        </div>
    </div>
    <?php endif; ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
(function () {

    new Chart(document.getElementById('myBalanceChart'), {
        type: 'doughnut',
        data: {
            labels: ['Shares', 'Savings', 'Loan Balance'],
            datasets: [{ data: [<?= $shareBalance ?>, <?= $savingBalance ?>, <?= $loanBalance ?>], backgroundColor: ['#007bff','#28a745','#ffc107'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' }, tooltip: { callbacks: { label: ctx => ' TZS ' + ctx.parsed.toLocaleString() } } } }
    });

    new Chart(document.getElementById('myTrendChart'), {
        type: 'line',
        data: {
            labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [
                { label: 'Savings', data: <?= json_encode($monthlySavings) ?>, borderColor: '#28a745', backgroundColor: 'rgba(40,167,69,0.10)', tension: 0.35, fill: true, pointRadius: 4 },
                { label: 'Shares',  data: <?= json_encode($monthlyShares)  ?>, borderColor: '#007bff', backgroundColor: 'rgba(0,123,255,0.10)',  tension: 0.35, fill: true, pointRadius: 4 }
            ]
        },
        options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'TZS ' + Number(v).toLocaleString() } } } }
    });

    <?php if ($isAdmin): ?>

    new Chart(document.getElementById('loanStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Pending','Approved','Rejected','Processing'],
            datasets: [{ data: <?= json_encode($loanCounts) ?>, backgroundColor: ['#ffc107','#28a745','#dc3545','#17a2b8'], borderWidth: 2 }]
        },
        options: { responsive: true, plugins: { legend: { position: 'bottom' } } }
    });

    new Chart(document.getElementById('disburseChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($monthlyLabels) ?>,
            datasets: [{ label: 'Disbursed (TZS)', data: <?= json_encode($disburseAmounts) ?>, backgroundColor: 'rgba(220,53,69,0.75)', borderColor: '#dc3545', borderWidth: 1, borderRadius: 4 }]
        },
        options: { responsive: true, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { callback: v => 'TZS ' + Number(v).toLocaleString() } } } }
    });

    new Chart(document.getElementById('branchChart'), {
        type: 'bar',
        data: {
            labels: <?= json_encode($branchLabels ?: ['No data']) ?>,
            datasets: [{ label: 'Members', data: <?= json_encode($branchCounts ?: [0]) ?>, backgroundColor: ['#007bff','#28a745','#ffc107','#dc3545','#17a2b8','#6f42c1','#fd7e14','#20c997'], borderWidth: 1, borderRadius: 4 }]
        },
        options: { indexAxis: 'y', responsive: true, plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { stepSize: 1 } } } }
    });

    <?php endif; ?>

})();
</script>
