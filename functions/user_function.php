<?php
        function registerUser(mysqli $conn, string $name, string $email, string $role, string $type, string $password, string $level, int $branchId, string $phone = '', string $status = 'approved'){
            if($conn === false){
                exit();
            }
            $sql = "INSERT IGNORE INTO `users`( `name`, `email`, `role`, `type`, `password`, `level`, `branch_id`, `phone`, `status`) VALUES (?,?,?,?,?,?,?,?,?);";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("ssssssiss", $name,$email,$role, $type,$password,$level, $branchId, $phone, $status);
            return ($stmt->execute()) ? $stmt->insert_id : $stmt->errno;
        }

        function selectUsersByRole(mysqli $conn, string $role){
            if($conn === false){
                exit();
            }
            $sql = "SELECT * FROM `users` WHERE role = ? AND deleted_at IS NULL;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("s", $role);
            return ($stmt->execute()) ? stmt_fetch_all($stmt) : $stmt->error;
        }

        function selectPendingUsers(mysqli $conn){
            if($conn === false){
                exit();
            }
            $sql = "SELECT users.*, branches.name AS branch_name FROM users LEFT JOIN branches ON users.branch_id = branches.id WHERE users.status = 'pending' AND users.deleted_at IS NULL ORDER BY users.created_at DESC;";
            $result = $conn->query($sql);
            return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        }

        function setUserStatus(mysqli $conn, int $userId, string $status){
            if($conn === false){
                exit();
            }
            $sql = "UPDATE `users` SET `status` = ? WHERE `id` = ?;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("si", $status, $userId);
            return ($stmt->execute()) ? true : $stmt->error;
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
            // Join through members.branch_id (the authoritative branch column).
            // users.branch_id is unreliable — it was never updated by update_member.
            // LEFT JOIN ensures users without a members row are excluded (INNER would also work,
            // but LEFT + WHERE members.branch_id gives a clearer intent).
            $sql = "SELECT DISTINCT users.id, users.name, users.email, users.role,
                           users.type, users.level, users.status,
                           branches.name as branch_name
                    FROM users
                    INNER JOIN members ON members.user_id = users.id
                                      AND members.deleted_at IS NULL
                                      AND members.branch_id = ?
                    INNER JOIN branches ON branches.id = ?
                    WHERE users.deleted_at IS NULL
                      AND users.status = 'approved'
                    ORDER BY users.name ASC;";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit();
            }
            $stmt->bind_param("ii", $branchId, $branchId);
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
            // FIX: AND has higher precedence than OR — wrap conditions in parentheses
            // Old (buggy):  WHERE email=? OR phone=? AND deleted_at IS NULL
            // Parsed as:    WHERE email=? OR (phone=? AND deleted_at IS NULL)
            // i.e. deleted_at was not checked when matching by email
            $sql = "SELECT * FROM `users` WHERE (`email` = ? OR `phone` = ?) AND deleted_at IS NULL;";
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