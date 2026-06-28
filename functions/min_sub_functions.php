<?php
    function selectAllMinSub(mysqli $conn){
		if($conn === false){
				exit();
			}
		$sql = "SELECT * FROM min_subs WHERE deleted_at IS NULL";
		if($result = $conn->query($sql)){
			$data = $result->fetch_all(MYSQLI_ASSOC);
			return $data;
		}else{
			return $conn->error;
		}
		
	}
        
        
        
        function createMinsub(mysqli $conn, string $name, int $userId = null, int $subId,int $branchId, string $type, string $category){
            if($conn === false){
                exit;
            }
            $stmt = null;
            if($userId === null){
                $sql = "INSERT INTO `min_subs`(`sub_id`, `name`, `type`, `category`,`branch_id`) VALUES (?,?,?,?,?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssi", $subId, $name, $type, $category,$branchId);
            } else{
                $sql = "INSERT INTO `min_subs`(`user_id`,`sub_id`, `name`, `type`, `category`,`branch_id`) VALUES (?,?,?,?,?,?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisssi",$userId ,$subId, $name, $type, $category, $branchId);
            }
           
            return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
        }
       function selectAllMinSubs(mysqli $conn, $branch_id = null) {
            if ($conn === false) exit;
        
            $sql = "SELECT min_subs.*, subsidiaries.name AS sub 
                    FROM min_subs 
                    JOIN subsidiaries ON subsidiaries.id = min_subs.sub_id 
                    WHERE min_subs.deleted_at IS NULL";
        
            if ($branch_id !== null) {
                $branch_id = $conn->real_escape_string($branch_id);
                $sql .= " AND (min_subs.branch_id = '$branch_id' OR min_subs.branch_id = 0)";
            }
        
            $sql .= " ORDER BY min_subs.name;";
            
            $result = $conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }


        function selectMinSubByBranchId(mysqli $conn, int $branchId){
            if($conn === false){
                exit;
            }
            $sql = "SELECT * FROM min_subs WHERE branch_id = ?  AND deleted_at IS NULL ORDER BY min_subs.name;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("i", $branchId);
            return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->errno;
        }
        function selectMinSubByNameAndCategory(mysqli $conn, string $name, string $category){
            if($conn === false){
                exit;
            }
            $sql = "SELECT * FROM min_subs WHERE name LIKE '%$name%' AND category = '$category' AND deleted_at IS NULL ";
                     //return $sql;
        } 
        
        function selectMinSubByCheckNoAndCategory(mysqli $conn, string $checkNo, string $category){
            if($conn === false){
                exit;
            }
            $sql = "SELECT min_subs.*, members.check_no FROM min_subs JOIN members ON members.user_id = min_subs.user_id WHERE members.check_no LIKE '%$checkNo%' AND min_subs.category  = '$category' AND min_subs.deleted_at IS NULL ";
          
          return($result = $conn->query($sql)) ? $result->fetch_assoc() : $conn->error;
          // return $sql;
        }
        
        function searchUserByName(mysqli $conn, string $name) {
    if($conn === false){
        exit;
    }
    
    $sql = "SELECT users.*, members.reg_no FROM users JOIN members ON users.id = members.user_id WHERE users.name LIKE '%$name%'";
    
    $result = $conn->query($sql);
    
    if($result && $result->num_rows > 0){
        return $result->fetch_all(MYSQLI_ASSOC);
    }
    
    return false;
}
        
        
        function selectMinSubByCategory(mysqli $conn, string $category){
            if($conn === false){
                exit;
            }
            $sql = "SELECT * FROM min_subs WHERE category = '$category' AND deleted_at IS NULL ORDER BY min_subs.name;";
          
           return($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->errno;
        }
       function getMinSubById(mysqli $conn, int $minSubId){
           if($conn === false){
               exit();
           }
           $sql = "SELECT * FROM min_subs WHERE id = ? AND deleted_at IS NULL";
           $stmt = $conn->prepare($sql);
           if($stmt === false){
               exit;
           }
           $stmt->bind_param("i", $minSubId);
           return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->errno;
       }
               
      
         
        function selectMinSubsByUserId(mysqli $conn, int $userId){
    $sql = "SELECT * FROM min_subs WHERE user_id = ? AND deleted_at IS NULL ORDER BY min_subs.name;";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        echo "<pre>Prepare failed: " . $conn->error . "</pre>";
        return [];
    }

    $stmt->bind_param("i", $userId);

    if (!$stmt->execute()) {
        echo "<pre>Execute failed: " . $stmt->error . "</pre>";
        return [];
    }

    return stmt_fetch_all($stmt);
}

        function selectMinSubByUserIDAndCategory(mysqli $conn, int $userId, string $type){
            if($conn === false){
                exit;
            }
            // FIX 1: was interpolating $userId and $type directly into SQL (injection risk).
            // FIX 2: was calling $conn->query() twice on the same SQL — the first result
            //         was discarded and the second fetch_assoc() could return stale/wrong data
            //         on servers where query() reuses internal result buffers.
            $sql = "SELECT * FROM min_subs WHERE user_id = ? AND category = ? AND deleted_at IS NULL ORDER BY name LIMIT 1";
            $stmt = $conn->prepare($sql);
            if(!$stmt) return false;
            $stmt->bind_param('is', $userId, $type);
            return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : false;
        }
         function selectMinSubsBySubsidiaryId(mysqli $conn, int $subsidiaryId){
            if($conn === false){
                exit;
            }
            $sql = "SELECT * FROM min_subs WHERE sub_id = ? AND deleted_at IS NULL ORDER BY min_subs.name;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("i", $subsidiaryId);
            return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->errno;
        }
        function selectMinSubsBySubsidiaryIdAndBranchId(mysqli $conn, int $subsidiaryId, int $branchId){
            if($conn === false){
                exit;
            }
            $sql = "SELECT * FROM min_subs WHERE sub_id = ? AND branch_id = ? AND deleted_at IS NULL ORDER BY min_subs.name;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("ii", $subsidiaryId, $branchId);
            return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->errno;
        }
        
           function softDeleteMinSub(mysqli $conn, int $subId){
            if($conn === false){
                exit;
            }
            $sql = "UPDATE min_subs SET deleted_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("i", $subId);
            return ($stmt->execute()) ? true : $stmt->errno;
        }
        
        
        
        function updateMinSubsAccounts(mysqli $conn, int $userId, string $name){
            if($conn === false){
                exit;
            }
            
            // Update Amana Account
            $sqlAmana = "UPDATE min_subs SET name = ? WHERE user_id = ? AND category = 'amana'";
            $stmtAmana = $conn->prepare($sqlAmana);
            if($stmtAmana === false){
                exit;
            }
            $amanaName = $name . " Amana Account";
            $stmtAmana->bind_param("si", $amanaName, $userId);
            if(!$stmtAmana->execute()){
                return false;
            }
            
            // Update Share Account
            $sqlShare = "UPDATE min_subs SET name = ? WHERE user_id = ? AND category = 'share'";
            $stmtShare = $conn->prepare($sqlShare);
            if($stmtShare === false){
                exit;
            }
            $shareName = $name . " Share Account";
            $stmtShare->bind_param("si", $shareName, $userId);
            if(!$stmtShare->execute()){
                return false;
            }
            
            // Update Saving Account
            $sqlSaving = "UPDATE min_subs SET name = ? WHERE user_id = ? AND category = 'saving'";
            $stmtSaving = $conn->prepare($sqlSaving);
            if($stmtSaving === false){
                exit;
            }
            $savingName = $name . " Saving Account";
            $stmtSaving->bind_param("si", $savingName, $userId);
            if(!$stmtSaving->execute()){
                return false;
            }
            
            // Update Loan Account (if exists)
            $sqlLoan = "UPDATE min_subs SET name = ? WHERE user_id = ? AND category = 'loan'";
            $stmtLoan = $conn->prepare($sqlLoan);
            if($stmtLoan === false){
                exit;
            }
            $loanName = $name . " Loan Account";
            $stmtLoan->bind_param("si", $loanName, $userId);
            $stmtLoan->execute(); // Don't return false if loan doesn't exist
            
            return true;
}
?>