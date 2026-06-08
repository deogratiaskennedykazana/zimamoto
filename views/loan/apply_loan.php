<div class=" card card-primary">
        <div class=" card-header">  <h4 class=" card-title">Apply Loan</h4> </div>
        <form action="./controllers/loan_controller.php" class=" was-validated" method="post" enctype="multipart/form-data">
            <div class=" card-body">
                <div class=" form-label">
                    <label for=""> Select Branch</label>
                    <select onchange="getUserOptionByBranch()" name="branch_id" class=" form-control select2-form select2bs4-form" id="" required>
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
                <div class=" form-label">
                    <label for="">Select Member</label>
                    <select name="user_id" class="user_id form-control select2-form select2bs4-form" onchange="setLoanCapacity();" id="user_id" required>
                        <option value="">Select Member</option>
                    </select>
                </div>
                <div class=" form-group" >
                    <label for="">Desired Amount</label>
                    <input type="number" name="amount" class=" form-control" min='1'  required id="">
                </div>
                <div class=" form-group">
                    <label for="">Loan Category</label>
                    <select name="loan_type" class=" form-control select2-form select2bs4-form" required id="">
                        <option value=""> select Loan Category</option>
                        <?php
                                $loanTypes = selectLoanTypes($conn);
                                if($loanTypes && is_array($loanTypes)){
                                    foreach($loanTypes as $loanType){
                                        echo "<option value='{$loanType['id']}'>{$loanType['name']}</option>";
                                    }
                                }

                        ?>
                    </select>
                </div>
                 
           
                <div class=" form-group">
                    <label for="">select grantors</label>
                    <div class=" row">
                        <div class=" col-md-6 col-sm-10">
                            <div class=" form-group">
                                <label for="">First Grantor</label>
                                <select name="grantor[]" class=" user_id form-control select2-form select2bs4-form" required id="">
                                    <option value="">Select First Grantor</option>
                                </select>
                            </div>
                        </div>
                        <div class=" col-md-6 col-sm-10">
                            <div class=" form-group">
                                <label for="">Second Grantor</label>
                                <select name="grantor[]" class=" user_id form-control select2-form select2bs4-form" required id="">
                                    <option value="">Select Second Grantor</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class=" form-group">
                    <label for="">Repayment Mode</label>
                    <select name="repayment_mode" class=" form-control" onchange="showRepaymentFields()" id="repaymentMode" required>
                        <option value="">Select Repayment Mode</option>
                        <option value="salary">Salary</option>
                        <option value="standing_order">Standing Order</option>
                    </select>
                </div>

                <!-- Salary Repayment Fields -->
                <div id="salaryFields" style="display: none;">
                    <div class=" form-group">
                        <label for="">Basic Salary</label>
                        <input type="number" name="basic_salary" class=" form-control" id="basicSalary" onchange="calculateTakeHome()">
                    </div>
                    <div class=" form-group">
                        <label for="">Take Home</label>
                        <input type="number" name="take_home" class=" form-control" id="takeHome" onchange="validateTakeHome()">
                        <small id="takeHomeError" style="color: red; display: none;">Take home must be at least 1/3 of basic salary</small>
                    </div>
                    <div class=" form-group">
                        <label for="">Upload Salary Slip</label>
                        <input type="file" name="salary_slip_file" class=" form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class=" form-group">
                        <label for="">Period (months)</label>
                        <input type="number" name="period" class=" form-control" min="1" max="60" id="salaryPeriod" placeholder="1 - 60 months">
                    </div>
                </div>

                <!-- Standing Order Repayment Fields -->
                <div id="standingOrderFields" style="display: none;">
                    <div class=" form-group">
                        <label for="">Upload Standing Order</label>
                        <input type="file" name="standing_order_file" class=" form-control" accept=".pdf,.jpg,.jpeg,.png">
                    </div>
                    <div class=" form-group">
                        <label for="">Period (months)</label>
                        <input type="number" name="period" class=" form-control" min="1" max="36" id="standingOrderPeriod" placeholder="1 - 36 months">
                    </div>
                </div>

             </div>
             <div class=" card-footer">
                <button type="submit" class=" btn btn-info btn-block btn-sm" name="apply_loan">Send Application</button>
             </div>
        </form>
</div>

<script>
function showRepaymentFields() {
    var repaymentMode = document.getElementById('repaymentMode').value;
    var salaryFields = document.getElementById('salaryFields');
    var standingOrderFields = document.getElementById('standingOrderFields');
    
    if (repaymentMode === 'salary') {
        salaryFields.style.display = 'block';
        standingOrderFields.style.display = 'none';
    } else if (repaymentMode === 'standing_order') {
        salaryFields.style.display = 'none';
        standingOrderFields.style.display = 'block';
    } else {
        salaryFields.style.display = 'none';
        standingOrderFields.style.display = 'none';
    }
}

function calculateTakeHome() {
    var basicSalary = document.getElementById('basicSalary').value;
    var takeHomeField = document.getElementById('takeHome');
    
    if (basicSalary) {
        var minTakeHome = basicSalary / 3;
        takeHomeField.placeholder = 'Minimum: ' + minTakeHome.toFixed(2);
    }
}

function validateTakeHome() {
    var basicSalary = document.getElementById('basicSalary').value;
    var takeHome = document.getElementById('takeHome').value;
    var errorMsg = document.getElementById('takeHomeError');
    
    if (basicSalary && takeHome) {
        var minTakeHome = basicSalary / 3;
        
        if (takeHome < minTakeHome) {
            errorMsg.style.display = 'block';
            document.getElementById('takeHome').style.borderColor = 'red';
            return false;
        } else {
            errorMsg.style.display = 'none';
            document.getElementById('takeHome').style.borderColor = '';
            return true;
        }
    }
    return true;
}

</script>