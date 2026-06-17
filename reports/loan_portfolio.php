<?php
    include("../configs.php");
    include("../links.php");
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    ?>
 <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>LOAN PORTIFOLIO </h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    
    <div>
        <table class=" table table-bordered">
            <thead>
                <tr>
                    <th>S/NO</th>
                    <th>Customer name</th>
                    <th>Loan Amount</th>
                   
                    <th>Interest rate</th>
                    <th>Start date</th>
                    <th>Outstanding Balance</th>
                    
            </thead>
            <tbody>

                <?php

                //get loan details

                $select = "SELECT *";


                  
               
                $sub_id = '';
                // echo "hello";
                
                    $select = "SELECT users.name as customer, users.id as mteja, approved_loan.* from users, approved_loan WHERE users.id = approved_loan.user_id and date_ BETWEEN '$start_date' AND '$end_date'";
                    $result = $conn->query($select);
                    if($result->num_rows>0){
                        $counter = 1;
                        while($rows = $result->fetch_assoc()){
                            $id = $rows['mteja'];
                            ?>
                            <tr>
                                <td><?php echo $counter?></td>
                                <td><?php echo $rows['customer']?></td>
                                <td><?php echo number_format( $rows['amount'], 2)?></td>
                                <td><?php echo $rows['interest']?>%</td>
                                <td><?php echo date("d/m/Y", strtotime($rows['date_'])) ?></td>
                                <td>
                                    <!-- ledger -->
                                    <?php
                                         $debit =0;
                                        $credit = 0;
                                        $balance = 0;
                                        $account_name = $rows['customer'] . " loan account";
                                        $mteja = "SELECT * FROM subsidiaries WHERE name like '$account_name%'; ";
                                        $mteja_res = $conn->query($mteja);
                                        if($mteja_res->num_rows>0){
                                        $mteja_row = $mteja_res->fetch_assoc();
                                        $sub_id = $mteja_row['id'];
                                     //   echo $sub_id;

                                        } else{
                                            $sub_id =0;
                                          //  echo "no";
                                        }
                                        // echo $account_name;
                                        // echo $sub_id ;

                                        // $select1 = "SELECT t.*,  d.name as dr_name, c.name as cr_name FROM transaction_voucher t 
                                        //             LEFT JOIN subsidiaries d ON d.id = t.dr_account
                                        //             LEFT JOIN subsidiaries c ON c.id = t.cr_account 
                                        //             WHERE
                                        //             t.dr_account = '$sub_id' OR t.cr_account='$sub_id'  AND date_ BETWEEN '$start_date' AND '$end_date'";
                                        $select1 = "SELECT t.*,  d.name as dr_name, c.name as cr_name FROM transaction_voucher t 
                                        LEFT JOIN subsidiaries d ON d.id = t.dr_account
                                        LEFT JOIN subsidiaries c ON c.id = t.cr_account AND date_ BETWEEN '$start_date' AND '$end_date'
                                        WHERE
                                        t.dr_account = '$sub_id' OR t.cr_account='$sub_id'  ";
                                            $result1 = $conn->query($select1);
                                            if($result1->num_rows>0){
                                                
                                                while($rows1 = $result1->fetch_assoc()){

                                                    
                                                    $date = $rows1['date_'];
                                                    $dr_name = $rows1['dr_name'];
                                                    $cr_name = $rows1['cr_name'];
                                                    $details = '';
                                                    if ($rows1['dr_account'] == $sub_id) {
                                                      $details = " $cr_name";
                                                      $debit += $rows1['dr_ammount'];
                                                    //  echo $debit ." hh";

                                                      $credit +=0;
                                                    } else if ($rows1['cr_account'] == $sub_id) {
                                                      $details = " $dr_name";
                                                      $debit += 0;
                                                      $credit += $rows1['cr_ammount'];
                                                    }

                                                   
                                                }
                                                 //echo "hello";
                                                $balance += (int)$debit- (int) $credit;

                                               echo number_format( $balance,2);

                                              //  echo  (int)$debit- (int) $credit;
                                                
                                              
                                            }

                                    ?>
                                </td>

                            </tr>
                            <?php
                            $counter++;
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>



















<?php
return;
    include("../configs.php");
    include("../links.php");
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    ?>
 <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>LOAN PORTIFOLIO </h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    <div>
        <table class=" table table-bordered">
            <thead>
                <tr>
                    <th>S/NO</th>
                    <th>Customer name</th>
                    <th>Loan Amount</th>
                   
                    <th>Interest rate</th>
                    <th>Start date</th>
                    <th>Outstanding Balance</th>
                    
            </thead>
            <tbody>
                <?php
                $debit =0;
                $credit = 0;
                $balance = 0;
                    $select = "SELECT users.name as customer, users.id as mteja, approved_loan.* from users, approved_loan WHERE users.id = approved_loan.user_id and date_ BETWEEN '$start_date' AND '$end_date'";
                    $result = $conn->query($select);
                    if($result->num_rows>0){
                        $counter = 1;
                        while($rows = $result->fetch_assoc()){
                            $id = $rows['mteja'];
                            ?>
                            <tr>
                                <td><?php echo $counter?></td>
                                <td><?php echo $rows['customer']?></td>
                                <td><?php echo number_format( $rows['amount'], 2)?></td>
                                <td><?php echo $rows['interest']?>%</td>
                                <td><?php echo date("d/m/Y", strtotime($rows['date_'])) ?></td>
                                <td>
                                    <!-- ledger -->
                                    <?php
                                    $sub_id = '';
                                        $account_name = $rows['customer'] . " loan account";
                                        $mteja = "SELECT * FROM subsidiaries WHERE name like '$account_name%'; ";
                                        $mteja_res = $conn->query($mteja);
                                        if($mteja_res->num_rows>0){
                                        $mteja_row = $mteja_res->fetch_assoc();
                                        $sub_id = $mteja_row['id'];
                                     //   echo $sub_id;

                                        } else{
                                            $sub_id =0;
                                          //  echo "no";
                                        }
                                       // echo $account_name;
                                       //echo $sub_id;

                                        $select1 = "SELECT t.*,  d.name as dr_name, c.name as cr_name FROM transaction_voucher t 
                                                    LEFT JOIN subsidiaries d ON d.id = t.dr_account
                                                    LEFT JOIN subsidiaries c ON c.id = t.cr_account 
                                                    WHERE
                                                    t.dr_account = '$sub_id' OR t.cr_account='$sub_id'  AND date_ BETWEEN '$start_date' AND '$end_date'";
                                        $result1 = $conn->query($select1);
                                        if($result1->num_rows>0){
                                              //  echo "ipo";
                                                while($rows1 = $result1->fetch_assoc()){
                                                
                                                    if($rows1['dr_account'] == $sub_id){
                                                        $debit += (int) $rows1['dr_ammount'];
                                                       // echo $debit ." ";
                                                        $credit += 0;
                                                    } elseif($rows1['cr_account'] == $sub_id){
                                                        $credit = $rows1['cr_ammount'];
                                                        $debit +=0;
                                                    }

                                                   
                                                }
                                                $balance += (int)$debit- (int) $credit;

                                                echo number_format( $balance,2) ;
                                                
                                              
                                            }

                                    ?>
                                </td>

                            </tr>
                            <?php
                        }
                    }
                ?>
            </tbody>
        </table>
    </div>