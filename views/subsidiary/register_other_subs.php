<div class="card card-info">
    <div class=" card-header">
        <h4 class=" card-title">Register Other type of Subsidiary</h4>
    </div>
    <form action="./controllers/subsidiary_controller.php" method="post" class=" was-validated">
        <div class=" card-body">
            <div class=" card-group">
                <label for="" class=" form-label"> Select Name</label>
                <input type="text" name="name" required class=" form-control" id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">Select Ledger</label>
                <select name="ledger" id="" class=" select2-form select2bs4-form form-control" required>
                    <option value="">Select Below</option>
                    <?php
                        $ledgers = selectAllLedgers($conn);
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
            <div class=" form-label">
                <label for="" class=" form-label">Type</label>
                <select name="type" class=" form-control" required id="">
                    <option value="">Select Below</option>
                    <option value="others">Others</option>
                    <option value="stock">Stock</option>
                    <option value="asset">Assets</option>
                    <option value="staff">Staff</option>
                </select>
            </div>
            <div class=" form-group">
                <label for="">Select Category</label>
                <select name="category" required class=" form-control" id="">
                    <option value="">select option</option>
                    <option value="person">Person</option>
                    <option value="company">Company / organization</option>
                    <option value="others">Others</option>
                </select>
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">Branch </label>
                <select name="branch" class=" form-control select2-form select2bs4-form" required id="">
                    <?php
                        $branchId = null;
                        if ($_SESSION['role'] === 'accountant' && $_SESSION['userlevel'] === 'branch') {
                            $branchId = $_SESSION['branchid'];  
                        }
            
                        $branches = selectAllBranches($conn, $branchId);
            
                        if ($_SESSION['role'] !== 'accountant' || $_SESSION['userlevel'] !== 'branch') {
                             
                            echo '<option value="">--Select Below--</option>';
                             echo '<option value="0">All Branch</option>';
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
            <div class=" card-footer">
                <button class=" btn btn-primary btn-sm btn-block" type="submit" name="registerothersub">Register Subsidiary</button>
    </form>
</div>