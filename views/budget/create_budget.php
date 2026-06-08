<div class="card card-primary">
    <div class="card-header"><h4 class="card-title">Create Budget</h4></div>
    <form action="./controllers/budget_controller.php" method="post" class="was-validated">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Budget Year</label>
                        <select name="year" class="form-control" required>
                            <option value="">Select Year</option>
                            <?php for($y = date('Y'); $y <= date('Y') + 5; $y++): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" class="form-control" rows="2" required></textarea>
                    </div>
                </div>
            </div>
            <div class="form-group">
                <label>Notes</label>
                <textarea name="notes" class="form-control" rows="2"></textarea>
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
                        <tr>
                            <td>
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
                        </tr>
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
            <button type="submit" name="addbudget" class="btn btn-primary">Create Budget</button>
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
    if(table.rows.length > 1) {
        btn.closest('tr').remove();
        calculateBudgetTotal();
    }
}
function calculateBudgetTotal() {
    var inputs = document.getElementsByClassName('amount-input');
    var total = 0;
    for(var i = 0; i < inputs.length; i++) {
        total += parseFloat(inputs[i].value) || 0;
    }
    document.getElementById('budgetTotal').innerText = total.toLocaleString('en-US', {minimumFractionDigits: 2});
}
</script>
