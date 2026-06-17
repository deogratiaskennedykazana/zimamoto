<?php
    session_start();
    if(!$_SESSION){
        // echo "<script>window.history.back();</script>";
    }
        require_once "../functions/transaction_functions.php";
        require_once "../functions/min_transaction_functions.php";
        require_once "../configs.php";
        $conn = openConn();
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['addreciptvoucher'])){
            // print_r($_POST);
            $refNo = $conn->real_escape_string($_POST['voucher_ref']);
            $date = $conn->real_escape_string($_POST['voucherdate']);
            $currency = (float) $_POST['curr'];
            $dr_account = (int) $_POST['deb_account'];
            $branchId = (int) $_POST['branch'];
            
            // arrays
            $cr_accounts = $_POST['cr_account'];
            $eqvs = $_POST['eqv'];
            $descriptions = $_POST['desc'];
            for($i = 0; $i < count($cr_accounts); $i++){
                $cr_account = (int) $cr_accounts[$i];
                $amount = (float) $eqvs[$i];
                $description = $conn->real_escape_string($descriptions[$i]);
                $newTransaction = createTransaction($conn,$refNo,$currency,$dr_account,$description,$amount,$cr_account,$date,null,$branchId, 'active');
                if(!$newTransaction){
                    echo $newTransaction;
                    return;
                }
                  
                
            }
            echo "<script>alert('SUCCESS'); window.history.back();</script>";
        }
        if(isset($_POST['addpaymentVoucher'])){
            // print_r($_POST);

            $date = $conn->real_escape_string($_POST['voucherdate']);
            $refNo = $conn->real_escape_string($_POST['voucher_ref']);
            $branchId = (int) $_POST['branchId'];
            $cr_account = (int) $_POST['cr_account'];
            $currencyValue = (float) $_POST['curr'];
            // array
            $dr_accounts = $_POST['dr_account'];
            $eqvs = $_POST['eqv'];
            $descs = $_POST['desc'];

            // loop
            for($i = 0; $i<count($eqvs); $i++){
                $eqv = (float) $eqvs[$i];
                $desc = $conn->real_escape_string($descs[$i]);
                $dr_account = (int) $dr_accounts[$i];
                $newTransaction = createTransaction($conn,$refNo,$currencyValue,$dr_account,$desc,$eqv,$cr_account,$date,null,$branchId, 'pending');
                if(!$newTransaction){
                    echo $newTransaction;
                    return;
                }
            }
            echo "<script>alert('SUCCESS'); window.history.back();</script>";

        }

        if(isset($_POST['addminreciptvoucher'])){
            print_r($_POST);
            $date = $conn->real_escape_string($_POST['voucherdate']);
            $refNo = $conn->real_escape_string($_POST['voucher_ref']);
            $branchId = (int) $_POST['branch'];
            $dr_account = (int) $_POST['deb_account'];
            $currencyValue = (float) $_POST['curr'];

            $cr_accounts = $_POST['min_item'];
            $eqvs = $_POST['eqv'];
            $descs = $_POST['desc'];
            for($i = 0; $i<count($eqvs);$i++){
                $desc = $conn->real_escape_string($descs[$i]);
                $eqv = (float) $eqvs[$i];
                $cr_account = (int) $cr_accounts[$i];
                $newTransaction = createMinTransaction($conn,$refNo,$dr_account,$desc,$eqv,$cr_account,$date,(int)$_SESSION['userid'],$branchId, 'active');
                if(!$newTransaction){
                    echo $newTransaction;
                    return;
                }
            }
            echo "<script>alert('SUCCESS'); window.history.back();</script>";
        }
        
        
        
        
         if (isset($_POST['addjv'])) {
           
            $branchId = (int) $conn->real_escape_string($_POST['branchId']);
            $currency = (int) $conn->real_escape_string($_POST['currency']);
            $voucher_ref = $conn->real_escape_string(trim($_POST['voucher_ref']));
            $voucherdate   = $conn->real_escape_string(trim($_POST['voucherdate']));
            $userId        = $_SESSION['userid'];  
        
            // Arrays  
            $deb_accounts  = $_POST['deb_account'];
            $cr_accounts   = $_POST['cr_account'];
            $debt_amounts  = $_POST['debt_amount'];
            $equiv_dr      = $_POST['equiv_dr'];
            $descriptions  = $_POST['desc'];
        
            for ($i = 0; $i < count($deb_accounts); $i++) {
                $dr_account   = (int) $deb_accounts[$i];
                $cr_account   = (int) $cr_accounts[$i];
                $dr_amount    = (float) $debt_amounts[$i];
                $description  = $conn->real_escape_string(trim($descriptions[$i]));
        
                $sql = "INSERT INTO transaction_voucher (
                            reference_no, currency, dr_account, description, dr_ammount,
                            cr_account, cr_ammount, date_, status, reconciled,
                            user_id, branch_id
                        ) VALUES (
                            '$voucher_ref', '$currency', '$dr_account', '$description', '$dr_amount',
                            '$cr_account', '$dr_amount', '$voucherdate', 'pending', 'no',
                            '$userId', '$branchId'
                        )";
        
                if (!mysqli_query($conn, $sql)) {
                    echo "MySQL Error: " . $conn->error;
                    return;
                }
            }
        
            echo "<script>alert('Added'); window.history.back();</script>";
        }
         if (isset($_POST['addminjv'])) {
           print_r($_POST);
            $branchId = (int) $conn->real_escape_string($_POST['branchId']);
            $currency = (int) $conn->real_escape_string($_POST['currency']);
            $refNo = $conn->real_escape_string(trim($_POST['voucher_ref']));
            $date   = $conn->real_escape_string(trim($_POST['voucherdate']));
            $userId        = $_SESSION['userid'];  
        
            // Arrays  
            $deb_accounts  = $_POST['min_item'];
            $cr_accounts   = $_POST['cr_account'];
            $debt_amounts  = $_POST['debt_amount'];
            $equiv_dr      = $_POST['equiv_dr'];
            $descriptions  = $_POST['desc'];
        
            for ($i = 0; $i < count($deb_accounts); $i++) {
                $dr_account   = (int) $deb_accounts[$i];
                $cr_account   = (int) $cr_accounts[$i];
                $eqv    = (float) $equiv_dr[$i];
                $desc  = $conn->real_escape_string(trim($descriptions[$i]));
        
                $newTransaction = createMinTransaction($conn,$refNo,$dr_account,$desc,$eqv,$cr_account,$date,(int)$_SESSION['userid'],$branchId, 'active');
                if(!$newTransaction){
                    echo $newTransaction;
                    return;
                }
            }
        
           echo "<script>alert('Added'); window.location.href='../?page=min_journal_voucher';</script>";
        }
        
           
  if (isset($_POST['addpurchvocher'])) {
    
    $date        = $conn->real_escape_string(trim($_POST['date']));
    $voucher_ref = $conn->real_escape_string(trim($_POST['voucher_ref']));
    $currency    = (int) $conn->real_escape_string($_POST['currency']);
    $cr_account  = (int) $conn->real_escape_string($_POST['cr_account']);
    $tax_rates   = (float) $conn->real_escape_string($_POST['rates']);
    $currency_rate = (float) $conn->real_escape_string($_POST['curr']);
    $branchId    = (int) $conn->real_escape_string($_POST['branchId']);
    $subtotal    = (float) $conn->real_escape_string($_POST['subtotal']);
    $tax         = (float) $conn->real_escape_string($_POST['tax']);
    $total       = (float) $conn->real_escape_string($_POST['total']);
    $userId      = (int) $_SESSION['userid'];

   
    $items        = $_POST['item'];
    $quantities   = $_POST['quantity'];
    $units        = $_POST['unit'];
    $prices       = $_POST['price'];
    $descriptions = $_POST['description'];
    $amounts      = $_POST['amount'];

    // 1. Insert into `purchase_voucher`
    $sql0 = "INSERT INTO `purchase_voucher` 
        (`branch_id`, `voucher_type`, `date_`, `currency_id`, `reference`, `cr_account_id`, `cr_ammount`,`tax_rate`,`currency_rate`) 
        VALUES ($branchId, 1, '$date', $currency, '$voucher_ref', $cr_account, $total,$tax_rates,$currency_rate)";
    
    if (mysqli_query($conn, $sql0)) {
        $pv_id = mysqli_insert_id($conn);

        // 2. Insert all items into `purchase_voucher_details`
        for ($i = 0; $i < count($items); $i++) {
            $item_id = (int) $items[$i];
            $qty     = (float) $quantities[$i];
            $unit    = (int) $units[$i];
            $price   = (float) $prices[$i];
            $amount  = (float) $amounts[$i];
            $desc    = $conn->real_escape_string($descriptions[$i]);

            $sql1 = "INSERT INTO `purchase_voucher_details` 
                (`pv_id`, `dr_account_id`, `quantity`, `price`, `dr_ammount`, `unit`, `description`) 
                VALUES ($pv_id, $item_id, $qty, $price, $amount, $unit, '$desc')";
            mysqli_query($conn, $sql1);
        }

        // 3. Insert subtotal amount   into `transaction_voucher`  
        $sqlv = "INSERT INTO `transaction_voucher` 
            (`reference_no`, `currency`, `dr_account`, `description`, `dr_ammount`, `cr_account`, `cr_ammount`, `date_`, `status`, `reconciled`, `user_id`, `branch_id`)
            VALUES ('$voucher_ref', $currency, $item_id, 'Purchase Voucher Summary', $amount, $cr_account, $subtotal, '$date', 'pending', 'no', $userId, $branchId)";
        mysqli_query($conn, $sqlv);

        // 4. If tax exists,  
        if ($tax_rates > 0) {
            $vat_account_id = 60;
            $sqlt = "INSERT INTO `transaction_voucher` 
                (`reference_no`, `currency`, `dr_account`, `description`, `dr_ammount`, `cr_account`, `cr_ammount`, `date_`, `status`, `reconciled`, `user_id`, `branch_id`)
                VALUES ('$voucher_ref', $currency, $vat_account_id, 'VAT Tax', $tax, $cr_account, $tax, '$date', 'pending', 'no', $userId, $branchId)";
            mysqli_query($conn, $sqlt);
        }

        echo "<script>alert('Added.'); window.history.back();</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
} 


if (isset($_POST['addslvocher'])) {

    $date        = $conn->real_escape_string(trim($_POST['date']));
    $voucher_ref = $conn->real_escape_string(trim($_POST['voucher_ref']));
    $currency    = (int) $conn->real_escape_string($_POST['currency']);
    $dr_account  = (int) $conn->real_escape_string($_POST['dr_account']); // customer as dr_account
    $tax_rates   = (float) $conn->real_escape_string($_POST['rates']);
    $currency_rate = (float) $conn->real_escape_string($_POST['curr']);
    $branchId    = (int) $conn->real_escape_string($_POST['branchId']);
    $subtotal    = (float) $conn->real_escape_string($_POST['subtotal']);
    $tax         = (float) $conn->real_escape_string($_POST['tax']);
    $total       = (float) $conn->real_escape_string($_POST['total']);
    $userId      = (int) $_SESSION['userid'];

    $items        = $_POST['item'];
    $quantities   = $_POST['quantity'];
    $units        = $_POST['unit'];
    $prices       = $_POST['price'];
    $descriptions = $_POST['description'];
    $amounts      = $_POST['amount'];

    // 1. Insert into `sales_voucher`
    $sql0 = "INSERT INTO `sales_voucher` 
        (`branch_id`, `voucher_type`, `date_`, `currency`, `reference`, `dr_account`, `dr_amount`, `tax_rate`,`currency_rate`) 
        VALUES ($branchId, 1, '$date', $currency, '$voucher_ref', $dr_account, $total, $tax_rates,$currency_rate)";

    if (mysqli_query($conn, $sql0)) {
        $sv_id = mysqli_insert_id($conn);

        // 2. Insert all items into `sales_voucher_details`
        for ($i = 0; $i < count($items); $i++) {
            $item_id = (int) $items[$i];
            $qty     = (float) $quantities[$i];
            $unit    = (int) $units[$i];
            $price   = (float) $prices[$i];
            $amount  = (float) $amounts[$i];
            $desc    = $conn->real_escape_string($descriptions[$i]);

            $sql1 = "INSERT INTO `sales_voucher_details` 
                (`sv_id`, `cr_account`, `quantity`, `unit`, `price`, `amount`, `description`) 
                VALUES ($sv_id, $item_id, $qty, $unit, $price, $amount, '$desc')";
            mysqli_query($conn, $sql1);
        }

        // 3. Insert subtotal into transaction_voucher  
       
        $sqlv = "INSERT INTO `transaction_voucher` 
            (`reference_no`, `currency`, `dr_account`, `description`, `dr_ammount`, `cr_account`, `cr_ammount`, `date_`, `status`, `reconciled`, `user_id`, `branch_id`)
            VALUES ('$voucher_ref', $currency, $dr_account, 'Sales Subtotal', $subtotal, $item_id, $amount, '$date', 'pending', 'no', $userId, $branchId)";
        mysqli_query($conn, $sqlv);

        // 4. VAT entry if exists  
        if ($tax_rates > 0) {
            $vat_account_id = 60;
            $sqlt = "INSERT INTO `transaction_voucher` 
                (`reference_no`, `currency`, `dr_account`, `description`, `dr_ammount`, `cr_account`, `cr_ammount`, `date_`, `status`, `reconciled`, `user_id`, `branch_id`)
                VALUES ('$voucher_ref', $currency, $dr_account, 'VAT Tax', $tax, $vat_account_id, $tax, '$date', 'pending', 'no', $userId, $branchId)";
            mysqli_query($conn, $sqlt);
        }

        echo "<script>alert('Added.'); window.history.back();</script>";
    } else {
        echo "Error: " . mysqli_error($conn);
    }
}



        
    }
?>