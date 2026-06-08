<div class=" card card-info">
    <div class=" card-header"> <h5 class=" card-title">Ledger Report</h5> </div>
    <form action="./?page=ledger_report" method="post" class="was-validated">
        <div class=" card-body">
            <div class=" form-group">
                <label for="">Select Branch</label>
                <select name="branch" id="" class=" select2-form select2bs4-form form-control" required>
                    <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                    
                                                }
                                    
                                                if ($branches && is_array($branches)) {
                                                    foreach ($branches as $result) {
                                                        $selected = ($branchId == $result['id']) ? 'selected' : '';
                                                        echo "<option value='{$result['id']}' $selected>{$result['name']}</option>";
                                                    }
                                                }
                                            ?>
                </select>
            </div>
            <div class=" form-group">
                <label for=""> Select Ledger</label>
                <select name="ledger_id" id="" required  class=" select2-form select2bs4-form form-control">
                    <option value="">Select ledger</option>
                    <?php
                        $ledgers  = selectAllLedgers($conn);
                        if($ledgers && is_array($ledgers)){
                            foreach($ledgers as $result){
                                ?>
                                <option value="<?= $result['id'] ?>"><?= $result['name'] ?></option>
                                <?php
                            }
                        }
                    ?>
                </select>
            </div>
            <div class=" row">
                <div class=" col-md-5 col-sm-10">
                    <label for="">Select Start Date</label>
                    <input type="date" name="date1" max="<?= date("Y-m-d")?>" class=" form-control" required id="">
                </div>
                <div class=" col-md-5 col-sm-10">
                    <label for="">Select end  Date</label>
                    <input type="date" name="date2" max="<?= date("Y-m-d")?>" class=" form-control" required id="">
                </div>

            </div>
        </div>
        <div class=" card-footer"> <input type="submit" value="Generate Report" class=" btn btn-primary btn-block"></div>
    </form>
</div>