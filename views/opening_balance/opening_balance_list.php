<div class="card card-secondary">
    <div class="card-header">
        <h4>Opening Balance List</h4>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered table-striped table-sm table-search">
            <thead>
                <tr class="table-primary">
                    <th>#</th>
                    <th>Account</th>
                    <th>Date</th>
                    <th>Amount</th>
                    <th>Currency Value</th>
                    <th>Type</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php
                    $balances = selectAllOpeningBalances($conn);
                    if($balances && is_array($balances)){
                        $counter = 1;
                        foreach($balances as $balance){
                            echo "<tr>";
                            echo "<td>$counter</td>";
                            echo "<td>{$balance['account']}</td>";
                            echo "<td>" . date('d F Y', strtotime($balance['date_'])) . "</td>";
                            echo "<td>" . number_format($balance['ammount'], 2) . "</td>";
                            echo "<td>{$balance['curr_value']}</td>";
                            echo "<td>" . ucfirst($balance['type']) . "</td>";
                            echo "<td>
                                <button class='btn btn-sm btn-info' 
                                        onclick='openEditOpeningBalanceModal(" . json_encode($balance['id']) . ", " . 
                                        json_encode($balance['date_']) . ", " . 
                                        json_encode($balance['account_id']) . ", " . 
                                        json_encode($balance['account']) . ", " . 
                                        json_encode($balance['ammount']) . ", " . 
                                        json_encode($balance['type']) . ")'>
                                    Edit
                                </button>
                                <a class='btn btn-sm btn-danger' 
                                   href='./controllers/opening_balance_controller.php?delete_opneing_balance={$balance['id']}' 
                                   onclick=\"return confirm('Are you sure you want to delete this balance?');\">Delete</a>
                            </td>";
                            echo "</tr>";
                            $counter++;
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editModal" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Edit Opening Balance</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="./controllers/opening_balance_controller.php" method="post" class="was-validated">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="edit_date">Date</label>
                        <input type="date" name="date" id="edit_date" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_account">Subsidiary</label>
                        <select name="account_id" id="edit_account" required class="form-control select2-form select2bs4-form"></select>
                    </div>
                    <div class="form-group">
                        <label for="edit_amount">Amount</label>
                        <input type="text" name="amount" id="edit_amount" class="form-control format-number" required>
                    </div>
                    <div class="form-group">
                        <label for="edit_type">Type</label>
                        <select name="type" id="edit_type" required class="form-control">
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button name="edit_opening_balance" type="submit" class="btn btn-info">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var allSubsidiaries = <?php 
    $allSubs = selectAllSubsidiaries($conn);
    echo json_encode($allSubs ? $allSubs : []);
?>;

function openEditOpeningBalanceModal(id, date, accountId, accountName, amount, type) {
    $('#edit_id').val(id);
    $('#edit_date').val(date);
    $('#edit_amount').val(parseFloat(amount).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ","));
    $('#edit_type').val(type);
    
    var options = '<option value="' + accountId + '" selected>' + accountName + '</option>';
    allSubsidiaries.forEach(function(sub) {
        if(sub.id != accountId) {
            options += '<option value="' + sub.id + '">' + sub.name + '</option>';
        }
    });
    $('#edit_account').html(options);
    
    initializeSelect();
    $('#editModal').modal('show');
}
</script>