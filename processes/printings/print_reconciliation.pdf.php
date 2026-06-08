
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>print </title>
    <link rel="stylesheet" href="../../dist/css/adminlte.css">
</head>
<?php   
        include "../../functions/subsidiary_functions.php";
        include "../../functions/transaction_functions.php";
        include "../../configs.php";
        $conn = openConn();
        $date1 = $conn->real_escape_string($_GET['date1']);
        $date2 = $conn->real_escape_string($_GET['date2']);
        $bankId = (int) $conn->real_escape_string($_GET['id']);

        
?>
<body class=" conatainer my-3">
<div class=" card card-info invoice">
    <div class=" card-header">
        <h4 class=" card-title text-center"> Bank Reconciliation statemet  as of  <?=  $date1 ?>  to  <?= $date2 ?></h4>
    </div>
        <div class=" box card-body">
            <div class=" container">
                <?php
                    $sub  = selectSubsidiaryById($conn, $bankId);
                    if($sub && is_array($sub)){
                        ?>  
                            <h5>Bank Name: <?= $sub['name'] ?> </h5>
                            <h5>Date: <?= date("d-M-Y", strtotime($date1)) ?> & <?= date("d-M-Y", strtotime($date2)) ?> </h5>
                        <?php
                    }
                ?>
            </div>
        </div>
        <div class="card-body  table-responsive">
                <table class=" table table-striped table-bordered table-sm">
                    <tr>
                        <td>#</td>
                       
                        <td>Date</td>
                        <td>Acountt</td>
                    
                        <td>Debit</td>
                        <td>Credit</td>
                    </tr>
                    <?php
                            $totalDebit =0;
                            $totalCredit =0;
                            $counter =1;
                            $transactions = selectReconciliationTransaction($conn, $bankId,$date1,$date2,"no");
                         //   print_r($transactions);
                            if($transactions && is_array($transactions)){
                                foreach($transactions as $transaction){
                                    echo "<tr>";
                                        echo "<td>$counter</td>";
                                        echo "<td>$transaction[date_]</td>";
                                        echo "<td>";
                                            if($bankId == $transaction['dr_account']){
                                                echo $transaction['credit_acc'];
                                            } elseif($bankId == $transaction['cr_account']){
                                                echo $transaction['debit_acc']; 
                                            }
                                        echo "</td>";
                                       
                                            if($bankId == $transaction['dr_account']){
                                                $totalDebit += $transaction['dr_ammount'];
                                                echo "<td>" . number_format($transaction['dr_ammount'],2) ."</td><td></td>";
                                            } elseif($bankId == $transaction['cr_account']){
                                                    $totalCredit += $transaction['cr_ammount'];
                                                echo "<td></td><td>" . number_format($transaction['dr_ammount'],2) ."</td>";
                                            }
                                      
                                        // echo "<td> ".  number_format( $transaction['dr_ammount'])."</td>";
                                        // echo "<td>$transaction[credit_acc]</td>";
                                      
                                    echo "</tr>";
                                    $counter++;
                                }
                            }
                    ?>
                    <tr class=" bg-danger">
                        <td  colspan="3" >Total</td>
                        <td><?= number_format($totalDebit,2) ?></td>
                        <td><?= number_format($totalCredit,2) ?></td>
                    </tr>
                </table>
                <div class=" my-3 ">
                    <h5>Book Balance as per Bank statement <?= number_format( $totalDebit - $totalCredit,2 ) ?> </h5>
                </div>
        </div>
       
    </form>
</div>
</body>
</html>


<script>
  window.addEventListener("load", window.print());
  window.addEventListener("afterprint", function () {
    window.close(); // Go back to the previous page
  });
</script>