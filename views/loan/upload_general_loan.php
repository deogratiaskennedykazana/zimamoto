<div class=" card card-danger">
    <div class=" card-header"> <h5 class=" card-title">Process General Loan Upload</h5></div>
    
<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;


          if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $data = [];
           if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
               $inputFileName = $_FILES['file']['tmp_name'];
                try{
                $spreadSheet = IOFactory::load($inputFileName);
                $workSheet  = $spreadSheet->getActiveSheet();
                $highestRow = $workSheet->getHighestRow();
                $highestColumn = $workSheet->getHighestColumn();
                 $currentRow =1;
                 for($row = 1; $row <= $highestRow; $row++){
                     $rowData = $workSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                     $data[] = $rowData[0];
                 }

               } catch(Exception $e){
                echo 'Error reading the Excel file: ' . $e->getMessage();

               }

            }


            // print_r($data);
            ?>
                <form action="./controllers/loan_controller.php" class=" was-validated" method="post">
                    <div class=" card-body table-responsive">
                        <div class=" row m-2">
                            <div class=" col-md-5 col-sm-10 m-1">
                                <label for="">Select Loan Account</label>
                                <select name="loan_account" required id="" class=" form-control select2-form select2bs4-form">
                                    <option value="">Select Below</option>
                                    <?php
                                            $accounts = selectMinSubByCategory($conn, 'others');
                                            if($accounts && is_array($accounts)){
                                                foreach($accounts as $account){
                                                    echo "<option value='$account[id]'>$account[name]</option>";
                                                }
                                            }
                                    ?>
                                </select>
                            </div>
                            <div class=" col-md-5 col-sm-10 m-1">
                                <label for="">Select Interest  Account</label>
                                <select name="interest_account" required id="" class=" form-control select2-form select2bs4-form">
                                    <option value="">Select Below</option>
                                    <?php
                                            $accounts = selectMinSubByCategory($conn, 'others');
                                            if($accounts && is_array($accounts)){
                                                foreach($accounts as $account){
                                                    echo "<option value='$account[id]'>$account[name]</option>";
                                                }
                                            }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class=" card-footer"> <h4 class=" text-center">Extracted Loan Data </h4> </div>
                        <table class=" table table-striped table-bordered table-sm table-search">
                                <tr>
                                    <td>#</td>
                                    <td>Name</td>
                                    <td>Branch</td>
                                    <td>Principle</td>
                                    <td>Interest</td>
                                    <td>Interest rate (%)</td>
                                    <td>Approve date</td>
                                    <td>Period (in month)</td>
                                    
                                </tr>

                                <?php
                                    $counter =1;
                                    $firstRow = true;
                                    foreach($data as $loan){
                                        if ($firstRow) {
                                            $firstRow = false;
                                            continue; // Skip the first row
                                        }
                                          if(empty($loan[1])){
                                                break;
                                            }
                                            echo "<tr>";
                                                echo "<td>$counter</td>";
                                             $name =  $loan[1];
                                              $member = selectMinSubByCheckNoAndCategory($conn,$name, "loan");
                                              
                                              if($member && is_array($member)){
                                                  // Get branch details using branch_id from min_subs table
                                                  $memberBranchId = $member['branch_id'];
                                                  $memberBranchDetails = SelectBranchById($conn, $memberBranchId);
                                                  $branchName = ($memberBranchDetails && is_array($memberBranchDetails)) ? $memberBranchDetails['name'] : 'Unknown';
                                                  
                                                  echo "<td>$member[name] <input type='hidden' name='sub_id[]' value='$member[id]' >";
                                                  echo "<input type='hidden' name='user_id[]' value='$member[user_id]' >";
                                                  echo "<input type='hidden' name='member_branch_id[]' value='$memberBranchId'> </td>";
                                                  echo "<td><span class='badge badge-info'>$branchName</span></td>";

                                              }else{
                                                $memberData = selectUserByName($conn, $name);
                                                if($memberData && is_array($memberData)){
                                                    echo "<td>$memberData[name] <input type='hidden' name='user_id[]' value='$memberData[id]' > </td>";
                                                    echo "<td><span class='badge badge-secondary'>New User</span></td>";
                                                } else {
                                                    echo "<td>$loan[1] <span class='text-danger'>(Not Found)</span></td>";
                                                    echo "<td><span class='badge badge-secondary'>-</span></td>";
                                                }
                                              }
                                            
                                              echo "<td><input type='number' name='principle[]' value='$loan[3]' step='any' class=' form-control' ></td>";
                                              echo "<td><input type='number' name='interest_amount[]' value='$loan[8]' step='any' class=' form-control' ></td>";
                                              echo "<td><input type='number' name='interest_rate[]' value='". number_format( $loan[9],1) ."' step='any' class=' form-control' ></td>";
                                        //     echo "<td><input type='date' name='date[]' value='" . (is_numeric($loan['5']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($loan['5'])->format('Y-m-d') : date('Y-m-d', strtotime($loan['5']))) . "' step='any' class='form-control'></td>";
                                             echo "<td><input type='date' name='date[]' value='".  $_POST["date"]  ."' step='any' class='form-control'></td>";

                                              echo "<td><input type='number' name='period[]' value='". number_format( $loan[7],0) ."' step='any' class=' form-control' ></td>";
                                            
                                            echo "</tr>";
                                            $counter++;
                                    }
                                
                                ?>

                        </table>
                    </div>
                    <div class=" card-footer">
                                <button type="submit" class=" btn btn-sm btn-info btn-block" name="upload_general_loan">Upload Data</button> 
                    </div>
                </form>
            <?php

        }
            
?>