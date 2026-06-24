<?php
// Require loan_id in URL
if(empty($_GET['loan_id'])){
    echo "<div class='alert alert-danger'>No loan specified.</div>";
    return;
}
$loanId = (int) $_GET['loan_id'];
$loan   = selectLoanById($conn, $loanId);
if(!$loan){
    echo "<div class='alert alert-danger'>Loan not found.</div>";
    return;
}
// Security: member can only view their own loans
if((int)$loan['user_id'] !== (int)$_SESSION['userid'] && !in_array($_SESSION['role'], ['admin','superadmin','accountant','manager','loan comitee','chairman'])){
    echo "<div class='alert alert-danger'>Access denied.</div>";
    return;
}
$schedule = selectLoanScheduleByLoanId($conn, $loanId);
$grantors = selectLoanGrantorByLoanId($conn, $loanId);
$grantor_notifs = selectGrantorNotificationsByLoanId($conn, $loanId);

$statusBadge = ['pending'=>'warning','approved'=>'success','rejected'=>'danger','hq_pending'=>'info','loan_comettee_processed'=>'info','hq_loan_officer_rejected'=>'danger'];
$cls = $statusBadge[$loan['status']] ?? 'secondary';
?>
<div class="card card-info">
    <div class="card-header"><h4 class="card-title">Loan Details &mdash; #<?= $loanId ?></h4></div>
    <div class="card-body">

        <!-- Status + Summary -->
        <div class="row mb-3">
            <div class="col-md-6">
                <table class="table table-sm table-bordered">
                    <tr><th>Status</th><td><span class="badge badge-<?= $cls ?>"><?= ucwords(str_replace('_',' ',$loan['status'])) ?></span></td></tr>
                    <tr><th>Loan Type</th><td><?= htmlspecialchars($loan['loan_type_name'] ?? 'N/A') ?></td></tr>
                    <tr><th>Principal Amount</th><td>TZS <?= number_format((float)$loan['principle'],2) ?></td></tr>
                    <tr><th>Interest Rate</th><td><?= $loan['interest_rate'] ?>%</td></tr>
                    <tr><th>Interest Amount</th><td>TZS <?= number_format((float)$loan['interest_amount'],2) ?></td></tr>
                    <tr><th>Total Payable</th><td>TZS <?= number_format((float)$loan['principle']+(float)$loan['interest_amount'],2) ?></td></tr>
                    <tr><th>Period</th><td><?= (int)$loan['period'] ?> months</td></tr>
                    <tr><th>Repayment Mode</th><td><?= htmlspecialchars(ucwords(str_replace('_',' ',$loan['repayment_mode'] ?? ''))) ?></td></tr>
                    <tr><th>Applied On</th><td><?= $loan['created_at'] ?? 'N/A' ?></td></tr>
                    <tr><th>Approved Date</th><td><?= $loan['approve_date'] ?? 'Pending' ?></td></tr>
                </table>
            </div>
            <div class="col-md-6">
                <!-- Guarantors and their response status -->
                <h5>Guarantors</h5>
                <?php if($grantors && is_array($grantors)): ?>
                <table class="table table-sm table-bordered">
                    <thead><tr><th>Name</th><th>Response</th><th>Date</th></tr></thead>
                    <tbody>
                    <?php foreach($grantors as $g):
                        // find matching notification for this grantor
                        $notif = null;
                        foreach(($grantor_notifs ?? []) as $n){ if((int)$n['grantor_id']===(int)$g['grantor_id']){ $notif=$n; break; } }
                        $gStatus = $notif['status'] ?? 'pending';
                        $gBadge = ['accepted'=>'success','rejected'=>'danger','pending'=>'warning','expired'=>'secondary'][$gStatus] ?? 'secondary';
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($g['name']) ?></td>
                        <td><span class="badge badge-<?= $gBadge ?>"><?= ucfirst($gStatus) ?></span></td>
                        <td><?= $notif['responded_at'] ? date('d/m/Y', strtotime($notif['responded_at'])) : '&mdash;' ?></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <p class="text-muted">No guarantors recorded.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Repayment Schedule -->
        <?php if($schedule && is_array($schedule) && count($schedule) > 0): ?>
        <h5>Repayment Schedule</h5>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-striped">
                <thead>
                    <tr><th>#</th><th>Due Date</th><th>Principal</th><th>Interest</th><th>Total</th><th>Paid</th><th>Status</th></tr>
                </thead>
                <tbody>
                <?php $i=1; foreach($schedule as $row): ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= date('d/m/Y', strtotime($row['payment_date'])) ?></td>
                    <td>TZS <?= number_format((float)$row['principle'],2) ?></td>
                    <td>TZS <?= number_format((float)$row['interest_amount'],2) ?></td>
                    <td>TZS <?= number_format((float)$row['principle']+(float)$row['interest_amount'],2) ?></td>
                    <td>TZS <?= number_format((float)$row['paid_amount'],2) ?></td>
                    <td><span class="badge badge-<?= $row['status']==='paid'?'success':'warning' ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="alert alert-info">Repayment schedule will be generated once the loan is approved.</div>
        <?php endif; ?>

    </div>
    <div class="card-footer">
        <a href="./?page=my_loan" class="btn btn-secondary btn-sm"><i class="fas fa-arrow-left"></i> Back to My Loans</a>
    </div>
</div>