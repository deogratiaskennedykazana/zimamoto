<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title">All Budgets</h4>
        <div class="card-tools">
            <a href="./?page=create_budget" class="btn btn-success btn-sm"><i class="fas fa-plus"></i> New Budget</a>
        </div>
    </div>
    <div class="card-body">
        <ul class="nav nav-tabs" id="budgetTabs">
            <li class="nav-item"><a class="nav-link active" href="#all" data-toggle="tab">All</a></li>
            <li class="nav-item"><a class="nav-link" href="#pending" data-toggle="tab">Pending</a></li>
            <li class="nav-item"><a class="nav-link" href="#approved" data-toggle="tab">Approved</a></li>
            <li class="nav-item"><a class="nav-link" href="#rejected" data-toggle="tab">Rejected</a></li>
        </ul>
        <div class="tab-content mt-3">
            <?php
            $statuses = ['all' => null, 'pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'];
            foreach($statuses as $tab => $status):
            ?>
            <div class="tab-pane <?= $tab === 'all' ? 'active' : '' ?>" id="<?= $tab ?>">
                <div class="table-responsive">
                    <table class="table table-bordered table-search">
                        <thead>
                            <tr>
                                <th>Ref No</th>
                                <th>Year</th>
                                <th>Description</th>
                                <th>Total Amount</th>
                                <th>Status</th>
                                <th>Created By</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $budgets = $status ? selectBudgetByStatus($conn, $status) : selectAllBudgets($conn);
                            if($budgets && is_array($budgets)):
                                foreach($budgets as $b):
                                    $badge = $b['status'] === 'approved' ? 'success' : ($b['status'] === 'rejected' ? 'danger' : 'warning');
                            ?>
                            <tr>
                                <td><?= $b['ref_no'] ?></td>
                                <td><?= $b['year'] ?></td>
                                <td><?= $b['descreption'] ?></td>
                                <td class="text-right"><?= number_format($b['total_amount'], 2) ?></td>
                                <td><span class="badge badge-<?= $badge ?>"><?= ucfirst($b['status']) ?></span></td>
                                <td><?= $b['created_by_name'] ?></td>
                                <td><?= date('d/m/Y', strtotime($b['created_at'])) ?></td>
                                <td>
                                    <a href="./?page=view_budget&id=<?= $b['id'] ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>
                                    <?php if($b['status'] === 'pending'): ?>
                                        <a href="./?page=edit_budget&id=<?= $b['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                        <a href="./?page=review_budget&id=<?= $b['id'] ?>" class="btn btn-success btn-sm"><i class="fas fa-check"></i></a>
                                        <a href="./controllers/budget_controller.php?delete_budget=<?= $b['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this budget?')"><i class="fas fa-trash"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
