







<?php

// Establish your database connection
include("../configs.php");
include("../links.php");
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$creditSide =0;
$debitSide = 0;
$total_credit =0;
$total_debit =0;
$total_balance = 0;

// Define your start_date and end_date variables
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
 <div class=" w3-row w3-border">
    <div class="  w3-half w3-container">
        <h3>NAME OF SACCOS: <?php echo $company_row['title'] ?></h3>
        <h4>	</h4>
        <h4>TRIAL BALANCE BY DETAILED LEDGER REPORT	 </h4>
        <!--<h4>Amount reported as TZS 0.00	</h4>-->
        <h5> Between <?php echo date("m-d-Y", strtotime($start_date))  ?> And <?php echo date("d-m-Y", strtotime($end_date)) ?> </h5>
    		

    </div>

 </div>
 <?php




$main_balance = 0;
$main_credit =0;
$main_debit = 0;

// table for given report
?>

<div class=" w3-padding w3-margin">
                             <input type="text" name="" id="" placeholder="Search account" class=" w3-input w3-border w3-margin-bottom" oninput="w3.filterHTML('#coa','.item', this.value)" >

    <table class=" w3-table-all w3-card w3-margin-top " id='coa'>
        <tr class=" w3-blue">
            <td>#</td>
            <td>Ledger Name</td>
            <td>Debit</td>
            <td>Credit</td>
        </tr>
<?php
$credit_balance =0;
$debit_balance = 0;
//get ledgers
$sql1 = "SELECT * FROM `ledgers` ";
$result1 = $conn->query($sql1);
if($result1->num_rows>0){
     $counter =1;
    while($row1 =$result1->fetch_assoc()){
        $ledger_id = $row1['id'];
        $ledger_name = $row1['name'];
        $ledger_balance =0;
      

        //get subsidiary 
        $sql2 = "SELECT * FROM subsidiaries WHERE `ledger_id` ='$ledger_id'";
        $result2 = $conn->query($sql2);
        if($result2->num_rows>0){
           
            while($row2 = $result2->fetch_assoc()){
                $sub_name =$row2['name'];
                $sub_balance = 0;
                $sub_id = $row2['id'];
                
                //get opening balance
                $sql3 = "SELECT * FROM `starting_balance` WHERE `account_id`='$sub_id' AND `date_` <= '$end_date'";
                $result3 = $conn->query($sql3);
                if($result3->num_rows>0){
                    $rows3 = $result3->fetch_assoc();
                   $opening_balance_amount = $rows3['ammount'];
                   $opening_balance_type = $rows3['type'];
                   if($opening_balance_type ==='debit'){
                           $sub_balance += $opening_balance_amount;
                        
                   } elseif($opening_balance_type ==='credit'){
                          $sub_balance -= $opening_balance_amount;
                   }
                }
                //get account double entry
                $sql4 = "SELECT t.*, s.name AS account_name FROM transaction_voucher t
                        LEFT JOIN subsidiaries s ON s.id = t.cr_account
                        WHERE (t.dr_account = '$sub_id' OR 
                        t.cr_account = '$sub_id') AND t.date_ <= '$end_date' ORDER BY t.date_";
          
          $result4 = $conn->query($sql4);
          if($result4->num_rows>0){
            while($rows4 = $result4->fetch_assoc()){
                if($rows4['dr_account'] == $sub_id ){
                      $sub_balance += $rows4['dr_ammount'];
                    
                } elseif($rows4['cr_account'] == $sub_id){
                     $sub_balance -= $rows4['cr_ammount'];
                     
                }
            }
          } else{
             
          }
          echo "<tr class='item w3-hover-grey'>";
            if($sub_balance>0){
                $total_debit += $sub_balance;
                echo "<td></td><td>$sub_name</td><td>". number_format( $sub_balance, 2)."</td><td> ". number_format( 00,2)."</td>";
            } elseif($sub_balance<0){
                $total_credit -= $sub_balance;
                echo "<td></td><td>$sub_name</td><td> ". number_format( 00,2)."</td><td>". number_format( $sub_balance*(-1), 2)."</td>";

            } elseif($sub_balance==0){
                $total_balance += 0;
                echo "<td></td><td>$sub_name</td><td> ". number_format( 00,2)."</td><td>". number_format( $sub_balance, 2)."</td>";

            }
          echo "</tr>";
          $ledger_balance += $sub_balance;
          
         }   

        }
       
        echo "<tr class=' w3-hover-black w3-red item'>";
        if($ledger_balance>0){
            // $total_debit += $ledger_balance;
            echo "<td>$counter</td><td>Total $ledger_name </td><td>". number_format( $ledger_balance, 2)."</td><td> ". number_format( 00,2)."</td>";

        } elseif($ledger_balance<0){
            
        echo "<td>$counter</td><td>Total $ledger_name </td><td>". number_format( 00, 2)."</td><td> ". number_format( $ledger_balance,2)."</td>";

        } elseif($ledger_balance == 0){
            
        echo "<td>$counter</td><td>Total $ledger_name </td><td>". number_format( $ledger_balance, 2)."</td><td> ". number_format( $ledger_balance,2)."</td>";

        }
        echo "</td>";
        // echo $ledger_name . " " . $main_balance . "</br>";
        $counter++;
    }


        echo "<tr class=' w3-hover-green w3-blue item'>";
        echo "<td></td><td>Total </td><td>".number_format( $total_debit,2) ."</td><td>". number_format( $total_credit,2)."</td>";
        echo "</td>";
}
?>
    </table>
</div>

