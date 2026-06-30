<div class=" card card-primary">
    <div class=" card-header">
        <h5>Apply Loan</h5>
    </div>
    <form action="./controllers/loan_controller.php" method="post" enctype="multipart/form-data" class=" was-validated">
        <div class=" card-body">
            <div class=" form-group">
                <label for="">Names</label>
                <input type="text" name="" value="<?= htmlspecialchars($_SESSION['username']) ?>"  class=" form-control" readonly>
                <input type="hidden" name="user_id" value="<?= (int) $_SESSION['userid'] ?>">
                <input type="hidden" name="branch_id" value="<?= (int) $_SESSION['branchid'] ?>">
                <small id="loan_capacity" class="text-info"></small>
            </div>
            <div class=" form-group" >
                <label for="">Desired Amount</label>
                <input type="number" name="amount" class=" form-control" min='1' required id="">
            </div>
            <div class=" form-group">
                <label for="">Loan Category</label>
                <select name="loan_type" class=" form-control select2-form select2bs4-form" required id="loan_type_select" onchange="showLoanConditions(this.value)">
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
            <div id="loanConditions" class=" alert alert-info" style="display:none;"></div>

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
var loanTypesData = <?= json_encode($loanTypes ?: []) ?>;
function showLoanConditions(id){
    var box = document.getElementById('loanConditions');
    if(!id){ box.style.display = 'none'; return; }
    var lt = null;
    for(var i=0;i<loanTypesData.length;i++){ if(String(loanTypesData[i].id) === String(id)){ lt = loanTypesData[i]; break; } }
    if(!lt){ box.style.display = 'none'; return; }
    var maxText = parseFloat(lt.max_amount) > 0 ? Number(lt.max_amount).toLocaleString() : 'no fixed cap (savings-based)';
    var html = '<strong>' + lt.name + ' \u2014 Conditions</strong><ul class="mb-0">' +
        '<li>Amount: TZS ' + Number(lt.min_amount).toLocaleString() + ' \u2013 ' + maxText + '</li>' +
        '<li>Repayment period: ' + lt.min_period + ' \u2013 ' + lt.max_period + ' months</li>' +
        '<li>Interest rate: ' + lt.interest_rate + '% per year</li>' +
        '<li>Required guarantors: ' + lt.required_grantors + '</li>' +
        '<li>Max eligible amount is up to ' + lt.savings_multiplier + 'x the member\'s total savings</li>' +
        (lt.eligibility_notes ? '<li>' + lt.eligibility_notes + '</li>' : '') +
        '</ul>';
    box.innerHTML = html;
    box.style.display = 'block';
}
</script>

<script>
    $(document).ready(function(){
        // Populate grantors and loan capacity using the session user's branch/id
        // These hidden inputs don't trigger the 'onchange' events, so we call manually.
        var branchId = <?= (int)$_SESSION['branchid'] ?>;
        var userId   = <?= (int)$_SESSION['userid'] ?>;

        // Populate all .user_id selects (grantor dropdowns) with members from this branch
        // Pass userId too so the server can re-verify the current branch from DB
        // (guards against stale session branchid after an admin changes the member's branch)
        if(branchId || userId){
            $.get('./requests/form_requests.php', { get_members_by_branch_id_json: '', branchId: branchId, userId: userId }, function(data){
                var members = typeof data === 'string' ? JSON.parse(data) : data;
                $('.user_id').each(function(){
                    var sel = $(this);
                    sel.empty().append('<option value="">Select Grantor</option>');
                    $.each(members, function(i, m){
                        if(m.id != userId){ // exclude self (also done server-side)
                            sel.append('<option value="'+m.id+'">'+m.name+'</option>');
                        }
                    });
                });
            });
        }

        // Set loan capacity
        if(userId){
            $.get('./requests/form_requests.php', { get_loan_capacity_by_user_id: '', userId: userId }, function(cap){
                $('#loan_capacity').text('Max eligible: TZS ' + parseFloat(cap || 0).toLocaleString());
            });
        }
    });

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