<?php
    $budget_id = (int) $_GET['id'];
    $budget = selectBudgetById($conn, $budget_id);
    $items = selectBudgetItems($conn, $budget_id);
    if(!$budget):
        echo "<script>alert('Budget not found'); window.location.href='./?page=all_budgets';</script>";
        exit;
    endif;
?>
<div class="card card-success">
    <div class="card-header"><h4 class="card-title">Review Budget - <?= $budget['ref_no'] ?></h4></div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Year:</strong> <?= $budget['year'] ?></p>
                <p><strong>Description:</strong> <?= $budget['descreption'] ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Status:</strong> <span class="badge badge-<?= $budget['status'] === 'approved' ? 'success' : ($budget['status'] === 'rejected' ? 'danger' : 'warning') ?>"><?= ucfirst($budget['status']) ?></span></p>
                <p><strong>Created By:</strong> <?= $budget['created_by_name'] ?></p>
            </div>
        </div>
        <?php if($budget['notes']): ?>
        <div class="alert alert-info"><?= $budget['notes'] ?></div>
        <?php endif; ?>
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>#</th><th>Account</th><th>Description</th><th class="text-right">Amount</th></tr></thead>
                <tbody>
                    <?php $total = 0; if($items && is_array($items)): $i=1; foreach($items as $item):
                        $sub = $conn->query("SELECT name FROM subsidiaries WHERE id={$item['sub_id']}")->fetch_assoc();
                        $total += $item['amount'];
                    ?>
                    <tr>
                        <td><?= $i++ ?></td>
                        <td><?= $sub ? $sub['name'] : 'N/A' ?></td>
                        <td><?= $item['description'] ?></td>
                        <td class="text-right"><?= number_format($item['amount'], 2) ?></td>
                    </tr>
                    <?php endforeach; endif; ?>
                </tbody>
                <tfoot>
                    <tr><th colspan="3" class="text-right">Total:</th><th class="text-right"><?= number_format($total, 2) ?></th></tr>
                </tfoot>
            </table>
        </div>

        <?php if($budget['status'] === 'pending'): ?>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <form action="./controllers/budget_controller.php" method="post" onsubmit="return confirm('Approve this budget?')">
                    <input type="hidden" name="budget_id" value="<?= $budget['id'] ?>">
                    <button type="submit" name="approve_budget" class="btn btn-success btn-block"><i class="fas fa-check"></i> Approve Budget</button>
                </form>
            </div>
            <div class="col-md-6">
                <form action="./controllers/budget_controller.php" method="post" onsubmit="return confirm('Reject this budget?')">
                    <input type="hidden" name="budget_id" value="<?= $budget['id'] ?>">
                    <div class="form-group">
                        <label>Rejection Reason</label>
                        <textarea name="rejection_reason" class="form-control" rows="2" required></textarea>
                    </div>
                    <button type="submit" name="reject_budget" class="btn btn-danger btn-block"><i class="fas fa-times"></i> Reject Budget</button>
                </form>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
