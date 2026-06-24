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
    require_once "../functions/member_functions.php";
    require_once "../functions/user_function.php"; // FIX: required by grantor_functions.php's selectUserById()
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

                // ── Validate against the selected loan product's own rules ──
                // Each loan product (Emergency, Development, Education, Business, ...)
                // carries its own min/max amount, period range, and required guarantor
                // count. Re-check server-side — never trust the client-side form alone.
                $selectedLoanType = selectLoanTypeById($conn, $loantype);
                if(!$selectedLoanType){
                    echo "<script>alert('Selected loan product is invalid or no longer available.'); window.history.back();</script>";
                    exit;
                }
                $minAmt = (float) $selectedLoanType['min_amount'];
                $maxAmt = (float) $selectedLoanType['max_amount'];
                if($amount < $minAmt || ($maxAmt > 0 && $amount > $maxAmt)){
                    $rangeText = 'TZS ' . number_format($minAmt,2) . ($maxAmt > 0 ? ' - TZS ' . number_format($maxAmt,2) : ' and above');
                    echo "<script>alert('The {$selectedLoanType['name']} allows amounts between $rangeText. Please adjust your requested amount.'); window.history.back();</script>";
                    exit;
                }
                $minPeriod = (int) $selectedLoanType['min_period'];
                $maxPeriod = (int) $selectedLoanType['max_period'];
                if($period < $minPeriod || $period > $maxPeriod){
                    echo "<script>alert('The {$selectedLoanType['name']} allows a repayment period of {$minPeriod}-{$maxPeriod} months. Please adjust the period.'); window.history.back();</script>";
                    exit;
                }
                $requiredGrantors = (int) $selectedLoanType['required_grantors'];
                if(count(array_filter($grantors)) < $requiredGrantors){
                    echo "<script>alert('The {$selectedLoanType['name']} requires at least {$requiredGrantors} guarantor(s).'); window.history.back();</script>";
                    exit;
                }
                
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
                    
                    echo "<script> alert('Loan application submitted successfully! Your guarantors have been notified by email.'); window.location.href='../?page=my_loan';</script>";
                    
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
            $loanSuspenceAccount = 3028;
            $accruedInterestIncomeOnLoan = 3027; 
            $approveDate = $conn->real_escape_string($_POST['approve_date']);

            $LoanSub = selectMinSubByUserIDAndCategory($conn, $userId, "loan");
            if($LoanSub && is_array($LoanSub)){
                $principleEntry = createMinTransaction($conn, "LAJV/".$loanId, $LoanSub['id'], "approved loan:principle", $principle, $loanSuspenceAccount, $approveDate, $_SESSION['userid'], $branchId, "active");
                if(!$principleEntry){
                    echo $principleEntry;
                    return;
                }
                $interestEntry = createMinTransaction($conn, "LAJV/".$loanId, $LoanSub['id'], "approved loan:interest", $interest_amount, $accruedInterestIncomeOnLoan, $approveDate, $_SESSION['userid'], $branchId, "active");
                if(!$interestEntry){
                    echo $interestEntry;
                    return;
                }
                $approvedLoan = approveLoan($conn, $loanId, $interest_amount, $interest_rate, "approved", $approveDate, (int) $_SESSION['userid']);
                if($approvedLoan){
                    // Persist a snapshot of the eligibility check that was visible to the
                    // reviewer at decision time — useful later for audits/disputes.
                    if(function_exists('evaluateLoanEligibility')){
                        $snapshot = evaluateLoanEligibility($conn, $loanId);
                        $stmtSnap = $conn->prepare("UPDATE loans SET eligibility_snapshot = ? WHERE id = ?");
                        if($stmtSnap){
                            $snapJson = json_encode($snapshot);
                            $stmtSnap->bind_param("si", $snapJson, $loanId);
                            $stmtSnap->execute();
                        }
                    }
                    $schedule = generateSchedule($principle, $interest_rate, $loanTerm, 'month', $approveDate);
                    if($schedule && is_array($schedule)){
                        foreach($schedule as $repayment){
                            $date = $repayment['repayment_date'];
                            $newSchedule = insertSchedule($conn, $userId, $branchId, $loanId, $repayment['principle'], $repayment['interest'], $date, 0.0, "pending");
                            if(!$newSchedule){
                                echo $newSchedule;
                                return;
                            }
                        }
                    }
                    // Notify the member their loan was approved
                    createSystemNotification(
                        $conn, $userId,
                        'Loan Approved',
                        "Congratulations! Your loan application of TZS " . number_format($principle, 2) . " has been approved. Repayment begins " . date('d/m/Y', strtotime($approveDate . ' +1 month')) . ".",
                        'success',
                        './?page=my_loan'
                    );
                } else {
                    echo $approvedLoan;
                    return;
                }
            } else {
                echo "<script>alert('Loan sub-account not found for this member.'); window.history.back();</script>";
                return;
            }
            echo "<script> alert('Loan approved successfully!'); window.location.href='../?page=loan_applications';</script>";
        }

        // ── Reject a pending loan application ──────────────────────────
        // Mirrors approveLoan(): records who reviewed it, when, and why,
        // and notifies the member so they know not to expect funds.
        if(isset($_POST['reject_loan'])){
            $loanId = (int) $_POST['loan_id'];
            $userId = (int) $_POST['user_id'];
            $reason = $conn->real_escape_string(trim($_POST['rejection_reason'] ?? ''));
            if($reason === ''){
                echo "<script>alert('Please provide a reason for rejecting this loan.'); window.history.back();</script>";
                exit;
            }
            $rejected = rejectLoan($conn, $loanId, $reason, (int) $_SESSION['userid']);
            if($rejected !== true){
                echo $rejected;
                exit;
            }
            createSystemNotification(
                $conn, $userId,
                'Loan Application Rejected',
                "Your loan application was not approved. Reason: " . $reason,
                'danger',
                './?page=my_loan'
            );
            echo "<script> alert('Loan application rejected.'); window.location.href='../?page=loan_applications';</script>";
        }

        // ================================================================
        //  LOAN PRODUCT (loan_types) CRUD — admin management
        // ================================================================
        if(isset($_POST['add_loan_product']) || isset($_POST['update_loan_product'])){
            $modes = [];
            if(!empty($_POST['mode_salary'])) $modes[] = 'salary';
            if(!empty($_POST['mode_standing_order'])) $modes[] = 'standing_order';
            $d = [
                'name'                    => $conn->real_escape_string(trim($_POST['name'] ?? '')),
                'description'             => $conn->real_escape_string(trim($_POST['description'] ?? '')),
                'min_amount'              => (float) ($_POST['min_amount'] ?? 0),
                'max_amount'              => (float) ($_POST['max_amount'] ?? 0),
                'interest_rate'           => (float) ($_POST['interest_rate'] ?? 0),
                'min_period'              => (int) ($_POST['min_period'] ?? 1),
                'max_period'              => (int) ($_POST['max_period'] ?? 12),
                'savings_multiplier'      => (float) ($_POST['savings_multiplier'] ?? 3),
                'required_grantors'       => (int) ($_POST['required_grantors'] ?? 0),
                'processing_fee_percent'  => (float) ($_POST['processing_fee_percent'] ?? 0),
                'allowed_repayment_modes' => implode(',', $modes ?: ['salary','standing_order']),
                'eligibility_notes'       => $conn->real_escape_string(trim($_POST['eligibility_notes'] ?? '')),
                'status'                  => ($_POST['status'] ?? 'active') === 'inactive' ? 'inactive' : 'active',
            ];
            if($d['name'] === ''){
                echo "<script>alert('Product name is required.'); window.history.back();</script>";
                exit;
            }
            if(isset($_POST['add_loan_product'])){
                $result = insertLoanType($conn, $d, (int) $_SESSION['userid']);
            } else {
                $id = (int) $_POST['id'];
                $result = updateLoanType($conn, $id, $d, (int) $_SESSION['userid']);
            }
            if($result === true || (is_numeric($result) && $result > 0)){
                echo "<script> alert('Loan product saved successfully.'); window.location.href='../?page=loan_products';</script>";
            } else {
                echo $result;
                exit;
            }
        }

        if(isset($_POST['toggle_loan_product_status'])){
            $id = (int) $_POST['id'];
            $newStatus = ($_POST['new_status'] ?? 'inactive') === 'active' ? 'active' : 'inactive';
            $result = toggleLoanTypeStatus($conn, $id, $newStatus);
            if($result === true){
                echo "<script> window.location.href='../?page=loan_products';</script>";
            } else {
                echo $result;
                exit;
            }
        }

        if(isset($_POST['delete_loan_product'])){
            $id = (int) $_POST['id'];
            $result = softDeleteLoanType($conn, $id);
            if($result === true){
                echo "<script> alert('Loan product deleted.'); window.location.href='../?page=loan_products';</script>";
            } else {
                echo $result;
                exit;
            }
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
            $loanId = (int) $_POST['loan_id'];
            $userId = (int) $_POST['user_id'];
            $branchId = (int) $_POST['branch_id'];
            $level = $conn->real_escape_string($_POST['level']);
            $role = $conn->real_escape_string($_POST['role']);
            $comment = $conn->real_escape_string($_POST['comment']);
            $status = $conn->real_escape_string($_POST['status']);
            $newComment = addLoanComment($conn, $userId, $loanId, $comment, $level, $status, $role);
            if($newComment){
                $updateLoanStatus = updateLoanStatus($conn, $loanId, $status);
                if(!$updateLoanStatus){
                    echo $updateLoanStatus;
                    return;
                }
                // Notify the member of their loan status change
                $statusLabels = [
                    'approved'                  => ['title' => 'Loan Approved',   'type' => 'success'],
                    'rejected'                  => ['title' => 'Loan Rejected',   'type' => 'danger'],
                    'hq_pending'                => ['title' => 'Loan Forwarded',  'type' => 'info'],
                    'loan_comettee_processed'   => ['title' => 'Loan Processed',  'type' => 'info'],
                    'hq_loan_officer_rejected'  => ['title' => 'Loan Rejected',   'type' => 'danger'],
                ];
                $label = $statusLabels[$status] ?? ['title' => 'Loan Update', 'type' => 'info'];
                createSystemNotification(
                    $conn,
                    $userId,
                    $label['title'],
                    "Your loan application has been updated to status: $status. Comment: $comment",
                    $label['type'],
                    './?page=my_loan'
                );
            }
            echo "<script>alert('SUCCESS'); window.location.href='../?page=branch_pending_loan&branch_id=$branchId'</script>";
        }
    }
    
?>