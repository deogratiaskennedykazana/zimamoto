<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Register Staff Form</h3>
    </div>
    <form class="was-validated" action="./controllers/subsidiary_controller.php" method="post">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label>Select Customer ledger</label>
                        <select name="ledger" class="form-control select2-form select2bs4-form" required>
                            <option value="">Select Below</option>
                            <?php
                            $ledgers = selectAllActiveLedgers($conn);
                            if($ledgers && is_array($ledgers)){
                                foreach($ledgers as $ledger){
                                    echo "<option value='$ledger[id]'>$ledger[name]</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="exampleInputEmail1">Staff Names</label>
                        <input type="text" name="name" maxlength="30" class="form-control" id="exampleInputEmail1" required placeholder="Enter Staff Names">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="exampleInputPassword1">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" id="exampleInputPassword1" required placeholder="Enter Phone Number">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="exampleInputPassword1">Email</label>
                        <input type="email" name="email" class="form-control" id="exampleInputPassword1" required placeholder="Enter Email">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="exampleInputPassword1">TIN</label>
                        <input type="text" maxlength="15" name="tin" class="form-control" id="exampleInputPassword1" placeholder="Enter TIN">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8">
                    <div class="form-group">
                        <label for="exampleInputPassword1">Address</label>
                        <input type="text" name="address" class="form-control" id="exampleInputPassword1" placeholder="Enter Address">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Staff Position</label>
                        <select name="subcategory" class="form-control select2-form select2bs4-form" required>
                            <option value="">Select Below</option>
                            <option value="driver">Driver</option>
                            <option value="security">Security</option>
                            <option value="others">Others</option>
                            <option value="accountant">Accountant</option>
                            <option value="manager">Manager</option>
                            <option value="others">Others</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="registerStaff" class="btn btn-primary w-100">Submit</button>
        </div>
    </form>
</div>