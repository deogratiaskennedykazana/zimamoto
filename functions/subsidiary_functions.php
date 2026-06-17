<?php

function createSubsidiary(mysqli $conn, string $name, int $ledger_id, int $created_by, string $type, string $subcategory = null, string $phone = null, string $email = null, string $tin = null, string $vrn = null, string $address = null, int $user_id = null) {
    if ($conn === false) exit();
    $sql = "INSERT INTO subsidiaries (`name`, `ledger_id`, `type`, `subcategory`, `phone`, `email`, `tin`, `vrn`, `address`, `created_by`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) return $conn->error;
    $stmt->bind_param("sisssssssi", $name, $ledger_id, $type, $subcategory, $phone, $email, $tin, $vrn, $address, $created_by);
    if ($stmt->execute()) {
        $id = $stmt->insert_id;
        $stmt->close();
        return $id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        return $error;
    }
}

function updateSubsidiary($conn, $subId, $name, $ledgerId, $type, $updatedBy){
    $sql = "UPDATE subsidiaries 
            SET name = ?, 
                ledger_id = ?, 
                type = ?, 
                updated_by = ? 
            WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sisii", $name, $ledgerId, $type, $updatedBy, $subId);
    if($stmt->execute()){
        return true;
    }
    return false;
}

function selectAllSubsidiaries(mysqli $conn){ 
    if($conn === false){
        exit;
    }
   $sql = "SELECT s.*, ledgers.name AS ledger_name FROM subsidiaries s 
           LEFT JOIN ledgers ON s.ledger_id = ledgers.id WHERE s.deleted_at IS NULL ORDER BY s.name";

    if($result = $conn->query($sql)){
        $subsidiaries = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $subsidiaries;

    } else{
        return $conn->error;
    }
}

function selectAllActiveSubsidiaries(mysqli $conn){
    if($conn === false){
        exit;
    }
    $sql = "SELECT * FROM subsidiaries WHERE status ='active' ORDER BY name";
    if($result = $conn->query($sql)){
        $subsidiaries = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $subsidiaries;

    } else{
        return $conn->error;
    }
}
function selectSubsidiariesBYledgerId(mysqli $conn, int $ledger_id){
    if($conn === false){
        exit;
    }
    $sql = "SELECT * FROM subsidiaries WHERE ledger_id='$ledger_id' AND deleted_at is null ORDER BY name";
    if($result = $conn->query($sql)){
        $subs = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $subs;
    } else{
        return $conn->error;
    }
}

        
function updateSubsidiaryStatus(mysqli $conn, int $id, string $status){
    if($conn === false){
        exit;
    }
    $sql = "UPDATE `subsidiaries` SET `status`='$status' WHERE id='$id'";
    if($conn->query($sql) === true){
        return true;

    } else{
        return $conn->error;
    }
}
function selectSubsidiaryById(mysqli $conn, int $id){
     if($conn === false){
        exit;
    }
      $sql = "SELECT * FROM subsidiaries WHERE id='$id' ORDER BY name";
      if($result = $conn->query($sql)){
            $data = $result->fetch_assoc();
            $result->free();
            return $data;
      } else{
        return $conn->error;
      }
}

function selectSubsidiaryByUserId(mysqli $conn, int $userId){
     if($conn === false){
        exit;
    }
      $sql = "SELECT * FROM subsidiaries WHERE user_id='$userId' AND deleted_at is null ORDER BY name";
      if($result = $conn->query($sql)){
            $data = $result->fetch_assoc();
            $result->free();
            return $data;
      } else{
        return $conn->error;
      }
}
function searchSubsidiaryByName(mysqli $conn, string $searchKey =''){
    if ($conn === false) {
        exit;
    }
   $sql = "SELECT * FROM subsidiaries WHERE name LIKE '%$searchKey%' ORDER BY name";
   

    if($result = $conn->query($sql)){
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $data;
    } else{
        return $conn->error;
    }
    
}
function countSubsByType(mysqli $conn, string $type, string $status){
    if ($conn === false) {
        exit;
    }
    $sql = "SELECT COUNT(id) as jumla FROM subsidiaries WHERE type='$type' AND status='$status'";
    if($result = $conn->query($sql)){
        $data = $result->fetch_assoc();
        $result->free();
        return $data;
    }else{
        return $conn->error;
    }
}
function selectSubsidiariesWhereNot(mysqli $conn, string $status){
    if($conn === false){
        exit;
    }
    $sql = "SELECT * FROM subsidiaries WHERE status !='$status'  ORDER BY name";
    if($result = $conn->query($sql)){
        $subsidiaries = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $subsidiaries;

    } else{
        return $conn->error;
    }
}
 
function selectSubsidiaryByType(mysqli $conn, string $type){
    if($conn === false){
        exit();
    }

     $sql = "SELECT s.*, ledgers.name AS ledger_name FROM subsidiaries s 
             LEFT JOIN ledgers ON s.ledger_id = ledgers.id WHERE s.type='$type' AND s.deleted_at IS NULL ORDER BY s.name";
    if($result = $conn->query($sql)){
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $data;
    }else{
        return $conn->error;
    }
}

 function selectDeletedSubsidiaries(mysqli $conn) {
    if($conn === false){
        exit();
    }

     $sql = "SELECT s.*, ledgers.name AS ledger_name FROM subsidiaries s 
             LEFT JOIN ledgers ON s.ledger_id = ledgers.id WHERE     s.deleted_at IS NOT NULL ORDER BY s.name";
    if($result = $conn->query($sql)){
        $data = $result->fetch_all(MYSQLI_ASSOC);
        $result->free();
        return $data;
    }else{
        return $conn->error;
    }
}
        
        
function softDeleteSubsidiary(mysqli $conn, int $subsidiaryId, int $deletedBy){
    if($conn === false){
        exit;
    }
    $sql = "UPDATE subsidiaries SET deleted_at = NOW(), deleted_by = '$deletedBy' WHERE id = '$subsidiaryId' AND deleted_at IS NULL";
    $conn->query($sql);
    if($conn->affected_rows > 0){
        return $subsidiaryId;
    } else{
        return "Subsidiary already deleted or not found";
    }
}

function restoreDeletedSubsidiary(mysqli $conn, int $subsidiaryId, int $restoredBy){
    if($conn === false){
        exit();
    }
    $sql = "UPDATE subsidiaries SET deleted_at = NULL, restored_at = NOW(), restored_by = '$restoredBy' WHERE id = '$subsidiaryId' AND deleted_at IS NOT NULL";
    $conn->query($sql);
    if($conn->affected_rows > 0){
        return $subsidiaryId;
    } else{
        return "Subsidiary not deleted or not found";
    }
}


function selectCOAByMasterId(mysqli $conn, int $masterId){
    if($conn === false){
        exit();
    }
    $sql = "SELECT 
              master.id, 
              master.name AS master, 
              submain.id, 
              submain.name AS submain, 
              ledgers.id, 
              ledgers.name AS ledger, 
              subsidiaries.id, 
              subsidiaries.name AS subs, 
              subsidiaries.ledger_id,
              subsidiaries.id as sub_id
          FROM 
              master
          JOIN 
              submain 
              ON submain.master_id = master.id
          JOIN 
              ledgers 
              ON ledgers.submain_id = submain.id
          JOIN 
              subsidiaries 
              ON subsidiaries.ledger_id = ledgers.id
              WHERE master.id = ? AND subsidiaries.deleted_at IS NULL;";
    $stmt = $conn->prepare($sql);
    if($stmt === false){
        exit();
    }
    $stmt->bind_param("i", $masterId);
    return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->errno;
}
        
?>