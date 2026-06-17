<div class=" card card-primary">
<div class=" card-header">Register Stock</div>
<form class="was-validated" action="./controllers/subsidiary_controller.php" method="post">
    <div class=" card-body">
        <label for="" class=" form-label">Ledger</label>
        <select name="ledger" id="" class=" form-control select2-form select2bs4-form" required>
            <option value="">Select Ledger</option>
             <?php
                $ledgers = selectAllActiveLedgers($conn);
                if($ledgers && is_array($ledgers)){
                    foreach($ledgers as $ledger){
                        echo "<option value='$ledger[id]'>$ledger[name]</option>";
                    }
                }
            ?>
        </select>
        <br>
        <label for="" class=" form-label"> Stock Name:</label>
        <input type="text" name="item" id="" class=" form-control" required>
    </div>
    <div class=" card-footer">
        <button class="btn btn-sm btn-primary w-100 " name="addstock" type="submit">Add Stock </button>
    </div>
</form>
</div>