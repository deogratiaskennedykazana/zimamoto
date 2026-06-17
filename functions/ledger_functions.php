<?php

function createLedger(mysqli $conn, string $name, int $submain_id, int $created_by){
    if($conn === false){
        return false;
    }
    $sql = "INSERT INTO ledgers (name, submain_id, status, created_at, created_by) VALUES (?, ?, 'active', NOW(), ?)";
    $stmt = $conn->prepare($sql);
    if($stmt === false){
        return false;
    }
    $stmt->bind_param("sii", $name, $submain_id, $created_by);
    if($stmt->execute()){
        $insert_id = $stmt->insert_id;
        $stmt->close();
        return $insert_id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        return $error;
    }
}

function updateLedger(mysqli $conn, int $id, string $name, int $submain_id, int $updated_by){
    if($conn === false){
        return false;
    }
    $sql = "UPDATE ledgers SET name = ?, submain_id = ?, updated_at = NOW(), updated_by = ? WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    if($stmt === false){
        return false;
    }
    $stmt->bind_param("siii", $name, $submain_id, $updated_by, $id);
    if($stmt->execute()){
        $stmt->close();
        return $id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        return $error;
    }
}

function softDeleteLedger(mysqli $conn, int $id, int $deleted_by){
    if($conn === false){
        return false;
    }
    $sql = "UPDATE ledgers SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    if($stmt === false){
        return false;
    }
    $stmt->bind_param("ii", $deleted_by, $id);
    if($stmt->execute()){
        $stmt->close();
        return $id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        return $error;
    }
}
    
     
    function selectAllActiveLedgers(mysqli $conn) {
        if ($conn === false) {
            exit;
        }

           $sql = "SELECT l.*, s.name AS submain_name
                    FROM ledgers l LEFT JOIN submain s ON l.submain_id = s.id
                    WHERE l.status='active' AND l.deleted_at IS NULL ORDER BY l.name
                    ";
        
            if ($result = $conn->query($sql)) {
                $ledgers = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                return $ledgers;
            } else {
                return $conn->error;
            }
         }
    
    

    
    function selectLedgerById(mysqli $conn, int $ledgerId){
        if($conn === true){
            exit;
        }
        $sql = "SELECT * FROM ledgers WHERE id ='$ledgerId' and deleted_at is null";
        if($result = $conn->query($sql)){
            $data = $result->fetch_assoc();
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
    function selectLedgerBySubMainId(mysqli $conn, int $submainId){
        if($conn === true){
            exit;
        }
        $sql = "SELECT * FROM ledgers WHERE submain_id ='$submainId' and ledgers.deleted_at is null";
        if($result = $conn->query($sql)){
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
?>