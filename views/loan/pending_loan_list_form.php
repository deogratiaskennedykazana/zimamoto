<div class=" card card-primary">
    <div class=" card-header"> Pending Loan Form </div>
    <form action="./?page=pending_loan_list" method="post" class=" was-validated">
        <div class=" card-body">
                <div class=" form-group">
                    <label for="">Select Branch</label>
                    <select name="branch_id" class=" form-control select2-form select2bs4-form" id="" required>
                        <option value="">Select Branch</option>
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
                    <label for="">Date Range</label>
                </div>
                <div class=" form-group row">
                    <div class=" col-6">
                        <label for="">Select startdate</label>
                        <input type="date"  name="date1" class=" form-control" required max="<?= date("Y-m-d"); ?>" id="">

                    </div>
                    <div class=" col-6">
                        <label for="">Select enddate</label>
                        <input type="date"  name="date2" class=" form-control" required max="<?= date("Y-m-d"); ?>" id="">
                    </div>
                </div>
                

        </div>
        <div class=" card-footer">
            <button type="submit" class=" btn btn-sm btn-block btn-primary">View List</button>
        </div>
        
    </form>
</div>