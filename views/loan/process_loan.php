<div class=" card card-info ">
    <div class=" card-header">
        <h4>Loan Processing</h4>
    </div>
    <div class=" card-body">
        <div class=" card-body">
            <?php
                $userDetails = null;
                try {
                    $userDetails = selectUserById($conn, (int) $_GET['user_id']);
                } catch (\Throwable $e) {
                    error_log('selectUserById failed for user_id=' . ((int) $_GET['user_id']) . ': ' . $e->getMessage());
                }
                if($userDetails && is_array($userDetails)){
                    echo "<h4>Name: {$userDetails['name']}</h4>";
                    echo "<h4>Branch: $userDetails[branch_name] </h4>";
                } else {
                    echo '<div class="alert alert-light border mb-2"><small class="text-muted">Member name/branch unavailable.</small></div>';
                }

                $loanDetails = null;
                try {
                    $loanDetails = selectLoanById($conn, (int) $_GET['loan_id']);
                } catch (\Throwable $e) {
                    error_log('selectLoanById failed for loan_id=' . ((int) $_GET['loan_id']) . ': ' . $e->getMessage());
                }
                if($loanDetails && is_array($loanDetails)){
                    echo "<h4> Loan Principle Requested: ". number_format( $loanDetails['principle'],2) ." </h4>";
                    echo "<h4> Period: $loanDetails[period] (months) </h4>";
                    echo "<h4> Repayment Mode: $loanDetails[repayment_mode] </h4>";
                    
                    $loanId = (int) $_GET['loan_id'];
                    
                    // Display details based on repayment mode
                    if($loanDetails['repayment_mode'] == 'salary'){
                        $salaryDetails = null;
                        try {
                            $salaryDetails = selectLoanSalaryDetails($conn, $loanId);
                        } catch (\Throwable $e) {
                            error_log('selectLoanSalaryDetails failed for loan_id=' . $loanId . ': ' . $e->getMessage());
                        }
                        if($salaryDetails && is_array($salaryDetails)){
                            echo "<h4> Basic Salary: ". number_format($salaryDetails['basic_salary'], 2) ." </h4>";
                            echo "<h4> Take Home: ". number_format($salaryDetails['take_home'], 2) ." </h4>";
                            
                            // Display salary slip attachment link
                            if($salaryDetails['salary_slip_file']){
                                $attachmentUrl = "./?page=review_attachments&id=" . $loanId . "&name=" . urlencode($salaryDetails['salary_slip_file']) . "&repayment_mode=salary";
                                echo "<h4> Salary Slip: <a href='$attachmentUrl'>View Attachment</a> </h4>";
                            } else {
                                echo "<h4> Salary Slip: No attachment uploaded </h4>";
                            }
                        }
                    }
                    
                    if($loanDetails['repayment_mode'] == 'standing_order'){
                        $standingOrderDetails = null;
                        try {
                            $standingOrderDetails = selectLoanStandingOrderDetails($conn, $loanId);
                        } catch (\Throwable $e) {
                            error_log('selectLoanStandingOrderDetails failed for loan_id=' . $loanId . ': ' . $e->getMessage());
                        }
                        if($standingOrderDetails && is_array($standingOrderDetails)){
                            
                            // Display standing order attachment link
                            if($standingOrderDetails['standing_order_file']){
                                $attachmentUrl = "./?page=review_attachments&id=" . $loanId . "&name=" . urlencode($standingOrderDetails['standing_order_file']) . "&repayment_mode=standing_order";
                                echo "<h4> Standing Order: <a href='$attachmentUrl'>View Attachment</a> </h4>";
                            } else {
                                echo "<h4> Standing Order: No attachment uploaded </h4>";
                            }
                        }
                    }
                } else {
                    $loanId = (int) $_GET['loan_id'];
                    echo '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle mr-1"></i> This loan application (#' . $loanId . ') could not be loaded. It may have been deleted, or its ID/links are incorrect.</div>';
                }
            ?>
        </div>

        <?php
            // ============================================================
            //  ELIGIBILITY ENGINE REPORT
            //  Pulls the member's savings, outstanding balances, overdue
            //  installments, guarantor acceptance and product rules into
            //  one verdict so the reviewer doesn't have to dig manually.
            //
            //  Defensive: this block touches several tables/columns that
            //  may not perfectly match on every environment. If anything
            //  inside throws (this DB layer runs in mysqli exception mode),
            //  we catch it here and show a small notice instead of letting
            //  it take down the entire approve/reject screen below.
            // ============================================================
            $eligibility = null;
            $eligibilityError = null;
            try {
                $eligibility = evaluateLoanEligibility($conn, (int) $_GET['loan_id']);
            } catch (\Throwable $e) {
                $eligibilityError = $e->getMessage();
                error_log('Eligibility engine failed for loan_id=' . ((int) $_GET['loan_id']) . ': ' . $eligibilityError);
            }

            if($eligibilityError !== null){
        ?>
        <div class="card-body">
            <div class="alert alert-warning mb-0">
                <i class="fas fa-exclamation-triangle mr-1"></i>
                Eligibility check could not be generated for this application, but you can still review the
                details below and approve or reject as normal.
            </div>
        </div>
        <?php
            } elseif($eligibility && empty($eligibility['error'])){
                $recoBadge = [
                    'recommended'       => ['label' => 'Recommended',        'class' => 'badge-success'],
                    'review_carefully'  => ['label' => 'Review Carefully',   'class' => 'badge-warning'],
                    'not_recommended'   => ['label' => 'Not Recommended',    'class' => 'badge-danger'],
                ][$eligibility['recommendation']] ?? ['label' => 'Unknown', 'class' => 'badge-secondary'];
                $checkIcon = ['pass' => 'fa-check-circle text-success', 'warning' => 'fa-exclamation-triangle text-warning', 'fail' => 'fa-times-circle text-danger'];
        ?>
        <div class="card-body">
            <div class="card card-outline <?= $eligibility['recommendation'] === 'not_recommended' ? 'card-danger' : ($eligibility['recommendation'] === 'review_carefully' ? 'card-warning' : 'card-success') ?>">
                <div class="card-header">
                    <h5 class="card-title"><i class="fas fa-shield-alt mr-1"></i> Eligibility Check</h5>
                    <div class="card-tools"><span class="badge <?= $recoBadge['class'] ?>"><?= $recoBadge['label'] ?></span></div>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-2">
                        <?php foreach($eligibility['checks'] as $c): ?>
                            <li class="mb-1">
                                <i class="fas <?= $checkIcon[$c['status']] ?? 'fa-info-circle' ?> mr-1"></i>
                                <strong><?= htmlspecialchars($c['label']) ?>:</strong> <?= htmlspecialchars($c['detail']) ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                    <small class="text-muted">Max eligible by savings: TZS <?= number_format($eligibility['max_by_savings'],2) ?> &nbsp;|&nbsp; Total savings: TZS <?= number_format($eligibility['savings']['total'],2) ?></small>
                </div>
            </div>

            <div class="card card-outline card-secondary">
                <div class="card-header"><h5 class="card-title"><i class="fas fa-history mr-1"></i> Member's Loan History</h5></div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <tr class="table-secondary"><th>#</th><th>Product</th><th>Amount</th><th>Period</th><th>Status</th></tr>
                            <?php
                                $history = [];
                                try {
                                    $history = getMemberLoanHistory($conn, (int) $_GET['user_id'], (int) $_GET['loan_id']);
                                } catch (\Throwable $e) {
                                    error_log('getMemberLoanHistory failed for loan_id=' . ((int) $_GET['loan_id']) . ': ' . $e->getMessage());
                                    $history = [];
                                }
                                if($history && is_array($history) && count($history) > 0){
                                    $hi = 1;
                                    foreach($history as $h){
                                        echo "<tr>";
                                        echo "<td>" . $hi++ . "</td>";
                                        echo "<td>" . htmlspecialchars($h['product_name'] ?? '\xe2\x80\x94') . "</td>";
                                        echo "<td>" . number_format((float)$h['principle'],2) . "</td>";
                                        echo "<td>" . (int)$h['period'] . " mo</td>";
                                        $badge = ['pending'=>'badge-warning','approved'=>'badge-success','rejected'=>'badge-danger'][$h['status']] ?? 'badge-secondary';
                                        echo "<td><span class='badge $badge'>" . htmlspecialchars($h['status']) . "</span></td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>No prior loan applications.</td></tr>";
                                }
                            ?>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <?php } ?>

    <div class=" card-body">
        <div class=" row">
            <div class=" col-md-6 col-sm-10">
                <div class=" card-body">
                    <!-- member details -->

                    <h5>Member`s Details</h5>
                    <?php
                            $memberDetails = null;
                            try {
                                $memberDetails = selectMemberByUserId($conn, (int) $_GET['user_id']);
                            } catch (\Throwable $e) {
                                error_log('selectMemberByUserId failed for user_id=' . ((int) $_GET['user_id']) . ': ' . $e->getMessage());
                            }
                            if($memberDetails && is_array($memberDetails)){
                                // print_r($memberDetails);
                                ?>
                                    <h4>Names: <?= htmlspecialchars($userDetails['name'] ?? ($memberDetails['name'] ?? '—')) ?></h4>
                                    <h4> Branch: <?= htmlspecialchars($memberDetails['branch'] ?? '—') ?></h4>
                                    <h4>Gender: <?= htmlspecialchars($memberDetails['gender'] ?? '—') ?> </h4>
                                    <h4>Phone: <?= htmlspecialchars($memberDetails['phone'] ?? '—') ?> </h4>
                                    <h4> Email: <?= htmlspecialchars($memberDetails['email'] ?? '—') ?> </h4>
                                    <h4> Nida: <?= htmlspecialchars($memberDetails['nida'] ?? '—') ?> </h4>
                                    <h4> check No: <?= htmlspecialchars($memberDetails['check_no'] ?? '—') ?> </h4>
                                    <h4> reg No: <?= htmlspecialchars($memberDetails['reg_no'] ?? '—') ?> </h4>
                                <?php
                            } else {
                                echo '<div class="alert alert-light border mb-2"><small class="text-muted">Member profile details unavailable.</small></div>';
                            }
                    ?>
                    <div class=" card-footer"> <h5>Contributions</h5> </div>
                    <div class=" card-body">
                        <div class=" table-responsive">
                            <table class=" table tabe-sm table-striped  table-sm table-bordered">
                                    <tr class=" table-primary">
                                        <td>#</td>
                                        <td>Account Name</td>
                                        <td>Balance</td>
                                    </tr>
                                    <?php
                                        $accounts = [];
                                        try {
                                            $accounts = selectMinSubsByUserId($conn, (int) $_GET['user_id']);
                                        } catch (\Throwable $e) {
                                            error_log('selectMinSubsByUserId failed for user_id=' . ((int) $_GET['user_id']) . ': ' . $e->getMessage());
                                            $accounts = [];
                                        }
                                        if($accounts && is_array($accounts)){
                                            $counter =1;
                                            foreach($accounts as $account){
                                                $accountBalance = 0;
                                                echo "<tr>";
                                                    echo "<td>$counter</td>";
                                                    echo "<td>$account[name]</td>";
                                                   try {
                                                       $minTransactions = getMinTransactionByMinSubId($conn, $account['id']);
                                                   } catch (\Throwable $e) {
                                                       error_log('getMinTransactionByMinSubId failed for min_sub_id=' . $account['id'] . ': ' . $e->getMessage());
                                                       $minTransactions = [];
                                                   }
                                                   if($minTransactions && is_array($minTransactions)){
                                                       foreach($minTransactions as $minTransaction){
                                                           if($minTransaction['dr_account'] == $account['id']){
                                                               $accountBalance += $minTransaction['amount'];
                                                           }elseif($minTransaction['cr_account'] == $account['id']){
                                                               $accountBalance -= $minTransaction['amount'];
                                                           }
                                                       }
                                                   }
                                                    echo "<td>". number_format( $accountBalance,2) ."</td>";
                                                echo "</tr>";
                                                $counter++;
                                            }
                                        }
                                    ?>
                          </table>
                        </div>
                       
                        <div class=" card card-primary card-outline">
                             <div class=" card-header">
                            <h5>Grantors details</h5>
                        </div>
                            <div class=" card-body" >
                                <ul type='1'>
                                    <?php
                                        $grantors = [];
                                        try {
                                            $grantors = selectLoanGrantorByLoanId($conn, (int) $_GET['loan_id']);
                                        } catch (\Throwable $e) {
                                            error_log('selectLoanGrantorByLoanId failed for loan_id=' . ((int) $_GET['loan_id']) . ': ' . $e->getMessage());
                                            $grantors = [];
                                        }
                                        if($grantors && is_array($grantors)){
                                            foreach($grantors as $grantor){
                                                echo "<li>$grantor[name]</li>";
                                                // details
                                                echo "<table class=' table table-sm table-bordered'>";
                                                    echo "<tr> <td>#</td> <td>Account</td> <td>Amount</td> </tr>";

                                                    try {
                                                        $accounts1 = selectMinSubsByUserId($conn, (int) $grantor['grantor_id']);
                                                    } catch (\Throwable $e) {
                                                        error_log('selectMinSubsByUserId (grantor) failed for grantor_id=' . $grantor['grantor_id'] . ': ' . $e->getMessage());
                                                        $accounts1 = [];
                                                    }
                                                    if($accounts1 && is_array($accounts1)){
                                                        $counter =1;
                                                        foreach($accounts1 as $account1){
                                                            $accountBalance = 0;
                                                            echo "<tr>";
                                                                echo "<td>$counter</td>";
                                                                echo "<td>$account1[name]</td>";
                                                            try {
                                                                $minTransactions = getMinTransactionByMinSubId($conn, $account1['id']);
                                                            } catch (\Throwable $e) {
                                                                error_log('getMinTransactionByMinSubId (grantor) failed for min_sub_id=' . $account1['id'] . ': ' . $e->getMessage());
                                                                $minTransactions = [];
                                                            }
                                                            if($minTransactions && is_array($minTransactions)){
                                                                foreach($minTransactions as $minTransaction){
                                                                    if($minTransaction['dr_account'] == $account1['id']){
                                                                        $accountBalance += $minTransaction['amount'];
                                                                    }elseif($minTransaction['cr_account'] == $account1['id']){
                                                                        $accountBalance -= $minTransaction['amount'];
                                                                    }
                                                                }
                                                            }
                                                                echo "<td>". number_format( $accountBalance,2) ."</td>";
                                                            echo "</tr>";
                                                            $counter++;
                                                        }
                                                    }
                                                echo "</table>";
                                            }
                                        }
                                    ?>
                                </ul>

                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class=" col-md-5 col-sm-10">
                <?php if($loanDetails && is_array($loanDetails)): ?>
                <div class=" card card-primary card-outline">
                    <div class=" card-header"> <h5>Processing form</h5> </div>
               
                <form action="./controllers/loan_controller.php" method="post" class=" was-validated">
                    <input type="hidden" name="loan_id" value="<?= $_GET['loan_id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
                    <input type="hidden" name="branch_id" value="<?= $_GET['branch_id'] ?>">
                    <div class=" card-body">
                        <div class=" form-group">
                            <label for="">Amount</label>
                            <input type="number" name="principle" value="<?= $loanDetails['principle'] ?>" class=" form-control" required id="">
                        </div>
                        <div class=" form-group">
                            <label for="">Interest rate</label>
                            <input type="number" step="any" oninput="calculateRepayment()" name="interest_rate" class=" form-control" required id="">
                        </div>

                        <div class=" form-group">
                            <label for="">Loan Term (in month) </label>
                            <input type="number" name="loan_term" value="<?= $loanDetails['period'] ?>"  class=" form-control" required id="">
                        </div>
                        <div class=" form-group">
                            <label for="">Interest Amount</label>
                            <input type="number" name="interest_amount" step="any" class=" form-control" id="">
                        </div>
                        <div class=" form-group">
                            <label for="">Repayment Amount</label>
                            <input type="number" name="repayment_amount" step="any" readonly class=" form-control" id="">
                        </div>
                        <div class=" form-group">
                            <label for="">Total Loan (principle + Interest)</label>
                            <input type="number" name="total_loan" step="any" readonly class=" form-control" id="">
                        </div>
                        <div class=" form-group">
                            <label for="">Approve Date</label>
                            <input type="date" name="approve_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"  required class=" form-control" id="">
                        </div>
                        <div class=" form-group">
                            <label for="">Approved BY</label>
                            <input type="text" name="approved_by" value="<?= $_SESSION['username'] ?>" class=" form-control" id="">
                        </div>
                </div>
                <div class=" card-footer">
                    <button type="submit" class=" btn btn-sm btn-block btn-primary" name="approve_loan">Aprrove Loan</button>
                </div>
                </form>
                 </div>

                <div class=" card card-outline card-danger mt-3">
                    <div class=" card-header"><h5 class=" card-title"><i class="fas fa-ban mr-1"></i> Reject This Application</h5></div>
                    <form action="./controllers/loan_controller.php" method="post" class=" was-validated" onsubmit="return confirm('Reject this loan application? The member will be notified with the reason given.');">
                        <input type="hidden" name="loan_id" value="<?= $_GET['loan_id'] ?>">
                        <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
                        <div class=" card-body">
                            <div class=" form-group">
                                <label for="">Reason for rejection</label>
                                <textarea name="rejection_reason" class=" form-control" rows="3" required placeholder="e.g. Insufficient savings relative to requested amount, outstanding arrears on a prior loan, etc."></textarea>
                            </div>
                        </div>
                        <div class=" card-footer">
                            <button type="submit" class=" btn btn-sm btn-block btn-danger" name="reject_loan">Reject Loan</button>
                        </div>
                    </form>
                </div>
                <?php else: ?>
                <div class=" card card-outline card-danger">
                    <div class=" card-body">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        The loan record itself could not be loaded, so it can't be approved or rejected from here.
                        Please verify the loan ID in the link, or check with an administrator.
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    </div>
</div>