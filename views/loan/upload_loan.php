<div class=" card card-danger">
    <div class=" card-header"> <h5 class=" card-title">Processed Member Loan </h5></div>
    
<?php
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;


          if($_SERVER['REQUEST_METHOD'] == 'POST'){
            $data = [];
           if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
               $inputFileName = $_FILES['file']['tmp_name'];
               $branch_id = (int) $_POST['branch_id'];
               $branchDetails = SelectBranchById($conn, $branch_id);
               if($branchDetails && is_array($branchDetails)){
                ?>
                    <div class=" card-body">
                        <h5>Branch Name:<?= $branchDetails['name']?></h5>
                        <h5>Branch Address:<?= $branchDetails['address']?></h5>
                        <h5>Branch phone:<?= $branchDetails['phone']?></h5>
                    </div>
                <?php
               }

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
                        <input type="hidden" name="branch_id" value="<?= $branch_id?>">
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
                                    <td>Principle</td>
                                    <td>Interest</td>
                                    <td>Interest rate (%)</td>
                                    <td>Approve date</td>
                                    <td>Period (in month)</td>
                                     <td>Status</td>
                                    
                                </tr>

                               <?php
                                    $counter =1;
                                    $firstRow = true;
                                    $lastRow = false;
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
                                             $name = $conn->real_escape_string( "%" . $loan[1] . "%");
                                              $member = selectMinSubByNameAndCategory($conn,$name, "loan");
                                              
                                              if($member && is_array($member)){
                                                  // Check if the member belongs to the selected branch
                                                  if($member['branch_id'] == $branch_id){
                                                      echo "<td>$member[name] <input type='hidden' name='sub_id[]' value='$member[id]' >";
                                                      echo "<input type='hidden' name='user_id[]' value='$member[user_id]' > </td>";
                                                      $allowProcessing = true;
                                                      $status = "<span class='badge badge-success'>Found</span>";
                                                  } else {
                                                      // Member found but belongs to different branch
                                                      $memberBranchDetails = SelectBranchById($conn, $member['branch_id']);
                                                      $memberBranchName = ($memberBranchDetails && is_array($memberBranchDetails)) ? $memberBranchDetails['name'] : 'Unknown';
                                                      echo "<td>$member[name]</td>";
                                                      $allowProcessing = false;
                                                      $status = "<span class='badge badge-warning'>Wrong Branch ($memberBranchName)</span>";
                                                  }
                                              }else{
                                                $memberData = selectUserByName($conn, $name);
                                                if($memberData && is_array($memberData)){
                                                    echo "<td>$memberData[name] <input type='hidden' name='user_id[]' value='$memberData[id]' >  </td>";
                                                    // create min sub for the selected branch
                                                    $newSub = createMinsub($conn, $memberData['name'] . " Loan Account", $memberData['id'],12,$branch_id,"person","loan");
                                                    if(!$newSub){
                                                        $allowProcessing = false;
                                                        $status = "<span class='badge badge-danger'>Creation Failed</span>";
                                                    } else{
                                                        echo "<input type='hidden' name='sub_id[]' value='$newSub'>";
                                                        $allowProcessing = true;
                                                        $status = "<span class='badge badge-info'>New Account Created</span>";
                                                    }
                                                } else {
                                                    echo "<td>$loan[1]</td>";
                                                    $allowProcessing = false;
                                                    $status = "<span class='badge badge-danger'>Not Found</span>";
                                                }
                                              }
                                            
                                              if($allowProcessing){
                                                  echo "<td><input type='number' name='principle[]' value='$loan[2]' step='any' class=' form-control' ></td>";
                                                  echo "<td><input type='number' name='interest_amount[]' value='$loan[3]' step='any' class=' form-control' ></td>";
                                                  echo "<td><input type='number' name='interest_rate[]' value='". number_format( $loan[4],2) ."' step='any' class=' form-control' ></td>";
                                                  echo "<td><input type='date' name='date[]' value='" . (is_numeric($loan['5']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($loan['5'])->format('Y-m-d') : date('Y-m-d', strtotime($loan['5']))) . "' step='any' class='form-control'></td>";
                                                  echo "<td><input type='number' name='period[]' value='". number_format( $loan[6],0) ."' step='any' class=' form-control' ></td>";
                                              } else {
                                                  echo "<td><input type='number' value='$loan[2]' step='any' class=' form-control' disabled></td>";
                                                  echo "<td><input type='number' value='$loan[3]' step='any' class=' form-control' disabled></td>";
                                                  echo "<td><input type='number' value='". number_format( $loan[4],2) ."' step='any' class=' form-control' disabled></td>";
                                                  echo "<td><input type='date' value='" . (is_numeric($loan['5']) ? \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($loan['5'])->format('Y-m-d') : date('Y-m-d', strtotime($loan['5']))) . "' step='any' class='form-control' disabled></td>";
                                                  echo "<td><input type='number' value='". number_format( $loan[6],0) ."' step='any' class=' form-control' disabled></td>";
                                              }
                                              
                                              // Display status in separate column
                                              echo "<td>$status</td>";
                                            
                                            echo "</tr>";
                                            $counter++;
                                    }
                                
                                ?>
                        </table>
                    </div>
                    <div class=" card-footer">
                                <button type="submit" class=" btn btn-sm btn-info btn-block" name="upload_loan">Upload Data</button> 
                    </div>
                </form>
            <?php

        }
            
?>
    
   