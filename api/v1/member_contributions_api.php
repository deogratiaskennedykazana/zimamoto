<?php
$response  =[];
  header("Access-Control-Allow-Origin: *");
  header("Content-Type: application/json; charset=UTF-8");

      require_once "../../configs.php";
      require_once "../../functions/loan_functions.php";
          require_once "../../functions/user_function.php";
           require_once "../../functions/min_sub_functions.php";
            require_once "../../functions/min_transaction_functions.php";
      
      $conn = openConn();
      
      if($_SERVER['REQUEST_METHOD'] === 'GET'){
    if(isset($_GET['app_token']) && $_GET['app_token'] === "zisa_system_app_token"){
  
       if(isset($_GET['select_member_account_balance'])){
    $userId = (int) $_GET['user_id'];
    $categories = ['amana', 'share', 'loan', 'saving'];
    $balances = [];
    
    foreach($categories as $category){
        $subId = selectMinSubByUserIDAndCategory($conn, $userId, $category);
        $balance = 0;
        
        if($subId && is_array($subId)){
            $transactions = getMinTransactionByMinSubId($conn, $subId['id']);
            
            if($transactions && is_array($transactions)){
                foreach($transactions as $transaction){
                    if($transaction['dr_account'] == $subId['id']){
                        $balance += $transaction['amount'];
                    } elseif($transaction['cr_account'] == $subId['id']){
                        $balance -= $transaction['amount'];
                    }
                }
            }
        }
        
        $balances[$category . '_balance'] = $balance;
    }
    
    $response['success'] = true;
    $response['balances'] = $balances;
    
    echo json_encode($response);
}


// 1. Get share transactions 
if(isset($_GET['select_member_share_transaction'])){
    $userId = (int) $_GET['user_id'];
    $subId = selectMinSubByUserIDAndCategory($conn, $userId, 'share');
    
    if($subId && is_array($subId)){
        $transactions = getMinTransactionByMinSubId($conn, $subId['id']);
        
        if($transactions && is_array($transactions)){
            foreach($transactions as &$transaction){
                if($transaction['cr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'credit';
                } elseif($transaction['dr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'debit';
                } else {
                    $transaction['transaction_type'] = 'unknown';
                }
            }
        }
        
        $response['success'] = true;
        $response['transactions'] = $transactions ? $transactions : [];
    } else {
        $response['success'] = false;
        $response['message'] = "No share account found";
        $response['transactions'] = [];
    }
    
    echo json_encode($response);
}

// 2. Get amana transactions  
if(isset($_GET['select_member_amana_transaction'])){
    $userId = (int) $_GET['user_id'];
    $subId = selectMinSubByUserIDAndCategory($conn, $userId, 'amana');
    
    if($subId && is_array($subId)){
        $transactions = getMinTransactionByMinSubId($conn, $subId['id']);
        
        if($transactions && is_array($transactions)){
            foreach($transactions as &$transaction){
                if($transaction['cr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'credit';
                } elseif($transaction['dr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'debit';
                } else {
                    $transaction['transaction_type'] = 'unknown';
                }
            }
        }
        
        $response['success'] = true;
        $response['transactions'] = $transactions ? $transactions : [];
    } else {
        $response['success'] = false;
        $response['message'] = "No amana account found";
        $response['transactions'] = [];
    }
    
    echo json_encode($response);
}

// 3. Get loan transactions
if(isset($_GET['select_member_loan_transaction'])){
    $userId = (int) $_GET['user_id'];
    $subId = selectMinSubByUserIDAndCategory($conn, $userId, 'loan');
    
    if($subId && is_array($subId)){
        $transactions = getMinTransactionByMinSubId($conn, $subId['id']);
        
        if($transactions && is_array($transactions)){
            foreach($transactions as &$transaction){
                if($transaction['cr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'credit';
                } elseif($transaction['dr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'debit';
                } else {
                    $transaction['transaction_type'] = 'unknown';
                }
            }
        }
        
        $response['success'] = true;
        $response['transactions'] = $transactions ? $transactions : [];
    } else {
        $response['success'] = false;
        $response['message'] = "No loan account found";
        $response['transactions'] = [];
    }
    
    echo json_encode($response);
}

// 4. Get saving transactions
if(isset($_GET['select_member_saving_transaction'])){
    $userId = (int) $_GET['user_id'];
    $subId = selectMinSubByUserIDAndCategory($conn, $userId, 'saving');
    
    if($subId && is_array($subId)){
        $transactions = getMinTransactionByMinSubId($conn, $subId['id']);
        
        if($transactions && is_array($transactions)){
            foreach($transactions as &$transaction){
                if($transaction['cr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'credit';
                } elseif($transaction['dr_account'] == $subId['id']){
                    $transaction['transaction_type'] = 'debit';
                } else {
                    $transaction['transaction_type'] = 'unknown';
                }
            }
        }
        
        $response['success'] = true;
        $response['transactions'] = $transactions ? $transactions : [];
    } else {
        $response['success'] = false;
        $response['message'] = "No saving account found";
        $response['transactions'] = [];
    }
    
    echo json_encode($response);
}
        
    } else {
        $response['success'] = false;
        $response['message'] = "Unauthorized";
        echo json_encode($response);
    }
} else {
    $response['success'] = false;
    $response['message'] = "Only GET requests are allowed";
    echo json_encode($response);
}

?>