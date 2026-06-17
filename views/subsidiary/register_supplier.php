<div class=" card card-info">
    <div class="card-header">
        <h3 class="card-title">Register Supplier</h3>
    </div>
    <form action="./controllers/subsidiary_controller.php" method="post" class=" was-validated">
        <div class="card-body">
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
            <div class="form-group">
                <label for="name"> Supplier Name </label>
                <input type="text" class="form-control" id="name" name="name" placeholder="Enter name" required>
            </div>
            <div class="form-group">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="tell" max='13' class="form-control" id="phone" name="phone" placeholder="Enter phone number" required>
            </div>
            <div class="form-group">
                <label for="email" class="form-label">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="Enter email" required>
            </div>
            <div class="form-group">
                <label for="address" class="form-label">Address</label>
                <input type="text" name="address" class="form-control" placeholder="Enter address" required id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">TIN</label>
                <input type="text" class=" form-control" placeholder=" TIN" name="tin" required  id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">VRN</label>
                <input type="text" class=" form-control" placeholder=" VRN" name="vrn" required  id="">
            </div>
            <div class=" form-label">
                <label for="" class=" form-label"> type</label>
                <select name="type" class=" form-control" required id="">
                    <option value="">Select Below</option>
                    <option value="person">Person</option>
                    <option value="company">Company / organization</option>
                </select>
            </div>
            <div class="form-group">
                <label for="" class="form-label">Branch</label>
                <select name="branch" class="form-control select2-form select2bs4-form" required id="">
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
                <button class=" btn btn-primary btn-sm btn-block" type="submit" name="registersupplier">Register Supplier</button>
            </div>
    </form>


</div>

<script>
    $(document).ready(function(){
        initializeSelect();
    });
</script>