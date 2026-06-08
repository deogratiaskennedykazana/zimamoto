<?php
      function createTransaction(
                                    mysqli $conn,
                                    string $referenceNo,
                                    float $currency,
                                    int $drAccount,
                                    string $description,
                                    float $amount,
                                    int $crAccount,
                                    string $date,
                                    int $userId = null,
                                    int $branchId,
                                    string $status
                                )
                {
                        if($conn === false){
                                exit;
                        }
                        $stmt = null;
                        if($userId == null){
                                $sql = "INSERT INTO `transaction_voucher`( `reference_no`, `currency`, `dr_account`, `description`, `dr_ammount`, `cr_account`, `cr_ammount`, `date_`, `branch_id`, `status`) VALUES (?,?,?,?,?,?,?,?,?,?);";
                                $stmt = $conn->prepare($sql);
                                $stmt->bind_param("sdisdidsis",$referenceNo,$currency,$drAccount,$description,$amount,$crAccount,$amount,$date,$branchId, $status);
                        } else{
                            $sql = "INSERT INTO `transaction_voucher`( `reference_no`, `currency`, `dr_account`, `description`, `dr_ammount`, `cr_account`, `cr_ammount`, `date_`,  `user_id`, `branch_id`, `status`) VALUES (?,?,?,?,?,?,?,?,?,?,?);";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sdisdidssiis", $referenceNo,$currency,$drAccount,$description,$amount,$crAccount,$amount,$date,$userId,$branchId, $status);
                        } 
                        return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno; 
                }
       function selectTransactionByStatus(mysqli $conn, string $status, $branch_id = null) {
            if ($conn === false) exit();
        
            $sql = "SELECT transaction_voucher.*, 
                           debit.name AS debit_acc, 
                           credit.name AS credit_ac 
                    FROM transaction_voucher
                    JOIN subsidiaries debit ON debit.id = transaction_voucher.dr_account
                    JOIN subsidiaries credit ON credit.id = transaction_voucher.cr_account
                    WHERE transaction_voucher.deleted_at IS NULL
                      AND transaction_voucher.status = '$status'";
        
            if ($branch_id !== null) {
                $branch_id = $conn->real_escape_string($branch_id);
                $sql .= " AND (transaction_voucher.branch_id = '$branch_id' OR transaction_voucher.branch_id = 0)";
            }
        
            $sql .= " ORDER BY transaction_voucher.id DESC";
        
            $result = $conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }

        function selectTransactionBySubIdAndBranchId(mysqli $conn, int $subId,int $branchId){
                if($conn === false){
                        exit();
                }
                $sql = "SELECT transaction_voucher.*, debit.name AS debit_acc, credit.name AS credit_ac FROM transaction_voucher
                        JOIN subsidiaries debit ON debit.id = transaction_voucher.dr_account
                        JOIN subsidiaries credit ON credit.id = transaction_voucher.cr_account
                        WHERE (transaction_voucher.dr_account = $subId OR transaction_voucher.cr_account = $subId)
                        AND transaction_voucher.status ='active'
                        AND transaction_voucher.branch_id = $branchId
                        AND transaction_voucher.deleted_at IS NULL;";
                return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;

        }
        function approveTransaction(mysqli $conn, int $id, string $status){
                if($conn === false){
                        exit();
                }
                $sql = "UPDATE `transaction_voucher` SET `status`=? WHERE id=?;";
                $stmt = $conn->prepare($sql);
                if($stmt === false){
                        return $stmt ;
                }
                $stmt->bind_param("si", $status, $id);
                return ($stmt->execute()) ? true : $stmt->error;
        }
        function selectTransactionById(mysqli $conn, int $transactionId){
                if($conn === false){
                        exit();
                }
                $sql = "SELECT transaction_voucher.*, debit.name AS debit_acc, credit.name AS credit_ac, branches.name as branch FROM transaction_voucher
                        JOIN subsidiaries debit ON debit.id = transaction_voucher.dr_account
                        JOIN subsidiaries credit ON credit.id = transaction_voucher.cr_account
                        JOIN branches ON branches.id = transaction_voucher.branch_id
                        WHERE
                         transaction_voucher.status ='active'
                        AND transaction_voucher.id = $transactionId
                        AND transaction_voucher.deleted_at IS NULL;";
                return ($result = $conn->query($sql)) ? $result->fetch_assoc() : $conn->error;

        }
         function selectAllTransactionsBySubId(mysqli $conn, int $subId){
                if($conn === false){
                        exit();
                }
                $sql = "SELECT transaction_voucher.*, debit.name AS debit_acc, credit.name AS credit_ac FROM transaction_voucher
                        JOIN subsidiaries debit ON debit.id = transaction_voucher.dr_account
                        JOIN subsidiaries credit ON credit.id = transaction_voucher.cr_account
                        WHERE (transaction_voucher.dr_account = $subId OR transaction_voucher.cr_account = $subId)
                        AND transaction_voucher.status ='active'
                        
                        AND transaction_voucher.deleted_at IS NULL;";
                return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;

        }
    function softDeleteTransactionByAccountId(mysqli $conn, int $subId){
        if($conn === false){
            exit();
        }
        $sql = "UPDATE min_transactions SET deleted_at = NOW() WHERE deleted_at IS NULL AND (dr_account = ? OR cr_account = ?);";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            exit();
        }
        $stmt->bind_param("ii", $subId, $subId);
        return ($stmt->execute()) ? true : $stmt->errno;
    }
        

        
?>