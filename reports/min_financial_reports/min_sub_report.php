<?php
        session_start();
        if(!$_SESSION){
            echo "<script>alert('SUSSEION EXPIRED'); window.location.href='../../'</script>";
        }

        require_once "../../configs.php";
        require_once "../../functions/min_sub_functions.php";
        require_once "../../functions/sub_transaction_functions.php";
        // require_once "../../links.php";

        $conn = openConn();
        //get data
        echo "<div class='container'>";
        echo "<h4 class=' text-center text-primary'>Sub Account report</h4>";
        $min_sub_id = (int) $conn->real_escape_string($_POST['min_sub_id']);

        $sub_details = selectMinSubsidiaryById($conn, $min_sub_id);
        if($sub_details && count($sub_details)>0){
            echo "<h3>Sub Account Name: $sub_details[name]</h3>";
        }
        echo "<table class=' table table-bordered table-hover data-table-basic'><thead>";
            echo "<tr class=' table-primary'><td>#</td><td>Corresponding/details</td><td>Description</td><td>Dr Amount</td><td>Cr Amount</td><td>balance</td></tr></thead>";
            echo "<tbody>";
            $balance =0;
            $credit =0;
            $debit =0;
            $openingBalance = selectOpeningBalanceByMinSubId($conn,$min_sub_id);
            if($openingBalance && is_array($openingBalance)){   
                if($openingBalance['type'] === 'debit'){
                    $balance += $openingBalance['amount'];
                    $credit += $openingBalance['amount'];
                    echo "<tr>";
                        echo "<td>1</td>";
                        echo "<td>Opening Balance</td>";
                        echo "<td>Opening Balance</td>";
                        echo "<td>$openingBalance[amount]</td>";
                        echo "<td>0.00</td>";
                        echo "<td>$openingBalance[amount]</td>";
                    echo "</tr>";
                } elseif($openingBalance['type'] === 'credit'){
                    $balance -= $openingBalance['amount'];
                    $debit -= $openingBalance['amount'];
                    echo "<tr>";
                        echo "<td>1</td>";
                        echo "<td>Opening Balance</td>";
                        echo "<td>Opening Balance</td>";
                        echo "<td>0.00</td>";
                        echo "<td>$openingBalance[amount]</td>";
                        
                        echo "<td>$openingBalance[amount]</td>";
                    echo "</tr>";
                }
                
            }
            $transactions = selectAllSubTransactionByMinSubId($conn, $min_sub_id);
            if($transactions && count($transactions)>0){
                $counter =1;
                foreach($transactions as $transaction){
                    echo "<tr>";
                            echo "<td>$counter</td>";
                            echo "<td>";
                           
                            if($transaction['dr_account'] == $min_sub_id){
                                $corresponding = selectMinSubsidiaryById($conn, $transaction['cr_account']);
                                echo $corresponding['name'];
                            } elseif($transaction['cr_account'] == $min_sub_id ){
                                $corresponding = selectMinSubsidiaryById($conn, $transaction['dr_account']);
                                echo $corresponding['name'];
                            }
                            echo "</td>";
                            echo "<td>$transaction[description]</td>";
                            if($transaction['dr_account'] == $min_sub_id){
                                $balance += $transaction['dr_ammount'];
                                $debit += $transaction['dr_ammount'];
                                echo "<td>"; 
                                 echo number_format( $transaction['dr_ammount'],2);
                                 echo "</td>";
                                 echo "<td>0.00</td>";
                            } elseif($transaction['cr_account'] == $min_sub_id ){
                                $balance -= $transaction['cr_ammount'];
                                $credit += $transaction['cr_ammount'];
                                echo "<td>0.00</td>";
                                echo "<td>"; 
                                echo  number_format( $transaction['cr_ammount'],2);
                                echo "</td>";
                                
                            }

                            echo "<td>$balance</td>";
                            

                          
                    echo "</tr>";

                    $counter++;
                }
                echo "</tbody><tfoot><tr class=' table-secondary'><th></th><th>Total</th><th><th>". number_format( $debit,2)."</td><td>". number_format($credit,2)."</th><th>". number_format($balance,2)."</th></th></tr>";
            }
            echo "<tr class=' table-primary'><th>#</th><td>Corresponding/details</th><th>Description</th><th>Dr Amount</th><th>Cr Amount</th><td>balance</th></tr></tfoot>";
        echo "</table>";

        echo "</div>";



?>
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