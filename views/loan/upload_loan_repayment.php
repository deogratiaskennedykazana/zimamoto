<div class=" card card-danger">
            <div class=" card-header"> <h5 class=" card-title">Process Loan Repayment Data</h5></div>
            
            <?php
            use PhpOffice\PhpSpreadsheet\IOFactory;

            if($_SERVER['REQUEST_METHOD'] == 'POST'){
                $data = [];
              //  print_r($_POST);
            if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
                $inputFileName = $_FILES['file']['tmp_name'];
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

                   // print_r($data);
            }
        }


            ?>
    <form action="./controllers/loan_controller.php" method="post" class=" was-validated">
                <input type="hidden" name="branch_id" value="<?= $branchId ?>">
                <input type="hidden" name="date" value="<?= $date ?>">
                <div class=" form-group col-md-6">
                    <label for="">Select Credit Account</label>
                    <select name="cr_account" required id="" class=" form-control select2-form select2bs4-form">
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
                    <td>Name</td>
                    <td>Amount Paid</td>
                    <td>Status</td>
                </tr>
                <?php
                        $counter =1;
                        $firstRow = true;
                        $lastRow = false;
                        foreach($data as $member ){

                            if ($firstRow) {
                                $firstRow = false;
                                continue; // Skip the first row
                            }
                            if(empty($member[1])){
                                break;
                            }
                            $name = htmlspecialchars($member[1]) ;
                            $amount =(float) $member[2];
                            if($amount >0){
                                echo "<tr>";
                                echo "<td>$counter</td>";
                                
                                $member_data = selectMinSubByNameAndCategory($conn, $name, "loan");
                                
                                if($member_data && is_array($member_data)){
                                    // Check if the member belongs to the selected branch
                                    if($member_data['branch_id'] == $branchId){
                                        echo "<td><input type='hidden' name='sub_id[]' value='$member_data[id]'>$member_data[name]</td>";
                                        echo "<td><input type='number' name='amount[]' value='$amount' step='0.01' required></td>";
                                        echo "<td><span class='badge badge-success'>Found</span></td>";
                                    } else {
                                        // Member found but belongs to different branch
                                        $memberBranchDetails = SelectBranchById($conn, $member_data['branch_id']);
                                        $memberBranchName = ($memberBranchDetails && is_array($memberBranchDetails)) ? $memberBranchDetails['name'] : 'Unknown';
                                        echo "<td>$member_data[name]</td>";
                                        echo "<td><input type='number' value='$amount' step='0.01' disabled></td>";
                                        echo "<td><span class='badge badge-warning'>Wrong Branch ($memberBranchName)</span></td>";
                                    }
                                } else {
                                    echo "<td>$name</td>";
                                    echo "<td><input type='number' value='$amount' step='0.01' disabled></td>";
                                    echo "<td><span class='badge badge-danger'>Not Found</span></td>";
                                }
                                echo "</tr>";
                                $counter++;
                            }
                          
                        }
                        ?>
            </table>
            </div>
            <div class=" card-footer">
                <button type="submit" class=" btn btn-sm btn-info btn-block" name="upload_loan_repayment">Upload Repayment Data</button>
            </div>
    </form>