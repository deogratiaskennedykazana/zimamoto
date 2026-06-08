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
      if($_SERVER['REQUEST_METHOD'] === 'POST'){
      if(isset($_POST['app_token']) && $_POST['app_token']=== "zisa_system_app_token" ){
          if(isset($_POST['loan_application_request'])){
             $branchId = (int) $_POST['branch_id'];
                $userId = (int) $_POST['user_id'];
                $loantype = (int) $_POST['loan_type'];
                $amount = (float) $_POST['amount'];
                 
                $grantors = $_POST['grantor'];
                
                // Get repayment mode data
                $repaymentMode = $_POST['repayment_mode'];
                $period = (int) $_POST['period'];
                
                // Initialize variables
                $basicSalary = 0;
                $takeHome = 0;
                $salarySlip = null;
                $standingOrder = null;
                
                // Process salary data if salary mode
                if($repaymentMode == 'salary'){
                    $basicSalary = (float) $_POST['basic_salary'];
                    $takeHome = (float) $_POST['take_home'];
                    
                    // Upload salary slip
                    if(isset($_FILES['salary_slip_file']) && $_FILES['salary_slip_file']['error'] == 0){
                        $fileExtension = pathinfo($_FILES['salary_slip_file']['name'], PATHINFO_EXTENSION);
                        $fileName = time() . '_salary_slip.' . $fileExtension;
                        $uploadPath = '../../uploads/salary_slips/' . $fileName;
                        
                        if(move_uploaded_file($_FILES['salary_slip_file']['tmp_name'], $uploadPath)){
                            $salarySlip = $fileName;
            
                        } else {
                            //echo "<script> alert('Failed to upload salary slip'); window.history.back();</script>";
                           // exit;
                            $response['success'] = false;
                            $response['message'] = "Failed to upload salary slip";
                            echo json_encode($response);
                            exit;
                        }
                    }
                }
                
                // Process standing order data if standing order mode
                if($repaymentMode == 'standing_order'){
                    // Upload standing order document
                    if(isset($_FILES['standing_order_file']) && $_FILES['standing_order_file']['error'] == 0){
                        $fileExtension = pathinfo($_FILES['standing_order_file']['name'], PATHINFO_EXTENSION);
                        $fileName = time() . '_standing_order.' . $fileExtension;
                        $uploadPath = '../../uploads/standing_orders/' . $fileName;
                        
                        if(move_uploaded_file($_FILES['standing_order_file']['tmp_name'], $uploadPath)){
                            $standingOrder = $fileName;
            
                        } else {
                            // echo "<script> alert('Failed to upload standing order file'); window.history.back();</script>";
                            // exit;
                            $response['success'] = false;
                            $response['message'] = "Failed to upload standing order file";
                            echo json_encode($response);
                            exit;
                        }
                    }
                }
                
                // Insert loan with repayment mode
                $newLoan = insertLoan($conn, $branchId, $userId, $amount, 0.0, 0.0, $period, "pending", $repaymentMode, null, $loantype);
                
                if(is_numeric($newLoan) && $newLoan > 0){
                    
                    // Save salary details if salary mode
                    if($repaymentMode == 'salary'){
                        $salaryResult = insertLoanSalaryDetails($conn, $newLoan, $basicSalary, $takeHome, $salarySlip);
                        if(!is_numeric($salaryResult) || $salaryResult <= 0){
                          //  echo "<script> alert('Failed to save salary details: $salaryResult'); window.history.back();</script>";
                          //  exit;
                            $response['success'] = false;
                            $response['message'] = "Failed to save salary details: $salaryResult";
                            echo json_encode($response);
                            exit;
                        }
                    }
                    
                    // Save standing order details if standing order mode
                    if($repaymentMode == 'standing_order'){
                        $standingOrderResult = insertLoanStandingOrderDetails($conn, $newLoan, $standingOrder);
                        if(!is_numeric($standingOrderResult) || $standingOrderResult <= 0){
                            // echo "<script> alert('Failed to save standing order details: $standingOrderResult'); window.history.back();</script>";
                            // exit;
                            $response['success'] = false;
                            $response['message'] = "Failed to save standing order details: $standingOrderResult";
                            echo json_encode($response);
                            exit;
                        }
                    }
                    
                    // Save grantors
                    $grantorErrors = [];
                    for($i = 0; $i < count($grantors); $i++){
                        $grantor = (int) $grantors[$i];
                        $newGrantor = insertLoanGrantor($conn, $userId, $newLoan, $grantor, null);
                        if(!is_numeric($newGrantor) || $newGrantor <= 0){
                            $grantorErrors[] = "Grantor $grantor: $newGrantor";
                        }
                    }
                    
                    if(!empty($grantorErrors)){
                        // echo "<script> alert('Failed to save some grantors: " . implode(', ', $grantorErrors) . "'); window.history.back();</script>";
                        // exit;
                        $response['success'] = false;
                        $response['message'] = "Failed to save some grantors: " . implode(', ', $grantorErrors);
                        echo json_encode($response);
                        exit;
                    }
                    
                    $response['success'] = true;
                    $response['message'] = "Loan created successfully";
                   // echo json_encode($response);
                   // exit;
                    
                } else {
                    // echo "<script> alert('Failed to create loan: $newLoan'); window.history.back();</script>";
                    // exit;
                    $response['success'] = false;
                    $response['message'] = "Failed to create loan: $newLoan";
                   // echo json_encode($response);
                   // exit;
                }

                $response['success'] = true;
                $response['message'] = "Loan created successfully";
                echo json_encode($response);
                exit;
          }


      }
    }
    
if($_SERVER['REQUEST_METHOD'] === 'GET'){
    if(isset($_GET['app_token']) && $_GET['app_token'] === "zisa_system_app_token"){
 
        if(isset($_GET['requet_user_loan_list'])){
            $userId = (int) $_GET['user_id'];
            $loans = selectLoanByUserId($conn,$userId);
            if($loans && is_array($loans)){
                $response['success'] = true;
                $response['loans'] = $loans;
            } else{
                $response['success'] = false;
                $response['message'] = "No loans found";
            }
            echo json_encode($response);
        } 
        
        if(isset($_GET['request_loan_schedule'])){
            $loanId = (int) $_GET['loan_id'];
            $schedule = selectLoanScheduleByLoanId($conn, $loanId);
            if($schedule && is_array($schedule)){
                $response['success'] = true;
                $response['schedule'] = $schedule;
            } else{
                $response['success'] = false;
                $response['message'] = "No schedule found";
            }
            echo json_encode($response);
        }
        
 
        if(isset($_GET['select_loan_types'])){
            $loanTypes = selectLoanTypes($conn);
            if($loanTypes && is_array($loanTypes)){
                $response['success'] = true;
                $response['loan_types'] = $loanTypes;
            } else{
                $response['success'] = false;
                $response['message'] = "No loan types found";
            }
            echo json_encode($response);
        }
        
 
        if(isset($_GET['select_users_by_branch_id'])){
            $branchId = (int) $_GET['branch_id'];
            $users = selectUsersByBranchId($conn, $branchId);
            if($users && is_array($users)){
                $response['success'] = true;
                $response['users'] = $users;
            } else{
                $response['success'] = false;
                $response['message'] = "No members found for this branch";
            }
            echo json_encode($response);
        }
        
        
        if(isset($_GET['select_loan_capacity'])){
            $userId = (int) $_GET['user_id'];
            $subId = selectMinSubByUserIDAndCategory($conn, $userId, 'saving');
            $amanaTransaction = [];
            $balance = 0;
            if($subId && is_array($subId)){
                $amanaTransaction = getMinTransactionByMinSubId($conn, $subId['id']);
                if($amanaTransaction && is_array($amanaTransaction)){
                    foreach($amanaTransaction as $transaction){
                        if($transaction['dr_account'] == $subId['id']){
                            $balance += $transaction['amount'];
                        } elseif($transaction['cr_account'] == $subId['id']){
                            $balance -= $transaction['amount'];
                        }
                    }
                }
            }
            
            $loanCapacity = $balance * 3;
            
            $response['success'] = true;
            $response['user_id'] = $userId;
            $response['savings_balance'] = $balance;
            $response['loan_capacity'] = $loanCapacity;
            
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