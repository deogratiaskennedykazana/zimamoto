<?php
/**
 * Loan Collection (Marejesho ya Mikopo) — AJAX schedule loader.
 *
 * Adapted from Kuringe's individual_loan_collection feature, rewritten
 * against zimamoto's own schema:
 *   loans            (Kuringe: approved_loan)
 *   loan_schedules   (Kuringe: loan_schedule)
 *   min_subs         (Kuringe: subsidiaries)
 *   min_transactions (Kuringe: transactions)
 */
session_start();
if (!isset($_SESSION['userid'])) {
    echo "<script>window.location.href='../login.php';</script>";
    exit();
}

require_once "../functions/loan_functions.php";
require_once "../functions/user_function.php";
require_once "../functions/min_sub_functions.php";
require_once "../configs.php";
$conn = openConn();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    if (isset($_GET['get_loan_repayment_schedule'])) {
        $user_id = intval($_GET['user_id']);
        if (!$user_id) {
            echo "<div class='alert alert-danger'>Invalid member selected.</div>";
            exit;
        }
        $user = selectUserById($conn, $user_id);
        if (!$user) {
            echo "<div class='alert alert-danger'>Member not found.</div>";
            exit;
        }
        $loans = selectLoansByStatusAndUserId($conn, 'approved', $user_id);
        if (!$loans || !is_array($loans) || count($loans) == 0) {
            echo "<div class='alert alert-warning'>This member has no approved loans.</div>";
            exit;
        }
        $loanSub = selectMinSubByUserIDAndCategory($conn, $user_id, 'loan');
        ?>
        <div class="card-footer">
            <div class="row">
                <div class="col-md-4">
                    <h5 class="mb-1"><i class="fas fa-user mr-1"></i> <?= htmlspecialchars($user['name']) ?></h5>
                    <small class="text-muted">Phone: <?= htmlspecialchars($user['phone'] ?? '—') ?> &nbsp;|&nbsp; Email: <?= htmlspecialchars($user['email'] ?? '—') ?></small>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Branch: <?= htmlspecialchars($user['branch_name'] ?? '—') ?></small>
                </div>
                <div class="col-md-4">
                    <small class="text-muted">Loan Account: <?= $loanSub && is_array($loanSub) ? htmlspecialchars($loanSub['name']) : 'Not found' ?></small>
                </div>
            </div>
        </div>
        <?php
        $loan_counter = 1;
        foreach ($loans as $loan) {
            $loan_id = (int) $loan['id'];
            $scheduleRows = selectLoanScheduleByLoanId($conn, $loan_id);
            if (!is_array($scheduleRows)) {
                $scheduleRows = [];
            }
            // selectLoanScheduleByLoanId() has no ORDER BY, so sort here for a
            // predictable, oldest-first display (also matches the FIFO order
            // used when the collection is submitted to the controller).
            usort($scheduleRows, function ($a, $b) {
                return strtotime($a['payment_date'] ?? 'now') <=> strtotime($b['payment_date'] ?? 'now');
            });
            $total_paid_for_loan = 0;
            foreach ($scheduleRows as $sr) {
                $total_paid_for_loan += (float) $sr['paid_amount'];
            }
            $loan_total = (float) $loan['principle'] + (float) $loan['interest_amount'];
            $loan_remaining = $loan_total - $total_paid_for_loan;
        ?>
            <div class="card-footer mt-4">
                <div class="row">
                    <div class="col-md-12">
                        <h4 class="mb-3 text-primary">Loan <?= $loan_counter ?> — #<?= $loan_id ?>
                            <?php if($loan_remaining <= 0.01): ?>
                                <span class="badge bg-success">Fully Paid</span>
                            <?php else: ?>
                                <span class="badge bg-warning">Active</span>
                            <?php endif; ?>
                        </h4>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3">
                        <h5>Disbursed: <?= !empty($loan['approve_date']) ? date("d-M-Y", strtotime($loan['approve_date'])) : '—' ?></h5>
                        <h5>Period: <?= (int) $loan['period'] ?> month(s)</h5>
                    </div>
                    <div class="col-md-3">
                        <h5>Principle: <?= number_format((float) $loan['principle'], 2) ?></h5>
                        <h5>Interest: <?= number_format((float) $loan['interest_amount'], 2) ?></h5>
                    </div>
                    <div class="col-md-3">
                        <h5>Interest Rate: <?= number_format((float) $loan['interest_rate'], 2) ?>%</h5>
                        <h5>Repayment Mode: <?= htmlspecialchars($loan['repayment_mode'] ?? '—') ?></h5>
                    </div>
                    <div class="col-md-3">
                        <h5>Total Loan: <?= number_format($loan_total, 2) ?></h5>
                        <h5>Paid: <?= number_format($total_paid_for_loan, 2) ?></h5>
                        <h5 class="<?= $loan_remaining > 0 ? 'text-danger' : 'text-success' ?>">Remaining: <?= number_format($loan_remaining, 2) ?></h5>
                    </div>
                </div>
            </div>

            <div class="mb-3 mt-3" id="payBtn_<?= $loan_id ?>" style="display:none;">
                <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#bulkPay_<?= $loan_id ?>">
                    <i class="fas fa-money-bill-wave mr-1"></i> Collect Selected (<span id="count_<?= $loan_id ?>">0</span>)
                </button>
            </div>

            <h5 class="card-text table-primary p-2 mt-2">Repayment Schedule</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-sm">
                    <thead class="table-info">
                        <tr>
                            <th>#</th>
                            <th>Due Date</th>
                            <th>Principle</th>
                            <th>Interest</th>
                            <th>Repayment</th>
                            <th>Paid</th>
                            <th>Remaining</th>
                            <th>
                                <input type="checkbox" class="selectAll" data-loan="<?= $loan_id ?>" style="cursor:pointer;"> Select / Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    $counter = 1;
                    $total_principle = 0;
                    $total_interest = 0;
                    $total_repayment = 0;
                    $total_paid = 0;
                    foreach ($scheduleRows as $row) {
                        $principle = (float) $row['principle'];
                        $interest = (float) $row['interest_amount'];
                        $repayment = $principle + $interest;
                        $paid_amount = (float) $row['paid_amount'];
                        $remaining = round($repayment - $paid_amount, 2);
                        $total_principle += $principle;
                        $total_interest += $interest;
                        $total_repayment += $repayment;
                        $total_paid += $paid_amount;
                        $isUnpaid = ($remaining > 0.01);
                        $isOverdue = ($isUnpaid && !empty($row['payment_date']) && strtotime($row['payment_date']) < strtotime(date('Y-m-d')));
                    ?>
                        <tr class="schRow" data-row="<?= $row['id'] ?>" data-loan="<?= $loan_id ?>" style="<?= $isUnpaid ? 'cursor:pointer;' : '' ?> <?= $isOverdue ? 'background-color:#fde8e8;' : '' ?>">
                            <td><?= $counter ?></td>
                            <td><?= !empty($row['payment_date']) ? date("d-M-Y", strtotime($row['payment_date'])) : '—' ?> <?= $isOverdue ? "<span class='badge bg-danger ml-1'>Overdue</span>" : '' ?></td>
                            <td><?= number_format($principle, 2) ?></td>
                            <td><?= number_format($interest, 2) ?></td>
                            <td><?= number_format($repayment, 2) ?></td>
                            <td><?= number_format($paid_amount, 2) ?></td>
                            <td><?= number_format($remaining, 2) ?></td>
                            <td>
                                <?php if ($isUnpaid) { ?>
                                    <input type="checkbox" class="schCb" data-loan="<?= $loan_id ?>" data-row="<?= $row['id'] ?>" data-rem="<?= $remaining ?>" style="cursor:pointer;">
                                    <span class="badge bg-warning"><?= ($paid_amount > 0) ? 'Half-paid' : 'Pending' ?></span>
                                <?php } else { ?>
                                    <span class="badge bg-success">Paid</span>
                                <?php } ?>
                            </td>
                        </tr>
                    <?php $counter++; }
                    $total_remaining = $total_repayment - $total_paid; ?>
                        <tr class="table-warning fw-bold">
                            <td colspan="2" class="text-end">TOTAL</td>
                            <td><?= number_format($total_principle, 2) ?></td>
                            <td><?= number_format($total_interest, 2) ?></td>
                            <td><?= number_format($total_repayment, 2) ?></td>
                            <td><?= number_format($total_paid, 2) ?></td>
                            <td><?= number_format($total_remaining, 2) ?></td>
                            <td></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="modal fade" id="bulkPay_<?= $loan_id ?>">
                <div class="modal-dialog modal-lg">
                    <div class="modal-content">
                        <div class="modal-header bg-success text-white">
                            <h5 class="modal-title">Collect Repayment — Loan #<?= $loan_id ?></h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <form action="./controllers/loan_controller.php" method="post" class="was-validated">
                                <input type="hidden" name="user_id" value="<?= $user_id ?>">
                                <input type="hidden" name="loan_id" value="<?= $loan_id ?>">
                                <input type="hidden" name="branch_id" value="<?= (int) $loan['branch_id'] ?>">
                                <input type="hidden" name="member_name" value="<?= htmlspecialchars($user['name']) ?>">
                                <input type="hidden" name="selected_schedules" id="sel_<?= $loan_id ?>">

                                <table class="table">
                                    <tr><th>Member</th><td><input type="text" readonly class="form-control" value="<?= htmlspecialchars($user['name']) ?> — Loan Account"></td></tr>
                                    <tr><th>Loan Remaining Balance</th><td><input type="text" readonly class="form-control" value="<?= number_format($loan_remaining, 2) ?>"></td></tr>
                                    <tr><th>Selected Installments Amount</th><td><input type="text" readonly class="form-control fw-bold format-number" id="amt_<?= $loan_id ?>" value="0.00"></td></tr>
                                    <tr><th>Payment Date</th><td><input type="date" name="payment_date" class="form-control" value="<?= date("Y-m-d") ?>" max="<?= date("Y-m-d") ?>" required></td></tr>
                                    <tr><th>Collected Amount</th><td><input type="text" name="collected_amount" id="col_<?= $loan_id ?>" min="0.01" step="0.01" class="form-control format-number" required></td></tr>
                                    <tr><th>Account Received Into</th><td>
                                        <select name="account_used" class="form-control select2-form select2bs4-form" required>
                                            <option value="">-- Select Account --</option>
                                            <?php
                                            $minsubs = selectMinSubByCategory($conn, "others");
                                            if ($minsubs && is_array($minsubs)) {
                                                foreach ($minsubs as $minsub) {
                                                    echo "<option value='{$minsub['id']}'>" . htmlspecialchars($minsub['name']) . "</option>";
                                                }
                                            }
                                            ?>
                                        </select>
                                    </td></tr>
                                    <tr><th>Reference / Receipt No (optional)</th><td><input type="text" name="transaction_reference" class="form-control"></td></tr>
                                </table>

                                <div class="alert alert-info">
                                    <strong>Note:</strong> The collected amount will be spread across the selected installment(s), oldest first.
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" name="submit_loan_collection" class="btn btn-success">Submit Collection</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mb-4"></div>
        <?php
            $loan_counter++;
        }
    }
}
?>
