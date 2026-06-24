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
                
                <!-- Original Application Data -->
                <div class=" card card-info card-outline mb-3">
                    <div class=" card-header"> <h5>Original Application Details</h5> </div>
                    <div class=" card-body">
                        <div class=" row">
                            <div class=" col-md-6">
                                <h6><strong>Loan Product:</strong> <?= htmlspecialchars($loanDetails['loan_type_name'] ?? '—') ?></h6>
                                <h6><strong>Requested Amount:</strong> TZS <?= number_format($loanDetails['principle'], 2) ?></h6>
                                <h6><strong>Period:</strong> <?= (int)$loanDetails['period'] ?> months</h6>
                                <h6><strong>Repayment Mode:</strong> <?= htmlspecialchars($loanDetails['repayment_mode'] ?? '—') ?></h6>
                            </div>
                            <div class=" col-md-6">
                                <h6><strong>Application Date:</strong> <?= !empty($loanDetails['created_at']) ? date('d-M-Y', strtotime($loanDetails['created_at'])) : '—' ?></h6>
                                <h6><strong>Status:</strong> <span class="badge badge-<?= $loanDetails['status'] === 'approved' ? 'success' : ($loanDetails['status'] === 'pending' ? 'warning' : 'danger') ?>"><?= htmlspecialchars($loanDetails['status']) ?></span></h6>
                                <?php if(!empty($loanDetails['approve_date']) && $loanDetails['status'] === 'approved'): ?>
                                    <h6><strong>Previously Approved:</strong> <?= date('d-M-Y', strtotime($loanDetails['approve_date'])) ?></h6>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Documents Section -->
                        <hr class="my-3">
                        <div class=" row">
                            <div class=" col-md-12">
                                <h6><strong><i class="fas fa-file-pdf mr-1"></i> Supporting Documents</strong></h6>
                            </div>
                        </div>
                        
                        <?php
                        $loanId = (int) $_GET['loan_id'];
                        $hasDocuments = false;
                        
                        // Display Salary Slip if salary mode
                        if($loanDetails['repayment_mode'] == 'salary'):
                            $salaryDetails = selectLoanSalaryDetails($conn, $loanId);
                            if($salaryDetails && is_array($salaryDetails)):
                                $hasDocuments = true;
                                echo "<div class=' col-md-6 mb-2'>";
                                echo "<h6><strong>Basic Salary:</strong> TZS " . number_format($salaryDetails['basic_salary'], 2) . "</h6>";
                                echo "<h6><strong>Take Home:</strong> TZS " . number_format($salaryDetails['take_home'], 2) . "</h6>";
                                if(!empty($salaryDetails['salary_slip_file'])):
                                    $attachmentUrl = "./?page=review_attachments&id=" . $loanId . "&name=" . urlencode($salaryDetails['salary_slip_file']) . "&repayment_mode=salary";
                                    echo "<h6><a href='$attachmentUrl' class='btn btn-sm btn-outline-primary' target='_blank'><i class='fas fa-download mr-1'></i>Download Salary Slip</a></h6>";
                                else:
                                    echo "<h6><span class='badge badge-warning'><i class='fas fa-exclamation-triangle mr-1'></i>No salary slip uploaded</span></h6>";
                                endif;
                                echo "</div>";
                            endif;
                        endif;
                        
                        // Display Standing Order if standing order mode
                        if($loanDetails['repayment_mode'] == 'standing_order'):
                            $standingOrderDetails = selectLoanStandingOrderDetails($conn, $loanId);
                            if($standingOrderDetails && is_array($standingOrderDetails)):
                                $hasDocuments = true;
                                echo "<div class=' col-md-6 mb-2'>";
                                if(!empty($standingOrderDetails['standing_order_file'])):
                                    $attachmentUrl = "./?page=review_attachments&id=" . $loanId . "&name=" . urlencode($standingOrderDetails['standing_order_file']) . "&repayment_mode=standing_order";
                                    echo "<h6><a href='$attachmentUrl' class='btn btn-sm btn-outline-primary' target='_blank'><i class='fas fa-download mr-1'></i>Download Standing Order</a></h6>";
                                else:
                                    echo "<h6><span class='badge badge-warning'><i class='fas fa-exclamation-triangle mr-1'></i>No standing order document uploaded</span></h6>";
                                endif;
                                echo "</div>";
                            endif;
                        endif;
                        
                        if(!$hasDocuments):
                            echo "<div class='col-md-12 mb-2'><span class='badge badge-secondary'><i class='fas fa-info-circle mr-1'></i>No documents available for this loan</span></div>";
                        endif;
                        ?>
                    </div>
                </div>

                <!-- Processing form -->
                <div class=" card card-primary card-outline">
                    <div class=" card-header"> <h5>Processing form</h5> </div>
               
                <form action="./controllers/loan_controller.php" method="post" class=" was-validated">
                    <input type="hidden" name="loan_id" value="<?= $_GET['loan_id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
                    <input type="hidden" name="branch_id" value="<?= $_GET['branch_id'] ?>">
                    <div class=" card-body">
                        <div class=" alert alert-info">
                            <i class="fas fa-calculator mr-1"></i>
                            <strong>Auto Calculation:</strong> Values will recalculate automatically as you modify amount, interest rate, or term.
                        </div>
                        <div class=" form-group">
                            <label for="principle_input">Amount (TZS)</label>
                            <input type="number" id="principle_input" name="principle" value="<?= $loanDetails['principle'] ?>" class=" form-control" required oninput="calculateRepayment()">
                        </div>
                        <div class=" form-group">
                            <label for="interest_rate_input">Interest Rate (%)</label>
                            <input type="number" step="any" id="interest_rate_input" name="interest_rate" class=" form-control" required oninput="calculateRepayment()" placeholder="e.g., 5.5">
                        </div>

                        <div class=" form-group">
                            <label for="loan_term_input">Loan Term (in months) </label>
                            <input type="number" id="loan_term_input" name="loan_term" value="<?= $loanDetails['period'] ?>"  class=" form-control" required oninput="calculateRepayment()">
                        </div>
                        <div class=" form-group">
                            <label for="interest_amount_input">Interest Amount (TZS)</label>
                            <input type="number" id="interest_amount_input" name="interest_amount" step="any" class=" form-control" oninput="calculateRepayment()">
                        </div>
                        <div class=" form-group">
                            <label for="repayment_amount_output">Monthly Repayment Amount (TZS)</label>
                            <input type="number" id="repayment_amount_output" name="repayment_amount" step="any" readonly class=" form-control bg-light" style="font-weight: bold; color: #28a745;">
                        </div>
                        <div class=" form-group">
                            <label for="total_loan_output">Total Loan (Principal + Interest)</label>
                            <input type="number" id="total_loan_output" name="total_loan" step="any" readonly class=" form-control bg-light" style="font-weight: bold; color: #007bff;">
                        </div>
                        <div class=" form-group">
                            <label for="approve_date_input">Approve Date</label>
                            <input type="date" id="approve_date_input" name="approve_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>"  required class=" form-control">
                        </div>
                        <div class=" form-group">
                            <label for="approved_by_input">Approved BY</label>
                            <input type="text" id="approved_by_input" name="approved_by" value="<?= $_SESSION['username'] ?>" class=" form-control">
                        </div>
                </div>
                <div class=" card-footer">
                    <button type="submit" class=" btn btn-sm btn-block btn-primary" name="approve_loan">Approve Loan</button>
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

<!-- Real-time Loan Calculation JavaScript -->
<script>
/**
 * Calculate loan repayment amounts in real-time
 * Recalculates whenever any of these change:
 * - Principal amount
 * - Interest rate
 * - Loan term (months)
 * - Interest amount
 */
function calculateRepayment() {
    // Get input values
    const principle = parseFloat(document.getElementById('principle_input').value) || 0;
    const interestRate = parseFloat(document.getElementById('interest_rate_input').value) || 0;
    const loanTerm = parseFloat(document.getElementById('loan_term_input').value) || 1;
    const interestAmount = parseFloat(document.getElementById('interest_amount_input').value) || 0;
    
    // Calculate total interest if not provided
    let finalInterestAmount = interestAmount;
    if (interestAmount === 0 && interestRate > 0) {
        // Simple interest calculation: Principal × Rate × Time / 100
        finalInterestAmount = (principle * interestRate * loanTerm) / 100;
    }
    
    // Update interest amount display
    document.getElementById('interest_amount_input').value = finalInterestAmount.toFixed(2);
    
    // Calculate total loan (principal + interest)
    const totalLoan = principle + finalInterestAmount;
    document.getElementById('total_loan_output').value = totalLoan.toFixed(2);
    
    // Calculate monthly repayment (total loan / number of months)
    const monthlyRepayment = loanTerm > 0 ? totalLoan / loanTerm : 0;
    document.getElementById('repayment_amount_output').value = monthlyRepayment.toFixed(2);
}

// Initialize calculations on page load
document.addEventListener('DOMContentLoaded', function() {
    calculateRepayment();
});
</script>