<div class=" card card-info">
    <div class=" card-header"> <h4 class=" card-title">Register Member</h4> </div>
    <form action="./controllers/member_controllers.php" class=" was-validated" method="post">
        <div class=" card-body">
            <div class=" form-group">
                <label for="" class=" form-label"> Names </label>
                <input type="text" name="name" class=" form-control"  required >
            </div>
            <div class=" form-group" >
                <label for="" class=" form-label">Reg No</label>
                <input type="text" name="reg_no" class=" form-control" required id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label">branch</label>
                <select name="branch_id" class=" form-control select2-form select2bs4-form" required  id="">
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
                <label for="" class=" form-label"> NIDA number</label>
                <input type="text" name="nida" required class=" form-control" maxlength="20" id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label"> Phone number</label>
                <input type="tell" name="phone" required class=" form-control" maxlength="13" id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label"> Check number</label>
                <input type="tell" name="check" required class=" form-control" maxlength="13" id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label"> Email</label>
                <input type="email" name="email" required class=" form-control" maxlength="43" id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label"> Address</label>
                <input type="text" name="address" required class=" form-control" maxlength="13" id="">
            </div>
            <div class=" form-group">
                <label for="" class=" form-label"> Select District</label>
                <select name="ditrictId" class=" form-control select2-form select2bs4-form" required id="">
                    <option value="">Select below</option>
                    <?php
                            $wilayas = selectDistricts($conn);
                            if($wilayas && is_array($wilayas)){
                                foreach($wilayas as $wilaya){
                                    echo "<option value='$wilaya[id]'>$wilaya[name]</option>";
                                }
                            }
                    ?>
                </select>
            </div>
            
            <div class=" form-group">
                <label for="" class=" form-label"> Gender</label>
                <select name="gender" class=" form-control select2-form select2bs4-form" required id="">
                    <option value="">Select Below</option>
                    <option value="male">Male</option>
                    <option value="female">Female</option>

                </select>
            </div>
            <div class=" form-group">
                <label for="">Birthdate</label>
                <input type="date" name="birthdate" class=" form-control" required max="<?= date("Y-m-d") ?>" id="">
            </div>
            <div class=" form-group">
                <label for="">Password</label>
                <input type="password" name="password" class=" form-control" required  id="">
            </div>
        </div>
        <div class=" card-footer">
            <button type="submit" class=" btn btn-primary btn-sm btn-block" name="register_member">Register Member</button>
        </div>
    </form>
</div>