<?php
    include("../configs.php");
    include("../links.php");
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    ?>
    <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>INTEREST RATES STRUCTURE FOR LOANS AS AT: </h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    <div class=" w3-margin">

    
    <?php
    $select ="SELECT * FROM `loan_type` WHERE 1";
    $result = $conn->query($select);
    if($result->num_rows>0){
        $counter =1;
        ?>
        <table class=" table table-bordered">
            <thead class=" w3-blue w3-center">
                <tr>
                    <th rowspan="2">S/NO</th>
                    <th rowspan="2">Type of loan</th>
                    <th rowspan="2">Number of Borrowers</th>
                    <th rowspan="2">Outstanding Loan Amount</th>
                    <th colspan="2">Weighted average Interest Rate Straight Line Amortization</th>
                    <th rowspan="2">*Weighted Average Interest Rate Reducing Balance Amortization (% p.a.)</th>
                    <th colspan="2">Norminal Interest Rate (% p.a.) for Reducing Balance Amortization</th>
                </tr>
                <tr>
                    
                    <th>lowest</th>
                    <th>highest</th>
                    
                    <td>Lowest</td>
                    <td>Highet</td>
                </tr>
            </thead>
            <tbody>


            <?php
                    while($rows = $result->fetch_assoc()){
                        $loan_type_id = $rows['id'];
                     //   print_r($rows);

                        ?>
                        <tr>
                            <td><?php echo $counter ?></td>
                            <td><?php echo $rows['name']?></td>
                            <td>

                                <?php //echo $loan_type_id
                                    $number = 0;
                                    $select1 = "SELECT * FROM `approved_loan` WHERE loantype='$loan_type_id'";
                                    $result1 = $conn->query($select1);
                                    if($result1->num_rows>0){
                                        $number = $result1->num_rows;
                                        
                                    } else{
                                       // echo "haipo";
                                    }
                                    echo $number;
                                ?>

                            </td>
                            <td>
                            <?php //echo $loan_type_id
                                  $amount = 0;
                                    $select2 = "SELECT * FROM `approved_loan` WHERE loantype='$loan_type_id'";
                                    $result2 = $conn->query($select2);
                                    if($result2->num_rows>0){

                                       // $number = $result1->num_rows;
                                       while($row2 = $result2->fetch_assoc()){
                                           // print_r($row2);
                                           $amount += $row2['amount'];

                                       }

                                        
                                    } else{
                                       // echo "haipo";
                                    }
                                    echo $amount;
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