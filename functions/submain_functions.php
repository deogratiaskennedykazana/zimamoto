<?php
function createSubmain(mysqli $conn, string $name, int $master_id, int $created_by){
    if($conn === false){
        return false;
    }
    $sql = "INSERT INTO submain (name, master_id, status, created_at, created_by) VALUES (?, ?, 'active', NOW(), ?)";
    $stmt = $conn->prepare($sql);
    if($stmt === false){
        return false;
    }
    $stmt->bind_param("sii", $name, $master_id, $created_by);
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

function updateSubmain(mysqli $conn, int $id, string $name, int $master_id, int $updated_by){
    if($conn === false){
        return false;
    }
    $sql = "UPDATE submain SET name = ?, master_id = ?, updated_at = NOW(), updated_by = ? WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    if($stmt === false){
        return false;
    }
    $stmt->bind_param("siii", $name, $master_id, $updated_by, $id);
    if($stmt->execute()){
        $stmt->close();
        return $id;
    } else {
        $error = $stmt->error;
        $stmt->close();
        return $error;
    }
}

function softDeleteSubmain(mysqli $conn, int $id, int $deleted_by){
    if($conn === false){
        return false;
    }
    $sql = "UPDATE submain SET deleted_at = NOW(), deleted_by = ? WHERE id = ? AND deleted_at IS NULL";
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


 
    function selectAllSubmains(mysqli $conn) {
        if ($conn === false) {
            exit;
        }
           $sql = "SELECT s.*, m.name AS master_name
                    FROM submain s LEFT JOIN master m ON s.master_id = m.id
                    WHERE s.deleted_at IS NULL ORDER BY s.name ASC
                    ";

            if ($result = $conn->query($sql)) {
                $ledgers = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                return $ledgers;
            } else {
                return $conn->error;
            }
         }

function selectSubmainByMasterId(mysqli $conn, int $masterId){
            if($conn === true){
                exit;
            }
            $sql = "SELECT * FROM submain WHERE master_id ='$masterId' and deleted_at is null";
            if($result = $conn->query($sql)){
                $data = $result->fetch_all(MYSQLI_ASSOC);
                $result->free();
                return $data;
            } else{
                return $conn->error;
            }
        }
?>