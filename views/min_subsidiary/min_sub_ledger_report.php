<div class="card card-primary">
    <div class=" card-header" > <h5 class=" card-title">Min Sub ledger Report</h5> </div>
     <div class=" card-body">
        <div>
               <?php
                            $subDetails = selectSubsidiaryById($conn,$subId);
                            if($subDetails && is_array($subDetails)){
                                ?>
                                    <h5 class=" card-text">Subsidiary Name: <?= $subDetails['name'] ?> </h5>
                                    <h5 class=" card-text">Subsidiary Category: <?= $subDetails['category'] ?> </h5>
                                    <h5 class=" card-text">Subsidiary Type: <?= $subDetails['type'] ?> </h5>
                                <?php
                            }
                        ?>
        </div>
    <div class=" table-responsive">
        <div class=" card-footer"> <h5>Distribution</h5> </div>
                             
        <table class=" table table-bordered table-hover data-table">
            <thead>
                <tr>
                    <td>#</td>
                    <td>Details</td>
                     <td>Debit</td>
                    <td>Credit</td>
                   
                    <td>Balance</td>
                </tr>
            </thead>
            <tbody>
                <?php
                    $minsubs = [];
                    if($branchId == 0){
                        $minsubs = selectMinSubsBySubsidiaryId($conn, $subId);
                    } else{
                        $minsubs = selectMinSubsBySubsidiaryIdAndBranchId($conn, $subId, $branchId);
                    }
                    $balance = 0;
                    $creditBalance = 0;
                    $debitBalance = 0;
                    $subBalance =0;
                   if($minsubs && is_array($minsubs)){
                        $counter = 1;
                      foreach($minsubs as $minsub){
                          $minSubBalance = 0;
                          $minSub =0;
                        $credit = 0;
                        $debit = 0;
                        //fetch transaction
                        $mintransactions  = [];
                        if($branchId == 0){
                            $mintransactions = getMinTransactionByMinSubId($conn, $minsub['id']);
                        } else{
                            $mintransactions = getMinTransactionByMinSubIdAndBranchId($conn, $minsub['id'], $branchId);
                        }
                        if($mintransactions && is_array($mintransactions)){
                            foreach($mintransactions as $mintransaction){
                                if($mintransaction['dr_account'] == $minsub['id']){
                                    $minSubBalance += $mintransaction['amount'];
                                   
                                } elseif($mintransaction['cr_account'] == $minsub['id']){
                                    $minSubBalance -= $mintransaction['amount'];
                                    
                                }
                            }
                        }

                        $subBalance+= $minSubBalance;
                        echo "<tr>";
                                echo "<td>$counter</td>";
                                echo "<td>$minsub[name]</td>";
                                if($minSubBalance>0){
                                    $debitBalance += $minSubBalance;
                                     echo "<td>".number_format($minSubBalance,2)."</td><td>".number_format(0,2)."</td><td>".number_format($subBalance,2)."</td>";
                                }elseif($minSubBalance < 0){
                                    $creditBalance += $minSubBalance;
                                     echo "<td>".number_format(0,2)."</td><td>".number_format($minSubBalance,2)."</td><td>".number_format($subBalance,2)."</td>";
                                } else{
                                     echo "<td>".number_format(0,2)."</td><td>".number_format(0,2)."</td><td>".number_format($subBalance,2)."</td>";
                                }
                               

                        echo "</tr>";
                        $counter++;
                        // $balance += $subBalance;
                        $creditBalance += $credit;
                        $debitBalance += $debit;
                       
                      }
                     


                   }
                     $balance += $subBalance;
                      
                        echo "<tr>";
                            echo "<td></td><td colspan=''>Balance</td>";
                            echo "<td>".number_format($debitBalance,2)."</td><td>".number_format($creditBalance,2)."</td><td>".number_format($balance,2)."</td>";
                        echo "</tr>";
                ?>
            </tbody>
        </table>
         </div>  

     </div>
</div>


<script src="./dist/datatable2/jquery-3.7.1.js"></script>
 <script src="./dist/datatable2/datatables.js"></script>
 
 <script>
     $(document).ready(function(){
                 new DataTable('.data-table', {
                     responsive: true,
                     ordering:false,
                     pageLength: 250,
                      lengthMenu: [ [10, 25, 50, 100,500], [10, 25, 50, 100,500] ], // Dropdown options
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