<div class=" card card-info">
    <div class=" card-header"> <h5 class=" card-title">Ledger Report</h5> </div>
    <div class=" card-body">

    
                    <?php
                   // print_r($_POST);
                     $branchId = (int) $_POST['branch'];
                     $ledgerId = (int) $_POST['ledger_id'];
                     $date1 = $conn->real_escape_string($_POST['date1']);
                     $date2 = $conn->real_escape_string($_POST['date2']);
                        $ledgerDetails = selectLedgerById($conn, $ledgerId);
                        if($ledgerDetails && is_array($ledgerDetails)){
                            ?>
                                <h5 class=" card-text">Ledger Name: <?= $ledgerDetails['name'] ?></h5>
                             
                            <?php
                        }
                       
                        $branch = SelectBranchById($conn,$branchId);
                        if($branch && is_array($branch)){
                            ?>
                                <h5 class=" card-text">Branch Name: <?= $branch['name'] ?></h5>
                                <h5 class=" card-text">Branch address: <?= $branch['address'] ?></h5>
                            <?php
                        }
                    ?>
     </div>
     <div class=" card-footer">
        <h5 class=" card-text">Report Distribution</h5>
     </div>
     <div class=" card-body">
                        <table class=" table table-bordered table-striped">
                            <tr>
                                <td>#</td>
                                <td>Details</td>
                                  <td>Credit</td>
                                <td>Debit</td>
                              
                                <td>Balance</td>
                            </tr>
                            <?php
                             $LedgerBalance = 0;
                             $ledgerCredit =0;
                             $ledgerDebeit = 0;
                                $subsidiaries = selectSubsidiaryByLedgerId($conn, $ledgerId);
                                if($subsidiaries && is_array($subsidiaries)){
                                    $counter = 1;
                                   
                                    foreach($subsidiaries as $subsidiary){
                                        $balance =0;
                                        
                                                 $minTransaction = getTransactionBYSubIdAndBranchId($conn,$subsidiary['id'],$branchId);
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
                                                 $openingBalance = selectOpeningBalanceBySubId($conn,$subsidiary['id']);
                                                //print_r($openingBalance);
                                               
                                                if($openingBalance && is_array($openingBalance)){
                                                    if($openingBalance['type'] === 'debit'){
                                                        $balance += (float) $openingBalance['ammount'];
                                                       
                                                    }elseif($openingBalance['type'] ==='credit'){
                                                        $balance -= (float) $openingBalance['ammount'];
                                                       
                                                    }
                                                    
                                                }
                                                 $transactions = selectTransactionBySubIdAndBranchId($conn,$subsidiary['id'],$branchId);
                                                    //  print_r($transactions);
                                                    
                                                        if($transactions && is_array($transactions)){
                                                            foreach($transactions as $transaction){
                                                                    
                                                                    if($transaction['dr_account'] == $subsidiary['id']){
                                                                        $balance += $transaction['dr_ammount']; 
                                                                        
                                                                                                        
                                                                                                    
                                                                                                           

                                                                        
                                                                    }elseif($transaction['cr_account'] == $subsidiary['id']){
                                                                        $balance -= $transaction['dr_ammount']; 
                                                                        
                                                                    }
                                                                
                                                                  
                                                            }
                                                        }
                                                echo "<tr>";
                                                            echo "<td>$counter</td>";
                                                            echo "<td>$subsidiary[name]</td>";
                                                            if($balance <0){
                                                                echo "<td> ". number_format($balance,2)." </td>";
                                                                echo "<td>0</td>";
                                                                echo "<td> ". number_format($balance,2)." </td>";
                                                            }elseif($balance>0){
                                                                echo "<td>0</td>";
                                                                echo "<td> ". number_format($balance,2)." </td>";
                                                                echo "<td> ". number_format($balance,2)." </td>";
                                                            } elseif($balance == 0){
                                                                echo "<td>0</td>";
                                                                echo "<td>0.0</td>";
                                                                echo "<td>0.0</td>";
                                                            }
                                        echo "<tr>";

                                        $counter++;
                                    }
                                }
                            ?>
                        </table>
     </div>

    
</div>