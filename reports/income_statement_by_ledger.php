





<?php
include("../configs.php");
include("../links.php");
$start_date = $_POST['date1'];
$end_date = $_POST['date2'];
?>
<!-- get company details -->
<?php
    $company_sql = "SELECT * FROM company_details";
    $company_result = $conn->query($company_sql);
    if($company_result->num_rows>0){
        $company_row = $company_result->fetch_assoc();

    }
?>
<div class="w3-container">
    <h3>COMPREHENISVE INCOME STATEMENT  </h3>
    <h4><?php echo $company_row['title'] ?></h4>
    <h5>ACCOUNTING PERIOD BETWEEN <?php echo date("d/m/Y", strtotime($start_date)) . " - " . date("d/m/Y", strtotime($end_date))  ?> </h5>
    
</div>

<?php
$master_name = '';
$submain = '';
$submain_name_array = array();
$submain_id_array = array();
$master_name_array = array();
$master_id_array = array();
$current_master = null;
$processed_ledgers = array(); // Keep track of processed ledgers
$total_expenses = 0;
$total_sales = 0;
$master_total =0;



// Select master
$sql1 = "SELECT * FROM master ORDER BY id ";
$result1 = $conn->query($sql1);
if ($result1->num_rows > 0) {
    while ($row1 = $result1->fetch_assoc()) {
        $master_name_array[] = $row1['name'];
        $master_id_array[] = $row1['id'];
    }
}

   
echo "<div class=' w3-row'>";
            echo "<div class=' w3-quarter w3-text-white'>.</div>";
            echo "<div class=' w3-padding  '>";
            echo "<table class=' w3-table-all w3-card   '>";
            echo "<tr class=' w3-yellow'><td>Particular</td><td>Amount</td>";

for ($i = 0; $i < count($master_name_array); $i++) {
    $master_balance = 0; // Initialize master balance for each master
        //trying to hide some master and their data
    $master_css_class = "";
    
    $class_hide ='';
    if($master_name_array[$i] === "Assets" || $master_name_array[$i] === "Liabilities" || $master_name_array[$i] === "Capital"  ){
        $master_css_class = "w3-hide ";
        $class_hide ="w3-hide";
    } else{
        $master_css_class = '';
        $class_hide = '';
    }
    
        

    
    $master_id = $master_id_array[$i];
    // echo " <h3 class='  w3-padding w3-text-blue  $master_css_class'>{$master_name_array[$i]}</h3>";
    echo "<tr class='$class_hide w3-text-blue'><td> <h3>$master_name_array[$i]</h3></td><td></td></tr>";

    // Get submaster
    $sql2 = "SELECT * FROM submain WHERE master_id='$master_id' ";
    $result2 = $conn->query($sql2);
    if ($result2->num_rows > 0) {

        while ($row2 = $result2->fetch_assoc()) {
            
            $balance =0;
            $submain_id_array[] = $row2['id'];
            $submain_name_array[] = $row2['name'];
            $submain_id = $row2['id'];
            $submain_name = $row2['name'];
            
            $submaster_css_class = '';
            // echo $master_id . " " . $submain_name;
            
            
            // echo "<h4 class=' w3-text-blue'> $submain_name</h4>";
            // echo "<h4 class=' w3-text-blue $master_css_class'> $submain_name</h4>";
             echo "<tr class=' $class_hide w3-hover-yellow'><td><h4 class=' w3-text-blue'> $master_name_array[$i] - $submain_name</h4></td><td></td><tr>";
            // echo "<tr><td>Ledger</td><td>Balance</td></tr>";

            // Get ledger
            $sql3 = "SELECT ledgers.id as ledger_id, ledgers.name as ledger FROM ledgers WHERE ledgers.submain_id='$submain_id' ";
            $result3 = $conn->query($sql3);
            if($result3->num_rows>0){
            while($row3 = $result3->fetch_assoc()){
                $ledger_balance=0;
                $ledger_id = $row3['ledger_id'];
                $ledger_name = $row3['ledger'];

                // let us  select subsidiaries
                $sql4 = "SELECT * FROM `subsidiaries` WHERE ledger_id='$ledger_id'";
                $result4 = $conn->query($sql4);
                if($result4->num_rows>0){
                    $credit = 0;
                    $debit =0;
                    while($row4 = $result4->fetch_assoc()){
                        $account_id = $row4['id'];

                        //get opening balance
                        $sql5 = "SELECT * FROM starting_balance WHERE account_id='$account_id' AND date_ BETWEEN '$start_date' AND '$end_date'";
                        $result5 = $conn->query($sql5);
                        if($result5->num_rows>0){
                            $row5 = $result5->fetch_assoc();
                            $opening_balance_amount = $row5['ammount'];
                            $opening_balance_type = $row5['type'];
                            if($opening_balance_type === 'debit'){
                                $ledger_balance +=$opening_balance_amount;
                                $credit =0;
                                $debit += $opening_balance_amount;

                            } elseif($opening_balance_type === 'credit'){
                                $debit =0;
                                $credit += $opening_balance_amount;
                                $ledger_balance -= $opening_balance_amount;
                            }
                        }
                        //time for transactions
                        $sql6 = "SELECT t.*, s.name AS account_name FROM transaction_voucher t
                                LEFT JOIN subsidiaries s ON s.id = t.cr_account
                                WHERE (t.dr_account = '$account_id' OR 
                                t.cr_account = '$account_id') AND t.date_ BETWEEN 
                                '$start_date' AND '$end_date' ORDER BY t.date_";
                            
                        $result6 = $conn->query($sql6);
                        if($result6->num_rows>0){
                            while($row6 = $result6->fetch_assoc()){
                                if($row6['cr_account'] == $account_id){
                                    $credit += $row6['cr_ammount'];
                                    $ledger_balance -= $row6['cr_ammount'];
                                    $debit +=0;
                                } elseif($row6['dr_account'] == $account_id){
                                    $debit += $row6['dr_ammount'];
                                    $credit += 0;
                                    $ledger_balance += $row6['dr_ammount'];
                                    
                                }
                            }
                        }
                       
                    }

                }
                if($ledger_name === 'Net Profit/Loss'){
                    $ledger_class= ' w3-blue';
                } 
                else{
                    $ledger_class = '';
                }
                echo "<tr class= ' $class_hide w3-hover-blue $ledger_class'><td>$ledger_name</td><td>". number_format($ledger_balance,2) ."</td></tr>";
                $balance += $ledger_balance;
            }

        }
        
        echo "<tr class= ' $class_hide w3-hover-blue w3-red'><td> Total $submain_name </td> <td>". number_format( $balance,2) ."</td></tr>";
         $master_balance += $balance;
        //  echo "</tabel>";
        //  echo "</div>" ; 
     }  //end of submain
     if($master_name_array[$i] === "Sales" || $master_name_array[$i] === "Income" || $master_name_array[$i] === "income"  ){

        // $master_css_class = " w3-hide";
        $total_sales += $master_balance;
     } 
     if($master_name_array[$i] === "Expenses"){
        $total_expenses +=$master_balance;
        // $total_expenses +=;
     }
     
    }
    echo "<tr class= ' $class_hide w3-blue'><td> $master_name_array[$i] total</td> <td>". number_format( $master_balance,2) ."</td></tr>";
     
    }
     echo "</table>";
     echo "</div>";
     echo "</div>";
?>

<div class=" w3-border w3-margin w3-padding w3-border-blue">
    <h4 class=' '>netprofit/Loss  =   <?php echo number_format( ($total_sales + $total_expenses),2) ?> </h4>
</div>








<?php
return;
include("../configs.php");
include("../links.php");
$start_date = $_POST['date1'];
$end_date = $_POST['date2'];
?>
<!-- get company details -->
<?php
    $company_sql = "SELECT * FROM company_details";
    $company_result = $conn->query($company_sql);
    if($company_result->num_rows>0){
        $company_row = $company_result->fetch_assoc();

    }
?>
<div class="w3-container">
    <h3>COMPREHENISVE INCOME STATEMENT  </h3>
    <h4><?php echo $company_row['title'] ?></h4>
    <h5>ACCOUNTING PERIOD BETWEEN <?php echo date("d/m/Y", strtotime($start_date)) . " - " . date("d/m/Y", strtotime($end_date))  ?> </h5>
    
</div>

<?php
$master_name = '';
$submain = '';
$submain_name_array = array();
$submain_id_array = array();
$master_name_array = array();
$master_id_array = array();
$current_master = null;
$processed_ledgers = array(); // Keep track of processed ledgers
$total_expenses = 0;
$total_sales = 0;
$master_total =0;



// Select master
$sql1 = "SELECT * FROM master ORDER BY id ";
$result1 = $conn->query($sql1);
if ($result1->num_rows > 0) {
    while ($row1 = $result1->fetch_assoc()) {
        $master_name_array[] = $row1['name'];
        $master_id_array[] = $row1['id'];
    }
}

    //  specific loop to get sale, expenses for get netprofit loss

  
    

// end of that specific loop

for ($i = 0; $i < count($master_name_array); $i++) {
    $master_balance = 0; // Initialize master balance for each master
        //trying to hide some master and their data
    $master_css_class = "";
    
   
    if($master_name_array[$i] === "Assets" || $master_name_array[$i] === "Liabilities" || $master_name_array[$i] === "Capital"  ){
        $master_css_class = "w3-hide ";
    } else{
        $master_css_class = '';
    }
    
        

    
    $master_id = $master_id_array[$i];
    echo " <h3 class='  w3-padding w3-text-blue  $master_css_class'>{$master_name_array[$i]}</h3>";

    // Get submaster
    $sql2 = "SELECT * FROM submain WHERE master_id='$master_id' ";
    $result2 = $conn->query($sql2);
    if ($result2->num_rows > 0) {

        while ($row2 = $result2->fetch_assoc()) {
            
            $balance =0;
            $submain_id_array[] = $row2['id'];
            $submain_name_array[] = $row2['name'];
            $submain_id = $row2['id'];
            $submain_name = $row2['name'];
            
            $submaster_css_class = '';
            // echo $master_id . " " . $submain_name;
            
            echo "<div class=' w3-row'>";
            echo "<div class=' w3-quarter w3-text-white'>.</div>";
            echo "<div class=' w3-padding  '>";
            // echo "<h4 class=' w3-text-blue'> $submain_name</h4>";
            // echo "<h4 class=' w3-text-blue $master_css_class'> $submain_name</h4>";
            echo "<table class='table table-bordered  $master_css_class '>";
             echo "<tr class=' table-borderless'><td><h4 class=' w3-text-blue'> $master_name_array[$i] </h4></td><td></td><tr>";
               echo "<tr class=' table-borderless'><td><h4 class=' w3-text-blue'> $master_name_array[$i] - $submain_name</h4></td><td>Amount</td><tr>";
            // echo "<tr><td>Ledger</td><td>Balance</td></tr>";

            // Get ledger
            $sql3 = "SELECT ledgers.id as ledger_id, ledgers.name as ledger FROM ledgers WHERE ledgers.submain_id='$submain_id' ";
            $result3 = $conn->query($sql3);
            if($result3->num_rows>0){
            while($row3 = $result3->fetch_assoc()){
                $ledger_balance=0;
                $ledger_id = $row3['ledger_id'];
                $ledger_name = $row3['ledger'];

                // let us  select subsidiaries
                $sql4 = "SELECT * FROM `subsidiaries` WHERE ledger_id='$ledger_id'";
                $result4 = $conn->query($sql4);
                if($result4->num_rows>0){
                    $credit = 0;
                    $debit =0;
                    while($row4 = $result4->fetch_assoc()){
                        $account_id = $row4['id'];

                        //get opening balance
                        $sql5 = "SELECT * FROM starting_balance WHERE account_id='$account_id' AND date_ BETWEEN '$start_date' AND '$end_date'";
                        $result5 = $conn->query($sql5);
                        if($result5->num_rows>0){
                            $row5 = $result5->fetch_assoc();
                            $opening_balance_amount = $row5['ammount'];
                            $opening_balance_type = $row5['type'];
                            if($opening_balance_type === 'debit'){
                                $ledger_balance +=$opening_balance_amount;
                                $credit =0;
                                $debit += $opening_balance_amount;

                            } elseif($opening_balance_type === 'credit'){
                                $debit =0;
                                $credit += $opening_balance_amount;
                                $ledger_balance -= $opening_balance_amount;
                            }
                        }
                        //time for transactions
                        $sql6 = "SELECT t.*, s.name AS account_name FROM transaction_voucher t
                                LEFT JOIN subsidiaries s ON s.id = t.cr_account
                                WHERE (t.dr_account = '$account_id' OR 
                                t.cr_account = '$account_id') AND t.date_ BETWEEN 
                                '$start_date' AND '$end_date' ORDER BY t.date_";
                            
                        $result6 = $conn->query($sql6);
                        if($result6->num_rows>0){
                            while($row6 = $result6->fetch_assoc()){
                                if($row6['cr_account'] == $account_id){
                                    $credit += $row6['cr_ammount'];
                                    $ledger_balance -= $row6['cr_ammount'];
                                    $debit +=0;
                                } elseif($row6['dr_account'] == $account_id){
                                    $debit += $row6['dr_ammount'];
                                    $credit += 0;
                                    $ledger_balance += $row6['dr_ammount'];
                                    
                                }
                            }
                        }
                       
                    }

                }
                if($ledger_name === 'Net Profit/Loss'){
                    $ledger_class= ' w3-blue';
                } 
                else{
                    $ledger_class = '';
                }
                echo "<tr class= ' $ledger_class'><td>$ledger_name</td><td>". number_format($ledger_balance,2) ."</td></tr>";
                $balance += $ledger_balance;
            }

        }
        
        echo "<tr class= ' w3-red'><td> $submain_name total</td> <td>". number_format( $balance,2) ."</td></tr>";
         $master_balance += $balance;
        //  echo "</tabel>";
        //  echo "</div>" ; 
     }  //end of submain
     if($master_name_array[$i] === "Sales" || $master_name_array[$i] === "Income"  ){

        // $master_css_class = " w3-hide";
        $total_sales += $master_balance;
     } 
     if($master_name_array[$i] === "Expenses"){
        $total_expenses +=$balance;
     }
     echo "<tr class= ' w3-blue'><td> $master_name_array[$i] total</td> <td>". number_format( $master_balance,2) ."</td></tr>";
     echo "</table>";
     echo "</div>";
     echo "</div>";
    }
     
    }
?>

<div class=" w3-border w3-margin w3-padding w3-border-blue">
    <h4 class=' '>netprofit/Loss  =   <?php echo number_format( ($total_sales + $total_expenses),2) ?> </h4>
</div>













