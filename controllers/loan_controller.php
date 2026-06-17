<?php
    session_start();
    if(!$_SESSION){
      //print_r("hello");
       echo "<script> window.history.back();</script>";
    }
    require_once  "../functions/loan_functions.php";
    require_once "../functions/min_transaction_functions.php";
    require_once  "../functions/min_sub_functions.php";
    require_once "../functions/grantor_functions.php";
    require_once "../functions/notification_functions.php";
    require_once "../configs.php";
    $conn = openConn();
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['upload_loan'])){
            print_r($_POST);
            $branchId = (int) $_POST['branch_id'];
            $loan_account = (int) $_POST['loan_account'];
            $interest_account = (int) $_POST['interest_account'];
            $subs = $_POST['sub_id'];
            $principles = $_POST['principle'];
            $interest_amounts = $_POST['interest_amount'];
            $interest_rates = $_POST['interest_rate'];
            $approve_dates = $_POST['date'];
            $periods = $_POST['period'];
            $user_ids  = $_POST['user_id'];
            // insert loan 
            for($i =0; $i<count($user_ids); $i++){
                $sub_id = (int) $subs[$i];
                $user_id = (int) $user_ids[$i];
                $interest_rate = (float) $interest_rates[$i];
                $principle = (float) $principles[$i];
                $interest_amount = (float) $interest_amounts[$i];
                $period = (int) $periods[$i];
                $approve_date = $conn->real_escape_string($approve_dates[$i]);
                if($principle >0){
                $newapprovedLoan = insertLoan($conn, $branchId,$user_id,$principle,$interest_amount,$interest_rate,$period,"approved",$approve_date);
                if(!$newapprovedLoan){
                    echo $newapprovedLoan;
                    return;
                } else{
                    // now insert schedule
                    $schedule = generateSchedule($principle,$interest_rate,$period,'month',$approve_date);
                    if($schedule && is_array($schedule)){
                        foreach($schedule as $repayment){
                            // print_r($repayment);
                           
                            $date = $repayment['repayment_date'];
                            $newSchedule = insertSchedule($conn,$user_id,$branchId,$newapprovedLoan,$repayment['principle'],$repayment['interest'],$date,0.0,"pending");
                            if(!$newSchedule){
                                echo $newSchedule;
                                return;
                            }
                        }
                    }
                    // // create transaction
                       $ref = "LUJV/" . date("Y-m-d/") . $i+1; 
                       $newPrincipleTransaction = createMinTransaction($conn,$ref,$sub_id,"loan principle upload", $principle,$loan_account,$approve_date,$_SESSION['userid'],$branchId,"active");
                       if(!$newPrincipleTransaction){
                            echo $newPrincipleTransaction;
                            return; 
                       }
                       $newInterestTransaction = createMinTransaction($conn,$ref,$sub_id,"loan Interest Upload",$interest_amount,$interest_account,$approve_date,$_SESSION['userid'],$branchId,"active");
                       if(!$newInterestTransaction){
                            echo $newInterestTransaction;
                            return; 
                       }
                }
             }
                //  return;
            }
            echo "<script> alert('SUCCESS'); window.location.href='../?page=upload_loan'; </script>";
        }
        
         if(isset($_POST['apply_loan'])){ 

                      echo "<pre>FILES DEBUG: "; print_r($_FILES); echo "</pre>";
                // Create upload directories if they don't exist
                if (!file_exists('uploads/salary_slips/')) {
                    mkdir('uploads/salary_slips/', 0755, true);
                }
                if (!file_exists('uploads/standing_orders/')) {
                    mkdir('uploads/standing_orders/', 0755, true);
                }
                
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
                        $uploadPath = '../uploads/salary_slips/' . $fileName;
                        
                        if(move_uploaded_file($_FILES['salary_slip_file']['tmp_name'], $uploadPath)){
                            $salarySlip = $fileName;
            
                        } else {
                            echo "<script> alert('Failed to upload salary slip'); window.history.back();</script>";
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
                        $uploadPath = '../uploads/standing_orders/' . $fileName;
                        
                        if(move_uploaded_file($_FILES['standing_order_file']['tmp_name'], $uploadPath)){
                            $standingOrder = $fileName;
            
                        } else {
                            echo "<script> alert('Failed to upload standing order file'); window.history.back();</script>";
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
                            echo "<script> alert('Failed to save salary details: $salaryResult'); window.history.back();</script>";
                            exit;
                        }
                    }
                    
                    // Save standing order details if standing order mode
                    if($repaymentMode == 'standing_order'){
                        $standingOrderResult = insertLoanStandingOrderDetails($conn, $newLoan, $standingOrder);
                        if(!is_numeric($standingOrderResult) || $standingOrderResult <= 0){
                            echo "<script> alert('Failed to save standing order details: $standingOrderResult'); window.history.back();</script>";
                            exit;
                        }
                    }
                    
                    // Save grantors and send notifications
                    $grantorErrors = [];
                    for($i = 0; $i < count($grantors); $i++){
                        $grantor = (int) $grantors[$i];
                        $newGrantor = insertLoanGrantor($conn, $userId, $newLoan, $grantor, null);
                        if(!is_numeric($newGrantor) || $newGrantor <= 0){
                            $grantorErrors[] = "Grantor $grantor: $newGrantor";
                        } else {
                            sendGrantorRequest($conn, $newLoan, $grantor, $userId);
                        }
                    }
                    
                    if(!empty($grantorErrors)){
                        echo "<script> alert('Failed to save some grantors: " . implode(', ', $grantorErrors) . "'); window.history.back();</script>";
                        exit;
                    }
                    
                    echo "<script> alert('Loan application submitted successfully!'); window.history.back();</script>";
                    
                } else {
                    echo "<script> alert('Failed to create loan: $newLoan'); window.history.back();</script>";
                    exit;
                }
            }
        if(isset($_POST['approve_loan'])){
            
            $loanId = (int) $_POST['loan_id'];
            $userId = (int) $_POST['user_id'];
            $branchId = (int) $_POST['branch_id'];
            $interest_rate = (float) $_POST['interest_rate'];
            $interest_amount = (float) $_POST['interest_amount'];
            $principle = (float) $_POST['principle'];
            $loanTerm = (int) $_POST['loan_term'];
            $loanSuspenceAccount =3028;
            $accruedInterestIncomeOnLoan =3027; 
            $approveDate = $conn->real_escape_string($_POST['approve_date']);
            //insert double entry 
            
            $LoanSub = selectMinSubByUserIDAndCategory($conn,$userId,"loan");
             print_r($LoanSub);
            if($LoanSub && is_array($LoanSub)){
                
                    $principleEntry = createMinTransaction($conn,"LAJV/".$loanId,$LoanSub['id'],"approved loan:principle",$principle,$loanSuspenceAccount,$approveDate,$_SESSION['userid'],$branchId,"active" );
                    if(!$principleEntry){
                        echo $principleEntry;
                        return;
                    }
                    $interestEntry = createMinTransaction($conn,"LAJV/".$loanId,$LoanSub['id'],"approved loan:interest",$interest_amount,$accruedInterestIncomeOnLoan,$approveDate,$_SESSION['userid'],$branchId,"active" );
                    if(!$interestEntry){
                        echo $interestEntry;
                        return;
                    }
                    // insert loan
                    $approvedLoan = approveLoan($conn,$loanId,$interest_amount,$interest_rate,"approved");
                    if($approvedLoan){
                        echo $approvedLoan;
                        $schedule = generateSchedule($principle,$interest_rate,$loanTerm,'month',$approveDate);
                        if($schedule && is_array($schedule)){
                            foreach($schedule as $repayment){
                                $date = $repayment['repayment_date'];
                                $newSchedule = insertSchedule($conn,$userId,$branchId,$loanId,$repayment['principle'],$repayment['interest'],$date,0.0,"pending");
                                if(!$newSchedule){
                                    echo $newSchedule;
                                    return;
                                }
                            }
                        }
                    } else{
                        echo $approvedLoan;
                        return;
                    }
            }
           
        
            echo "<script> alert('SUCCESS'); window.location.href='../?page=Pending_loan_list_form';</script>";
        } 
        
        if(isset($_POST['upload_general_loan'])){
    print_r($_POST);
    $loan_account = (int) $_POST['loan_account'];
    $interest_account = (int) $_POST['interest_account'];
    $subs = $_POST['sub_id'];
    $principles = $_POST['principle'];
    $interest_amounts = $_POST['interest_amount'];
    $interest_rates = $_POST['interest_rate'];
    $approve_dates = $_POST['date'];
    $periods = $_POST['period'];
    $user_ids = $_POST['user_id'];
    $member_branch_ids = $_POST['member_branch_id']; // Array of branch IDs for each member
    
    // insert loan 
    for($i = 0; $i < count($user_ids); $i++){
        $sub_id = (int) $subs[$i];
        $user_id = (int) $user_ids[$i];
        $member_branch_id = (int) $member_branch_ids[$i]; // Each member's branch ID
        $interest_rate = (float) $interest_rates[$i];
        $principle = (float) $principles[$i];
        $interest_amount = (float) $interest_amounts[$i];
        $period = (int) $periods[$i];
        $approve_date = $conn->real_escape_string($approve_dates[$i]);
        
        if($principle > 0){
            $newapprovedLoan = insertLoan($conn, $member_branch_id, $user_id, $principle, $interest_amount, $interest_rate, $period, "approved", $approve_date);
            if(!$newapprovedLoan){
                echo $newapprovedLoan;
                return;
            } else{
                // now insert schedule
                $schedule = generateSchedule($principle, $interest_rate, $period, 'month', $approve_date);
                if($schedule && is_array($schedule)){
                    foreach($schedule as $repayment){
                        // print_r($repayment);
                       
                        $date = $repayment['repayment_date'];
                        $newSchedule = insertSchedule($conn, $user_id, $member_branch_id, $newapprovedLoan, $repayment['principle'], $repayment['interest'], $date, 0.0, "pending");
                        if(!$newSchedule){
                            echo $newSchedule;
                            return;
                        }
                    }
                }
                // create transaction with unique reference per member and branch
                $ref = "LUJV/" . date("Y-m-d/") . $member_branch_id . "/" . ($i + 1); 
                $newPrincipleTransaction = createMinTransaction($conn, $ref, $sub_id, "loan principle upload", $principle, $loan_account, $approve_date, $_SESSION['userid'], $member_branch_id, "active");
                if(!$newPrincipleTransaction){
                     echo $newPrincipleTransaction;
                     return; 
                }
                $newInterestTransaction = createMinTransaction($conn, $ref, $sub_id, "loan Interest Upload", $interest_amount, $interest_account, $approve_date, $_SESSION['userid'], $member_branch_id, "active");
                if(!$newInterestTransaction){
                     echo $newInterestTransaction;
                     return; 
                }
            }
        }
        //  return;
    }
    echo "<script> alert('SUCCESS'); window.location.href='../?page=upload_loan'; </script>";
}

// Branch-specific loan repayment processing
if(isset($_POST['upload_loan_repayment'])){
    print_r($_POST);
    $date = $conn->real_escape_string($_POST['date']);
    $branch_id = $conn->real_escape_string($_POST['branch_id']);
    $subs = $_POST['sub_id'];
    $amounts = $_POST['amount'];
    $ref = "LRUP/" . $date . "/" . $branch_id;
    $cr_account = (int) $_POST['cr_account'];
    
    for($i=0; $i<count($subs); $i++){
        $sub_id = $conn->real_escape_string($subs[$i]);
        $amount = $conn->real_escape_string($amounts[$i]);
        $newTransaction = createMinTransaction($conn,$ref,$cr_account,"Loan Repayment Upload",$amount,$sub_id,$date, (int) $_SESSION['userid'],$branch_id,"active");
        if(!$newTransaction){
            echo $newTransaction;
            return;
        }
    }
    echo "<script>alert('SUCCESS'); window.location.href='../?page=upload_loan_repayments';</script>";
}

// General loan repayment processing (multiple branches)
if(isset($_POST['upload_general_loan_repayment'])){
    print_r($_POST);
    $date = $conn->real_escape_string($_POST['date']);
    $subs = $_POST['sub_id'];
    $amounts = $_POST['amount'];
    $member_branch_ids = $_POST['member_branch_id']; // Array of branch IDs for each member
    $cr_account = (int) $_POST['cr_account'];
    
    for($i=0; $i<count($subs); $i++){
        $sub_id = $conn->real_escape_string($subs[$i]);
        $amount = $conn->real_escape_string($amounts[$i]);
        $member_branch_id = $conn->real_escape_string($member_branch_ids[$i]); // Each member's branch ID
        
        // Create unique reference for each branch
        $ref = "LRUP/" . $date . "/" . $member_branch_id . "/" . ($i + 1);
        
        $newTransaction = createMinTransaction(
            $conn,
            $ref,
            $cr_account,
            "Loan Repayment Upload",
            $amount,
            $sub_id,
            $date, 
            (int) $_SESSION['userid'],
            $member_branch_id, // Use individual member's branch ID
            "active"
        );
        
        if(!$newTransaction){
            echo $newTransaction;
            return;
        }
    }
    echo "<script>alert('SUCCESS'); window.location.href='../?page=upload_loan_repayments';</script>";
}
        
        
        
        if(isset($_POST['send_loan_comment'])){
            print_r($_POST);
             $loanId = (int) $_POST['loan_id'];
            $userId = (int) $_POST['user_id'];
            $branchId = (int) $_POST['branch_id'];
            $level = $conn->real_escape_string($_POST['level']);
            $role = $conn->real_escape_string($_POST['role']);
            $comment = $conn->real_escape_string($_POST['comment']);
            $status = $conn->real_escape_string($_POST['status']);
            $newComment = addLoanComment($conn,$userId,$loanId,$comment,$level,$status, $role);
            if($newComment){
                $updateLoanStatus = updateLoanStatus($conn,$loanId,$status);
                if(!$updateLoanStatus){
                    echo $updateLoanStatus;
                    return;
                }
                
            }
            echo "<script>alert('SUCCESS'); window.location.href='../?page=branch_pending_loan&branch_id=$branchId'</script>";
        }
    }
    
?>