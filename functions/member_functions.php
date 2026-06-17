<?php
        function registerMember(mysqli $conn, int $userId, string $phone, string $address,  string $regNo, string $birthdate,  int $wilayaId, int $branchId, string $gender, string $nida, string $checkNo ){
                if($conn === false){
                        exit();
                }
                $sql = " INSERT INTO `members`(`user_id`, `phone`, `address`, `reg_no`, `birthdate`, `district_id`, `branch_id`, `gender`, `nida`, `check_no`) VALUES (?,?,?,?,?,?,?,?,?,?) ;";
                $stmt = $conn->prepare($sql);
                if($stmt === false){
                        exit();
                }
                $stmt->bind_param("issssiisss",$userId,$phone,$address,$regNo,$birthdate,$wilayaId,$branchId,$gender,$nida,$checkNo);
                return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
        }
        function getAllMembers(mysqli $conn){
                if($conn === false){
                        exit();
                }
                $sql = "SELECT members.*, branches.name as branch,  users.name, users.email FROM members 
                        JOIN branches ON branches.id = members.branch_id
                        JOIN users ON users.id = members.user_id
                         WHERE members.deleted_at IS NULL AND users.deleted_at IS NULL ORDER BY name;";
                return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }
        function getAllMembersByBranch(mysqli $conn, int $branchId) {
                if($conn === false){
                        exit();
                }
                $sql = "SELECT members.*, branches.name as branch,  users.name, users.email FROM members 
                        JOIN branches ON branches.id = members.branch_id
                        JOIN users ON users.id = members.user_id
                         WHERE members.branch_id = $branchId AND members.deleted_at IS NULL AND users.deleted_at IS NULL ORDER BY name;";
                return ($result = $conn->query($sql)) ? $result->fetch_all(MYSQLI_ASSOC) : $conn->error;
        }
         function selectMemberByUserId(mysqli $conn, int $id){
                if($conn === false){
                        exit();
                }
                $sql = "SELECT members.*, branches.name as branch,  users.name, users.email FROM members 
                        JOIN branches ON branches.id = members.branch_id
                        JOIN users ON users.id = members.user_id
                         WHERE members.user_id = $id AND members.deleted_at IS NULL AND users.deleted_at IS NULL ORDER BY name;";
                return ($result = $conn->query($sql)) ? $result->fetch_assoc() : $conn->error;
        }
        function selectMemberById(mysqli $conn, int $id){
                if($conn === false){
                        exit();
                }
                $sql = "SELECT members.*, branches.name as branch,  users.name, users.email, users.status FROM members 
                        JOIN branches ON branches.id = members.branch_id
                        JOIN users ON users.id = members.user_id
                         WHERE members.id = $id AND members.deleted_at IS NULL AND users.deleted_at IS NULL ORDER BY name;";
                return ($result = $conn->query($sql)) ? $result->fetch_assoc() : $conn->error;
        }
           function softDeleteMember(mysqli $conn, int $memberId){
                if($conn === false){
                        exit();
                }
                $sql = "UPDATE `members` SET `deleted_at` = NOW() WHERE `id` = ?;";
                $stmt = $conn->prepare($sql);
                if($stmt === false){
                        exit();
                }
                $stmt->bind_param("i", $memberId);
                return ($stmt->execute()) ? true : $stmt->error;
        } 
        
        function updateMember(mysqli $conn, int $userId, string $phone, string $birthdate, string $nida, string $gender, string $status){
            if($conn === false){
                exit;
            }
            $sql = "UPDATE members SET phone = ?, birthdate = ?, nida = ?, gender = ?, status=?, updated_at = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("ssssis", $phone, $birthdate, $nida, $gender, $userId, $status);
            return ($stmt->execute()) ? true : $stmt->errno;
        }


?>