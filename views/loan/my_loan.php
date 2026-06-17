<div class=" card card-info">
    <div class=" card-footer"> 
        <a href="./?page=apply_user_loan" class=" btn btn-primary btn-sm  float-right">Apply loan</a>
    </div>
    <div class=" card-header">
        <h5  class=" card-title">My loans</h5>
    </div>
    <div class=" card-body">
        <table class=" table table-sm table-striped table-bordered">
            <thead>
                <tr>
                    <td>#</td>
                    <td>Disbursed Date</td>
                    <td>Principle</td>
                    <td>Interest</td>
                    <td>Interest Rate</td>
                    <td>Loan Type</td>
                    <td>Loan period (in months)</td>
                    <td>Status</td>
                    <td>Action</td>
                </tr>
            </thead>
            <tbody>
                <?php
                    $loans = selectLoanByUserId($conn, $_SESSION['userid']);
                    if($loans && is_array($loans)){
                        $counter = 1;
                        foreach($loans as $loan){
                            echo "<tr>";
                                    echo "<td>$counter</td>";
                                    echo "<td>$loan[approve_date]</td>";
                                    echo "<td>$loan[principle]</td>";
                                    echo "<td>$loan[interest_amount]</td>";
                                    echo "<td>$loan[interest_rate]</td>";
                                    echo "<td>$loan[name]</td>";
                                    echo "<td>$loan[period]</td>";
                                    if($loan['status'] ==='pending'){
                                        echo "<td> <h5 class=' badge bg-warning'>Pending</h5> </td>";
                                    }elseif($loan['status'] ==='approved'){
                                        echo "<td> <h5 class=' badge bg-success'>Approved</h5> </td>";
                                    } elseif($loan['status'] ==='rejected'){
                                        echo "<td> <h5 class=' badge bg-danger'>Rejected</h5> </td>";
                                    }

                                    echo "<td><a href='./?page=preview_loan&loan_id=$loan[id]' class=' btn btn-sm btn-primary'>View</a></td>";
                            echo "</tr>";
                            $counter++;

                        }
                    }
                ?>
            </tbody>

        </table>
    </div>
</div>