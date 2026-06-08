<?php
     function createMinTransaction(
                              mysqli $conn,
                              string $referenceNo,
                             
                              int $drAccount,
                              string $description,
                              float $amount,
                              int $crAccount,
                              string $date,
                              int $userId = null,
                              int $branchId,
                              string $status
                          ){
                            $sql = "INSERT INTO `min_transactions`(`ref_no`, `dr_account`, `description`, `amount`, `cr_account`, `date_`, `status`,  `user_id`, `branch_id`) VALUES (?,?,?,?,?,?,?,?,?);";
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param("sisdissii", $referenceNo,$drAccount,$description,$amount,$crAccount,$date,$status,$userId,$branchId);
                            return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
                          }
    function getMinTransactions(mysqli $conn){
        if($conn === false){
            exit();
        }
        $sql = "SELECT min_transactions.*, debit.name AS debit_acc, credit.name AS credit_acc FROM min_transactions
                JOIN min_subs debit ON debit.id = min_transactions.dr_account
                JOIN min_subs credit ON credit.id = min_transactions.cr_account
                WHERE min_transactions.deleted_at IS NULL;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
    function getMinTransactionByBranch(mysqli $conn, int $branchId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT min_transactions.*, debit.name AS debit_acc, credit.name AS credit_acc FROM min_transactions
                JOIN min_subs debit ON debit.id = min_transactions.dr_account
                JOIN min_subs credit ON credit.id = min_transactions.cr_account
                WHERE min_transactions.deleted_at IS NULL AND min_transactions.branch_id = $branchId;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
    function getMinTransactionByMinSubId(mysqli $conn, int $minSubId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT min_transactions.*, debit.name AS debit_acc, credit.name AS credit_acc FROM min_transactions
                JOIN min_subs debit ON debit.id = min_transactions.dr_account
                JOIN min_subs credit ON credit.id = min_transactions.cr_account
                WHERE min_transactions.deleted_at IS NULL AND (min_transactions.dr_account = $minSubId OR min_transactions.cr_account = $minSubId);";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
    function getMinTransactionByMinSubIdAndBranchId(mysqli $conn, int $minSubId, int $branchId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT min_transactions.*, debit.name AS debit_acc, credit.name AS credit_acc FROM min_transactions
                JOIN min_subs debit ON debit.id = min_transactions.dr_account
                JOIN min_subs credit ON credit.id = min_transactions.cr_account
                WHERE min_transactions.deleted_at IS NULL AND min_transactions.branch_id = $branchId AND (min_transactions.dr_account = $minSubId OR min_transactions.cr_account = $minSubId);";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
    	function selectMinSubsidiariesByParentId(mysqli $conn, int $parent_id){
		if($conn === false){
			exit;
		}
		$sql = "SELECT * FROM min_subs WHERE sub_id='$parent_id' AND deleted_at IS NULL ORDER BY name";
		if($result = $conn->query($sql)){
			$subs = $result->fetch_all(MYSQLI_ASSOC);
			$result->free();
			return $subs;
		} else{
			return $conn->error;
		}
	
    	    
    	    
    	}
    	function selectMinSubLastBalance(mysqli $conn, int $minSubId, string $lastDate){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM sub_transactions  WHERE 
                    (cr_account = '$minSubId' OR dr_account = '$minSubId') 
                    AND date_<='$lastDate'";
            if($result = $conn->query($sql)){
                $balance =0;
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();

                if($data && count($data)>0){
                    foreach($data as $transaction){
                        if($transaction['cr_account'] == $minSubId ){
                            $balance -= $transaction['cr_ammount'];

                        }elseif($transaction['dr_account'] == $minSubId){
                            $balance += $transaction['dr_ammount'];
                        }
                    }
                } else{
                    $balance =0;
                }


                return $balance;

            }else{
                return $conn->error;
            }
    }
     function getTransactionBYSubIdAndBranchId(mysqli $conn, int $subId, int $branchId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT min_transactions.*, debit.name AS debit_acc, debit.id as debit_id, credit.name AS credit_acc, credit.id AS credit_id FROM min_transactions
                JOIN min_subs debit ON debit.id = min_transactions.dr_account
                JOIN min_subs credit ON credit.id = min_transactions.cr_account
                WHERE min_transactions.deleted_at IS NULL AND min_transactions.branch_id = $branchId AND debit.sub_id = $subId OR credit.sub_id = $subId ;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
     function  selectAllSubTransactionByMinSubId(mysqli $conn, int $minSubId){
        if($conn === false){
            exit();
        }
        $sql = "SELECT min_transactions.*, debit.name AS debit_acc, credit.name AS credit_acc FROM min_transactions
                JOIN min_subs debit ON debit.id = min_transactions.dr_account
                JOIN min_subs credit ON credit.id = min_transactions.cr_account
                WHERE min_transactions.deleted_at IS NULL AND (min_transactions.dr_account = $minSubId OR min_transactions.cr_account = $minSubId);";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
    }
   function softDeleteMinTransactionByAccountId(mysqli $conn, int $subId){
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