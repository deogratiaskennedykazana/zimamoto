<?php
    include("../configs.php");
    include("../links.php");

    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];



?>

<div class=" w3-container w3-border">
    <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>GEOGRAPHICAL DISTRIBUTION OF SACCOS, BRANCHES, EMPLOYEES AND LOANS BY AGE FOR THE MONTH ENDED: 		 </h4>
        <h4>Amount reported as TZS 0.00	</h4>
        <h5> Between <?php echo date("m-d-Y", strtotime($start_date))  ?> And <?php echo date("d-m-Y", strtotime($end_date)) ?> </h5>
    		

    </div>
    
    <table class=" table table-bordered">
        <thead class=" table-info">
                <tr class=" w3-center">
                    <th rowspan="2">S/No</th>
                    <th colspan="2">Geographical area</th>
                   
                    <th rowspan="3">Number of branch </th>
                    <th rowspan="3">Number of employee</th>
                    <th colspan="4">Number Of Borrowers</th>
                    <th rowspan="3">Outstanding Loan</th>
                  
                </tr>
                <tr>
                    <th rowspan="1">Region</th>
                    <th rowspan="1">District</th>
                    <th colspan="2" >Upto 35 years</th>
                    <th colspan="2" >Above 35 years</th>
                </tr>
                <tr>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>Female</th>
                    <th>Male</th>
                    <th>Female</th>
                    <th>Male</th>
                </tr>
        </thead>
        <tbody>
            <?php
                $select = "SELECT mikoa.id,mikoa.name as mkoa, wilaya.id, wilaya.name as wilaya, wilaya.id FROM mikoa, wilaya WHERE  mikoa.id  = wilaya.mkoa_id";
                $result = $conn->query($select);
                if($result->num_rows>0){
                    $counter =1;
                    while($rows = $result->fetch_assoc()){
                        ?>
                            <tr>
                                <td><?php echo $counter ?></td>
                                <td><?php echo $rows['mkoa'] ?></td>
                                <td><?php echo $rows['wilaya'] ?></td>
                                <td><?php echo "0" ?></td>
                                <td><?php echo "0" ?></td>
                                <td><?php echo "0" ?></td>
                                <td><?php echo "0" ?></td>
                                <td><?php echo "0" ?></td>
                                <td><?php echo "0" ?></td>
                                <td><?php echo "0" ?></td>
                            </tr>

                        <?php
                        $counter++;

                    }
                }
            
            ?>
           
        </tbody>
    </table>

</div>