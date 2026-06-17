<div class=" card card-primary">
    <div class=" card-header">
        <h5 class=" card-title">Loan Details </h5>
    </div>
    <div class=" card-body">
        <?php
            $memberDetails = selectUserById($conn, (int) $_GET['user_id']);
            if($memberDetails && is_array($memberDetails)){
                echo "<h5>Member Name:$memberDetails[name] </h5>";
                echo "<h5>Branch:$memberDetails[branch_name] </h5>";
                // print_r($memberDetails);
            }
        ?>
        
        <h5 class=" card-text bg-success p-2">Loan Details</h5>
        <div class=" card-footer">
            <?php
                    $loan = selectLoanById($conn, (int) $_GET['loan_id']);
                    if($loan && is_array($loan)){
                        echo "<h5>Loan Principle: ". number_format( $loan['principle'],2) ." </h5>";
                        echo "<h5> Interest:". number_format( $loan['interest_amount'],2) ." </h5>";
                        echo "<h5> Interest Rate: $loan[interest_rate]%</h5>";
                        echo "<h5> Period: $loan[period] (months) </h5>";
                    }
            ?>
        </div>

        <h5 class=" card-text table-primary p-2">Loan Schedule</h5>
        <div class=" card-footer">
            <div class=" table-responsive">
                <table class=" table table-striped table-bordered table-sm">
                        <tr>
                            <th>#</th>
                            <th>Repayment Date</th>
                            <th>Principle</th>
                            <th>Interest </th>
                            <th>Paid amount</th>
                            <th>Status</th>
                        </tr>
                        <?php
                                $loanSchedule = selectLoanScheduleByLoanId($conn, (int) $_GET['loan_id']);
                                if($loanSchedule && is_array($loanSchedule)){
                                    $counter = 1;
                                    foreach($loanSchedule as $schedule){
                                        echo "<tr>";
                                        echo "<td>$counter</td>";
                                        echo "<td>$schedule[payment_date]</td>";
                                        echo "<td>". number_format($schedule['principle'],2)."</td>";
                                        echo "<td>". number_format($schedule['interest_amount'],2)."</td>";
                                        echo "<td>". number_format($schedule['paid_amount'],2)."</td>";
                                        echo "<td>$schedule[status]</td>";
                                        echo "</tr>";
                                        $counter++;
                                    }
                                }
                        ?>
                </table>
            </div>
        </div>
        

    </div>
</div>