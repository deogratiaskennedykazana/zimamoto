<?php

        function selectOpeningBalance(mysqli $conn, int $subId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM opening_balances WHERE `account_id`='$subId'";
            if($result = $conn->query($sql)){
                $data = $result->fetch_assoc();
                $result->free();
                return $data;
            }else{
                return $conn->error;
            }
        }

        function addOpeningBalance(mysqli $conn, int $subId, float $amount, int $currencyId, float $currValue, string $type,  string $date){
            if($conn === false){
                exit();
            }
            $sql = "INSERT INTO `opening_balances`(`account_id`, `ammount`, `currency_id`,`curr_value`, `type`, `date_`) VALUES (?,?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("ididss", $subId, $amount,$currencyId, $currValue, $type, $date);
            return ($stmt->execute()) ? $stmt->insert_id : $conn->error;
        }
        function selectAllOpeningBalances(mysqli $conn){
            if($conn === false){
                exit();
            }
            $sql = "SELECT opening_balances.*, subsidiaries.name AS account FROM opening_balances
                        JOIN subsidiaries ON opening_balances.account_id = subsidiaries.id
                        WHERE opening_balances.deleted_at IS NULL 
                        AND subsidiaries.deleted_at IS NULL
                        ORDER BY date_ DESC, name ASC";
            return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }
        function  deleteOpeningBalance(mysqli $conn, int $id){
                if($conn === false){
                    exit();
                }
                $now = date("Y-m-d H:m:s");
                $sql = "UPDATE `opening_balances` SET deleted_at ='$now' WHERE id=$id ;";
                return ($conn->query($sql) === true) ? true : $conn->errno;
        }
        function selectOpeningBalanceBySubId(mysqli $conn, int $subId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT opening_balances.*, subsidiaries.name AS account FROM opening_balances
                        JOIN subsidiaries ON opening_balances.account_id = subsidiaries.id
                        WHERE opening_balances.deleted_at IS NULL 
                        AND subsidiaries.deleted_at IS NULL
                        AND opening_balances.account_id=$subId;";
            return ($result = $conn->query($sql)) ? $result->fetch_assoc() : $conn->error;
        }
        
         function addMinSubOpeningBalance(mysqli $conn, int $minSubId, float $amount,  float $currValue, string $type,  string $date){
                if($conn === false){
                    exit();
                }
            $sql = "INSERT INTO `min_sub_opening_balances`( `min_sub_id`, `amount`, `type`, `currValue`, `date_`) VALUES (?,?,?,?,?)";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("idsds", $minSubId, $amount, $type, $currValue, $date);
            return ($stmt->execute()) ? $stmt->insert_id : $conn->error;
        } 
        
        
         function selectAllMinSubOpeningBalances(mysqli $conn){
            if($conn === false){
                exit();
            }
            $sql = "SELECT min_sub_opening_balances.*, min_subs.name AS account FROM min_sub_opening_balances
                        JOIN min_subs ON min_sub_opening_balances.min_sub_id = min_subs.id
                        WHERE min_sub_opening_balances.deleted_at IS NULL 
                        AND min_subs.deleted_at IS NULL
                        ORDER BY date_ DESC, name ASC";
            return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        } 
        
        function deleteMinSubOpeningBalance(mysqli $conn, int $id){
                if($conn === false){
                    exit();
                }
                $now = date("Y-m-d H:m:s");
                $sql = "UPDATE `min_sub_opening_balances` SET deleted_at ='$now' WHERE id=? ;";
                $stmt = $conn->prepare($sql);
                if($stmt === false){
                    exit();
                }
                $stmt->bind_param("i", $id);
                return ($stmt->execute()) ? true : $stmt->errno;
        }
         function selectOpeningBalanceByMinSubId(mysqli $conn, int $minSubId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM min_sub_opening_balances WHERE `min_sub_id`='$minSubId'";
            if($result = $conn->query($sql)){
                $data = $result->fetch_assoc();
                $result->free();
                return $data;
            }else{
                return $conn->error;
            }
        }
?>