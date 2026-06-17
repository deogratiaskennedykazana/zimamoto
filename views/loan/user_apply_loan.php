<div class=" card card-primary">
    <div class=" card-header">
        <h5>Apply loan</h5>
    </div>
    <form action="./controllers/loan_controller.php" method="post" class=" was-validated">
        <div class=" card-body">
            <div class=" form-group">
                <label for="">Names</label>
                <input type="text" name="" value="<?= $_SESSION['username'] ?>"  class=" form-control" readonly>
                <input type="hidden" name="user_id" value="<?= $_SESSION['userid'] ?>">
                <input type="hidden" name="branch_id" value="<?= $_SESSION['branchid'] ?>">
            </div>
            <div class=" form-group">
                <label for="">Desired Amount</label>
                <input type="number" name="amount"  class=" form-control" required id="">

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
                    <label for="">Loan term in months</label>
                    <input type="number" class=" form-control" name="loan_term" required id="">
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
                                    <option value="">Select First Grantor</option>
                                </select>
                            </div>
                        </div>
                    </div>

                </div>
        </div>
          <div class=" card-footer">
                <button type="submit" class=" btn btn-info btn-block btn-sm" name="apply_loan">Send Application</button>
             </div>
    </form>
</div>

<script>
    $(document).ready(function(){
        
        getUserOptionByBranch();
           setLoanCapacity();
    });
</script>