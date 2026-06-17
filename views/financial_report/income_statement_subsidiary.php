<div class=" card card-info">
    <div class=" card-header"> <h5 class=" card-title">Income Statement by Subsidiary</h5> </div> 
    <div class=" card-body">
    <?php 
            print_r($_POST);
            $branchId = (int) $_POST['branch'];
              $branch = SelectBranchById($conn,$branchId);
                        if($branch && is_array($branch)){
                            ?>
                                <h5 class=" card-text">Branch Name: <?= $branch['name'] ?></h5>
                                <h5 class=" card-text">Branch address: <?= $branch['address'] ?></h5>
                            <?php
                        }
    ?>
    </div>
   <div class=" card-footer"> <h5 class=" card-text">Distribution</h5> </div>
   <div class=" card-body">
                <table class=" table table-bordered table-hover table-striped table-sm">
                    <tr>
                        <td>#</td>
                        <td>Details</td>
                        <td>Amount</td>
                    </tr>
                   <tr>
                    <td colspan="3"> <h3>Sales / Income</h3> </td>

                   </tr>
                   <?php
                    $salesItem = selectCOAByLedgerId($conn, 10);
                    $totalsales = 0;
                    $totalExpenses = 0;

                    if($salesItem && is_array($salesItem)){
                        $counter = 1;
                                foreach($salesItem as $item){
                                    $balance =0;
                                   // $totalExpenses += $item['balance'];
                                          $minTransaction = getTransactionBYSubIdAndBranchId($conn,$item['sub_id'],$branchId);
                                            //  print_r($minTransaction);
                                                if($minTransaction && is_array($minTransaction)){
                                                    foreach($minTransaction as $transaction){
                                                        if($transaction['debit_id'] == $transaction['dr_account']){
                                                            $balance += $transaction['amount'];
                                                        } elseif($transaction['credit_id'] == $transaction['cr_account']){
                                                            $balance -= $transaction['amount'];

                                                        }
                                                    }
                                                }
                                                  $openingBalance = selectOpeningBalanceBySubId($conn,$item['sub_id']);
                                                //print_r($openingBalance);
                                               
                                                if($openingBalance && is_array($openingBalance)){
                                                    if($openingBalance['type'] === 'debit'){
                                                        $balance += (float) $openingBalance['ammount'];
                                                       
                                                    }elseif($openingBalance['type'] ==='credit'){
                                                        $balance -= (float) $openingBalance['ammount'];
                                                       
                                                    }
                                                    
                                                }
                                                 $transactions = selectTransactionBySubIdAndBranchId($conn,$item['sub_id'],$branchId);
                                                    //  print_r($transactions);
                                                    
                                                        if($transactions && is_array($transactions)){
                                                            foreach($transactions as $transaction){
                                                                    
                                                                    if($transaction['dr_account'] == $item['sub_id']){
                                                                        $balance += $transaction['dr_ammount']; 
                                                                        
                                                                                                        
                                                                                                    
                                                                                                           

                                                                        
                                                                    }elseif($transaction['cr_account'] == $item['sub_id']){
                                                                        $balance -= $transaction['dr_ammount']; 
                                                                        
                                                                    }
                                                                
                                                                  
                                                            }
                                                        }

                                    echo "<tr>";
                                    echo "<td>$counter</td>";
                                    echo "<td>$item[subs]</td>";
                                    echo "<td>". number_format($balance,2)."</td>";

                                    echo "</tr>";
                                    $counter++;
                                    $totalsales += $balance;
                            }
                    }
                   ?>
                   <tr>
                    <td colspan="3"> <h3>Expenses</h3> </td>

                   </tr>
                   <?php
                    $expensesItem = selectCOAByLedgerId($conn, 11);
                   

                    if($expensesItem && is_array($expensesItem)){
                        $counter = 1;
                                foreach($expensesItem as $item){
                                    $balance =0;
                                   // $totalExpenses += $item['balance'];
                                          $minTransaction = getTransactionBYSubIdAndBranchId($conn,$item['sub_id'],$branchId);
                                            //  print_r($minTransaction);
                                                if($minTransaction && is_array($minTransaction)){
                                                    foreach($minTransaction as $transaction){
                                                        if($transaction['debit_id'] == $transaction['dr_account']){
                                                            $balance += $transaction['amount'];
                                                        } elseif($transaction['credit_id'] == $transaction['cr_account']){
                                                            $balance -= $transaction['amount'];

                                                        }
                                                    }
                                                }
                                                  $openingBalance = selectOpeningBalanceBySubId($conn,$item['sub_id']);
                                                //print_r($openingBalance);
                                               
                                                if($openingBalance && is_array($openingBalance)){
                                                    if($openingBalance['type'] === 'debit'){
                                                        $balance += (float) $openingBalance['ammount'];
                                                       
                                                    }elseif($openingBalance['type'] ==='credit'){
                                                        $balance -= (float) $openingBalance['ammount'];
                                                       
                                                    }
                                                    
                                                }
                                                 $transactions = selectTransactionBySubIdAndBranchId($conn,$item['sub_id'],$branchId);
                                                    //  print_r($transactions);
                                                    
                                                        if($transactions && is_array($transactions)){
                                                            foreach($transactions as $transaction){
                                                                    
                                                                    if($transaction['dr_account'] == $item['sub_id']){
                                                                        $balance += $transaction['dr_ammount']; 
                                                                        
                                                                                                        
                                                                                                    
                                                                                                           

                                                                        
                                                                    }elseif($transaction['cr_account'] == $item['sub_id']){
                                                                        $balance -= $transaction['dr_ammount']; 
                                                                        
                                                                    }
                                                                
                                                                  
                                                            }
                                                        }

                                    echo "<tr>";
                                    echo "<td>$counter</td>";
                                    echo "<td>$item[subs]</td>";
                                    echo "<td>". number_format($balance,2)."</td>";

                                    echo "</tr>";
                                    $counter++;
                                    $totalExpenses += $balance;
                            }
                    }
                   ?>
                </table>

                <div class=" card-footer">
                    <h5>NetProft/Loss: <?= number_format(($totalsales*-1) - $totalExpenses,2); ?> </h5>
                </div>
   </div>
</div>