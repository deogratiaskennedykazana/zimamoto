
      
      
          <?php
          session_start();
          if(!$_SESSION){
          //  echo "<script>alert('Authentication failed'); window.location.href='../'</script>";
          }
          
          ?>  
          <!DOCTYPE html>
          <html lang="en">
          <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Ledger report</title>
          </head>
          <body>
            
          
        <?php
     
        include("../configs.php");
      
            require_once "../functions/ledger_functions.php";
            require_once "../functions/subsidiary_functions.php";
            require_once "../functions/min_sub_functions.php";
            require_once "../functions/transaction_functions.php";
            require_once "../functions/sub_transaction_functions.php";
        $conn = openConn();
        //print_r($_POST);
        $date1 = $conn->real_escape_string($_POST['start_date']);
        $date2 = $conn->real_escape_string($_POST['end_date']);
        $ledger_id = (int) $conn->real_escape_string($_POST['ledger']);
        ?>
            <div  class=" container mt-2">
                
            <?php
                $ledgerDetails = selectLedgerById($conn, (int) $conn->real_escape_string($_POST['ledger']));
                if($ledgerDetails && is_array($ledgerDetails)){
                    echo "<h4 class=' text-center text-primary'> $ledgerDetails[name] Ledger Report By ". date("d-M-Y", strtotime( $_POST['start_date'])) ." AND  ". date("d-M-Y", strtotime(  $_POST['end_date'])) ." </h4>";
                } else{
                    exit();
                }


             ?>
             <table class=' table table-sm data-table-basic'>
                <thead>
                        <tr class=" table-success ">
                            <td>#</td>
                            <td>Item Name</td>
                            <td>Debit</td>
                            <td>Credit</td>
                            <td>Balance</td>

                        </tr>
                </thead>
                <tbody>
                <?php
                $ledgerBalance =0;
                    $subsidiaries = selectSubsidiariesBYledgerId($conn, $ledger_id);
                    if($subsidiaries && is_array($subsidiaries)){
                        $counter =1;
                        foreach($subsidiaries as $subsidiary){
                            $sub_balance =0;
                            echo "<tr>";
                                echo "<td>$counter</td>";
                                echo "<td>$subsidiary[name]</td>";
                                //getAttache min sub
                                $minsubs = selectMinSubsidiariesByParentId($conn,$subsidiary['id']);
                                if($minsubs && is_array($minsubs)){
                                    foreach($minsubs as $minsub){
                                        $minSubBalance =0;
                                        $subTransactions  = selectAllSubTransactionByMinSubId($conn,$minsub['id']);
                                        if($subTransactions && is_array($subTransactions)){
                                            foreach($subTransactions as $subTransaction){
                                                if($subTransaction['date_'] <= $date2){
                                                   if($subTransaction['cr_account']== $minsub['id']){
                                                        $minSubBalance -= $subTransaction['cr_ammount'];
                                                   }elseif($subTransaction['dr_account'] == $minsub['id']){
                                                    $minSubBalance += $subTransaction['dr_ammount'];
                                                   }
                                                }
                                            }
                                        }
                                        $sub_balance += $minSubBalance;
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
                                $ledgerBalance += $sub_balance;
                                if($sub_balance>0){
                                    echo "<td>". number_format( $sub_balance, 2)  ."</td><td>0.0</td><td>". number_format($ledgerBalance,2) ."</td>";
                                } elseif($sub_balance<0){
                                    echo "<td>0.0</td><td>". number_format( $sub_balance, 2)  ."</td><td>". number_format($ledgerBalance,2) ."</td>";
                                }else{
                                    echo "<td>0.0</td><td>". number_format( $sub_balance, 2)  ."</td><td>". number_format($ledgerBalance,2) ."</td>";
                                }
                            echo "</tr>";

                            $counter++;
                           
                        }
                    }
                ?>
                <tr class=" table-success">
                    <td>#</td>
                    <td>Item Name</td>
                    <td>Debit</td>
                    <td>Credit</td>
                    <td>Balance</td>

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
