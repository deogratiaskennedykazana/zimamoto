<?php
    function selectMasterByMasterName(mysqli $conn, string $name){
        if($conn === false){
            exit();

        }

        $sql ="SELECT * FROM master WHERE name LIKE '$name%'";
        if($result = $conn->query($sql)){
            $data = $result->fetch_assoc();
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
    
    function selectAllMasters(mysqli $conn){
        if($conn === false){
            exit();

        }

         $sql ="SELECT * FROM master WHERE 1";
        if($result = $conn->query($sql)){
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }


    function selectAllSubmasterByMasterId(mysqli $conn, $masterId){
        if($conn === false){
            exit();

        }

        $sql ="SELECT * FROM submain WHERE master_id = '$masterId'and deleted_at is null";
        if($result = $conn->query($sql)){
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
    function getSubmaster(mysqli $conn){
        $sql = "SELECT * from submain ORDER BY name;";
        if($result = $conn->query($sql)){
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
    function getSubmasterChart(mysqli $conn){
        $sql = "SELECT submain.*, master.name as main from submain JOIN master ON submain.master_id = master.id ORDER BY name;";
        if($result = $conn->query($sql)){
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
    function getMaster(mysqli $conn){
        $sql = "SELECT * FROM master ORDER BY name;";
        if($result = $conn->query($sql)){
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $result->free();
            return $data;
        } else{
            return $conn->error;
        }
    }
    function updatesubMasterMainId(mysqli $conn,  int $submainId, int $masterId){
        $sql = "UPDATE `submain` SET `master_id`=? WHERE id=?;";
        $stmt = $conn->prepare($sql);
        if($stmt === false){
            return $stmt->error;
        }
        $stmt->bind_param('ii',$masterId,  $submainId);
        if($stmt->execute()){
            return true;
            $stmt->close();
        }else{
            return $stmt->error;
            $stmt->close();
        }
    }
?>