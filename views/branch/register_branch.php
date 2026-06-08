
<div class=" card card-info">
    <div class=" card-header"> <h4 class=" card-title">Register Branch</h4> </div>
    <form action="./controllers/branch_controller.php" method="post" class=" was-validated">
        <div class=" card-body">
            <div class="form-group">
                <label for="branch_name">Branch Name</label>
                <input type="text" class="form-control" id="branch_name" name="name" placeholder="Enter Branch Name" required>
              
            </div>
            <div class="form-group">
                <label for="branch_address">Branch Address</label>
                <input type="text" class="form-control" id="branch_address" name="address" placeholder="Enter Branch Address" required>
            </div>
            <div class="form-group">
                <label for="branch_phone">Branch Phone</label>
                <input type="text" class="form-control" id="branch_phone" name="phone" placeholder="Enter Branch Phone" required>
            </div>
            <div class="form-group">
                <label for="branch_email">Branch Email</label>
                <input type="email" class="form-control" id="branch_email" name="email" placeholder="Enter Branch Email" required>
            </div>
            <div class="form-group">
                <label for="branch_region">region </label>
                <select name="region" class="form-control" id="branch_region">
                    <option value="">Select Region</option>
                    <?php
                            $regions = selectRegions($conn);
                            if($regions && is_array($regions)){
                                foreach($regions as $region){
                                    echo "<option value='{$region['id']}'>{$region['name']}</option>";
                                }
                            }
                    ?>
                </select>
            </div>
            <div class=" card-footer">
                <button type="submit" name="reg_branch" class="btn btn-primary btn-sm btn-block">Register Branch</button>
            </div>
    </form>
</div>