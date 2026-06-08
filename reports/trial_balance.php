
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trial Balance Report</title>
    <?php
            require_once "../configs.php";
            $conn = openConn();
            // require_once "../links.php";
            require_once "../functions/subsdiary_functions.php";
            require_once "../functions/min_sub_functions.php";
            require_once "../functions/transaction_functions.php";
            require_once "../functions/min_transaction_functions.php";
            require_once "../functions/opening_balance_functions.php";
            //print_r($_POST);
            $date1 = $conn->real_escape_string($_POST['date1']);
            $date2 = $conn->real_escape_string($_POST['date2']);
    ?>
</head>
<body>
        <div class=" container my-2">
            <h4 class=' text-center text-primary'>Trial Balance Report By <?= date("d-M-Y", strtotime($date1)) ?> AND <?= date("d-M-Y", strtotime($date2)) ?>  </h4>
            <table class=' table table-sm data-table-basic'>
                <thead>
                    <tr class=" table-success">
                        <td>#</td>
                        <td>Item Name</td>
                        <td>Credit</td>
                        <td>Debit</td>
                    </tr>
                </thead>
                <tbody>
                    <?php
                        $credit =0;
                        $debit =0;
                        $subsidiaries = getAllSubsidiaries($conn);
                        if($subsidiaries && is_array($subsidiaries)){
                            $counter =1;
                            foreach($subsidiaries as $subsidiary){
                                $sub_balance =0;
                                echo "<tr>";
                                    echo "<td>$counter</td>";
                                    echo "<td>$subsidiary[name]</td>";
                                    //get sub transaction 
                                    $minsubs = selectMinSubsidiariesByParentId($conn,$subsidiary['id']);
                                    if($minsubs && is_array($minsubs)){
                                        foreach($minsubs as $minsub){
                                            $minSubBalance =0;
                                            $subTransactions  = selectAllSubTransactionByMinSubId($conn,$minsub['id']);
                                            if($subTransactions && is_array($subTransactions)){
                                                foreach($subTransactions as $subTransaction){
                                                    if($subTransaction['date_'] <= $date2){
                                                       if($subTransaction['cr_account']== $minsub['id']){
                                                            $minSubBalance -= $subTransaction['amount'];
                                                       }elseif($subTransaction['dr_account'] == $minsub['id']){
                                                        $minSubBalance += $subTransaction['amount'];
                                                       }
                                                    }
                                                }
                                            }
                                            
                                            $sub_balance += $minSubBalance;
                                        } 
                                        $opening_balance = selectOpeningBalance($conn, $subsidiary['id']);
                                        if($opening_balance && is_array($opening_balance)){
                                            if($opening_balance['type'] === 'credit'){
                                                $sub_balance -= $opening_balance['ammount'];
                                            }elseif($opening_balance['type']=== 'debit'){
                                                $sub_balance += $opening_balance['ammount'];

                                            }
                                        }
                                       // echo $sub_balance;
                                    }
                                    //get subsidiary transaction
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
    
                                        if($sub_balance>0){
                                            $debit += $sub_balance;
                                            echo "<td>". number_format( $sub_balance, 2)  ."</td><td>0.0</td>";
                                        } elseif($sub_balance<0){
                                            $credit += $sub_balance;
                                            echo "<td>0.0</td><td>". number_format( $sub_balance, 2)  ."</td>";
                                        }else{
                                            echo "<td>0.0</td><td>". number_format( $sub_balance, 2)  ."</td>";
                                        }
                                    echo "</tr>";

                               

                                $counter++;
                            }
                            echo "<tr class=' table-danger'><td></td><td>Balance</td><td>". number_format($debit,2)."</td><td>". number_format($credit,2)."</td></tr>";
                        }

                    
                    ?>
                    <tr class=" table-success">
                        <td>#</td>
                        <td>Item Name</td>
                        <td>Credit</td>
                        <td>Debit</td>
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
         "paging": true, 
         "pageLength":100,
        
        
    } );
} );
</script>