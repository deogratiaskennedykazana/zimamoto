<div class=" card card-primary">
    <div class=" card-header"> <h5 class=" card-title">Income Statement</h5> </div>
     <div class=" card-body">
        <div class="row">
            <div class=" col-md-6 col-sm-10">
                <div class=" card card-outline card-info">
                    <div class=" card-header">
                        <h5 class=" card-title">Income Statement by Subsidiary</h5>
                    </div>
                    <form action="./?page=income_statement_subsidiary" method="post" class="was-validated">
                        <div class=" card-body">
                            <div class=" form-group">
                                <label for="">Select Branch</label>
                                <select name="branch" required class=" form-control select2-form select2bs4-form" id="">
                                     
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
                                <label for="">Select Account Period</label>
                                <div class=" row">
                                    <div class=" col-md-5 col-sm-10">
                                        <label for="">Select first Date</label>
                                        <input type="date" name="date1" class=" form-control" required max="<?= date("Y-m-d"); ?>" id="">
                                    </div>
                                    <div class=" col-md-5 col-sm-10">
                                        <label for="">Select last Date</label>
                                        <input type="date" name="date2" class=" form-control" required max="<?= date("Y-m-d"); ?>" id="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=" card-footer">
                            <button type="submit" class=" btn btn-info btn-block">View Report</button> 
                        </div>
                    </form>
                </div>
            </div>
     
            <div class=" col-md-6 col-sm-10">
                <div class=" card card-outline card-primary">
                    <div class=" card-header">
                        <h5 class=" card-title">Income Statement by ledger</h5>
                    </div>
                    <form action="./?page=income_statement_ledger" method="post" class="was-validated">
                        <div class=" card-body">
                            <div class=" form-group">
                                <label for="">Select Branch</label>
                                <select name="branch" required class=" form-control select2-form select2bs4-form" id="">
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
                                <label for="">Select Account Period</label>
                                <div class=" row">
                                    <div class=" col-md-5 col-sm-10">
                                        <label for="">Select first Date</label>
                                        <input type="date" name="date1" class=" form-control" required max="<?= date("Y-m-d"); ?>" id="">
                                    </div>
                                    <div class=" col-md-5 col-sm-10">
                                        <label for="">Select last Date</label>
                                        <input type="date" name="date2" class=" form-control" required max="<?= date("Y-m-d"); ?>" id="">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class=" card-footer">
                            <button type="submit" class=" btn btn-primary btn-block">View Report</button> 
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
</div>