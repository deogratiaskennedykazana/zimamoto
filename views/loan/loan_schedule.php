<div class=" card card-secondary">
    <div class=" card-header"> <h5 class=" card-title">Loan Schedule</h5> </div>
    <div class=" card-body">
        <?php
            $userDetails = selectUserById($conn, (int) $_GET['user_id']);
            if($userDetails && is_array($userDetails)){
                echo "<h4>Customer Name: {$userDetails['name']}</h4>";
               echo "<h4>Branch: $userDetails[branch_name] </h4>";
            }
            $loanDetails = selectLoanById($conn, (int) $_GET['loan_id']);
            if($loanDetails && is_array($loanDetails)){
                echo "<h4> Loan Principle: ". number_format( $loanDetails['principle'],2) ." </h4>";
                echo "<h4> Interest:". number_format( $loanDetails['interest_amount'],2) ." </h4>";
                echo "<h4> Interest Rate: $loanDetails[interest_rate]%</h4>";
                echo "<h4> Period: $loanDetails[period] (months) </h4>";
            }
        ?>
    </div>
    <div class=" card-body">
        <table class=" table table-bordered table-sm table-striped table-hover">
            <thead class=" table-primary">
                <tr>
                    <td>#</td>
                    <td>Principle</td>
                    <td>Interest Amount</td>
                    <td>Repayment date</td>
                    <td>Paid Amount</td>
                    <td>Status</td>
                </tr>
            </thead>
            <tbody>
                <?php
                    $schedules = selectLoanScheduleByLoanId($conn, (int) $_GET['loan_id']);
                    if($schedules && is_array($schedules)){
                        $count = 1;
                        foreach($schedules as $schedule){
                            echo "<tr>";
                            echo "<td>$count</td>";
                            echo "<td>". number_format( $schedule['principle'],2) ."</td>";
                            echo "<td>". number_format( $schedule['interest_amount'],2) ."</td>";
                            echo "<td>$schedule[payment_date]</td>";
                            echo "<td>". number_format( $schedule['paid_amount'],2) ."</td>";
                            echo "<td>$schedule[status]</td>";
                            echo "</tr>";
                            $count++;
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>
</div>