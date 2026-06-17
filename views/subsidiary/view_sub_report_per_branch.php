<div class=" card card-success">
    <div class=" card-header"> <h4 class=" card-title">Subsidiary Report</h4> </div>
        <div class=" card-body">
            <div class=" row">
                <div class=" col-md-6 col-sm-10">
               
                        <?php
                            $subDetails = selectSubsidiaryById($conn,$subId);
                            if($subDetails && is_array($subDetails)){
                                ?>
                                    <h5 class=" card-text">Subsidiary Name: <?= $subDetails['name'] ?> </h5>
                                    <h5 class=" card-text">Subsidiary Category: <?= $subDetails['category'] ?> </h5>
                                    <h5 class=" card-text">Subsidiary Type: <?= $subDetails['type'] ?> </h5>
                                <?php
                            }
                        ?>
                 
                 </div>
                 <div class=" col-md-6 col-sm-10">
                    <?php
                        $branch = SelectBranchById($conn,$branchId);
                        if($branch && is_array($branch)){
                            ?>
                                <h5 class=" card-text">Branch Name: <?= $branch['name'] ?></h5>
                                <h5 class=" card-text">Branch address: <?= $branch['address'] ?></h5>
                            <?php
                        }
                    ?>
                 </div>
            </div>
        </div>
        <div class=" card-footer">
               <h5 class=" card-title"> Transactions </h5>
        </div>
        <div class=" card-body">
           <table class="table table-bordered table-sm table-striped data-table">
    <thead class="table-info">
        <tr>
            <th>#</th>
            <th>Date</th>
            <th>Corresponding</th>
            <th>Description</th>
            <th>Debit Amount</th>
            <th>Credit Amount</th>
            <th>Balance</th>
        </tr>
    </thead>
    <tbody>
        <?php
            $balance = 0;
            $openingBalance = selectOpeningBalanceBySubId($conn, $subId);
            $counter = 1;
            
            if($openingBalance && is_array($openingBalance)){
                if($openingBalance['type'] === 'debit'){
                    $balance += (float) $openingBalance['ammount'];
                    echo "<tr>";
                        echo "<td>$counter</td>";
                        echo "<td>{$openingBalance['date_']}</td>";
                        echo "<td>Opening Balance</td>";
                        echo "<td>Opening Balance of {$openingBalance['date_']}</td>";
                        echo "<td>" . number_format($openingBalance['ammount'], 2) . "</td>";
                        echo "<td>" . number_format(0, 2) . "</td>";
                        echo "<td>" . number_format($balance, 2) . "</td>";
                    echo "</tr>";
                } elseif($openingBalance['type'] === 'credit'){
                    $balance -= (float) $openingBalance['ammount'];
                    echo "<tr>";
                        echo "<td>$counter</td>";
                        echo "<td>{$openingBalance['date_']}</td>";
                        echo "<td>Opening Balance</td>";
                        echo "<td>Opening Balance of {$openingBalance['date_']}</td>";
                        echo "<td>" . number_format(0, 2) . "</td>";
                        echo "<td>" . number_format($openingBalance['ammount'], 2) . "</td>";
                        echo "<td>" . number_format($balance, 2) . "</td>";
                    echo "</tr>";
                }
            }
            
            $transactions = selectTransactionBySubIdAndBranchId($conn, $subId, $branchId);
            
            if($transactions && is_array($transactions)){
                foreach($transactions as $transaction){
                    $counter++;
                    echo "<tr>";
                        echo "<td>$counter</td>";
                        echo "<td>{$transaction['date_']}</td>";
                        
                        if($transaction['dr_account'] == $subId){
                            $balance += $transaction['dr_ammount']; 
                            echo "<td>{$transaction['credit_ac']}</td>";
                            echo "<td>{$transaction['description']}</td>";
                            echo "<td>" . number_format($transaction['dr_ammount'], 2) . "</td>";                                    
                            echo "<td>" . number_format(0, 2) . "</td>";                                    
                            echo "<td>" . number_format($balance, 2) . "</td>";                                    
                        } elseif($transaction['cr_account'] == $subId){
                            $balance -= $transaction['dr_ammount']; 
                            echo "<td>{$transaction['debit_acc']}</td>";
                            echo "<td>{$transaction['description']}</td>";
                            echo "<td>" . number_format(0, 2) . "</td>"; 
                            echo "<td>" . number_format($transaction['dr_ammount'], 2) . "</td>";                                    
                            echo "<td>" . number_format($balance, 2) . "</td>";   
                        }
                    echo "</tr>";
                }
            }
        ?>
    </tbody>
</table>
           
        </div>
</div>

<script src="./dist/datatable2/jquery-3.7.1.js"></script>
 <script src="./dist/datatable2/datatables.js"></script>
 
 <script>
     $(document).ready(function(){
                 new DataTable('.data-table', {
                     responsive: true,
                     ordering:false,
                     layout: {
                 topStart: {
                     buttons: [
                         'copy', 'excel', 'pdf','print'
                     ]
                 }
             }
         });
     });
 </script>