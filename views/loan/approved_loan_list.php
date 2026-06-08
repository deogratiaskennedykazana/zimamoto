<div class=" card card-secondary">
    <div class=" card-header">
        <h5 class=" card-title">Approved loan list</h5>
    </div>
    <div class=" card-body">
        <div class=" table-responsive">
            <table class=" table table-sm table-bordered table-striped">
                <thead>
                    <tr class="table-primary" >
                        <td>#</td>
                        <td>Member Name</td>
                        <td>Principle</td>
                        <td>Interest</td>
                        <td>Interest rate</td>
                        <td>Loan Category</td>
                        <td>Loan Period</td>
                        <td>Provided Date</td>
                        <td>Action</td>
                    </tr>
                    
                </thead>
                <tbody>
                    <?php
                    $branchId=$_SESSION['branchid'];
                        $loans = selectLoansByStatus($conn, 'approved',$branchId);
                        if($loans && is_array($loans)){
                            $counter = 1;
                            foreach($loans as $loan){
                                echo "<tr>";
                                echo "<td>$counter</td>";
                                echo "<td>$loan[name]</td>";
                                echo "<td>". number_format($loan['principle'],2)."</td>";
                                echo "<td>". number_format($loan['interest_amount'],2)."</td>";
                                echo "<td>". number_format($loan['interest_rate'],2)."</td>";
                                echo "<td>$loan[product]</td>";
                                echo "<td>$loan[period]</td>";
                                echo "<td>$loan[approve_date]</td>";
                                echo "<td>";
                                    echo "<a class=' btn btn- btn-sm btn-outline-primary' href='./?page=view_loan_details&loan_id=$loan[id]&user_id=$loan[user_id]' > Preview </a>";
                                echo "</td>";
                                echo "</tr>";
                                $counter++;
                            }
                        }
                    ?>
                </tbody>

            </table>
        </div>
    </div>
</div>