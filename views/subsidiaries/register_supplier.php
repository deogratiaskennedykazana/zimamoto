<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Register Supplier Form</h3>
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
                        <label>Supplier Names</label>
                        <input type="text" name="name" maxlength="30" class="form-control" required placeholder="Enter Supplier Names">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" class="form-control"   placeholder="Enter Phone Number">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" class="form-control"   placeholder="Enter Email">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>TIN</label>
                        <input type="text" maxlength="15" name="tin" class="form-control"   placeholder="Enter TIN">
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Address</label>
                        <input type="text" name="address" class="form-control"   placeholder="Enter Address">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>VRN</label>
                        <input type="text" maxlength="20" name="vrn" class="form-control" placeholder="Enter VRN">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Select Supplier Type</label>
                        <select name="subcategory" class="form-control select2-form select2bs4-form" required>
                            <option value="">Select Below</option>
                            <option value="company">Company</option>
                            <option value="person">Person</option>
                            <option value="others">Others</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="registerSupplier" class="btn btn-primary w-100">Submit</button>
        </div>
    </form>
</div>