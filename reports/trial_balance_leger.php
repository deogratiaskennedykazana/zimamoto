<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance By Ledger</title>
    <?php
        include("../configs.php");
        
        require_once "../functions/ledger_functions.php";
        require_once "../functions/subsdiary_functions.php";
        require_once "../functions/min_sub_functions.php";
        require_once "../functions/transaction_functions.php";
        require_once "../functions/min_transaction_functions.php";
        require_once "../functions/opening_balance_functions.php";
        // print_r($_POST);
        $conn = openConn();
        $date1 = $conn->real_escape_string($_POST['date1']);
        $date2 = $conn->real_escape_string($_POST['date2']);
        
    ?>

</head>
<body>
    <div class=' container mt-3'>
        <?php echo "<h4>Trial balance ledger Report By ".date("d-M-Y", strtotime($date1))." AND ".date("d-M-Y", strtotime($date2))." </h4>";?>

        <table class=" table table-sm  data-table-basic">
            <thead>
                <tr class=" table-success">
                    <td>#</td>
                    <td>Item</td>
                    <td>Debit</td>
                    <td>Credit</td>
                </tr>
            </thead>
            <tbody>
                <?php
                    $credit =0;
                    $debit = 0;
                    $balance =0;
                    $ledgerDetails = selectAllLedgers($conn);
                    if($ledgerDetails && is_array($ledgerDetails)){
                       
                        $counter =1;
                        foreach($ledgerDetails as $ledgerDetail){
                            $ledgerBalance =0;
                           // echo "<tr class=' table-info'><td>$counter</td><td>$ledgerDetail[name]</td><td></td><td></td></tr>";
                                //get subsidiary

                                $subsidiaries = selectSubsidiaryByLedgerId($conn, (int) $ledgerDetail['id']);
                                if($subsidiaries && is_array($subsidiaries)){
                                   
                                    foreach($subsidiaries as $subsidiary ){
                                        $sub_balance =0;
                                        //check available submin & subtransaction
                                        $minsubs = selectMinSubsidiariesByParentId($conn, $subsidiary['id']);
                                        if($minsubs && is_array($minsubs)){
                                            
                                            foreach($minsubs as $minsub){
                                                $minsub_balance =0;
                                                //get transaction
                                                $minTransactions = selectAllSubTransactionByMinSubId($conn,$minsub['id']);
                                                if($minTransactions && is_array($minTransactions)){
                                                    foreach($minTransactions as $minTransaction){
                                                        if($minTransaction['date_'] <= $date2 ){
                                                            if($minTransaction['cr_account'] == $minsub['id']){
                                                                    $minsub_balance -= $minTransaction['amount'];
                                                            } elseif($minTransaction['dr_account'] == $minsub['id']){
                                                                $minsub_balance += $minTransaction['amount'];

                                                            }
                                                        }

                                                    }
                                                }
                                                $sub_balance += $minsub_balance;
                                            }
                                        }
                                        //get oprning balance
                                        $opening_balance = selectOpeningBalance($conn, $subsidiary['id']);
                                        if($opening_balance && is_array($opening_balance)){
                                            if($opening_balance['type'] === 'credit'){
                                                $sub_balance -= $opening_balance['ammount'];
                                            }elseif($opening_balance['type']=== 'debit'){
                                                $sub_balance += $opening_balance['ammount'];

                                            }
                                        }
                                        //get transaction
                                        $transactions = selectAllTransactionsBySubId($conn,$subsidiary['id']);
                                        if($transactions && is_array($transactions)){
                                            foreach($transactions as $transaction){
                                                if($transaction['dr_account'] == $subsidiary['id']){
                                                    $sub_balance +=$transaction['dr_ammount'];

                                                } elseif($transaction['cr_account'] == $subsidiary['id']){
                                                    $sub_balance -= $transaction['cr_ammount'];
                                                }
                                            }
                                        }
    

                                        // if($sub_balance>0){
                                        //     echo "<tr><td></td><td>$subsidiary[name]</td><td>". number_format($sub_balance,2) ."</td><td>0.00</td></tr>";
                                        // } elseif($sub_balance<0){
                                        //     echo "<tr><td></td><td>$subsidiary[name]</td><td>0.00</td><td>". number_format($sub_balance,2) ."</td></tr>";
                                        // } else{
                                        //     echo "<tr><td></td><td>$subsidiary[name]</td><td>0.00</td><td>". number_format($sub_balance,2) ."</td></tr>";
                                        // }
                                        $ledgerBalance += $sub_balance;
                                    }
                                }

                            
                            // output ledger balance

                            if($ledgerBalance>0){
                                $debit += $ledgerBalance;
                                echo "<tr class=' table-d'><td></td><td>$ledgerDetail[name] Balance</td><td>".number_format($ledgerBalance,2)."</td><td>0.00</td></tr>";
                            } elseif($ledgerBalance<0){
                                $credit -= $ledgerBalance;
                                echo "<tr  class=' table-d'><td></td><td>$ledgerDetail[name] Balance</td><td>0.00</td><td>".number_format($ledgerBalance,2)."</td></tr>";
                            } else{
                                echo "<tr  class=' table-dan'><td></td><td>$ledgerDetail[name] Balance</td><td>0.00</td><td>0.00</td></tr>";
                            }

                            $counter++;
                        }
                    }
                    echo "<tr  class=' table-primary'><td></td><td>Balance</td><td>". number_format($debit,2)."</td><td>".number_format( $credit,2)."</td></tr>"; 
                ?>
                <tr class=' table-success'>
                    <td>#</td>
                    <td>Item</td>
                    <td>Debit</td>
                    <td>Credit</td>
                </tr>
        </tbody>
        </table>
    </div>
</body>
</html>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
         <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
         <script src="https://www.w3schools.com/lib/w3.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
         <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">

<script>
$(document).ready(function() {
    $('.data-table-basic').DataTable( {
         dom: 'Bfrtip',
        pagingType: 'full_numbers',
        'ordering':false,
          
         "pageLength":100,
        
        
    } );
} );
</script>