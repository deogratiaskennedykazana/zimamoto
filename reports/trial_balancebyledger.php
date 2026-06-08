
<?php
// Establish your database connection
include("../configs.php");
include("../links.php");
 ?>
    <div class=" w3-border w3-container">
        <h3>NAME OF SACCOS : XXXX SACCOS LTD</h3>
        <h4>MSP CODE:  MSPXXX	</h4>
        <h4>SECTORAL CLASSIFICATION OF LOANS AS AT:</h4>
        <h4>Amount reported as TZS 0.00	</h4>
    		

    </div>
    <?php
// Check the connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$creditSide =0;
$debitSide = 0;

// Define your start_date and end_date variables
$start_date = $_POST['date1'];
$end_date = $_POST['date2'];

// SQL query to fetch the ledger, subsidiary, and transaction_voucher data with date range
$sql = "SELECT l.id AS ledger_id, l.name AS ledger_name, s.id AS subsidiary_id, s.name AS subsidiary_name,
        SUM(CASE WHEN t.dr_account = s.id THEN t.dr_ammount ELSE 0 END) AS dr_total,
        SUM(CASE WHEN t.cr_account = s.id THEN t.cr_ammount ELSE 0 END) AS cr_total
        FROM ledgers l
        JOIN subsidiaries s ON l.id = s.ledger_id
        JOIN transaction_voucher t ON t.dr_account = s.id OR t.cr_account = s.id
        WHERE t.date_ BETWEEN '$start_date' AND '$end_date'
        GROUP BY l.id, l.name, s.id, s.name";

$result = $conn->query($sql);

// Initialize an empty array to store the trial balance data
$trialBalance = array();

// Loop through the result set
while ($row = $result->fetch_assoc()) {
    $ledgerId = $row["ledger_id"];
    $ledgerName = $row["ledger_name"];
    $subsidiaryId = $row["subsidiary_id"];
    $subsidiaryName = $row["subsidiary_name"];
    $drAmount = $row["dr_total"];
    $crAmount = $row["cr_total"];
    $balance = $drAmount - $crAmount;

    // Check if the ledger already exists in the trial balance array
    if (!array_key_exists($ledgerId, $trialBalance)) {
        // If not, initialize a new ledger entry
        $trialBalance[$ledgerId] = array(
            "name" => $ledgerName,
            "subsidiaries" => array(),
            "debit_total" => 0,
            "credit_total" => 0,
            "balance" => 0
        );
    }

    // Add the subsidiary, debit, credit, and balance amounts to the ledger's subsidiary array
    $trialBalance[$ledgerId]["subsidiaries"][$subsidiaryId] = array(
        "name" => $subsidiaryName,
        "dr_amount" => $drAmount,
        "cr_amount" => $crAmount,
        "balance" => $balance
    );

    // Update the debit, credit, and balance totals for the ledger
    $trialBalance[$ledgerId]["debit_total"] += $drAmount;
    $trialBalance[$ledgerId]["credit_total"] += $crAmount;
    $trialBalance[$ledgerId]["balance"] += $balance;
}

// Close the database connection
$conn->close();

// Print the trial balance in HTML table form
echo '<table class=" table table-bordered">
        <tr class=" bg-warning">
            <th>Ledger</th>
            <th class=" w3-hide">Subsidiary</th>
            <th>Debit</th>
            <th>Credit</th>
            
        </tr>';

foreach ($trialBalance as $ledger) {
    
    // echo '<tr><td colspan="5"><strong>Ledger: ' . $ledger["name"] . '</strong></td></tr>';

    foreach ($ledger["subsidiaries"] as $subsidiary) {
        
    }

    // Print ledger totals row
    ?>
    <tr>
    <td ><strong><?php echo $ledger['name'];?></strong></td>

    <?php 
     if( $ledger["balance"]>0){
        $debitSide += $ledger['balance'];
        ?>
        <td><?php echo number_format( $ledger["balance"],2) ?></td>
        <?php
     } elseif($ledger["balance"]<0){ 
        $creditSide += $ledger['balance']; 
    ?>  
        <td></td>
        <td><?php echo number_format( $ledger["balance"],2) ?></td>
        <?php
        }  else{
        ?>
            
        <td><?php echo number_format( $ledger["balance"],2)  ?></td>
        <?php }?>
    </tr>
    <?php   
}
?>
    <tr class=" bg-primary w3-text-white">
        <td>Total</td>
        <td><?php echo number_format( $debitSide, 2) ?></td>
        <td><?php echo  number_format( $creditSide, 2)  ?></td>
    </tr>
    </table>

<?php
return;
 echo "<tr class=' '>";
    echo "<td>totali<td>";
    echo "<td>".$debitSide. "</td>";
    echo "<td>".$creditSide. "</td>";
    echo "</tr>";
   
// Print overall totals row
echo '<tr class=" w3-hide">
        <td colspan="2"><strong>Overall Total</strong></td>
        <td>';

$overallDebitTotal = 0;
$overallCreditTotal = 0;
$overallBalance = 0;

foreach ($trialBalance as $ledger) {
    $overallDebitTotal += $ledger["debit_total"];
    $overallCreditTotal += $ledger["credit_total"];
    $overallBalance += $ledger["balance"];
}

echo $overallDebitTotal . '</td>
        <td>' . $overallCreditTotal . '</td>
        <td>' . $overallBalance . '</td>
    </tr>';
    echo "<tr>";
    echo "<td>totali<td>";
    echo "<td>".$debitSide. "</td>";
    echo "<td>".$creditSide. "</td>";
    echo "</tr>";
echo '</table>';
return;
?>

