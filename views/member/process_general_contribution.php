<div class=" card card-danger">
            <div class=" card-header"> <h5 class=" card-title">Process Members' data</h5></div>
            
            <?php
            use PhpOffice\PhpSpreadsheet\IOFactory;

            if($_SERVER['REQUEST_METHOD'] == 'POST'){
                $data = [];
                $branchIds = []; // Array to store branch IDs found from members
              //  print_r($_POST);
            if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
                $inputFileName = $_FILES['file']['tmp_name'];
                
                // If branch_id is provided (branch-specific upload)
                if(isset($branchId) && $branchId > 0){
                    $branchDetails = SelectBranchById($conn, $branchId);
                    if($branchDetails && is_array($branchDetails)){
                        ?>
                            <div class=" card-body">
                                <h5>Branch Name: <?= $branchDetails['name']?></h5>
                                <h5>Branch Address: <?= $branchDetails['address']?></h5>
                                <h5>Branch phone: <?= $branchDetails['phone']?></h5>
                            </div>
                        <?php
                    }
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

                    //print_r($data);
            }
        }


            ?>
    <form action="./controllers/member_controllers.php" method="post" class=" was-validated">
                <input type="hidden" name="date" value="<?= $date ?>">
                <input type="hidden" name="upload_general_memeber_contribution" value="">
                <div class=" form-group col-md-6">
                    <label for="">Select Debit Account</label>
                    <select name="dr_account" required id="" class=" form-control select2-form select2bs4-form">
                        <option value="">Select Below</option>
                        <?php
                            $minsubs = selectMinSubByCategory($conn,"others");
                            if($minsubs && is_array($minsubs)){
                                foreach($minsubs as $minsub){
                                    echo "<option value='$minsub[id]'>$minsub[name]</option>";
                                }
                            }
                        ?>
                    </select>
                    <div></div>
                </div>
            <div class="card-body table-responsive">
                       <table class=" table table-bordered table-striped table-sm">
                <tr>
                    <td>#</td>
                    <td>Na</td>
                    <td>Branch</td>
                    <td>Amount</td>
                    <td>Status</td>
                </tr>
                <?php
                        $counter =1;
                        $firstRow = true;
                        $lastRow = false;
                      
                        foreach($data as $member ){
                            //  print_r($member);
                            if ($firstRow) {
                                $firstRow = false;
                                continue; // Skip the first row
                            }
                            if(empty($member[1])){
                                break;
                            }
                           $name = trim($member[1]);
                           $regexName = $conn->real_escape_string( preg_replace('/\s+/', '[[:space:]]+', trim($name)));
            
                            $amount =(float) $member[2];
                            if($amount >= 1){
                                echo "<tr>";
                                echo "<td>$counter</td>";
                                
                               $member_data = selectMinSubByCheckNoAndCategory($conn, $name, $type);
                              //  echo "<pre>";
                                
                              //  print_r($member_data);
                                
                           // echo "</pre>";
                                if($member_data && is_array($member_data)){
                                    // Get branch details using branch_id from min_subs table
                                    $memberBranchId = $member_data['branch_id'];
                                    $memberBranchDetails = SelectBranchById($conn, $memberBranchId);
                                    $branchName = ($memberBranchDetails && is_array($memberBranchDetails)) ? $memberBranchDetails['name'] : 'Unknown';
                                    echo "<td><input type='hidden' name='sub_id[]' value='$member_data[id]'>";
                                    echo "<input type='hidden' name='member_branch_id[]' value='$memberBranchId'>";
                                    echo  "$member_data[name]   </td>";
                                    echo "<td><span class='badge badge-info'>$branchName</span></td>";
                                    echo "<td><input type='number' name='amount[]' value='$amount' step='0.01' required></td>";
                                    echo "<td><span class='badge badge-success'>Found</span></td>";
                                } else {
                                    echo "<td>$name $member[2]</td>";
                                    echo "<td><span class='badge badge-secondary'>-</span></td>";
                                    echo "<td><input type='number' value='$amount' step='0.01' disabled></td>";
                                    echo "<td><span class='badge badge-danger'>Not Found</span></td>";
                                }
                                echo "</tr>";
                                $counter++;
                            }
                          if($counter == 30){
                             // break;
                          }
                        }
 
                        ?>
            </table>
          </div>
            <div class=" card-footer">
                <button type="submit" class=" btn btn-sm btn-info btn-block" name="upload_general_memeber_contribution">Upload Data</button>
            </div>
    </form>