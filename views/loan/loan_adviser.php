<div class="card card-info">
    <div class="card-header"><h4 class="card-title">Loan Advisor - Get Best Loan Suggestions</h4></div>
    <div class="card-body">
        <form method="post" class="was-validated">
            <?php
                // Auto-resolve the logged-in member — no manual selection allowed
                $loggedInUserId   = (int) ($_SESSION['userid'] ?? 0);
                $loggedInUserName = htmlspecialchars($_SESSION['name'] ?? 'You');
            ?>
            <!-- Hidden: always use the logged-in user's own ID -->
            <input type="hidden" name="user_id" value="<?= $loggedInUserId ?>">

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Member</label>
                        <input type="text" class="form-control bg-light" value="<?= $loggedInUserName ?>" disabled>
                        <small class="text-muted"><i class="fas fa-user-check text-success"></i> Showing your own loan eligibility.</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Loan Type</label>
                        <select name="loan_type" class="form-control" required>
                            <option value="">Select Type</option>
                            <?php
                            $types = selectLoanTypes($conn);
                            if($types && is_array($types)):
                                foreach($types as $t):
                            ?>
                                <option value="<?= $t['id'] ?>" <?= isset($_POST['loan_type']) && $_POST['loan_type'] == $t['id'] ? 'selected' : '' ?>><?= $t['name'] ?></option>
                            <?php endforeach; endif; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Desired Amount (TZS)</label>
                        <input type="number" name="amount" class="form-control" step="0.01" min="1" value="<?= $_POST['amount'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label>Period (Months)</label>
                        <input type="number" name="period" class="form-control" min="1" max="60" value="<?= $_POST['period'] ?? 12 ?>" required>
                    </div>
                </div>
            </div>
            <button type="submit" name="get_advice" class="btn btn-info"><i class="fas fa-calculator"></i> Get Advice</button>
        </form>

        <?php if(isset($_POST['get_advice'])):
            // Always use the session user — ignore any tampered POST value
            $userId = (int) ($_SESSION['userid'] ?? 0);
            $amount = (float) $_POST['amount'];
            $loanTypeId = (int) $_POST['loan_type'];
            $period = (int) $_POST['period'];
            $advice = getLoanAdvisorSuggestion($conn, $userId, $amount, $loanTypeId, $period);
        ?>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <div class="card card-<?= $advice['is_affordable'] ? 'success' : 'danger' ?>">
                    <div class="card-header"><h5>Loan Analysis</h5></div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><td>Loan Type</td><td><strong><?= $advice['loan_type_name'] ?></strong></td></tr>
                            <tr><td>Requested Amount</td><td class="text-right"><strong>TZS <?= number_format($advice['requested_amount'], 2) ?></strong></td></tr>
                            <tr><td>Period</td><td><?= $advice['period_months'] ?> months</td></tr>
                            <tr><td>Interest Rate</td><td><?= $advice['interest_rate'] ?>% per annum</td></tr>
                            <tr><td>Monthly Payment</td><td class="text-right">TZS <?= number_format($advice['monthly_payment'], 2) ?></td></tr>
                            <tr><td>Total Interest</td><td class="text-right">TZS <?= number_format($advice['total_interest'], 2) ?></td></tr>
                            <tr><td>Total Repayment</td><td class="text-right">TZS <?= number_format($advice['total_repayment'], 2) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card card-info">
                    <div class="card-header"><h5>Member Savings & Status</h5></div>
                    <div class="card-body">
                        <table class="table table-bordered">
                            <tr><td>Savings Balance</td><td class="text-right">TZS <?= number_format($advice['savings_balance'], 2) ?></td></tr>
                            <tr><td>Amana Balance</td><td class="text-right">TZS <?= number_format($advice['amana_balance'], 2) ?></td></tr>
                            <tr><td>Share Balance</td><td class="text-right">TZS <?= number_format($advice['share_balance'], 2) ?></td></tr>
                            <tr class="font-weight-bold"><td>Total Savings</td><td class="text-right">TZS <?= number_format($advice['total_savings'], 2) ?></td></tr>
                            <tr><td>Existing Loan Balance</td><td class="text-right">TZS <?= number_format($advice['existing_loan_balance'], 2) ?></td></tr>
                            <tr class="font-weight-bold"><td>Max Loan Based on Savings (3x)</td><td class="text-right text-info">TZS <?= number_format($advice['max_loan_based_on_savings'], 2) ?></td></tr>
                        </table>
                        <div class="alert alert-<?= $advice['is_affordable'] ? 'success' : 'danger' ?> mt-3">
                            <i class="fas fa-<?= $advice['is_affordable'] ? 'check-circle' : 'exclamation-circle' ?>"></i>
                            <?= $advice['message'] ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
