<?php

        print_r($_POST);

        require_once "../configs.php";
        require_once "../functions/master_functions.php";
        require_once "../functions/ledger_functions.php";
        require_once "../functions/subsidiary_functions.php";
        require_once "../functions/sub_transaction_functions.php";
        require_once "../functions/min_sub_functions.php";
        require_once "../functions/transaction_functions.php";

        require_once "../links.php";
        $conn = openConn();
        $date1 =  $conn->real_escape_string($_POST['date1']);
        $date2 =  $conn->real_escape_string($_POST['date2']);
            ?>

            <div class=' container'>

                <h4>Balance  Sheet  between <?= date("d-M-Y", strtotime($date1)) ?> AND  <?= date("d-M-Y", strtotime($date2)) ?></h4>

                <table class=' table table-sm data-table-basic table-bordered '>
                    <thead>
                        <tr class=' table-primary'>
                            <td>#</td>
                            <td>Item name</td>
                            <td>Balance</td>
                        </tr>
                  </thead>
                  <tbody>
                    <?php
                    $libalityEquitBalance =0;
                    $counter =0;
                            $asset = selectMasterByMasterName($conn, "Assets");
                            if($asset && is_array($asset)){
                                $masterBalance =0;
                                echo "<tr class=' table-primary'><td></td><td>$asset[name]</td><td></td></tr>";
                                $submains = selectAllSubmasterByMasterId($conn, $asset['id']);
                                //print_r($submains);
                                if($submains && is_array($submains)){
                                  
                                    foreach($submains as $submain){
                                        $subMainBalance =0;
                                        echo "<tr class=' table-danger'><td></td><td>$submain[name]</td><td></td></tr>";
                                        $ledgers = selectLedgerBySubMainId($conn, $submain['id']);
                                        //print_r($ledgers);
                                        if($ledgers && is_array($ledgers)){
                                            foreach($ledgers as $ledger){
                                                $ledgerBalance =0;
                                                echo "<tr class=' table-secondary'><td></td><td>$ledger[name]</td><td></td></tr>";
                                                $subs = selectSubsidiariesBYledgerId($conn, $ledger['id']);
                                                if($subs && is_array($subs)){
                                                   
                                                    foreach($subs as $sub ){
                                                        $counter++;
                                                        $AccountBalance = 0;
                                                        echo "<tr>";
                                                            echo "<td>$counter</td>";
                                                            echo "<td>$sub[name]</td>";

                                                            // get min transaction
                                                            $subAccounts =selectMinSubsidiariesByParentId($conn, $sub['id']);
                                                            if($subAccounts && is_array($subAccounts)){
                                                                foreach($subAccounts as $subAccount){
                                                                    $minAccountId = $subAccount['id'];
                                                                    $minTransactions =  selectAllSubTransactionByMinSubId($conn, $minAccountId);
                                                                    if($minTransactions && is_array($minTransactions)){
                                                                        foreach($minTransactions as $minTransaction){
                                                                            if($minTransaction['date_'] <= $date2){
                                                                                    if($minTransaction['cr_account'] == $minAccountId){
                                                                                        $AccountBalance -= $minTransaction['cr_ammount'];

                                                                                    } elseif($minTransaction['dr_account'] == $minAccountId){
                                                                                        $AccountBalance += $minTransaction['cr_ammount'];
                                                                                    }
                                                                                }
                                                                        }
                                                                    }
                                                                }
                                                                //get transaction
                                                              

                                                            }
                                                            $transactions = selectAllTransactionsBySubId($conn, $sub['id'] );
                                                            if($transactions && is_array($transactions)){
                                                                foreach($transactions as $transaction){
                                                                    if($transaction['date_'] <= $date2){
                                                                        if($transaction['cr_account'] ==$sub['id']){
                                                                            $AccountBalance -= $transaction['cr_ammount'];
                                                                        } elseif($transaction['dr_account'] == $sub['id']){
                                                                            $AccountBalance += $transaction['cr_ammount'];
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            echo "<td>". number_format( $AccountBalance,2)."</td>";
                                                            echo "</tr>";
                                                            $subMainBalance += $AccountBalance;
                                                            $ledgerBalance += $AccountBalance;
                                                            // $counter++;
                                                        }
                                                      
                                                       
                                                    }
                                                    echo "<tr class=' table-secondary'><td></td><td>$ledger[name] ledger Balance</td><td>". number_format ($ledgerBalance)."</td></tr>";    
                                                }
                                                // echo "<tr class=' table-secondary'><td></td><td>$ledger[name] Balance</td><td>". number_format ($ledgerBalance)."</td></tr>";  
                                            }
                                            echo "<tr class=' table-danger'><td></td><td>$submain[name] balance</td><td>". number_format( $subMainBalance)."</td></tr>";
                                            $masterBalance += $subMainBalance;
                                        }
                                    }
                                    echo "<tr class=' table-primary'><td></td><td>$asset[name] Balance</td><td>". number_format( $masterBalance)."</td></tr>";
                                }
                            $liability = selectMasterByMasterName($conn, "Liabilities");
                            if($liability && is_array($liability)){
                                $masterBalance =0;
                                echo "<tr class=' table-warning'><td></td><td><h2>LIABLITY AND EQUITY</h2></td><td></td></tr>";
                                echo "<tr class=' table-primary'><td></td><td><h3> $liability[name]</h3></td><td></td></tr>";
                                $submains = selectAllSubmasterByMasterId($conn, $liability['id']);
                                //print_r($submains);
                                if($submains && is_array($submains)){
                                 
                                    foreach($submains as $submain){
                                        $subMainBalance =0;
                                        echo "<tr class=' table-danger'><td></td><td>$submain[name]</td><td></td></tr>";
                                        $ledgers = selectLedgerBySubMainId($conn, $submain['id']);
                                        //print_r($ledgers);
                                        if($ledgers && is_array($ledgers)){
                                            foreach($ledgers as $ledger){
                                                echo "<tr class=' table-secondary'><td></td><td>$ledger[name] ledger </td><td></td></tr>";  
                                                $ledgerBalance =0;
                                                $subs = selectSubsidiariesBYledgerId($conn, $ledger['id']);
                                                if($subs && is_array($subs)){
                                                   
                                                    foreach($subs as $sub ){
                                                        $counter++;
                                                        $AccountBalance = 0;
                                                        echo "<tr>";
                                                            echo "<td>$counter</td>";
                                                            echo "<td>$sub[name]</td>";

                                                            // get min transaction
                                                            $subAccounts =selectMinSubsidiariesByParentId($conn, $sub['id']);
                                                            if($subAccounts && is_array($subAccounts)){
                                                                foreach($subAccounts as $subAccount){
                                                                    $minAccountId = $subAccount['id'];
                                                                    $minTransactions =  selectAllSubTransactionByMinSubId($conn, $minAccountId);
                                                                    if($minTransactions && is_array($minTransactions)){
                                                                        foreach($minTransactions as $minTransaction){
                                                                            if($minTransaction['date_'] <= $date2){
                                                                                if($minTransaction['cr_account'] == $minAccountId){
                                                                                    $AccountBalance -= $minTransaction['cr_ammount'];

                                                                                } elseif($minTransaction['dr_account'] == $minAccountId){
                                                                                    $AccountBalance += $minTransaction['cr_ammount'];
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                //get transaction
                                                              

                                                            }
                                                            $transactions = selectAllTransactionsBySubId($conn, $sub['id'] );
                                                            if($transactions && is_array($transactions)){
                                                                foreach($transactions as $transaction){
                                                                    if($transaction['date_'] <= $date2){
                                                                        if($transaction['cr_account'] ==$sub['id']){
                                                                            $AccountBalance -= $transaction['cr_ammount'];
                                                                        } elseif($transaction['dr_account'] == $sub['id']){
                                                                            $AccountBalance += $transaction['cr_ammount'];
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            echo "<td>". number_format( $AccountBalance) ."</td>";
                                                            echo "</tr>";
                                                            $subMainBalance += $AccountBalance;
                                                            $ledgerBalance += $AccountBalance;
                                                            // $counter++;
                                                        }
                                                      
                                                       
                                                    }
                                                    echo "<tr class=' table-secondary'><td></td><td>$ledger[name] ledger Balance</td><td>". number_format ($ledgerBalance)."</td></tr>";  
                                                }

                                            }
                                            echo "<tr class=' table-danger'><td></td><td>$submain[name] balance</td><td>". number_format( $subMainBalance)."</td></tr>";
                                            $masterBalance += $subMainBalance;
                                        }
                                    }
                                    echo "<tr class=' table-primary'><td></td><td>$liability[name] Balance</td><td> ". number_format($masterBalance)."</td></tr>";
                                    $libalityEquitBalance += $masterBalance;
                                }
                            $equity = selectMasterByMasterName($conn, "Capital");
                            if($equity && is_array($equity)){
                                $masterBalance =0;
                               
                                echo "<tr class=' table-primary'><td></td><td><h3> Equity</h3></td><td></td></tr>";
                                $submains = selectAllSubmasterByMasterId($conn, $equity['id']);
                                //print_r($submains);
                                if($submains && is_array($submains)){
                                 
                                    foreach($submains as $submain){
                                        $subMainBalance =0;
                                        echo "<tr class=' table-danger'><td></td><td>$submain[name]</td><td></td></tr>";
                                        $ledgers = selectLedgerBySubMainId($conn, $submain['id']);
                                        //print_r($ledgers);
                                        if($ledgers && is_array($ledgers)){

                                            foreach($ledgers as $ledger){
                                                $ledgerBalance =0;
                                                echo "<tr class=' table-success'><td></td><td>$ledger[name] ledger</td><td></td></tr>";
                                                $subs = selectSubsidiariesBYledgerId($conn, $ledger['id']);
                                                if($subs && is_array($subs)){
                                                   
                                                    foreach($subs as $sub ){
                                                        $counter++;
                                                        $AccountBalance = 0;
                                                        echo "<tr>";
                                                            echo "<td>$counter</td>";
                                                            echo "<td>$sub[name]</td>";

                                                            // get min transaction
                                                            $subAccounts =selectMinSubsidiariesByParentId($conn, $sub['id']);
                                                            if($subAccounts && is_array($subAccounts)){
                                                                foreach($subAccounts as $subAccount){
                                                                    $minAccountId = $subAccount['id'];
                                                                    $minTransactions =  selectAllSubTransactionByMinSubId($conn, $minAccountId);
                                                                    if($minTransactions && is_array($minTransactions)){
                                                                        foreach($minTransactions as $minTransaction){
                                                                            if($minTransaction['date_'] <= $date2){
                                                                                if($minTransaction['cr_account'] == $minAccountId){
                                                                                    $AccountBalance -= $minTransaction['cr_ammount'];

                                                                                } elseif($minTransaction['dr_account'] == $minAccountId){
                                                                                    $AccountBalance += $minTransaction['cr_ammount'];
                                                                                }
                                                                            }
                                                                        }
                                                                    }
                                                                }
                                                                //get transaction
                                                              

                                                            }
                                                            $transactions = selectAllTransactionsBySubId($conn, $sub['id'] );
                                                            if($transactions && is_array($transactions)){
                                                                foreach($transactions as $transaction){
                                                                    if($transaction['date_'] <= $date2){
                                                                        if($transaction['cr_account'] ==$sub['id']){
                                                                            $AccountBalance -= $transaction['cr_ammount'];
                                                                        } elseif($transaction['dr_account'] == $sub['id']){
                                                                            $AccountBalance += $transaction['cr_ammount'];
                                                                        }
                                                                    }
                                                                }
                                                            }
                                                            echo "<td> ". number_format($AccountBalance)."</td>";
                                                            echo "</tr>";
                                                            $subMainBalance += $AccountBalance;
                                                            $ledgerBalance += $AccountBalance;
                                                            // $counter++;
                                                        }
                                                      
                                                       
                                                    }  
                                                    echo "<tr class=' table-secondary'><td></td><td>$ledger[name] ledger Balance</td><td>". number_format ($ledgerBalance)."</td></tr>";
                                                }
                                                
                                            }
                                            echo "<tr class=' table-danger'><td></td><td>$submain[name] balance</td><td>". number_format( $subMainBalance)."</td></tr>";
                                            $masterBalance += $subMainBalance;
                                        }
                                    }
                                    echo "<tr class=' table-primary'><td></td><td>$equity[name] Balance</td><td> ". number_format($masterBalance)."</td></tr>";
                                    $libalityEquitBalance += $masterBalance;
                                }
                                echo "<tr class=' table-info'><td></td><td>Liability & Equity Balance</td><td>". number_format( $libalityEquitBalance,2)."</td></tr>";
                    ?>
                    <tr class=' table-primary'>
                        <td>#</td>
                        <td>Item name</td>
                        <td>Balance</td>
                    </tr>
                    </tbody>
                </table>

            </div>
        <?php



        

?>


<script>
    $(document).ready(function() {
    $('.data-table-basic').DataTable( {
         dom: 'Bfrtip',
        pagingType: 'full_numbers',
        'ordering':false,
         "paging": true, 
         "pageLength":100,
        
        
    } );
} );
</script>