<div class="card card-danger">
    <div class="card-header">
        <h5 class="card-title">Process Members' Balance Correction</h5>
    </div>
    
    <?php
    use PhpOffice\PhpSpreadsheet\IOFactory;

    if($_SERVER['REQUEST_METHOD'] == 'POST'){
        $data = [];
        $branchIds = [];
        
        if(isset($_FILES['file']) && $_FILES['file']['error'] == UPLOAD_ERR_OK){
            $inputFileName = $_FILES['file']['tmp_name'];
            try{
                $spreadSheet = IOFactory::load($inputFileName);
                $workSheet  = $spreadSheet->getActiveSheet();
                $highestRow = $workSheet->getHighestRow();
                $highestColumn = $workSheet->getHighestColumn();
                
                for($row = 1; $row <= $highestRow; $row++){
                    $rowData = $workSheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
                    $data[] = $rowData[0];
                }
            } catch(Exception $e){
                echo 'Error reading the Excel file: ' . $e->getMessage();
            }
        }
    }
    ?>
    
    <form action="./controllers/member_controllers.php" method="post" class="was-validated">
        <input type="hidden" name="date" value="<?= $date ?>">
        <input type="hidden" name="upload_general_memeber_contribution" value="">
        
        <!-- Account Selection Section -->
        <div class="form-group col-md-6">
            <label for="">Select Debit Account</label>
            <select name="dr_account" required id="" class="form-control select2-form select2bs4-form">
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
        </div>
        
        <!-- Balance Comparison Table -->
        <div class="card-body ">
            <table class="table table-bordered table-striped table-sm">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Branch</th>
                        <th>New Balance (Excel)</th>
                        <th>Available Balance (System)</th>
                        <th>Difference</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $counter = 1;
                $firstRow = true;
                $totalNewBalance = 0;
                $totalAvailableBalance = 0;
                $totalDifference = 0;
                
                foreach($data as $member){
                    // Skip header row
                    if ($firstRow) {
                        $firstRow = false;
                        continue;
                    }
                    
                    // Break if empty row
                    if(empty($member[1])){
                        break;
                    }
                    
                    // Extract member data from Excel
                    $name = trim($member[1]);
                    $newBalance = isset($member[3]) && !empty($member[3]) ? (float)$member[3] : 0.00;
                    
                    echo "<tr>";
                    echo "<td>$counter</td>";
                    
                    // Fetch member account details
                    $member_data = selectMinSubByCheckNoAndCategory($conn, $name, $type);
                    
                    if($member_data && is_array($member_data)){
                        $minSubId = $member_data['id'];
                        $memberBranchId = $member_data['branch_id'];
                        $memberBranchDetails = SelectBranchById($conn, $memberBranchId);
                        $branchName = ($memberBranchDetails && is_array($memberBranchDetails)) ? $memberBranchDetails['name'] : 'Unknown';
                        
                        // Calculate system balance for this account
                        $availableBalance = 0;
                        $transactions = getMinTransactionByMinSubId($conn, $minSubId);
                        if($transactions && is_array($transactions)){
                            foreach ($transactions as $transaction) {
                                if($transaction['dr_account'] == $minSubId){
                                    $availableBalance += $transaction['amount'];
                                }elseif($transaction['cr_account'] == $minSubId){
                                    $availableBalance -= $transaction['amount'];
                                }
                            }
                        }
                        
                        // Calculate difference
                        $difference = $newBalance - $availableBalance;
                        
                        // Store data for submission
                        echo "<input type='hidden' name='sub_id[]' value='$minSubId'>";
                        echo "<input type='hidden' name='member_branch_id[]' value='$memberBranchId'>";
                        echo "<input type='hidden' name='difference[]' value='$difference'>";
                        
                        // Display row data
                        echo "<td>$member_data[name]</td>";
                        echo "<td><span class='badge badge-info'>$branchName</span></td>";
                        echo "<td class='text-right'>" . number_format($newBalance, 2) . "</td>";
                        echo "<td class='text-right'>" . number_format($availableBalance, 2) . "</td>";
                        echo "<td class='text-right'>" . number_format($difference, 2) . "</td>";
                        echo "<td><span class='badge badge-success'>Found</span></td>";
                        
                        // Add to totals
                        $totalNewBalance += $newBalance;
                        $totalAvailableBalance += $availableBalance;
                        $totalDifference += $difference;
                        
                    } else {
                        // Member not found in system
                        echo "<td>$name</td>";
                        echo "<td><span class='badge badge-secondary'>-</span></td>";
                        echo "<td class='text-right'>" . number_format($newBalance, 2) . "</td>";
                        echo "<td class='text-right'>-</td>";
                        echo "<td class='text-right'>-</td>";
                        echo "<td><span class='badge badge-danger'>Not Found</span></td>";
                    }
                    
                    echo "</tr>";
                    $counter++;
                }
                
                // Display total row
                echo "<tr class='table-danger font-weight-bold'>";
                echo "<td colspan='3' class='text-right'>TOTAL</td>";
                echo "<td class='text-right'>" . number_format($totalNewBalance, 2) . "</td>";
                echo "<td class='text-right'>" . number_format($totalAvailableBalance, 2) . "</td>";
                echo "<td class='text-right'>" . number_format($totalDifference, 2) . "</td>";
                echo "<td>-</td>";
                echo "</tr>";
                ?>
                </tbody>
            </table>
        </div>
        
        <!-- Submit Button -->
        <div class="card-footer">
            <button type="submit" class="btn btn-sm btn-info btn-block" name="upload_general_balance_memeber_contribution">Apply Correction</button>
        </div>
    </form>
</div>