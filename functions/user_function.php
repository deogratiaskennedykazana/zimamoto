<?php
        function registerUser(mysqli $conn, string $name, string $email, string $role, string $type, string $password, string $level, int $branchId){
            if($conn === false){
                exit();
            }
            $sql = "INSERT IGNORE INTO `users`( `name`, `email`, `role`, `type`, `password`, `level`, `branch_id`) VALUES (?,?,?,?,?,?,?);";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("ssssssi", $name,$email,$role, $type,$password,$level, $branchId);
            return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
        }
        
        
        function recordUserLogin(mysqli $conn, array $data) {
    $sql = "INSERT INTO `login_logs` (`user_id`, `email`, `session_id`, `ip_address`, `user_agent`, `status`, `failure_reason`) VALUES (?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        exit();
    }
    $user_id        = $data['user_id'] ?? NULL;
    $failure_reason = $data['failure_reason'] ?? NULL;
    $stmt->bind_param("issssss", $user_id, $data['email'], $data['session_id'], $data['ip_address'], $data['user_agent'], $data['status'], $failure_reason);
    return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
}
        
        

        function selectUserByEmail(mysqli $conn, string $email) : array|string |null{
            if($conn === false){
                exit();
            }
             
$sql = "SELECT users.*, branches.name AS branch_name 
        FROM users 
        LEFT JOIN branches ON users.branch_id = branches.id 
        WHERE users.email = ? AND users.deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("s", $email);
           return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
        }
          function selectUserByName(mysqli $conn, string $name) : array|string |null{
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `users` WHERE `name` LIKE ? AND deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("s", $name);
           return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
        }
        
        function selectUserById(mysqli $conn, int $userId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT users.*, branches.name as branch_name FROM `users`  INNER JOIN branches ON branches.id = users.branch_id WHERE users.id = ? AND users.deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("i", $userId);
            return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
        }
        function selectUsersByBranchId(mysqli $conn, int $branchId){
            if($conn === false){
                exit();
            }
            $sql = "SELECT users.*, branches.name as branch_name FROM `users`  INNER JOIN branches ON branches.id = users.branch_id WHERE users.branch_id = ? AND users.deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("i", $branchId);
            return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
        }
        
        function selectsByBranchIdAndUserRole(mysqli $conn, int $branchId, string $role){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `users` WHERE branch_id = ? AND role = ? AND deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("is", $branchId, $role);
            return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
        }
        
        function updateUserPassword(mysqli $conn, int $userId, string $password){
            if($conn === false){
                exit();
            }
            $sql = "UPDATE `users` SET password = ? WHERE id = ? AND deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("si", $password, $userId);
            return ($stmt->execute()) ? true : false;
        }
        function setUserRole(mysqli $conn, int $userId, string $role){
            if($conn === false){
                exit();
            }
            $sql = "UPDATE `users` SET `role` = ? WHERE `id` = ?;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("si", $role, $userId);
            return ($stmt->execute()) ? true : $stmt->error;
        }
          function softDeleteUser(mysqli $conn, int $userid){
            if($conn === false){
                exit();
            }
            $sql = "UPDATE `users` SET `deleted_at` = NOW() WHERE `id` = ?;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("i", $userid);
            return ($stmt->execute()) ? true : $stmt->error;
        }
           function selectAllUsers(mysqli $conn) {
            $sql = "SELECT users.*, branches.name AS branch_name FROM users LEFT JOIN branches ON users.branch_id = branches.id WHERE users.deleted_at IS NULL ORDER BY users.name";
            $result = $conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        function selectUserByEmailOrPhone(mysqli $conn, string $emailOrPhone){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `users` WHERE `email` = ? OR `phone` = ? AND deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("ss", $emailOrPhone, $emailOrPhone);
            return ($stmt->execute()) ? stmt_fetch_assoc($stmt) : $stmt->error;
        }
        
        
        function updateUser(mysqli $conn, int $userId, string $name, string $email){
            if($conn === false){
                exit;
            }
            $sql = "UPDATE users SET name = ?, email = ?, updated_at = NOW() WHERE id = ?";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("ssi", $name, $email, $userId);
            return ($stmt->execute()) ? true : $stmt->errno;
        }
?>