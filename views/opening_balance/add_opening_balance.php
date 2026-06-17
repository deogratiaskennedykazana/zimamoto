<div class="card card-info">
    <div class="card-header">
        <h4 class="card-title">Add Opening Balance</h4>
    </div>
    <form action="./controllers/opening_balance_controller.php" method="post" class="was-validated">
        <div class="card-body">
            <div class="m-3 row">
                <div class="col-md-6 col-sm-10">
                    <div class="form-group">
                        <label for="" class="form-label">Date</label>
                        <input type="date" name="date" id="" class="form-control" required>
                    </div>
                </div>
                <div class="col-md-6 col-sm-10">
                    <div class="form-group">
                        <label for="" class="form-label">Currency</label>
                        <select name="currency" id="" class="form-control" onchange="selectCurrency(this.value)" required>
                            <option value="">Select below</option>
                            <?php
                                $sql = "SELECT * FROM currency";
                                $query = mysqli_query($conn, $sql);
                                while($rows = mysqli_fetch_assoc($query)){
                                    echo "<option value='{$rows['id']}'>{$rows['name']}</option>";
                                }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="form-group col-md-6 col-sm-10">
                <label for="" class="form-label">Exchange Rate</label>
                <div id="currency1"></div>
            </div>
        
        <div class="mb-3">
            <button type="button" class="btn btn-info btn-sm" onclick="addTableRow('opening_balance_table')">Add Row</button>
            <button type="button" class="btn btn-danger btn-sm" onclick="removeTableRow('opening_balance_table')">Remove Row</button>
        </div>
        <table class="table table-bordered table-sm" id="opening_balance_table">
            <thead>
                <tr>
                    <th>Subsidiary</th>
                    <th>Amount</th>
                    <th>Type</th>
                    <th>Equivalent</th>
                </tr>
            </thead>
            <tbody>
                <tr class="data-row">
                    <td>
                        <select name="item[]" required class="form-control select2bs4-form select2-form">
                            <option value="">Select below</option>
                            <?php
                                $items = selectAllSubsidiaries($conn);
                                if($items && is_array($items)){
                                    foreach($items as $item){
                                        echo "<option value='{$item['id']}'>{$item['name']}</option>";
                                    }
                                }
                            ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="amount[]" oninput="calculateEqv()" class="amount debt_amount form-control format-number" step="any" required>
                    </td>
                    <td>
                        <select name="type[]" class="form-control" required>
                            <option value="">Select Below</option>
                            <option value="debit">Debit</option>
                            <option value="credit">Credit</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" readonly name="eqv[]" class="eqv equiv_dr form-control " required>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="card-footer text-center">
            <button name="add_opening_balance" type="submit" class="btn-block btn btn-sm btn-info">Post</button>
        </div>
    </form>
</div>
</div>