<?php
    session_start();
    if(!$_SESSION){
        // echo "<script>window.history.back();</script>";
    }
    require_once "../functions/opening_balance_functions.php";
    require_once "../configs.php";
    $conn = openConn();
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['add_opening_balance'])){
            // print_r($_POST);
             $date = $conn->real_escape_string($_POST['date']);
             $currecny = $conn->real_escape_string($_POST['currency']);
             $currencyValue = (float) $conn->real_escape_string($_POST['curr']);
             // arrays
             $subIds = $_POST['item'];
             $amounts = $_POST['amount'];
             $types = $_POST['type'];
             $eqv_amounts = $_POST['eqv']; 

             // loop
             for($i=0; $i<count($subIds); $i++){
                 $subId = (int) $subIds[$i];
                 $amount = (float) $amounts[$i];
                 $type = $conn->real_escape_string( $types[$i]);
                 $eqv_amount = (float) $eqv_amounts[$i];
                 $newOpeningBalance = addOpeningBalance($conn,$subId,$eqv_amount,$currecny,$currencyValue,$type,$date);
                 if(!$newOpeningBalance){
                   echo $newOpeningBalance;
                   return;
                 }

             }
             echo "<script> alert('SUCCESS'); window.history.back(); </script>";
     }
     
     if(isset($_POST['add_min_sub_opening_balance'])){
         print_r($_POST);
            $min_sub = $conn->real_escape_string($_POST['min_sub']);
            $amount = (float) $conn->real_escape_string(str_replace(',', '', $_POST['amount']));
            $type = $conn->real_escape_string($_POST['type']);
            $date = $conn->real_escape_string($_POST['date']);
            $currencyValue = (float) $conn->real_escape_string(str_replace(',', '', $_POST['curr']));
            $newOpeningBalance = addMinSubOpeningBalance($conn,$min_sub,$amount,$currencyValue,$type,$date);
            if(!$newOpeningBalance){
              echo $newOpeningBalance;
              return;
            }
            echo "<script> alert('SUCCESS'); window.history.back(); </script>";
         }
     
     
    }

     if($_SERVER['REQUEST_METHOD'] === 'GET'){
        if(isset($_GET['delete_opneing_balance'])){
            $id = (int) $_GET['delete_opneing_balance'];
            $deleteBalance = deleteOpeningBalance($conn,$id);
            if(!$deleteBalance){
                $deleteBalance;
                return;
            }
            echo "<script> alert('SUCCESS'); window.history.back(); </script>";
        }
    }
    
?>