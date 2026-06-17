<?php
    function selectRegions(mysqli $conn){
        if($conn === false){
            exit();
        }
        $sql = "SELECT * FROM mikoa ORDER BY name;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error; 

       }

    function selectDistricts(mysqli $conn){
        if($conn === false){
            exit();
        }
        $sql = "SELECT * FROM wilaya ORDER BY name;";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error; 

       }

    function selectDistrictByName(mysqli $conn, string $name){
        if($conn === false){
            exit();
        }
        $sql = "SELECT * FROM wilaya WHERE name LIKE '$name%';";
        return ($result = $conn->query($sql)) ? $result->fetch_assoc() : " ";
    }

    function selectCurrencyById(mysqli $conn, int $id){
        if($conn === false){
            exit();
        }
        $sql = "SELECT * FROM currencies WHERE id = '$id'";
        return ($result = $conn->query($sql)) ? $result->fetch_assoc() : " ";
    }
    
    
     function selectAllUnit(mysqli $conn){
        if($conn === false){
            exit();
        }
        $sql = "SELECT  units.*, users.name as creator FROM units JOIN users ON units.created_by = users.id WHERE units.deleted_at IS NULL; ";
        return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->errno;
     }
?>