<?php


        function registerBranch(mysqli $conn, string $name,string $phone, string $email, string $address, int $region){
            if($conn === false){
                exit();
            }
            $sql = "INSERT INTO `branches`( `name`, `phone`, `email`, `address`, `region`) VALUES (?,?,?,?,?);";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("ssssi", $name, $phone, $email, $address, $region);
            return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
           
            
        } 
        
        function selectAllBranches(mysqli $conn, $branch_id = null) {
            if ($conn === false) {
                exit();
            }
        
            // Base SQL
            $sql = "SELECT branches.*, mikoa.name as mkoa 
                    FROM branches 
                    JOIN mikoa ON mikoa.id = branches.region 
                    WHERE branches.deleted_at IS NULL";
        
            // Add condition if $branch_id is provided
            if (!is_null($branch_id)) {
                $branch_id = $conn->real_escape_string($branch_id); // sanitize input
                $sql .= " AND branches.id = '$branch_id'";
            }
        
            // Execute query
            $result = $conn->query($sql);
            return ($result) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }

        function SelectBranchById(mysqli $conn, int $id){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM branches WHERE id = '$id' AND deleted_at IS NULL;";
            return ($result = $conn->query($sql)) ? $result->fetch_assoc() : $conn->error;
        }
             function countBranchMember(mysqli $conn, int $branchId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT count(id) as member FROM members WHERE branch_id = '$branchId' AND deleted_at IS NULL;";
            return ($result = $conn->query($sql)) ? $result->fetch_assoc()   : $conn->error;
        }

        function selectBranchStaff(mysqli $conn, $branchId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM users WHERE role !='member' AND branch_id = '$branchId' AND deleted_at IS NULL;";
            return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }
?>