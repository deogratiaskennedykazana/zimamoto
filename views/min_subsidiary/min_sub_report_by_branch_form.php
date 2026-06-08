<div class=" card card-primary ">
    <div class=" card-header"> <h4 class=" card-title">Min Subsidiary Report By Branch</h4></div>
    <form action="./?page=view_min_sub_report_by_branch" method="post" class=" was-validated">
        <div class=" card-body">
           <div class="form-group">
                <label for="" class="form-label">Branch</label>
                <select name="branch" onchange="fetchMinSubByBranch()" class="form-control select2-form select2bs4-form" required id="">
                    <option value="" selected>--Select Below--</option>
                    <?php
                        $branchId = null;
            
                         
                        if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                            $branchId = $_SESSION['branchid'];
                        }
            
                        
                        $branches = selectAllBranches($conn, $branchId);
            
                        if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                            
                            echo '<option value="0">All Branch</option>';
                        }
            
                        if ($branches && is_array($branches)) {
                            foreach ($branches as $result) {
                                
                                echo "<option value='{$result['id']}'>{$result['name']}</option>";
                            }
                        }
                    ?>
                </select>
            </div>

            <div class=" form-group">
                <label for="" class=" form-label">Min Subsidiary</label>
                <select name="min_sub"  class=" min_item data form-control select2-form select2bs4-form" required  id="">
                    <option value="">Select below</option>
                </select>
            </div>
        </div>
        <div>
            <div class=" card-footer">
                <button type="submit" class=" btn btn-sm btn-info btn-block">View Report</button>
            </div>
        </div>
    </form>
</div>