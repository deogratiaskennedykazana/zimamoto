<div class=" card  card-info ">
    <div class=" card-header">
        <h4 class=" card-title">Pending  Loan List</h4>
    </div>
    
    <div class=" card-body">
        <div class=" table-responsive">
            <table class=" table table-hover table-striped table-bordered">
                <thead>
                    <tr>
                        <th>#</th>
                        <td>Name</td>
                        <td>Principle</td>
                        <td>Interest</td>
                        <td>Interest Rate</td>
                        <td>Period</td>
                        
                        <td>Action</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                            $loanList = selectLoansByStatus($conn, "hq_pending");
                            if($loanList && is_array($loanList)){
                                $count = 1;
                                foreach($loanList as $loan){
                                    echo "<tr>";
                                    echo "<td>$count</td>";
                                    echo "<td>$loan[name]</td>";
                                    echo "<td>". number_format( $loan['principle'],2) ."</td>";
                                    echo "<td>". number_format( $loan['interest_amount'],2 ) ."</td>";
                                    echo "<td>$loan[interest_rate]</td>";
                                    echo "<td>$loan[period]</td>";
                                   
                                    echo "<td><a href='./?page=ln_commetee_process_loan&loan_id=$loan[id]&user_id=$loan[user_id]' class='btn btn-sm btn-primary'>Process Loan</a></td>";
                                    echo "</tr>";
                                    $count++;
                                }
                            } 
                    ?>
                </tbody>
            </table>
        </div>
    </div>
    </div>
</div>