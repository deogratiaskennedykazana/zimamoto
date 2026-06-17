<?php
session_start();
if(!$_SESSION){
    echo "<script> window.history.back();</script>";
}
require_once "../functions/loan_functions.php";
require_once "../functions/min_transaction_functions.php";
require_once "../functions/min_sub_functions.php";
require_once "../configs.php";
$conn = openConn();
if(isset($_POST['upload_reconciliation'])){
    $sub_ids = $_POST['sub_id'];
    $user_ids = $_POST['user_id'];
    $branch_ids = $_POST['member_branch_id'];
    $drs = $_POST['dr'];
    $crs = $_POST['cr'];
    $approve_date = "2026-02-27";
    $dr_account = 94;
    for($i = 0; $i < count($sub_ids); $i++){
        $sub_id = $sub_ids[$i];
        $branch_id = $branch_ids[$i];
        $dr_amount = floatval($drs[$i]);
        $cr_amount = floatval($crs[$i]);
        if(empty($sub_id)) continue;
        $ref = "LUJV/" . date("Y-m-d/") . ($i + 1);
        if($dr_amount > 0){
            //$newDrTransaction = createMinTransaction($conn, $ref, $sub_id, "loan principle upload", $dr_amount, $dr_account, $approve_date, (int)$_SESSION['userid'], $branch_id, "active");
            if(!$newDrTransaction){
                echo "Error on DR transaction row " . ($i + 1);
                return;
            }
        }
        if($cr_amount > 0){
            //$newCrTransaction = createMinTransaction($conn, $ref, $dr_account, "loan repayment upload", $cr_amount, $sub_id, $approve_date, (int)$_SESSION['userid'], $branch_id, "active");
            if(!$newCrTransaction){
                echo "Error on CR transaction row " . ($i + 1);
                return;
            }
        }
    }
    echo "<script>alert('Reconciliation uploaded successfully'); window.history.back();</script>";
}