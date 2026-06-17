<?php
    include("../configs.php");
    include("../links.php");


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
// Query to get the report data
$sql = "SELECT 
            s.id AS sector_id,
            s.name AS sector,
            ss.id AS subsector_id,
            ss.name AS subsector,
          
            COUNT(DISTINCT al.id) AS number_of_borrowers,
            SUM(al.amount) AS total_outstanding
        FROM 
            sectors s
        INNER JOIN 
            subsector ss ON s.id = ss.sector_id
        LEFT JOIN 
            approved_loan al ON ss.id = al.subsector_id
        GROUP BY 
            ss.id
        ORDER BY 
            s.id, ss.id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Report</title>
    <style>
        table {
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
        }
    </style>
</head>
<body class=" w3-margin">
    
     <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>SECTORAL CLASSIFICATION OF LOANS AS AT:</h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    <br>
    <table>
        <thead>
            <tr class=" w3-blue">
                <th rowspan="2">S/No</th>
                <th rowspan="2" >Sector</th>
                <th rowspan="2" >Subsector</th>
                <th rowspan="2">Number of Borrowers</th>
                <th rowspan="2">Total Outstanding</th>
                <th rowspan="2">Current</th>
                <th colspan="4">Past due</th>
                <th rowspan="2">Write of During Quarter</th>
            </tr>
            <tr class=" w3-blue">
               
               
               

                 <th>ESM</th>
                <th>Sub Standard</th>
                <th>Doubtfull</th>
                <th>Loss</th>
            
            </tr>
            
        </thead>
        <tbody>
            <?php
                $sub_credit = 0;
                $sub_debit = 0;
                $sub_balance = 0;
                $main_debit = 0;
                $main_credit = 0;
                $main_balance = 0; 
            if ($result->num_rows > 0) {
                $serialNumber = 1;
                while ($row = $result->fetch_assoc()) {
                   $subsector_id = $row['subsector_id'];
                   
                    echo "<tr>";
                    echo "<td>" . $serialNumber . "</td>";
                    echo "<td>" . $row['sector'] . "</td>";
                    echo "<td>" . $row['subsector'] . "</td>";
                    echo "<td>" . $row['number_of_borrowers'] . "</td>";
                    ?>
                    <td>
                        <?php
                                $sub_array = array();
                                $select_user = "select approved_loan.* FROM approved_loan where subsector_id='$subsector_id'";
                                $result_user = $conn->query($select_user);
                                if($result_user->num_rows>0){
                                    //echo "tumefika hapa";
                                    while($user_row = $result_user->fetch_assoc()){
                                        $user_id = $user_row['user_id'];
                                        //select name
                                        //get subsidiary
                                        $select_sub = "SELECT subsidiaries.id as id, subsidiaries.name as name FROM subsidiaries where subtype='1'";
                                        $result_sub = $conn->query($select_sub);
                                        if($result_sub->num_rows>0){
                                          while($sub_row = $result_sub->fetch_assoc()){
                                          //  echo "subsidiary " . $sub_row['name'] . " ";
                                            $sub_array[] = $sub_row['id'];
                                            
                                          }
                                          
                                        } else{
                                            echo "haipo";
                                        }

                                    }

                                    for($i=0; $i< count($sub_array); $i++){
                                       // echo $i . " " . $sub_array[$i] . " ";
                                       $sub_id =$sub_array[$i];
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
                                                      $sub_debit += (int) $rows1['dr_ammount'];
                                                     // echo $debit ." ";
                                                      $sub_credit += 0;
                                                  } elseif($rows1['cr_account'] == $sub_id){
                                                      $sub_credit = $rows1['cr_ammount'];
                                                      $sub_debit +=0;
                                                  }
                                              

                                                 
                                              }
                                              $main_debit += $sub_debit;
                                              $main_credit += $sub_credit;
                                            //  echo "sub" . $sub_debit . " " ;

                                    }

                                   // print_r($sub_array);
                                }
                              //  echo "<br> main debit " . $main_debit;
                              //  echo "<br> main  credit " . $main_debit ." ";
                                echo  ($main_debit- $main_credit);


                                
                                
                                // echo "Hatujafika";
                                  //  echo $subsector_id;
                                }
                       ?>
                    </td>
                    <?php
                    // echo "<td>" . $row['total_outstanding'] . "</td>";
                  
                    $serialNumber++;
                
                ?>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                
                

                <?php
            }   
           } else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>












































































<?php
return;
// Close the database connection


    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    ?>
    <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>SECTORAL CLASSIFICATION OF LOANS AS AT:</h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    <?php
    $select = "SELECT sectors.name as sector, subsector.* FROM sectors, subsector WHERE sectors.id = subsector.sector_id";
    $result = $conn->query($select);
    if($result->num_rows>0){
        ?>
        <table class=" table table-bordered">
            <thead>
                <tr>
                    <th>S/NO</th>
                    <th>Sector</th>
                    <th>Sub Sector</th>
                    <th>Number of Borrowers</th>
                    <th>Total Outstanding</th>
                    <th>Current</th>
                    <th >ESM</th>
                    <th>Substandard</th>
                    <th>Doubtfull</th>
                    <th>Loss</th>
                    <th>Write-off During the quarter </th>
                </tr>
            </thead>
            <tbody>
            <?php
            $counter =1;
        while($rows = $result->fetch_assoc()){
            $subsector_id = $rows['id'];
            ?>
            <tr>
                <td> <?php echo $counter ?> </td>
                <td> <?php echo $rows['sector'] ?> </td>
                <td> <?php echo $rows['name'] ?> </td>
                <td> <?php $select ="SELECT users.name, loan_requests.* from users, loan_requests where users.id = loan_requests."  ?> </td>
                
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                
            </tr>
            
            <?php 
            $counter++;   
        }?>
        <tr>
            <td>Gross loan (a)</td>
            <td></td>
            <td></td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
        </tr>
        <tr>
                <td>  <p><b>Provision Rate</b></p>  </td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>0%</td>
                <td>10%</td>

                <td>30%</td>
                <td>50%</td>
                <td>100%</td>
        </tr>
         
        <tr>    
                <td> <p><b>Provision Amount (b)</b></p> </td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>Less Cash Collateral/Insurance Guarantee/Compulsory Saving (c)</td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>Net Provision amount (d = b - c)</td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>TOTAL (Net Amount) (a - d)</td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            </tbody>
        </table>
        <?php
        
    }
?>

<?php
return;
    include("../configs.php");
    include("../links.php");


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
// Query to get the report data
$sql = "SELECT 
            s.id AS sector_id,
            s.name AS sector,
            ss.id AS subsector_id,
            ss.name AS subsector,
          
            COUNT(DISTINCT al.id) AS number_of_borrowers,
            SUM(al.amount) AS total_outstanding
        FROM 
            sectors s
        INNER JOIN 
            subsector ss ON s.id = ss.sector_id
        LEFT JOIN 
            approved_loan al ON ss.id = al.subsector_id
        GROUP BY 
            ss.id
        ORDER BY 
            s.id, ss.id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Report</title>
    <style>
        table {
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
        }
    </style>
</head>
<body>
    
    <h1>Loan Report</h1>
    <table>
        <thead>
            <tr>
                <th>S/No</th>
                <th>Sector</th>
                <th>Subsector</th>
                <th>Number of Borrowers</th>
                <th>Total Outstanding</th>
            </tr>
        </thead>
        <tbody>
            <?php
                $sub_credit = 0;
                $sub_debit = 0;
                $sub_balance = 0;
                $main_debit = 0;
                $main_credit = 0;
                $main_balance = 0; 
            if ($result->num_rows > 0) {
                $serialNumber = 1;
                while ($row = $result->fetch_assoc()) {
                   $subsector_id = $row['subsector_id'];
                   
                    echo "<tr>";
                    echo "<td>" . $serialNumber . "</td>";
                    echo "<td>" . $row['sector'] . "</td>";
                    echo "<td>" . $row['subsector'] . "</td>";
                    echo "<td>" . $row['number_of_borrowers'] . "</td>";
                    ?>
                    <td>
                        <?php
                                $sub_array = array();
                                $select_user = "select approved_loan.* FROM approved_loan where subsector_id='$subsector_id'";
                                $result_user = $conn->query($select_user);
                                if($result_user->num_rows>0){
                                    //echo "tumefika hapa";
                                    while($user_row = $result_user->fetch_assoc()){
                                        $user_id = $user_row['user_id'];
                                        //select name
                                        //get subsidiary
                                        $select_sub = "SELECT subsidiaries.id as id, subsidiaries.name as name FROM subsidiaries where subtype='1'";
                                        $result_sub = $conn->query($select_sub);
                                        if($result_sub->num_rows>0){
                                          while($sub_row = $result_sub->fetch_assoc()){
                                          //  echo "subsidiary " . $sub_row['name'] . " ";
                                            $sub_array[] = $sub_row['id'];
                                            
                                          }
                                          
                                        } else{
                                            echo "haipo";
                                        }

                                    }

                                    for($i=0; $i< count($sub_array); $i++){
                                       // echo $i . " " . $sub_array[$i] . " ";
                                       $sub_id =$sub_array[$i];
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
                                                      $sub_debit += (int) $rows1['dr_ammount'];
                                                     // echo $debit ." ";
                                                      $sub_credit += 0;
                                                  } elseif($rows1['cr_account'] == $sub_id){
                                                      $sub_credit = $rows1['cr_ammount'];
                                                      $sub_debit +=0;
                                                  }
                                              

                                                 
                                              }
                                              $main_debit += $sub_debit;
                                              $main_credit += $sub_credit;
                                            //  echo "sub" . $sub_debit . " " ;

                                    }

                                   // print_r($sub_array);
                                }
                              //  echo "<br> main debit " . $main_debit;
                              //  echo "<br> main  credit " . $main_debit ." ";
                                echo  ($main_debit- $main_credit);


                                
                                
                                // echo "Hatujafika";
                                  //  echo $subsector_id;
                        ?>
                    </td>
                    <?php
                    // echo "<td>" . $row['total_outstanding'] . "</td>";
                    echo "</tr>";
                    $serialNumber++;
                }
            }   
            } else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>

<?php
// Close the database connection
return;




    




// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get the report data
$sql = "SELECT 
            s.id AS sector_id,
            s.name AS sector,
            ss.id AS subsector_id,
            ss.name AS subsector,
            COUNT(DISTINCT al.user_id) AS number_of_borrowers,
            SUM(al.amount - al.interest_ammount) AS total_outstanding
        FROM 
            sectors s
        INNER JOIN 
            subsector ss ON s.id = ss.sector_id
        LEFT JOIN 
            approved_loan al ON ss.id = al.subsector_id
        GROUP BY 
            ss.id
        ORDER BY 
            s.id, ss.id";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Loan Report</title>
    <style>
        table {
            border-collapse: collapse;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
        }
    </style>
</head>
<body>
    <h1>Loan Report</h1>
    <table>
        <thead>
            <tr>
                <th>S/No</th>
                <th>Sector</th>
                <th>Subsector</th>
                <th>Number of Borrowers</th>
                <th>Total Outstanding</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $data = array();
            if ($result->num_rows > 0) {
                $serialNumber = 1;
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row['sector'];
                    print_r($data);
                    echo "<tr>";
                    echo "<td>" . $serialNumber . "</td>";
                    echo "<td>" . $row['sector'] . "</td>";
                    echo "<td>" . $row['subsector'] . "</td>";
                    echo "<td>" . $row['number_of_borrowers'] . "</td>";
                    echo "<td>" . $row['total_outstanding'] . "</td>";
                    echo "</tr>";
                    $serialNumber++;
                }
            } else {
                echo "<tr><td colspan='5'>No data available</td></tr>";
            }
            ?>
        </tbody>
    </table>
</body>
</html>








































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
        <h4>SECTORAL CLASSIFICATION OF LOANS AS AT:</h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    <?php
    $select = "SELECT sectors.name as sector, subsector.* FROM sectors, subsector WHERE sectors.id = subsector.sector_id";
    $result = $conn->query($select);
    if($result->num_rows>0){
        ?>
        <table class=" table table-bordered">
            <thead>
                <tr>
                    <th>S/NO</th>
                    <th>Sector</th>
                    <th>Sub Sector</th>
                    <th>Number of Borrowers</th>
                    <th>Total Outstanding</th>
                    <th>Current</th>
                    <th >ESM</th>
                    <th>Substandard</th>
                    <th>Doubtfull</th>
                    <th>Loss</th>
                    <th>Write-off During the quarter </th>
                </tr>
            </thead>
            <tbody>
            <?php
            $counter =1;
        while($rows = $result->fetch_assoc()){
            ?>
            <tr>
                <td> <?php echo $counter ?> </td>
                <td> <?php echo $rows['sector'] ?> </td>
                <td> <?php echo $rows['name'] ?> </td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                
            </tr>
            
            <?php 
            $counter++;   
        }?>
        <tr>
            <td>Gross loan (a)</td>
            <td></td>
            <td></td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
            <td>0</td>
        </tr>
        <tr>
                <td>  <p><b>Provision Rate</b></p>  </td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td>0%</td>
                <td>10%</td>

                <td>30%</td>
                <td>50%</td>
                <td>100%</td>
        </tr>
         
        <tr>    
                <td> <p><b>Provision Amount (b)</b></p> </td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>Less Cash Collateral/Insurance Guarantee/Compulsory Saving (c)</td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>Net Provision amount (d = b - c)</td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            <tr>
                <td>TOTAL (Net Amount) (a - d)</td>
                <td></td>
                <td></td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
                <td>0</td>
            </tr>
            </tbody>
        </table>
        <?php
        
    }
?>