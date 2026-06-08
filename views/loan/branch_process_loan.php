<div class=" card card-info ">
    <div class=" card-header">
        <h4>Loan Processing  </h4>
    </div>
    <div class=" card-body">
         <div class=" card-body">
        <?php
            $userDetails = selectUserById($conn, (int) $_GET['user_id']);
            if($userDetails && is_array($userDetails)){
                echo "<h4>Name: {$userDetails['name']}</h4>";
               echo "<h4>Branch: $userDetails[branch_name] </h4>";
            }
            $loanDetails = selectLoanById($conn, (int) $_GET['loan_id']);
            if($loanDetails && is_array($loanDetails)){
                echo "<h4> Loan Principle Requested: ". number_format( $loanDetails['principle'],2) ." </h4>";
               
                echo "<h4> Period: $loanDetails[period] (months) </h4>";
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
                            $memberDetails = selectMemberByUserId($conn, (int) $_GET['user_id']);
                            if($memberDetails && is_array($memberDetails)){
                                // print_r($memberDetails);
                                ?>
                                    <h4>Names:<?= $userDetails['name'] ?></h4>
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
                                        $accounts = selectMinSubsByUserId($conn, (int) $_GET['user_id']);
                                        if($accounts && is_array($accounts)){
                                            $counter =1;
                                            foreach($accounts as $account){
                                                $accountBalance = 0;
                                                echo "<tr>";
                                                    echo "<td>$counter</td>";
                                                    echo "<td>$account[name]</td>";
                                                   $minTransactions = getMinTransactionByMinSubId($conn, $account['id']);
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
                                        $grantors = selectLoanGrantorByLoanId($conn, (int) $_GET['loan_id']);
                                        if($grantors && is_array($grantors)){
                                            foreach($grantors as $grantor){
                                                echo "<li>$grantor[name]</li>";
                                                // details
                                                echo "<table class=' table table-sm table-bordered'>";
                                                    echo "<tr> <td>#</td> <td>Account</td> <td>Amount</td> </tr>";

                                                    $accounts1 = selectMinSubsByUserId($conn, (int) $grantor['grantor_id']);
                                                    if($accounts1 && is_array($accounts1)){
                                                        $counter =1;
                                                        foreach($accounts1 as $account1){
                                                            $accountBalance = 0;
                                                            echo "<tr>";
                                                                echo "<td>$counter</td>";
                                                                echo "<td>$account1[name]</td>";
                                                            $minTransactions = getMinTransactionByMinSubId($conn, $account1['id']);
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
                                <option value="approved">Approve</option>
                                <option value="rejected">Reject</option>
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