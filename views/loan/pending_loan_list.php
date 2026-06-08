<div class=" card  card-info ">
    <div class=" card-header">
        <h4 class=" card-title">Approved Loan List</h4>
    </div>
    <div class=" card-body">
        <?php
                $branchDetails = SelectBranchById($conn, (int) $_POST['branch_id']);
                if($branchDetails && is_array($branchDetails)){
                    echo "<h4>Branch: {$branchDetails['name']}</h4>";
                    echo "<h4>Start Date: {$_POST['date1']}</h4>";
                    echo "<h4>End Date: {$_POST['date2']}</h4>";
                }
        ?>
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
                            $loanList = selectLoansByStatus($conn, "pending", (int) $_POST['branch_id']);
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
                                   
                                    echo "
                                    <td>
                                    <a href='./?page=process_loan&loan_id=$loan[id]&branch_id=$_POST[branch_id]&user_id=$loan[user_id]' class='btn btn-sm btn-primary'>Process Loan</a> 
                                     <a href='./?page=download_loan_form&loan_id=$loan[id]&branch_id=$_POST[branch_id]&user_id=$loan[user_id]' class='btn btn-sm btn-warning'>Download Loan Form</a>
                                     </td>
                                    ";
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