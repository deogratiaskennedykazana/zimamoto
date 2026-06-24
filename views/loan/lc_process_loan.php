<div class=" card card-info ">
    <div class=" card-header">
        <h4>Loan Processing  </h4>
    </div>
    <div class=" card-body">
         <div class=" card-body">
        <?php
            $userDetails = null;
            try {
                $userDetails = selectUserById($conn, (int) $_GET['user_id']);
            } catch (\Throwable $e) {
                error_log('selectUserById failed for user_id=' . ((int) $_GET['user_id']) . ': ' . $e->getMessage());
            }
            if($userDetails && is_array($userDetails)){
                echo "<h4>Name: {$userDetails['name']}</h4>";
                echo "<h4>Branch: $userDetails[branch_name] </h4>";
            } else {
                echo '<div class="alert alert-light border mb-2"><small class="text-muted">Member name/branch unavailable.</small></div>';
            }
            $loanDetails = null;
            try {
                $loanDetails = selectLoanById($conn, (int) $_GET['loan_id']);
            } catch (\Throwable $e) {
                error_log('selectLoanById failed for loan_id=' . ((int) $_GET['loan_id']) . ': ' . $e->getMessage());
            }
            if($loanDetails && is_array($loanDetails)){
                echo "<h4> Loan Principle Requested: ". number_format( $loanDetails['principle'],2) ." </h4>";
                echo "<h4> Period: $loanDetails[period] (months) </h4>";
            } else {
                $loanId = (int) $_GET['loan_id'];
                echo '<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle mr-1"></i> This loan application (#' . $loanId . ') could not be loaded.</div>';
            }
        ?>
    </div>
    <div class=" card-body">
        <div class=" row">
            <div class=" col-md-6 col-sm-10">
                <div class=" card-body">
                    <!-- member details -->

                    <h5>Member`s Details</h5>
                    <?php
                            $memberDetails = null;
                            try {
                                $memberDetails = selectMemberByUserId($conn, (int) $_GET['user_id']);
                            } catch (\Throwable $e) {
                                error_log('selectMemberByUserId failed for user_id=' . ((int) $_GET['user_id']) . ': ' . $e->getMessage());
                            }
                            if($memberDetails && is_array($memberDetails)){
                                // print_r($memberDetails);
                                ?>
                                    <h4>Names:<?= htmlspecialchars($userDetails['name'] ?? ($memberDetails['name'] ?? '—')) ?></h4>
                                    <h4> Branch: <?= $memberDetails['branch'] ?></h4>
                                    <h4>Gender: <?= $memberDetails['gender'] ?> </h4>
                                    <h4>Phone: <?= $memberDetails['phone'] ?> </h4>
                                    <h4> Email: <?= $memberDetails['email'] ?> </h4>
                                    <h4> Nida: <?= $memberDetails['nida'] ?> </h4>
                                    <h4> check No: <?= $memberDetails['check_no'] ?> </h4>
                                    <h4> reg No: <?= $memberDetails['reg_no'] ?> </h4>
                                <?php
                            }
                    ?>
                    <div class=" card-footer"> <h5>Contributions</h5> </div>
                    <div class=" card-body">
                        <div class=" table-responsive">
                            <table class=" table tabe-sm table-striped  table-sm table-bordered">
                                    <tr class=" table-primary">
                                        <td>#</td>
                                        <td>Account Name</td>
                                        <td>Balance</td>
                                    </tr>
                                    <?php
                                        $accounts = [];
                                        try {
                                            $accounts = selectMinSubsByUserId($conn, (int) $_GET['user_id']);
                                        } catch (\Throwable $e) {
                                            error_log('selectMinSubsByUserId failed for user_id=' . ((int) $_GET['user_id']) . ': ' . $e->getMessage());
                                            $accounts = [];
                                        }
                                        if($accounts && is_array($accounts)){
                                            $counter =1;
                                            foreach($accounts as $account){
                                                $accountBalance = 0;
                                                echo "<tr>";
                                                    echo "<td>$counter</td>";
                                                    echo "<td>$account[name]</td>";
                                                   try {
                                                       $minTransactions = getMinTransactionByMinSubId($conn, $account['id']);
                                                   } catch (\Throwable $e) {
                                                       error_log('getMinTransactionByMinSubId failed for min_sub_id=' . $account['id'] . ': ' . $e->getMessage());
                                                       $minTransactions = [];
                                                   }
                                                   if($minTransactions && is_array($minTransactions)){
                                                       foreach($minTransactions as $minTransaction){
                                                           if($minTransaction['dr_account'] == $account['id']){
                                                               $accountBalance += $minTransaction['amount'];
                                                           }elseif($minTransaction['cr_account'] == $account['id']){
                                                               $accountBalance -= $minTransaction['amount'];
                                                           }
                                                       }
                                                   }
                                                    echo "<td>". number_format( $accountBalance,2) ."</td>";
                                                echo "</tr>";
                                                $counter++;
                                            }
                                        }
                                    ?>
                          </table>
                        </div>
                       
                        <div class=" card card-primary card-outline">
                             <div class=" card-header">
                            <h5>Grantors details</h5>
                        </div>
                            <div class=" card-body" >
                                <ul type='1'>
                                    <?php
                                        $grantors = [];
                                        try {
                                            $grantors = selectLoanGrantorByLoanId($conn, (int) $_GET['loan_id']);
                                        } catch (\Throwable $e) {
                                            error_log('selectLoanGrantorByLoanId failed for loan_id=' . ((int) $_GET['loan_id']) . ': ' . $e->getMessage());
                                            $grantors = [];
                                        }
                                        if($grantors && is_array($grantors)){
                                            foreach($grantors as $grantor){
                                                echo "<li>$grantor[name]</li>";
                                                // details
                                                echo "<table class=' table table-sm table-bordered'>";
                                                    echo "<tr> <td>#</td> <td>Account</td> <td>Amount</td> </tr>";

                                                    try {
                                                        $accounts1 = selectMinSubsByUserId($conn, (int) $grantor['grantor_id']);
                                                    } catch (\Throwable $e) {
                                                        error_log('selectMinSubsByUserId (grantor) failed for grantor_id=' . $grantor['grantor_id'] . ': ' . $e->getMessage());
                                                        $accounts1 = [];
                                                    }
                                                    if($accounts1 && is_array($accounts1)){
                                                        $counter =1;
                                                        foreach($accounts1 as $account1){
                                                            $accountBalance = 0;
                                                            echo "<tr>";
                                                                echo "<td>$counter</td>";
                                                                echo "<td>$account1[name]</td>";
                                                            try {
                                                                $minTransactions = getMinTransactionByMinSubId($conn, $account1['id']);
                                                            } catch (\Throwable $e) {
                                                                error_log('getMinTransactionByMinSubId (grantor) failed for min_sub_id=' . $account1['id'] . ': ' . $e->getMessage());
                                                                $minTransactions = [];
                                                            }
                                                            if($minTransactions && is_array($minTransactions)){
                                                                foreach($minTransactions as $minTransaction){
                                                                    if($minTransaction['dr_account'] == $account1['id']){
                                                                        $accountBalance += $minTransaction['amount'];
                                                                    }elseif($minTransaction['cr_account'] == $account1['id']){
                                                                        $accountBalance -= $minTransaction['amount'];
                                                                    }
                                                                }
                                                            }
                                                                echo "<td>". number_format( $accountBalance,2) ."</td>";
                                                            echo "</tr>";
                                                            $counter++;
                                                        }
                                                    }
                                                echo "</table>";
                                            }
                                        }
                                    ?>
                                </ul>

                            </div>
                        </div>
                        
                    </div>
                </div>
            </div>
            <div class=" col-md-5 col-sm-10">
                <div class=" card card-primary card-outline">
                    <div class=" card-header"> <h5>Processing form</h5> </div>
               <?php
                    $branchId = $_GET['branch_id'] ?? $_SESSION['branchid'];
               ?>
                <form action="./controllers/loan_controller.php" method="post" class=" was-validated">
                    <input type="hidden" name="loan_id" value="<?= $_GET['loan_id'] ?>">
                    <input type="hidden" name="user_id" value="<?= $_GET['user_id'] ?>">
                    <input type="hidden" name="branch_id" value="<?= $branchId ?>">
                    <input type="hidden" name="level" value="<?=$_SESSION['userlevel']  ?>">
                    <input type="hidden" name="role" value="<?=$_SESSION['role']  ?>">
                    <div class=" card-body">
                        <div class=" form-group">
                            <label for="">Comment</label>
                           <textarea name="comment" id="" class=" form-control" required></textarea>
                        </div>
                        <div class=" form-group">
                            <label for="">type of Approval</label>
                            <select name="status" id="" class=" form-control" required>
                                <option value="">Select Status</option>
                                <option value="loan_comettee_processed">Approve</option>
                                <option value="hq_loan_officer_rejected">Reject</option>
                            </select>
                        </div>

                        
                </div>
                <div class=" card-footer">
                    <button type="submit" class=" btn btn-sm btn-block btn-primary" name="send_loan_comment">send Comment</button>
                </div>
                </form>
                 </div>
            </div>
        </div>
    </div>
    </div>
</div>