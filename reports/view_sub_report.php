<?php
  session_start();
  if(!$_SESSION){
    echo "<script>alert('SUSSEION EXPIRED'); window.location.href='../../'</script>";
  }
  require_once "../configs.php";
//   require_once "../functions/sub_transaction_functions.php";
  require_once "../functions/subsdiary_functions.php";
  require_once "../functions/min_transaction_functions.php";
  require_once "../functions/min_sub_functions.php";
  require_once "../functions/opening_balance_functions.php";
  require_once "../functions/utilities_functions.php";
  $conn = openConn();
  $currencyValue =1;
//   $currency = selectCurrency($conn, "USD");
//   if($currency && is_array($currency)){
//     $currencyValue = $currency['value'];
//   }
  //print_r($currencyValue);
  ?>
  <head>
    <title>Subsidiary Report</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link rel="stylesheet" href="../dist/css/adminlte.min.css">
    <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../plugins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="../plugins/fontawesome-free/css/all.min.css">

  </head>
  <?php
 
  $sub_id =(int) $conn->real_escape_string($_GET['sub_id']);

  //get data
  echo "<div class='container'>";
       echo "<h4 class=' text-center text-primary '>Subsidiary Account report</h4>";
        $sub_details = selectSubsidiaryById($conn, $sub_id);
        if($sub_details && count($sub_details)>0){
          echo "<h4>Subsidiary: $sub_details[name]</h4>";
        }
          echo "<table class=' table table-hover table-bordered data-table-basic'>";
              echo "<thead><tr class=' table-primary'><td>#</td><td>Date</td><td>Corresponding</td><td>Description</td><td>Debit Amount</td><td>Credit Amount</td><td>Balance</td></tr></thead>";
              echo "<tbody>";
              $balance =0;
              $credit =0;
              $debit =0;
              $counter =1;
              //get opening balance
              $openingBalance = selectOpeningBalance($conn, $sub_id);
            //  print_r($openingBalance);
              if($openingBalance && is_array($openingBalance)){
                if($openingBalance['type'] ==='credit'){
                   $balance -= $openingBalance['ammount'];
                  echo "<tr>";
                      echo "<td>$counter</td>";
                      echo "<td>$openingBalance[date_]</td>";
                      echo "<td>Opening Balance </td>";
                      echo "<td>Opening Balance </td>";
                      echo "<td>0.0 </td>";
                      echo "<td>". number_format($balance)."</td>";
                      echo "<td>". number_format($balance)."</td>";
                  echo "</tr>";
                  $counter +=1;
                } elseif($openingBalance['type'] ==='debit'){
                  $balance += $openingBalance['ammount'];
                      echo "<tr>";
                          echo "<td>$counter</td>";
                          echo "<td>$openingBalance[date_]</td>";
                          echo "<td>Opening Balance </td>";
                          echo "<td>Opening Balance </td>";
                         
                          echo "<td>". number_format($balance)."</td>";
                          echo "<td>0.o </td>";
                          echo "<td>". number_format($balance)."</td>";
                      echo "</tr>";
                      $counter +=1;
                }
               
              }
                //get balance of attach minor sub
                $minor_subs = selectMinSubsidiariesByParentId($conn,$sub_id);
                if($minor_subs && count($minor_subs)>0){
                  foreach($minor_subs as $minor_sub){
                    $subTransaction = selectMinSubLastBalance($conn, (int)$minor_sub['id'], date("Y-m-d"));
                    if($subTransaction){
                      $balance += $subTransaction;
                    }
                  }
                }
               
                  echo "<tr>
                            <td>$counter</td>
                            <td></td>
                            <td>Last Balance By Attached min subsidiaries</td>
                            <td></td>";
                            if($balance>=0){
                              $debit += $balance;
                              echo "<td>".number_format($balance,2)."</td>";
                              echo "<td>0.00</td>";
                              echo "<td>". number_format(  $balance/$currencyValue,2)."</td>";
                            }else{
                              echo "<td>0.00</td>";
                              echo "<td>".number_format($balance,2)."</td>";
                            
                              $credit += $balance;
                            }
                            echo "<td>".number_format($balance,2)."</td>";
                  echo "</tr>";
                  
                        $transactions = selectAllTransactionsBySubId($conn, $sub_id);
                       
                        if($transactions && count($transactions)>0){
                         // echo count($transactions);
                          foreach($transactions as $transaction){
                            $counter++;
                            echo "<tr>";
                            echo "<td>$counter</td>";
                            echo "<td>". date("d-M-Y", strtotime( $transaction['date_'])) ."</td>";
                            echo "<td>";
                              if($transaction['dr_account'] == $sub_id){
                                    $corresponding = selectSubsidiaryById($conn, (int) $transaction['cr_account']);
                                    echo $corresponding['name'];
                              }elseif($transaction['cr_account']== $sub_id){
                                $corresponding = selectSubsidiaryById($conn, (int) $transaction['dr_account']);
                                echo $corresponding['name'];
                              }
                           echo "</td>";
                           echo "<td>$transaction[description]</td>";
                           if($transaction['dr_account'] == $sub_id){
                                $balance += $transaction['dr_ammount'];
                                $debit += $transaction['dr_ammount'];
                                echo "<td>". number_format($transaction['dr_ammount'],2)."</td>";
                                echo "<td>0.0</td>";
                                echo "<td>". number_format( $transaction['dr_ammount']/$currencyValue,)."</td>";
                          }elseif($transaction['cr_account']== $sub_id){
                            $balance -= $transaction['dr_ammount'];
                            $credit += $transaction['dr_ammount'];
                            echo "<td>0.00</td>";
                            //$balance -= $transaction['dr_ammount'];
                            echo "<td>". number_format($transaction['dr_ammount'],2)."</td>";
                            echo "<td>". number_format( $transaction['dr_ammount']/$currencyValue,2)."</td>";
                          }
                          echo "<td>".number_format($balance,2)."</td>";
                           echo "</tr>";
                          }
                        }
                  
             echo "<tr class=' table-secondary'><td></td><td></td><td></td><td>Total</td> <td>". number_format ($debit,2)."</td><td>".number_format( $credit,2)."</td><td>". number_format( $balance/$currencyValue,2)."</td><td>". number_format( $balance,2)."</td></tr>";
             echo "<tr class=' table-primary'><td>#</td><td>Date</td><td>Corresponding</td><td>Description</td><td>Debit Amount</td><td>Credit Amount</td><td>USD Equivalent</td><td>Balance</td></tr>";
              

              echo "</tbody>";
          echo "</table>";
 echo  "</div>";

?>

<!-- jQuery -->
<script src="../plugins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- DataTables  & Plugins -->
<script src="../plugins/datatables/jquery.dataTables.min.js"></script>
<script src="../plugins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plugins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plugins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../plugins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../plugins/jszip/jszip.min.js"></script>
<script src="../plugins/pdfmake/pdfmake.min.js"></script>
<script src="../plugins/pdfmake/vfs_fonts.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../plugins/datatables-buttons/js/buttons.colVis.min.js"></script>

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