<?php
    include("../configs.php");
    include("../links.php");


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$subsector_id =0;
?>

<div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>INTEREST RATES STRUCTURE FOR LOANS AS AT: </h4>
        <!--<h4>Amount reported as TZS 0.00	</h4>-->
        <h5> Between <?php echo date("m-d-Y", strtotime($start_date))  ?> And <?php echo date("d-m-Y", strtotime($end_date)) ?> </h5>
    		

    </div>

<div class=" w3-margin">
    <table class=" table table-bordered">
        <thead class=" w3-blue">
            <tr >
                <th rowspan="2" >S/NO</th>
                <th rowspan="2" >sector</th>
                <th rowspan="2" >subsector</th>
                <th colspan="2" >Loan distributed to female</th>
                <th colspan="2"  >Loan distributed to male</th>
                <th colspan="2">Total Loan distributed</th>
            </tr>
            <tr>
                    <th>Number</th>
                    <th>Amount</th>
                    <th>Number</th>
                    <th>Amount</th>
                    <th>Number </th>
                    <th>Amount</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $select = "SELECT sectors.name  sector, sectors.id, subsector.name as subsector, subsector.sector_id as sector_id  FROM sectors, subsector WHERE subsector.sector_id = sectors.id ";
                
                $result = $conn->query($select);
                if($result->num_rows>0){
                    $counter =1;
                    while($rows = $result->fetch_assoc()){
                        $subsector_id = $rows['sector_id']; 
                      //  echo $subsector_id;
                        ?>
                        <tr>
                          
                         <td><?php echo $counter; ?></td>
                         <td><?php echo $rows['sector']; ?></td>
                         <td><?php echo $rows['subsector']; ?></td>
                         <td>
                            
                            <?php
                               $select1 = "SELECT users.name as jina, approved_loan.amount FROM users, approved_loan WHERE approved_loan.subsector_id='$subsector_id' AND users.gender='F' and users.id = approved_loan.user_id AND approved_loan.date_ BETWEEN '$start_date' AND '$end_date' " ;
                               $result1 = $conn->query($select1);
                               if($result1->num_rows>0){
                                    $count = $result1->num_rows;
                                          echo $count;
                               } else{
                                    echo 0;
                               }
                              // echo $subsector_id;
                            ?>
                         </td>
                         <td>
                            <?php
                            $amount = 0;
                                $select2 = "SELECT users.name as jina, approved_loan.amount as kiasi FROM users, approved_loan WHERE approved_loan.subsector_id='$subsector_id' AND users.gender='F' and users.id = approved_loan.user_id AND approved_loan.date_ BETWEEN '$start_date' AND '$end_date' " ;
                                $result2 = $conn->query($select2);
                                if($result2->num_rows>0){
                                        while($row2 = $result2->fetch_assoc()){
                                            $amount += $row2['amount'];
                                            
                                        }
                                        
                                } else{
                                        echo 0;
                                }
                             //   echo $subsector_id;
                               echo $amount ;
                            ?>
                         </td>

                         <td>
                            <?php $count =0;
                                    $select3 = "SELECT users.name as jina, approved_loan.amount as kiasi FROM users, approved_loan WHERE approved_loan.subsector_id='$subsector_id' AND users.gender='M' and users.id = approved_loan.user_id AND approved_loan.date_ BETWEEN '$start_date' AND '$end_date' " ;
                                    $result3 = $conn->query($select3);
                                    if($result3->num_rows>0){
                                             $count = $result3->num_rows;
                                         
                                    } else{
                                           // echo 0;
                                    }
                                     echo $count . " ";
                               // echo $subsector_id;
                                ?>
                         </td>
                         <td>
                            <?php   $amount4 =0;
                                    $select4 = "SELECT users.name as jina, approved_loan.amount as kiasi FROM users, approved_loan WHERE approved_loan.subsector_id='$subsector_id' AND users.gender='M' and users.id = approved_loan.user_id AND approved_loan.date_ BETWEEN '$start_date' AND '$end_date' " ;
                                    $result4 = $conn->query($select4);
                                    if($result4->num_rows>0){
                                            while($row4 = $result4->fetch_assoc()){
                                                $amount4 += $row4['kiasi'];
                                              
                                                //echo count($row3['jina']);
                                            }
                                            
                                    } else{
                                          //  echo 0;
                                    } 
                                    // echo $sector_id;
                                    echo $amount4 . " " ;
                              //  echo $subsector_id;
                                ?>
                         </td>
                         <td>
                            <?php
                                    $select5 = "SELECT users.name as jina, approved_loan.amount as kiasi FROM users, approved_loan WHERE approved_loan.subsector_id='$subsector_id' AND  users.id = approved_loan.user_id AND approved_loan.date_ BETWEEN '$start_date' AND '$end_date' " ;
                                    $result5 = $conn->query($select5);
                                    if($result5->num_rows>0){
                                            $count = $result5->num_rows;
                                          echo $count;
                                    } else{
                                            echo 0;
                                    }
                                
                                ?>
                         </td>
                         <td>
                            <?php   
                                    $amount6 =0;
                                    $select6 = "SELECT users.name as jina, approved_loan.amount as kiasi FROM users, approved_loan WHERE approved_loan.subsector_id='$subsector_id' AND  users.id = approved_loan.user_id AND approved_loan.date_ BETWEEN '$start_date' AND '$end_date' " ;
                                    $result6 = $conn->query($select5);
                                    if($result6->num_rows>0){
                                            while($row6 = $result6->fetch_assoc()){
                                                $amount6 += $row6['kiasi'];
                                            //  echo $row6['kiasi'];
                                             
                                              
                                              //  echo count($row6['jina']);
                                            }
                                           
                                    } else{
                                            echo 0;
                                    }
                                    echo $amount6 ;
                                
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