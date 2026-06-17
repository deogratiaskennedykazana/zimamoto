<?php
          function selectLoanSalaryDetails(mysqli $conn, int $loanId) {
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `loan_requester_salaries` WHERE `loan_id` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
    $stmt->store_result();
    return stmt_fetch_assoc($stmt);
        }
        
        // Function to select loan standing order details
        function selectLoanStandingOrderDetails(mysqli $conn, int $loanId) {
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `loan_requester_standing_order` WHERE `loan_id` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
    $stmt->store_result();
    return stmt_fetch_assoc($stmt);
        }
        
        
        function insertLoan(mysqli $conn, int $branchId, int $user_id, float $principle, float $interestAm, float $interestRate, int $period, string $status, ?string $repaymentMode = null, ?string $approveDate = null, ?int $loanType = null) {
            if($conn === false){
                exit();
            }
            
            $sql = "INSERT INTO `loans`(`branch_id`, `user_id`, `principle`, `interest_amount`, `interest_rate`, `period`, `approve_date`, `status`, `loan_type`, `repayment_mode`) VALUES (?,?,?,?,?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iidddissis", $branchId, $user_id, $principle, $interestAm, $interestRate, $period, $approveDate, $status, $loanType, $repaymentMode);
            return ($stmt->execute()) ? $stmt->insert_id : false;
        }
        
        // Function to insert salary details
        function insertLoanSalaryDetails(mysqli $conn, int $loanId, float $basicSalary, float $takeHome, ?string $salarySlipFile = null) {
            if($conn === false){
                exit();
            }
            
            $sql = "INSERT INTO `loan_requester_salaries`(`loan_id`, `basic_salary`, `take_home`, `salary_slip_file`) VALUES (?,?,?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("idds", $loanId, $basicSalary, $takeHome, $salarySlipFile);
            return ($stmt->execute()) ? $stmt->insert_id : false;
        }
        
        // Function to insert standing order details
        function insertLoanStandingOrderDetails(mysqli $conn, int $loanId, ?string $standingOrderFile = null) {
            if($conn === false){
                exit();
            }
            
            $sql = "INSERT INTO `loan_requester_standing_order`(`loan_id`, `standing_order_file`) VALUES (?,?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("is", $loanId, $standingOrderFile);
            return ($stmt->execute()) ? $stmt->insert_id : false;
        }


        function generateSchedule(float $loanAmount, float $interestrate, float $loanTerm, string $repaymentType,string $approveDate){
        $schedule = [];
        $totalInterest = 0;
        $totalAmount = 0;
        $monthlyInterest = $interestrate / 12;
        //$principle = $loanAmount + ($loanAmount * $interestrate/100); ;
        $initialpayementDate = date("Y-m-d", strtotime("$approveDate +1 $repaymentType"));
        for($i = 0; $i < $loanTerm; $i++){
            $principle = $loanAmount / $loanTerm;
            $interest = $principle * $interestrate/100;
            $totalInterest += $interest;
            $totalAmount += $principle + $interest;

            // $repaymentDate = date("Y-m-d", strtotime(" $approveDate +$i $repaymentType"));
            $repaymentDate = date("Y-m-d", strtotime(" $initialpayementDate +$i $repaymentType"));
            $schedule[] = [
                "month" => $i + 1,
                "principle" => $principle,
                "interest" => $interest,
                "total" => $totalAmount,
                "repayment_date" => $repaymentDate,
               

            ];
           // $principle -= $principle+$interest;
        }
        return $schedule;
    }
     function insertSchedule(mysqli $conn, int $user_id, int $branchId, int $loanId, int $principle, int $interestAm, string $repaymentDate, float $paidAmount, string $status){
        if($conn === false){
            exit();
        }
        $sql = "INSERT INTO `loan_schedules`(`user_id`, `branch_id`, `loan_id`, `principle`, `interest_amount`, `payment_date`, `paid_amount`, `status`)VALUES(?,?,?,?,?,?,?,?);";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            return $stmt ;
        }
        $stmt->bind_param("iiiddsds", $user_id,$branchId,$loanId,$principle,$interestAm,$repaymentDate,$paidAmount, $status);
        return ($stmt->execute()) ? $stmt->insert_id : $stmt->error;
    }
    
    function selectLoansByStatus(mysqli $conn, string $status, $branchId = null) {
        $sql = "SELECT loans.*, users.name, loan_types.name AS product 
                FROM loans 
                JOIN users ON loans.user_id = users.id
                LEFT JOIN loan_types ON loans.loan_type = loan_types.id
                WHERE loans.status = ? AND loans.deleted_at IS NULL";
    
        if (!is_null($branchId)) {
            $sql .= " AND users.branch_id = ?";
        }
    
        $stmt = $conn->prepare($sql);
    
        if (!is_null($branchId)) {
            $stmt->bind_param("ss", $status, $branchId);
        } else {
            $stmt->bind_param("s", $status);
        }
    
        return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }

    function selectLoanById(mysqli $conn, int $loanId){
        $sql = "SELECT * FROM `loans` WHERE id = ? AND deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loanId);
         return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
    }

    function selectLoanScheduleByLoanId(mysqli $conn, int $loanId){
        $sql = "SELECT * FROM `loan_schedules` WHERE loan_id = ? AND deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loanId);
         return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }
    function insertLoanGrantor(mysqli $conn, int $userId, int $loanId, int $grantorId, ?string $grantorComment = null){
        $sql = "INSERT INTO `loan_grantors`(`user_id`, `loan_id`, `grantor_id`, `grantor_comment`)VALUES(?,?,?,?);";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiis", $userId, $loanId, $grantorId, $grantorComment);
        return ($stmt->execute()) ? $stmt->insert_id : $stmt->error;
    }

    function approveLoan(mysqli $conn, int $loanId, float $InterestAmount, float $interestRate, string $status){
        $sql = "UPDATE `loans` SET `interest_amount` = ?, `interest_rate` = ?, `status` = ? WHERE id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddsi", $InterestAmount, $interestRate, $status, $loanId);
        return ($stmt->execute()) ? true : $stmt->error;
    }
    function selectLoanGrantorByLoanId(mysqli $conn, int $loanId){
        $sql = "SELECT loan_grantors.*, users.name FROM `loan_grantors` INNER JOIN users ON users.id = loan_grantors.grantor_id WHERE loan_id = ? AND loan_grantors.deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loanId);
         return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }
     function selectLoanTypes(mysqli $conn){
        $sql = "SELECT * FROM loan_types ORDER BY name;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
    function selectLoanByUserId(mysqli $conn, int $userId){
        $sql = "SELECT loans.*, loan_types.name FROM loans LEFT JOIN loan_types ON loans.loan_type = loan_types.id WHERE loans.user_id = ? AND loans.deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
         return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }
      function addLoanComment(mysqli $conn, int $commenterId, int $loanId, string $comment,string $level, string $status, string $role){
        if($conn === false){
            exit(); 
        }
        $sql = "INSERT INTO `loan_comments`(`loan_id`, `commenter_id`, `level`, `date_`,`status`, `details`, `role`) VALUES (?,?,?,?,?,?,?);";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            exit();
        } 
        $date = date("Y-m-d");
        $stmt->bind_param("iisssss", $loanId, $commenterId, $level, $date, $status, $comment, $role);
        return ($stmt->execute()) ? $stmt->insert_id : $stmt->error;
    }
    function updateLoanStatus(mysqli $conn, int $loanId, string $status){
        if($conn === false){
            exit();
        }
        $sql = "UPDATE `loans` SET `status` = ? WHERE id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $loanId);
        return ($stmt->execute()) ? true : $stmt->error;
    }
     function selectLoansByStatusAndUserId(mysqli $conn, string $status, int $userId  ){
        $sql = "SELECT loans.*, users.name, loan_types.name as product FROM loans 
                JOIN users ON loans.user_id = users.id
                LEFT JOIN loan_types ON loans.loan_type = loan_types.id
                WHERE loans.status =? AND loans.user_id = ? AND loans.deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status,$userId);
         return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }
    
?>