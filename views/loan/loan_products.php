<?php
    // ================================================================
    //  LOAN PRODUCTS MANAGEMENT (admin)
    //  Create / edit / activate-deactivate / delete loan products.
    //  Each product carries its own eligibility rules which are shown
    //  to members on the application form and used by the eligibility
    //  engine when an admin reviews an application.
    // ================================================================
    $products = selectAllLoanTypesAdmin($conn);
?>
<div class="card card-primary">
    <div class="card-header">
        <h4 class="card-title"><i class="fas fa-layer-group mr-1"></i> Loan Products</h4>
        <div class="card-tools">
            <button type="button" class="btn btn-sm btn-success" data-toggle="modal" data-target="#addProductModal">
                <i class="fas fa-plus mr-1"></i> Add Loan Product
            </button>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm table-search">
                <thead>
                    <tr class="table-primary">
                        <th>#</th>
                        <th>Name</th>
                        <th>Amount Range (TZS)</th>
                        <th>Interest</th>
                        <th>Period</th>
                        <th>Savings x</th>
                        <th>Grantors</th>
                        <th>Status</th>
                        <th>Loans Using</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($products && is_array($products)): $i = 1; foreach ($products as $p):
                        $usage = countLoansByLoanType($conn, (int)$p['id']);
                        $modes = explode(',', $p['allowed_repayment_modes'] ?? '');
                    ?>
                        <tr>
                            <td><?= $i++ ?></td>
                            <td><strong><?= htmlspecialchars($p['name']) ?></strong><br><small class="text-muted"><?= htmlspecialchars($p['description'] ?? '') ?></small></td>
                            <td><?= number_format((float)$p['min_amount'],2) ?> – <?= $p['max_amount'] > 0 ? number_format((float)$p['max_amount'],2) : 'No cap' ?></td>
                            <td><?= number_format((float)$p['interest_rate'],2) ?>%</td>
                            <td><?= (int)$p['min_period'] ?>–<?= (int)$p['max_period'] ?> mo</td>
                            <td><?= number_format((float)$p['savings_multiplier'],2) ?>x</td>
                            <td><?= (int)$p['required_grantors'] ?></td>
                            <td>
                                <span class="badge <?= $p['status'] === 'active' ? 'badge-success' : 'badge-secondary' ?>"><?= htmlspecialchars($p['status']) ?></span>
                            </td>
                            <td><?= $usage ?></td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-xs btn-info" data-toggle="modal" data-target="#editProductModal<?= $p['id'] ?>">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="./controllers/loan_controller.php" method="post" class="d-inline" onsubmit="return confirm('<?= $p['status'] === 'active' ? 'Deactivate' : 'Activate' ?> this product? Members will <?= $p['status'] === 'active' ? 'no longer' : 'now' ?> see it on the application form.');">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <input type="hidden" name="new_status" value="<?= $p['status'] === 'active' ? 'inactive' : 'active' ?>">
                                    <button type="submit" name="toggle_loan_product_status" class="btn btn-xs <?= $p['status'] === 'active' ? 'btn-warning' : 'btn-success' ?>">
                                        <i class="fas <?= $p['status'] === 'active' ? 'fa-ban' : 'fa-check' ?>"></i>
                                    </button>
                                </form>
                                <form action="./controllers/loan_controller.php" method="post" class="d-inline" onsubmit="return confirm('Delete &quot;<?= htmlspecialchars($p['name'], ENT_QUOTES) ?>&quot;? <?= $usage > 0 ? "Warning: $usage loan(s) already reference this product — their records are kept, but the product will no longer be selectable." : 'No loans reference this product yet.' ?>');">
                                    <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                    <button type="submit" name="delete_loan_product" class="btn btn-xs btn-danger"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>

                        <!-- Edit modal for this product -->
                        <div class="modal fade" id="editProductModal<?= $p['id'] ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form action="./controllers/loan_controller.php" method="post" class="was-validated">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Edit: <?= htmlspecialchars($p['name']) ?></h5>
                                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="id" value="<?= $p['id'] ?>">
                                            <div class="row">
                                                <div class="col-md-6 form-group">
                                                    <label>Product Name</label>
                                                    <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($p['name']) ?>" required>
                                                </div>
                                                <div class="col-md-6 form-group">
                                                    <label>Status</label>
                                                    <select name="status" class="form-control">
                                                        <option value="active" <?= $p['status']==='active'?'selected':'' ?>>Active</option>
                                                        <option value="inactive" <?= $p['status']==='inactive'?'selected':'' ?>>Inactive</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Description</label>
                                                <textarea name="description" class="form-control" rows="2"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3 form-group">
                                                    <label>Min Amount</label>
                                                    <input type="number" step="any" name="min_amount" class="form-control" value="<?= $p['min_amount'] ?>" required>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Max Amount (0 = no cap)</label>
                                                    <input type="number" step="any" name="max_amount" class="form-control" value="<?= $p['max_amount'] ?>" required>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Interest Rate (%/yr)</label>
                                                    <input type="number" step="any" name="interest_rate" class="form-control" value="<?= $p['interest_rate'] ?>" required>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Processing Fee (%)</label>
                                                    <input type="number" step="any" name="processing_fee_percent" class="form-control" value="<?= $p['processing_fee_percent'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3 form-group">
                                                    <label>Min Period (months)</label>
                                                    <input type="number" name="min_period" class="form-control" value="<?= $p['min_period'] ?>" required>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Max Period (months)</label>
                                                    <input type="number" name="max_period" class="form-control" value="<?= $p['max_period'] ?>" required>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Savings Multiplier</label>
                                                    <input type="number" step="any" name="savings_multiplier" class="form-control" value="<?= $p['savings_multiplier'] ?>" required>
                                                </div>
                                                <div class="col-md-3 form-group">
                                                    <label>Required Grantors</label>
                                                    <input type="number" name="required_grantors" class="form-control" value="<?= $p['required_grantors'] ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Allowed Repayment Modes</label><br>
                                                <?php $modesArr = explode(',', $p['allowed_repayment_modes'] ?? ''); ?>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="mode_salary" value="1" <?= in_array('salary',$modesArr) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Salary</label>
                                                </div>
                                                <div class="form-check form-check-inline">
                                                    <input class="form-check-input" type="checkbox" name="mode_standing_order" value="1" <?= in_array('standing_order',$modesArr) ? 'checked' : '' ?>>
                                                    <label class="form-check-label">Standing Order</label>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label>Eligibility Conditions <small class="text-muted">(plain language — shown to members)</small></label>
                                                <textarea name="eligibility_notes" class="form-control" rows="3"><?= htmlspecialchars($p['eligibility_notes'] ?? '') ?></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <button type="submit" name="update_loan_product" class="btn btn-primary">Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; else: ?>
                        <tr><td colspan="10" class="text-center text-muted">No loan products yet — add one above.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="./controllers/loan_controller.php" method="post" class="was-validated">
                <div class="modal-header">
                    <h5 class="modal-title">Add Loan Product</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Product Name</label>
                            <input type="text" name="name" class="form-control" required placeholder="e.g. Emergency Loan">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Status</label>
                            <select name="status" class="form-control">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Min Amount</label>
                            <input type="number" step="any" name="min_amount" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Max Amount (0 = no cap)</label>
                            <input type="number" step="any" name="max_amount" class="form-control" value="0" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Interest Rate (%/yr)</label>
                            <input type="number" step="any" name="interest_rate" class="form-control" value="12" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Processing Fee (%)</label>
                            <input type="number" step="any" name="processing_fee_percent" class="form-control" value="0" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label>Min Period (months)</label>
                            <input type="number" name="min_period" class="form-control" value="1" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Max Period (months)</label>
                            <input type="number" name="max_period" class="form-control" value="12" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Savings Multiplier</label>
                            <input type="number" step="any" name="savings_multiplier" class="form-control" value="3" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Required Grantors</label>
                            <input type="number" name="required_grantors" class="form-control" value="2" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Allowed Repayment Modes</label><br>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="mode_salary" value="1" checked>
                            <label class="form-check-label">Salary</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="mode_standing_order" value="1" checked>
                            <label class="form-check-label">Standing Order</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Eligibility Conditions <small class="text-muted">(plain language — shown to members)</small></label>
                        <textarea name="eligibility_notes" class="form-control" rows="3" placeholder="e.g. Member must have at least 3 months of active savings. One guarantor required."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_loan_product" class="btn btn-success">Create Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════════════════════════════════════════
     SAVINGS MULTIPLIER MANAGER
     Lets admin set the savings_multiplier either:
     (a) Same value for ALL loan types at once, or
     (b) A different value per loan type individually.
══════════════════════════════════════════════════════════ -->
<div class="card card-warning mt-4" id="savingsMultiplierCard">
    <div class="card-header">
        <h5 class="card-title">
            <i class="fas fa-times-circle mr-1"></i> Savings Multiplier Manager
        </h5>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">

        <div class="alert alert-warning">
            <i class="fas fa-info-circle mr-1"></i>
            The <strong>savings multiplier</strong> controls the maximum loan a member can get relative to their savings.
            E.g. multiplier <strong>3×</strong> means a member with TZS 100,000 in savings can borrow up to TZS 300,000.
            You can set the <em>same multiplier for all products</em>, or adjust each product individually below.
        </div>

        <!-- Mode tabs -->
        <ul class="nav nav-tabs mb-3" id="multiplierTabs">
            <li class="nav-item">
                <a class="nav-link active" data-toggle="tab" href="#tabSameAll">Same for All Products</a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-toggle="tab" href="#tabPerProduct">Set Per Product</a>
            </li>
        </ul>

        <div class="tab-content">

            <!-- ── Tab A: Same multiplier for every product ── -->
            <div class="tab-pane fade show active" id="tabSameAll">
                <form action="./controllers/loan_controller.php" method="post">
                    <div class="row align-items-end">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">New Multiplier (applies to ALL loan types)</label>
                            <div class="input-group">
                                <input type="number" step="0.1" min="0.1" max="100"
                                       name="global_multiplier" class="form-control"
                                       value="<?= number_format((float)($products[0]['savings_multiplier'] ?? 3), 1) ?>"
                                       placeholder="e.g. 3">
                                <div class="input-group-append">
                                    <span class="input-group-text">×</span>
                                </div>
                            </div>
                            <small class="text-muted">This will overwrite the multiplier on <strong>every</strong> loan product.</small>
                        </div>
                        <div class="col-md-3 form-group">
                            <button type="submit" name="set_global_savings_multiplier"
                                    class="btn btn-warning btn-block"
                                    onclick="return confirm('Apply this multiplier to ALL loan products?')">
                                <i class="fas fa-check mr-1"></i> Apply to All
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- ── Tab B: Per-product individual multipliers ── -->
            <div class="tab-pane fade" id="tabPerProduct">
                <form action="./controllers/loan_controller.php" method="post">
                    <table class="table table-sm table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Loan Product</th>
                                <th style="width:180px">Current Multiplier</th>
                                <th style="width:220px">New Multiplier</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($products && is_array($products)): foreach ($products as $p): ?>
                            <tr>
                                <td>
                                    <strong><?= htmlspecialchars($p['name']) ?></strong>
                                    <input type="hidden" name="product_ids[]" value="<?= (int)$p['id'] ?>">
                                    <br><small class="text-muted"><?= htmlspecialchars($p['description'] ?? '') ?></small>
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-secondary px-2 py-1" style="font-size:14px">
                                        <?= number_format((float)$p['savings_multiplier'], 2) ?>×
                                    </span>
                                </td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <input type="number" step="0.1" min="0.1" max="100"
                                               name="per_multipliers[]" class="form-control"
                                               value="<?= number_format((float)$p['savings_multiplier'], 1) ?>">
                                        <div class="input-group-append">
                                            <span class="input-group-text">×</span>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; else: ?>
                            <tr><td colspan="3" class="text-center text-muted">No loan products yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <?php if ($products && is_array($products)): ?>
                    <button type="submit" name="set_per_product_savings_multiplier"
                            class="btn btn-primary"
                            onclick="return confirm('Save individual multipliers for each product?')">
                        <i class="fas fa-save mr-1"></i> Save Per-Product Multipliers
                    </button>
                    <?php endif; ?>
                </form>
            </div>

        </div><!-- /.tab-content -->
    </div>
</div>
