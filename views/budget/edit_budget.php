<?php
    $budget_id = (int) $_GET['id'];
    $budget = selectBudgetById($conn, $budget_id);
    $items = selectBudgetItems($conn, $budget_id);
    if(!$budget || $budget['status'] !== 'pending'):
        echo "<script>alert('Budget not found or cannot be edited'); window.location.href='./?page=all_budgets';</script>";
        exit;
    endif;
?>
<div class="card card-warning">
    <div class="card-header"><h4 class="card-title">Edit Budget - <?= $budget['ref_no'] ?></h4></div>
    <form action="./controllers/budget_controller.php" method="post" class="was-validated">
        <input type="hidden" name="budget_id" value="<?= $budget['id'] ?>">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Budget Year</label>
                        <select name="year" class="form-control" required>
                            <?php for($y = date('Y'); $y <= date('Y') + 5; $y++): ?>
                                <option value="<?= $y ?>" <?= $y == $budget['year'] ? 'selected' : '' ?>><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" required><?= $budget['descreption'] ?></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"><?= $budget['notes'] ?></textarea>
            </div>
            <hr>
            <h5>Budget Items</h5>
            <div class="table-responsive">
                <table class="table table-bordered" id="budgetTable">
                    <thead>
                        <tr>
                            <th>Subsidiary Account</th>
                            <th>Description</th>
                            <th>Amount (TZS)</th>
                            <th><button type="button" class="btn btn-success btn-sm" onclick="addBudgetRow()"><i class="fas fa-plus"></i></button></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if($items && is_array($items)): foreach($items as $item): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="item_id[]" value="<?= $item['id'] ?>">
                                <select name="sub_id[]" class="form-control select2-form select2bs4-form" required>
                                    <option value="">Select Account</option>
                                    <?php
                                    $subs = selectAllSubsidiaries($conn);
                                    if($subs && is_array($subs)):
                                        foreach($subs as $sub):
                                    ?>
                                        <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $item['sub_id'] ? 'selected' : '' ?>><?= $sub['name'] ?></option>
                                    <?php endforeach; endif; ?>
                                </select>
                            </td>
                            <td><input type="text" name="item_description[]" class="form-control" value="<?= $item['description'] ?>"></td>
                            <td><input type="number" name="amount[]" class="form-control amount-input" step="0.01" min="0" value="<?= $item['amount'] ?>" required oninput="calculateBudgetTotal()"></td>
                            <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)"><i class="fas fa-times"></i></button></td>
                        </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="2" class="text-right">Total:</th>
                            <th><span id="budgetTotal">0.00</span></th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="updatebudget" class="btn btn-warning">Update Budget</button>
            <a href="./?page=all_budgets" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
<script>
function addBudgetRow() {
    var table = document.getElementById('budgetTable').getElementsByTagName('tbody')[0];
    var row = table.insertRow();
    row.innerHTML = `
        <td>
            <input type="hidden" name="item_id[]" value="0">
            <select name="sub_id[]" class="form-control select2-form select2bs4-form" required>
                <option value="">Select Account</option>
                <?php
                $subs = selectAllSubsidiaries($conn);
                if($subs && is_array($subs)):
                    foreach($subs as $sub):
                ?>
                    <option value="<?= $sub['id'] ?>"><?= $sub['name'] ?></option>
                <?php endforeach; endif; ?>
            </select>
        </td>
        <td><input type="text" name="item_description[]" class="form-control" placeholder="Item description"></td>
        <td><input type="number" name="amount[]" class="form-control amount-input" step="0.01" min="0" required oninput="calculateBudgetTotal()"></td>
        <td><button type="button" class="btn btn-danger btn-sm" onclick="removeBudgetRow(this)"><i class="fas fa-times"></i></button></td>
    `;
    $(row).find('.select2-form').select2({theme: 'bootstrap4'});
}
function removeBudgetRow(btn) {
    var table = document.getElementById('budgetTable').getElementsByTagName('tbody')[0];
    if(table.rows.length > 1) { btn.closest('tr').remove(); calculateBudgetTotal(); }
}
function calculateBudgetTotal() {
    var inputs = document.getElementsByClassName('amount-input');
    var total = 0;
    for(var i = 0; i < inputs.length; i++) total += parseFloat(inputs[i].value) || 0;
    document.getElementById('budgetTotal').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});
}
calculateBudgetTotal();
</script>
