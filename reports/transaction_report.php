        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
         <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
         <script src="https://www.w3schools.com/lib/w3.js"></script>

    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
                <script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/dataTables.buttons.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.bootstrap5.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
                <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.html5.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.print.min.js"></script>
                <script src="https://cdn.datatables.net/buttons/2.4.2/js/buttons.colVis.min.js"></script>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
         <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">


<script>
    $(document).ready(function() {
    $('.data-table-basic').DataTable( {
         dom: 'Bfrtip',
        pagingType: 'full_numbers',
        'ordering':false,
         "paging": false
        
        
    } );
} );
</script>
<?php
include("../configs.php");

$id = "";
$date1 ='';
$date2 ='';
//print_r($_POST);
if(isset($_POST)){
   $id =$conn->real_escape_string( $_POST['sub_id']) ;
   $date1 =$conn->real_escape_string(  $_POST['date1']);
   $date2 =$conn->real_escape_string(  $_POST['date2']);
}

$sql_name = "SELECT name FROM `subsidiaries` WHERE id='$id'";
$query = mysqli_query($conn, $sql_name);
$row = mysqli_fetch_assoc($query);
$name = $row['name'];
?>


  <h5 class="w3-text-blue text-center my-2 ">Name: <?php echo $name ?> Transaction Report Between <?php echo date("d-M-Y", strtotime($date1)) ?> AND <?php echo date("d-M-Y", strtotime($date2)) ?></h5>

  <?php
  $balance = 0;
  $net_credit =0;
  $net_debit =0;
  // Retrieve the opening balance for the selected subsidiary
  $sql_opening_balance = "SELECT * FROM `starting_balance` WHERE account_id='$id' AND date_  BETWEEN '$date1' AND  '$date2' ";
  $result_opening_balance = $conn->query($sql_opening_balance);
  $row_opening_balance = $result_opening_balance->fetch_assoc();
  echo "<table class='container table table-hover table-bordered data-table-basic'>";
  echo "<thead><tr class=' table-primary'><th>Date</th><th>Details</th><th>Debit</th><th>Credit</th><th>Balance</th></tr></thead><tbody>";

  // Initialize the balance with the opening balance amount
  if(mysqli_num_rows(mysqli_query($conn,$sql_opening_balance))>0){

  
//   $balance +=$row_opening_balance['ammount'];
  if($row_opening_balance['type'] == "credit"){

     $balance -=$row_opening_balance['ammount'];
     $net_credit -=$row_opening_balance['ammount'];
     
    
  echo "<tr><td>$row_opening_balance[date_]</td><td>Opening Balance</td><td></td><td>".number_format($balance,2)."</td><td>".number_format($balance,2)."</td></tr>";
  } else{
       $balance += $row_opening_balance['ammount'];
       $net_debit += $row_opening_balance['ammount'];
    echo "<tr> <td>$row_opening_balance[date_]</td><td>Opening balance</td><td>".number_format( $balance,2)."</td><td></td><td>".number_format($balance,2)."</td></tr>";
    

  }
  } 
  // Display the opening balance row
 

  // Retrieve the transaction vouchers for the selected subsidiary
  $sql_vouchers = "SELECT t.*, d.name AS dr_name, c.name AS cr_name
      FROM transaction_voucher t
      LEFT JOIN subsidiaries d ON d.id = t.dr_account
      LEFT JOIN subsidiaries c ON c.id = t.cr_account
      WHERE (t.dr_account = '$id' OR t.cr_account = '$id') AND t.date_ BETWEEN '$date1' AND '$date2'
      ORDER BY t.date_";
  $result_vouchers = $conn->query($sql_vouchers);

  if ($result_vouchers->num_rows > 0) {
    // Display the transaction vouchers
    while ($row_vouchers = $result_vouchers->fetch_assoc()) {
        $debit =0;
        $credit =0;
      $date = $row_vouchers['date_'];
      $dr_name = $row_vouchers['dr_name'];
      $cr_name = $row_vouchers['cr_name'];
      $details = '';
      if ($row_vouchers['dr_account'] == $id ) {
        $details = " $cr_name";
        $debit = $row_vouchers['dr_ammount'];
        $net_debit += $row_vouchers['dr_ammount'];
        
        $credit = 0;
      } else if ($row_vouchers['cr_account'] == $id) {
        $details = " $dr_name";
        $debit = 0;
        $credit = $row_vouchers['cr_ammount'];
        $net_credit -= $row_vouchers['cr_ammount'];
      }   
    //   if ($row_vouchers['dr_account'] == $id && $row_vouchers['date_'] >= date('Y-01-01')) {
    //     $details = " $cr_name";
    //     $debit = $row_vouchers['dr_ammount'];
    //     $credit = 0;
    //   } else if ($row_vouchers['cr_account'] == $id && $row_vouchers['date_'] >= date('Y-01-01')) {
    //     $details = " $dr_name";
    //     $debit = 0;
    //     $credit = $row_vouchers['cr_ammount'];
    //   }
     
    //   if( $row_vouchers['date_']>= date('Y-01-01')):
    //       $balance += $debit - $credit;
    //          echo "<tr><td>$date</td><td>$details</td><td>".number_format($debit,2)."</td><td>".number_format($credit,2)."</td><td>".number_format($balance,2)."</td></tr>";
    //   endif; 
      
           $balance += $debit - $credit;
             echo "<tr><td>$date</td><td>$details</td><td>".number_format($debit,2)."</td><td>".number_format($credit,2)."</td><td>".number_format($balance,2)."</td></tr>";
      
    }
  } else {
    echo "<tr><td></td><td></td><td></td><td></td><td >No transactions found.</td></tr>";
  }
        echo "<tr class=' table-primary'><td></td><td>Total</td><td>".number_format($net_debit,2)."</td><td>".number_format($net_credit,2)."</td><td>".number_format($balance,2)."</td></tr>";
  echo "</tbody></table>";
  return;
  ?>
</div>


   
<?php

return;
include("../configs.php");
include("../links.php");
$id = $_GET['id'];

$sql_name = "SELECT name FROM `subsidiaries` WHERE id='$id'";
$query = mysqli_query($conn, $sql_name);
$row = mysqli_fetch_assoc($query);
$name = $row['name'];

// Get opening balance
$sql_balance = "SELECT type, ammount FROM `starting_balance` WHERE account_id = '$id'";
$query_balance = mysqli_query($conn, $sql_balance);
$row_balance = mysqli_fetch_assoc($query_balance);
$opening_balance = $row_balance['ammount'];
$balance_type = $row_balance['type'];

// Set opening balance amount and type
if ($balance_type == 'credit') {
  $credit = number_format($opening_balance);
  $debit = '';
} else {
  $debit = number_format($opening_balance);
  $credit = '';
}

?>

<div class=" w3-container">
  <div class=" w3-row w3-blue">
    <div class=" w3-third w3-hide-small">logo</div>
    <div class=" w3-third ">
      <h3>TELLIC ACCOUNTING PACKAGE</h3>
    </div>
    <div class=" w3-third">
      <h5 class=" w3-right w3-container">Logout</h5>
    </div>
  </div>

  <h5 class=" w3-text-blue">Name: <?php echo $name?> Ledger Report</h5>

  <?php
  $sql = "SELECT t.*, d.name AS dr_name, c.name AS cr_name
          FROM transaction_voucher t
          LEFT JOIN subsidiaries d ON d.id = t.dr_account
          LEFT JOIN subsidiaries c ON c.id = t.cr_account
          WHERE t.dr_account = '$id' OR t.cr_account = '$id'
          ORDER BY t.date_";
  $result = $conn->query($sql);

  if ($result->num_rows > 0) {
    echo "<table class='w3-table w3-striped data-table-basic'>";
    echo "<tr><th>Date</th><th>Details</th><th>Debit</th><th>Credit</th><th>Balance</th></tr>";
    $balance = $opening_balance;
    while ($row = $result->fetch_assoc()) {
      $date = $row['date_'];
      $dr_name = $row['dr_name'];
      $cr_name = $row['cr_name'];
      $details = '';
      if ($row['dr_account'] == $id) {
          $details = " $cr_name";
          $debit = $row['dr_ammount'];
          $credit = '';
      } else if ($row['cr_account'] == $id) {
          $details = " $dr_name";
          $debit = '';
          $credit = $row['cr_ammount'];
      }
      $balance += (int)$debit - (int)$credit;
      echo "<tr><td>$date</td><td>$details</td><td>". number_format($debit)."</td><td>". number_format($credit)."</td><td>". number_format($balance)."</td></tr>";
    }
    echo "</table>";
  } else {
    echo "0 results";
  }
  ?>
</div>


