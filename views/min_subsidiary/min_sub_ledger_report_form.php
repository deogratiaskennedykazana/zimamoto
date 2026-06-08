<div class=" card card-primary">
    <div class=" card-header" > <h5 class=" card-title">Min Sub ledger Report</h5> </div>
    <form action="./?page=min_sub_ledger_report" method="post" class="was-validated">
        <div class=" card-body">
            <div class=" form-group">
                <label for="">Select Branch</label>
                <select name="branch_id" class=" form-control select2-form select2bs4-form" required  id="">
                    
                    <?php
                                                $branchId = null;
                                                if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                                                    $branchId = $_SESSION['branchid'];  
                                                }
                                    
                                                $branches = selectAllBranches($conn, $branchId);
                                    
                                                if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                                                    echo '<option value="">--Select Below--</option>';
                                                 echo "     <option value='0'>All Branch</option>";
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
                <label for="">Select Sub ledger</label>
                <select name="sub_id" class=" form-control select2-form select2bs4-form" required id="">
                    <option value="">Select below</option>
                    <?php
                        $subs = getAllSubsidiaries($conn);
                        if($subs && is_array($subs)){
                            foreach($subs as $sub){
                                echo "<option value='$sub[id]'>$sub[name]</option>";
                            }
                        }
                    ?>
                </select>
            </div>
        </div>
        <div class=" card-footer">
                <button type="submit" class=" btn btn-sm btn-info btn-block">View Report</button>
        </div>
    </form>
</div>