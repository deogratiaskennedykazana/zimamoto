<div class=" card card-secondary card-outline">
        <div class=" card-header"> <h4 class=" card-title">Min Subsidiary Report</h4></div>
        <div class=" card-footer">
            <h5>Date:<?= date("Y-m-d") ?></h5>
            <h5>Branch:All Branches</h5>
            <?php
                    $subDetails = getMinSubById($conn, $minSubId);
                    if($subDetails && is_array($subDetails)){
                        echo "<h5>Min Subsidiary: $subDetails[name]</h5>";
                        echo "<h5>Category: $subDetails[category]</h5>";
                    }
            ?>
        </div>
        <div class=" card-body">
            <table class=" table table-bordered table-sm data-table">
                <thead>
                    <tr class=" bg-primary">
                        <td>#</td>
                        <td>Date</td>
                        <td>Corresponding Account</td>
                        <td>Description</td>
                       
                        <td>Debit Amount</td>
                        <td>Credit Amount</td>
                        <td>Balance</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                            $balance =0;
                            $transactions = getMinTransactionByMinSubId($conn, $minSubId);
                            if($transactions && is_array($transactions)){
                                $index = 1;
                                foreach ($transactions as $transaction) {
                                    echo "<tr>";
                                    echo "<td>$index</td>";
                                    echo "<td>$transaction[date_]</td>";
                                    
                                   
                                    if($transaction['dr_account'] == $minSubId){
                                        echo "<td>$transaction[credit_acc]</td>";
                                        echo "<td>$transaction[description]</td>";
                                        echo "<td>". number_format( $transaction['amount'])."</td>";
                                        echo "<td>". number_format( 0)."</td>";
                                        $balance += $transaction['amount'];
                                    }elseif($transaction['cr_account'] == $minSubId){
                                        echo "<td>$transaction[debit_acc]</td>";
                                        echo "<td>$transaction[description]</td>";
                                        echo "<td>". number_format( 0)."</td>";
                                        echo "<td>". number_format( $transaction['amount'])."</td>";
                                        $balance -= $transaction['amount'];
                                    }
                                    echo "<td>". number_format( $balance)."</td>";
                                    echo "</tr>";
                                    $index++;
                                }
                               
                            }
                    ?>
                </tbody>

            </table>
        </div>
</div>

<script src="./dist/datatable2/jquery-3.7.1.js"></script>
 <script src="./dist/datatable2/datatables.js"></script>
 
 <script>
     $(document).ready(function(){
                 new DataTable('.data-table', {
                     responsive: true,
                     ordering:false,
                     layout: {
                 topStart: {
                     buttons: [
                         'copy', 'excel', 'pdf','print'
                     ]
                 }
             }
         });
     });
 </script>