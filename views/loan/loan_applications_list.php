<?php
    // ================================================================
    //  UNIFIED LOAN APPLICATIONS LIST
    //  Replaces the old separate "Approved Loan List" and "Pending Loan
    //  List" pages. One filterable view: status (incl. "all" = both
    //  pending and approved together), loan product, branch, dates, name.
    // ================================================================
    $isBranchLocked = (($_SESSION['role'] ?? '') === 'accountant' && ($_SESSION['userlevel'] ?? '') === 'branch');
    $lockedBranchId  = $isBranchLocked ? (int) $_SESSION['branchid'] : null;

    $status   = $_GET['status']    ?? 'all';
    $loanType = $_GET['loan_type'] ?? '';
    $branchId = $isBranchLocked ? $lockedBranchId : ($_GET['branch_id'] ?? '');
    $date1    = $_GET['date1']     ?? '';
    $date2    = $_GET['date2']     ?? '';
    $search   = $_GET['search']    ?? '';

    $loans = selectLoansFiltered($conn, [
        'status'    => $status,
        'loan_type' => $loanType,
        'branch_id' => $branchId,
        'date1'     => $date1,
        'date2'     => $date2,
        'search'    => $search,
    ]);

    $allBranches  = selectAllBranches($conn, $lockedBranchId);
    $allLoanTypes = selectAllLoanTypesAdmin($conn);

    $statusBadge = [
        'pending'  => 'badge-warning',
        'approved' => 'badge-success',
        'rejected' => 'badge-danger',
    ];
?>
<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-file-invoice-dollar mr-1"></i> Loan Applications</h4>
    </div>
    <form action="./?page=loan_applications" method="get" class="was-validated">
        <input type="hidden" name="page" value="loan_applications">
        <div class="card-body">
            <div class="row">
                <div class="col-md-2 form-group">
                    <label>Status</label>
                    <select name="status" class="form-control">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>All (Pending + Approved + Rejected)</option>
                        <option value="pending"  <?= $status === 'pending'  ? 'selected' : '' ?>>Pending</option>
                        <option value="approved" <?= $status === 'approved' ? 'selected' : '' ?>>Approved</option>
                        <option value="rejected" <?= $status === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Loan Product</label>
                    <select name="loan_type" class="form-control select2-form select2bs4-form">
                        <option value="">All Products</option>
                        <?php foreach ($allLoanTypes as $lt): ?>
                            <option value="<?= $lt['id'] ?>" <?= ((string)$loanType === (string)$lt['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($lt['name']) ?><?= $lt['status'] === 'inactive' ? ' (inactive)' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 form-group">
                    <label>Branch</label>
                    <select name="branch_id" class="form-control select2-form select2bs4-form" <?= $isBranchLocked ? 'disabled' : '' ?>>
                        <option value="">All Branches</option>
                        <?php foreach ($allBranches as $b): ?>
                            <option value="<?= $b['id'] ?>" <?= ((string)$branchId === (string)$b['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($b['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <?php if ($isBranchLocked): ?>
                        <input type="hidden" name="branch_id" value="<?= $lockedBranchId ?>">
                    <?php endif; ?>
                </div>
                <div class="col-md-2 form-group">
                    <label>From</label>
                    <input type="date" name="date1" value="<?= htmlspecialchars($date1) ?>" class="form-control" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label>To</label>
                    <input type="date" name="date2" value="<?= htmlspecialchars($date2) ?>" class="form-control" max="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-md-2 form-group">
                    <label>Member Name</label>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-control" placeholder="Search member...">
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-filter mr-1"></i> Apply Filters</button>
            <a href="./?page=loan_applications" class="btn btn-sm btn-outline-secondary">Reset</a>
        </div>
    </form>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h5 class="card-title">Results (<?= is_array($loans) ? count($loans) : 0 ?>)</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm table-search">
                <thead>
                    <tr class="table-primary">
                        <th>#</th>
                        <th>Member</th>
                        <th>Branch</th>
                        <th>Product</th>
                        <th>Amount</th>
                        <th>Period</th>
                        <th>Applied</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($loans && is_array($loans)): $i = 1; foreach ($loans as $loan): ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><?= htmlspecialchars($loan['member_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($loan['branch_name'] ?? '') ?></td>
                            <td><?= htmlspecialchars($loan['product_name'] ?? '—') ?></td>
                            <td><?= number_format((float)$loan['principle'], 2) ?></td>
                            <td><?= (int)$loan['period'] ?> mo</td>
                            <td><?= !empty($loan['created_at']) ? date('d/m/Y', strtotime($loan['created_at'])) : '—' ?></td>
                            <td><span class="badge <?= $statusBadge[$loan['status']] ?? 'badge-secondary' ?>"><?= htmlspecialchars($loan['status']) ?></span></td>
                            <td>
                                <?php if ($loan['status'] === 'pending'): ?>
                                    <a href="./?page=process_loan&loan_id=<?= $loan['id'] ?>&user_id=<?= $loan['user_id'] ?>&branch_id=<?= $loan['branch_id'] ?>" class="btn btn-xs btn-warning">
                                        <i class="fas fa-search mr-1"></i>Review
                                    </a>
                                <?php else: ?>
                                    <a href="./?page=view_loan_details&loan_id=<?= $loan['id'] ?>&user_id=<?= $loan['user_id'] ?>" class="btn btn-xs btn-info">
                                        <i class="fas fa-eye mr-1"></i>View
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; else: ?>
                        <tr><td colspan="9" class="text-center text-muted">No loan applications match these filters.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
