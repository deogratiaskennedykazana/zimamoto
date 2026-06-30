<?php
          function selectLoanSalaryDetails(mysqli $conn, int $loanId) {
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `loan_requester_salaries` WHERE `loan_id` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $loanId);
            $stmt->execute();
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
     function insertSchedule(mysqli $conn, int $user_id, int $branchId, int $loanId, float $principle, float $interestAm, string $repaymentDate, float $paidAmount, string $status){
        if($conn === false){
            exit();
        }
        // FIX: principle and interestAm were typed as int in the original signature,
        // causing decimal values (e.g. 41666.67) to be silently truncated to integers
        // (41666) before being written to loan_schedules. Changed to float + 'd' binding.
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
            // Filter by loans.branch_id (set at application time) — NOT users.branch_id
            // which is unreliable and may not match after branch transfers.
            $sql .= " AND loans.branch_id = ?";
        }
    
        $stmt = $conn->prepare($sql);
    
        if (!is_null($branchId)) {
            $stmt->bind_param("si", $status, $branchId);
        } else {
            $stmt->bind_param("s", $status);
        }
    
        return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }

    function selectLoanById(mysqli $conn, int $loanId){
        $sql = "SELECT loans.*, loan_types.name AS loan_type_name FROM `loans` LEFT JOIN loan_types ON loan_types.id = loans.loan_type WHERE loans.id = ? AND loans.deleted_at IS NULL;";
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

    function approveLoan(mysqli $conn, int $loanId, float $InterestAmount, float $interestRate, string $status, ?string $approveDate = null, ?int $reviewerId = null){
        $sql = "UPDATE `loans` SET `interest_amount` = ?, `interest_rate` = ?, `status` = ?, `approve_date` = ?, `reviewed_by` = ?, `reviewed_at` = NOW() WHERE id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ddssii", $InterestAmount, $interestRate, $status, $approveDate, $reviewerId, $loanId);
        return ($stmt->execute()) ? true : $stmt->error;
    }

    function rejectLoan(mysqli $conn, int $loanId, string $reason, int $reviewerId){
        $sql = "UPDATE `loans` SET `status` = 'rejected', `rejection_reason` = ?, `reviewed_by` = ?, `reviewed_at` = NOW() WHERE id = ?;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $reason, $reviewerId, $loanId);
        return ($stmt->execute()) ? true : $stmt->error;
    }
    function selectLoanGrantorByLoanId(mysqli $conn, int $loanId){
        $sql = "SELECT loan_grantors.*, users.name FROM `loan_grantors` INNER JOIN users ON users.id = loan_grantors.grantor_id WHERE loan_id = ? AND loan_grantors.deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loanId);
         return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }
     function selectLoanTypes(mysqli $conn){
        // Member-facing list — only products that are currently active and not deleted.
        $sql = "SELECT * FROM loan_types WHERE status = 'active' AND deleted_at IS NULL ORDER BY name;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }

    // Admin-facing list — every product, including inactive/disabled ones, for the
    // Loan Products management screen.
    function selectAllLoanTypesAdmin(mysqli $conn){
        $sql = "SELECT lt.*, u1.name AS created_by_name, u2.name AS updated_by_name
                FROM loan_types lt
                LEFT JOIN users u1 ON u1.id = lt.created_by
                LEFT JOIN users u2 ON u2.id = lt.updated_by
                WHERE lt.deleted_at IS NULL
                ORDER BY lt.name;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }

    function selectLoanTypeById(mysqli $conn, int $id){
        $sql = "SELECT * FROM loan_types WHERE id = ? AND deleted_at IS NULL;";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
    }

    function insertLoanType(mysqli $conn, array $d, int $createdBy){
        $sql = "INSERT INTO `loan_types`
                (`name`,`description`,`min_amount`,`max_amount`,`interest_rate`,`min_period`,`max_period`,
                 `savings_multiplier`,`required_grantors`,`processing_fee_percent`,`allowed_repayment_modes`,
                 `eligibility_notes`,`status`,`created_by`)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
        $stmt = $conn->prepare($sql);
        if($stmt === false) return $conn->error;
        $stmt->bind_param(
            "ssdddiiidssssi",
            $d['name'], $d['description'], $d['min_amount'], $d['max_amount'], $d['interest_rate'],
            $d['min_period'], $d['max_period'], $d['savings_multiplier'], $d['required_grantors'],
            $d['processing_fee_percent'], $d['allowed_repayment_modes'], $d['eligibility_notes'],
            $d['status'], $createdBy
        );
        return ($stmt->execute()) ? $stmt->insert_id : $stmt->error;
    }

    function updateLoanType(mysqli $conn, int $id, array $d, int $updatedBy){
        $sql = "UPDATE `loan_types` SET
                    `name` = ?, `description` = ?, `min_amount` = ?, `max_amount` = ?, `interest_rate` = ?,
                    `min_period` = ?, `max_period` = ?, `savings_multiplier` = ?, `required_grantors` = ?,
                    `processing_fee_percent` = ?, `allowed_repayment_modes` = ?, `eligibility_notes` = ?,
                    `status` = ?, `updated_by` = ?
                WHERE `id` = ?";
        $stmt = $conn->prepare($sql);
        if($stmt === false) return $conn->error;
        $stmt->bind_param(
            "ssdddiiidssssii",
            $d['name'], $d['description'], $d['min_amount'], $d['max_amount'], $d['interest_rate'],
            $d['min_period'], $d['max_period'], $d['savings_multiplier'], $d['required_grantors'],
            $d['processing_fee_percent'], $d['allowed_repayment_modes'], $d['eligibility_notes'],
            $d['status'], $updatedBy, $id
        );
        return ($stmt->execute()) ? true : $stmt->error;
    }

    function softDeleteLoanType(mysqli $conn, int $id){
        $sql = "UPDATE `loan_types` SET `deleted_at` = NOW() WHERE `id` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id);
        return ($stmt->execute()) ? true : $stmt->error;
    }

    function toggleLoanTypeStatus(mysqli $conn, int $id, string $status){
        $sql = "UPDATE `loan_types` SET `status` = ? WHERE `id` = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        return ($stmt->execute()) ? true : $stmt->error;
    }

    // Count how many loans (any status) reference a given product —
    // used to warn the admin before deleting a product that's already in use.
    function countLoansByLoanType(mysqli $conn, int $loanTypeId){
        $sql = "SELECT COUNT(*) AS total FROM loans WHERE loan_type = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $loanTypeId);
        $stmt->execute();
        $row = stmt_fetch_assoc($stmt);
        return $row ? (int)$row['total'] : 0;
    }

    // ================================================================
    //  LOAN COLLECTION (individual repayment collection screen)
    //  Ported/adapted from the equivalent "individual_loan_collection"
    //  feature in the sister Kuringe system, but rewritten against
    //  zimamoto's own schema (loans / loan_schedules / min_subs /
    //  min_transactions) instead of Kuringe's (approved_loan /
    //  loan_schedule / subsidiaries / transactions).
    // ================================================================

    // Members who currently have at least one approved loan — feeds the
    // "Select Loan Customer" dropdown on the collection screen.
    // Optionally restricted to one branch for branch-level accountants.
    function selectUsersWithApprovedLoans(mysqli $conn, ?int $branchId = null){
        if($conn === false){
            exit();
        }
        $sql = "SELECT DISTINCT users.id AS user_id, users.name, users.phone, users.email
                FROM loans
                JOIN users ON users.id = loans.user_id
                WHERE loans.status = 'approved' AND loans.deleted_at IS NULL
                  AND users.deleted_at IS NULL";
        if($branchId !== null){
            $sql .= " AND loans.branch_id = ?";
        }
        $sql .= " ORDER BY users.name ASC";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            return $conn->error;
        }
        if($branchId !== null){
            $stmt->bind_param("i", $branchId);
        }
        return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }

    // Single loan_schedules row, by its own id — needed when collecting
    // a payment against one or more individually-selected installments.
    function selectLoanScheduleRowById(mysqli $conn, int $scheduleId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT * FROM `loan_schedules` WHERE id = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            return $conn->error;
        }
        $stmt->bind_param("i", $scheduleId);
        return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
    }

    // Records the result of a collection against one schedule row:
    // new cumulative paid_amount + resulting status ('half-paid'/'paid').
    function updateLoanScheduleRowPayment(mysqli $conn, int $scheduleId, float $paidAmount, string $status){
        if($conn === false){
            exit();
        }
        $sql = "UPDATE `loan_schedules` SET `paid_amount` = ?, `status` = ? WHERE id = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            return $conn->error;
        }
        $stmt->bind_param("dsi", $paidAmount, $status, $scheduleId);
        return ($stmt->execute()) ? true : $stmt->error;
    }

    // Total amount already paid across a loan's schedule rows — used to
    // show "Paid" / "Remaining" totals on the collection screen header.
    function getTotalPaidForLoan(mysqli $conn, int $loanId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT COALESCE(SUM(paid_amount),0) AS total_paid FROM loan_schedules WHERE loan_id = ? AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            return 0.0;
        }
        $stmt->bind_param("i", $loanId);
        $stmt->execute();
        $row = stmt_fetch_assoc($stmt);
        return $row ? (float)$row['total_paid'] : 0.0;
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

    // ================================================================
    //  UNIFIED, FILTERABLE LOAN APPLICATIONS LIST
    //  Replaces the separate "Approved Loan List" / "Pending Loan List"
    //  pages — one query, any combination of filters, any status
    //  (including "all", which shows pending + approved + rejected together).
    // ================================================================
    function selectLoansFiltered(mysqli $conn, array $filters = []){
        $sql = "SELECT loans.*, users.name AS member_name, branches.name AS branch_name,
                       loan_types.name AS product_name
                FROM loans
                JOIN users ON loans.user_id = users.id
                LEFT JOIN branches ON loans.branch_id = branches.id
                LEFT JOIN loan_types ON loans.loan_type = loan_types.id
                WHERE loans.deleted_at IS NULL";
        $types = '';
        $params = [];

        if(!empty($filters['status']) && $filters['status'] !== 'all'){
            $sql .= " AND loans.status = ?";
            $types .= 's';
            $params[] = $filters['status'];
        }
        if(!empty($filters['loan_type'])){
            $sql .= " AND loans.loan_type = ?";
            $types .= 'i';
            $params[] = (int)$filters['loan_type'];
        }
        if(!empty($filters['branch_id'])){
            $sql .= " AND loans.branch_id = ?";
            $types .= 'i';
            $params[] = (int)$filters['branch_id'];
        }
        if(!empty($filters['date1'])){
            $sql .= " AND loans.created_at >= ?";
            $types .= 's';
            $params[] = $filters['date1'] . ' 00:00:00';
        }
        if(!empty($filters['date2'])){
            $sql .= " AND loans.created_at <= ?";
            $types .= 's';
            $params[] = $filters['date2'] . ' 23:59:59';
        }
        if(!empty($filters['search'])){
            $sql .= " AND users.name LIKE ?";
            $types .= 's';
            $params[] = '%' . $filters['search'] . '%';
        }
        $sql .= " ORDER BY loans.id DESC";
        if(!empty($filters['limit'])){
            $sql .= " LIMIT " . (int)$filters['limit'];
        }

        $stmt = $conn->prepare($sql);
        if($stmt === false) return $conn->error;
        if(!empty($params)){
            $stmt->bind_param($types, ...$params);
        }
        return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
    }

    // ================================================================
    //  MEMBER FINANCIAL HISTORY HELPERS — used by the eligibility engine
    //  and by the loan processing screen so the reviewer can see the
    //  member's real standing before deciding.
    // ================================================================

    // Signed ledger balance (dr - cr) for one of the member's min-sub accounts.
    // Returns null if the member has no such account yet.
    function getMinSubSignedBalance(mysqli $conn, int $userId, string $category){
        $sub = selectMinSubByUserIDAndCategory($conn, $userId, $category);
        if(!$sub || !is_array($sub)) return null;
        $balance = 0.0;
        $transactions = getMinTransactionByMinSubId($conn, $sub['id']);
        if($transactions && is_array($transactions)){
            foreach($transactions as $t){
                if($t['dr_account'] == $sub['id']) $balance += (float)$t['amount'];
                elseif($t['cr_account'] == $sub['id']) $balance -= (float)$t['amount'];
            }
        }
        return $balance;
    }

    // Total savings (saving + amana + share), expressed as a positive amount,
    // regardless of which side of the ledger the contribution accounts sit on.
    function getMemberTotalSavings(mysqli $conn, int $userId){
        $saving = abs(getMinSubSignedBalance($conn, $userId, 'saving') ?? 0.0);
        $amana  = abs(getMinSubSignedBalance($conn, $userId, 'amana')  ?? 0.0);
        $share  = abs(getMinSubSignedBalance($conn, $userId, 'share')  ?? 0.0);
        return [
            'saving' => $saving,
            'amana'  => $amana,
            'share'  => $share,
            'total'  => $saving + $amana + $share,
        ];
    }

    // Outstanding balance across the member's currently-approved loans:
    // (principal + interest billed so far) - (amount actually paid).
    function getMemberOutstandingLoanBalance(mysqli $conn, int $userId, ?int $excludeLoanId = null){
        $sql = "SELECT id FROM loans WHERE user_id = ? AND status = 'approved' AND deleted_at IS NULL";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $loans = stmt_fetch_all($stmt);
        $outstanding = 0.0;
        $activeCount = 0;
        if($loans && is_array($loans)){
            foreach($loans as $l){
                if($excludeLoanId !== null && (int)$l['id'] === $excludeLoanId) continue;
                $schedule = selectLoanScheduleByLoanId($conn, (int)$l['id']);
                if($schedule && is_array($schedule)){
                    $loanHasBalance = false;
                    foreach($schedule as $row){
                        $due = (float)$row['principle'] + (float)$row['interest_amount'];
                        $paid = (float)$row['paid_amount'];
                        if($due - $paid > 0.01) $loanHasBalance = true;
                        $outstanding += max(0, $due - $paid);
                    }
                    if($loanHasBalance) $activeCount++;
                }
            }
        }
        return ['outstanding_balance' => $outstanding, 'active_loan_count' => $activeCount];
    }

    // Count overdue, unpaid installments (a simple default/arrears indicator)
    // across ALL of the member's approved loans.
    function getMemberOverdueInstallmentCount(mysqli $conn, int $userId){
        $sql = "SELECT COUNT(*) AS total FROM loan_schedules ls
                JOIN loans l ON ls.loan_id = l.id
                WHERE l.user_id = ? AND l.deleted_at IS NULL AND ls.deleted_at IS NULL
                  AND ls.status != 'paid' AND ls.payment_date < CURDATE()";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $row = stmt_fetch_assoc($stmt);
        return $row ? (int)$row['total'] : 0;
    }

    // Full loan history for a member — used on the processing screen so the
    // reviewer can see prior applications, not just the one on screen.
    function getMemberLoanHistory(mysqli $conn, int $userId, ?int $excludeLoanId = null){
        $sql = "SELECT loans.*, loan_types.name AS product_name FROM loans
                LEFT JOIN loan_types ON loans.loan_type = loan_types.id
                WHERE loans.user_id = ? AND loans.deleted_at IS NULL";
        if($excludeLoanId !== null){
            $sql .= " AND loans.id != " . (int)$excludeLoanId;
        }
        $sql .= " ORDER BY loans.id DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        return stmt_fetch_all($stmt);
    }

    // Guarantor acceptance summary for a loan — accepted / pending / rejected counts,
    // so the reviewer can see at a glance whether guarantors have actually agreed.
    function getLoanGrantorAcceptanceSummary(mysqli $conn, int $loanId){
        $grantors = selectLoanGrantorByLoanId($conn, $loanId);
        $summary = ['total' => 0, 'accepted' => 0, 'pending' => 0, 'rejected' => 0, 'details' => []];
        if($grantors && is_array($grantors)){
            foreach($grantors as $g){
                $status = $g['status'] ?? 'pending';
                $summary['total']++;
                if(isset($summary[$status])) $summary[$status]++;
                $summary['details'][] = ['name' => $g['name'], 'status' => $status];
            }
        }
        return $summary;
    }

    // ================================================================
    //  THE ELIGIBILITY ENGINE
    //  Pulls together the loan, its product rules, and the member's
    //  financial history into one structured verdict the admin can act on.
    //  Each entry in "checks" is: label, status (pass|warning|fail), detail.
    // ================================================================
    function evaluateLoanEligibility(mysqli $conn, int $loanId){
        $loan = selectLoanById($conn, $loanId);
        if(!$loan || !is_array($loan)){
            return ['error' => 'Loan not found'];
        }
        $userId = (int)$loan['user_id'];
        $loanType = !empty($loan['loan_type']) ? selectLoanTypeById($conn, (int)$loan['loan_type']) : null;

        $savings = getMemberTotalSavings($conn, $userId);
        $outstanding = getMemberOutstandingLoanBalance($conn, $userId, $loanId);
        $overdueCount = getMemberOverdueInstallmentCount($conn, $userId);
        $grantorSummary = getLoanGrantorAcceptanceSummary($conn, $loanId);
        $history = getMemberLoanHistory($conn, $userId, $loanId);
        $rejectedCount = 0;
        foreach($history as $h){ if($h['status'] === 'rejected') $rejectedCount++; }

        $multiplier = $loanType ? (float)$loanType['savings_multiplier'] : 3.0;
        $maxBySavings = round($savings['total'] * $multiplier, 2);
        $amount = (float)$loan['principle'];

        $checks = [];

        // 1. Savings-based capacity
        $checks[] = [
            'label'  => 'Savings-based loan capacity',
            'status' => $amount <= $maxBySavings ? 'pass' : 'fail',
            'detail' => "Member's total savings: TZS " . number_format($savings['total'],2) .
                        " x {$multiplier} = max TZS " . number_format($maxBySavings,2) .
                        " eligible. Requested: TZS " . number_format($amount,2) . ".",
        ];

        // 2. Product amount range
        if($loanType){
            $minA = (float)$loanType['min_amount'];
            $maxA = (float)$loanType['max_amount'];
            $withinRange = $amount >= $minA && ($maxA <= 0 || $amount <= $maxA);
            $rangeText = 'TZS ' . number_format($minA,2) . ($maxA > 0 ? ' – TZS ' . number_format($maxA,2) : ' and above');
            $checks[] = [
                'label'  => "Within '{$loanType['name']}' amount range",
                'status' => $withinRange ? 'pass' : 'fail',
                'detail' => "Product allows {$rangeText}. Requested: TZS " . number_format($amount,2) . ".",
            ];
            $minP = (int)$loanType['min_period'];
            $maxP = (int)$loanType['max_period'];
            $periodOk = (int)$loan['period'] >= $minP && (int)$loan['period'] <= $maxP;
            $checks[] = [
                'label'  => "Within '{$loanType['name']}' period range",
                'status' => $periodOk ? 'pass' : 'fail',
                'detail' => "Product allows {$minP}–{$maxP} months. Requested: " . (int)$loan['period'] . " months.",
            ];
        } else {
            $checks[] = [
                'label'  => 'Loan product',
                'status' => 'warning',
                'detail' => 'No loan product is linked to this application — rules could not be checked.',
            ];
        }

        // 3. Existing outstanding balance / multiple active loans
        $checks[] = [
            'label'  => 'Existing loan balance',
            'status' => $outstanding['outstanding_balance'] > 0 ? ($outstanding['active_loan_count'] > 1 ? 'warning' : 'warning') : 'pass',
            'detail' => $outstanding['outstanding_balance'] > 0
                ? "Member still owes TZS " . number_format($outstanding['outstanding_balance'],2) . " across {$outstanding['active_loan_count']} active loan(s)."
                : 'No outstanding balance on prior loans.',
        ];

        // 4. Repayment / default history
        $checks[] = [
            'label'  => 'Repayment history',
            'status' => $overdueCount > 0 ? 'fail' : 'pass',
            'detail' => $overdueCount > 0
                ? "{$overdueCount} overdue, unpaid installment(s) found on prior/active loans."
                : 'No overdue installments on record.',
        ];

        // 5. Prior rejections
        if($rejectedCount > 0){
            $checks[] = [
                'label'  => 'Application history',
                'status' => 'warning',
                'detail' => "Member has {$rejectedCount} previously rejected loan application(s).",
            ];
        }

        // 6. Guarantor acceptance
        $requiredGrantors = $loanType ? (int)$loanType['required_grantors'] : max(1, $grantorSummary['total']);
        $checks[] = [
            'label'  => 'Guarantor acceptance',
            'status' => $grantorSummary['total'] === 0
                ? 'warning'
                : ($grantorSummary['accepted'] >= $requiredGrantors ? 'pass' : ($grantorSummary['rejected'] > 0 ? 'fail' : 'warning')),
            'detail' => $grantorSummary['total'] === 0
                ? 'No guarantors recorded for this application.'
                : "{$grantorSummary['accepted']} accepted, {$grantorSummary['pending']} pending, {$grantorSummary['rejected']} rejected (of {$grantorSummary['total']}; product requires {$requiredGrantors}).",
        ];

        // Overall recommendation: pass only if there are no 'fail' checks
        $hasFail = false; $hasWarning = false;
        foreach($checks as $c){
            if($c['status'] === 'fail') $hasFail = true;
            if($c['status'] === 'warning') $hasWarning = true;
        }
        $recommendation = $hasFail ? 'not_recommended' : ($hasWarning ? 'review_carefully' : 'recommended');

        return [
            'loan'              => $loan,
            'loan_type'         => $loanType,
            'savings'           => $savings,
            'outstanding'       => $outstanding,
            'overdue_count'     => $overdueCount,
            'grantor_summary'   => $grantorSummary,
            'rejected_count'    => $rejectedCount,
            'max_by_savings'    => $maxBySavings,
            'checks'            => $checks,
            'recommendation'    => $recommendation,
        ];
    }
?>