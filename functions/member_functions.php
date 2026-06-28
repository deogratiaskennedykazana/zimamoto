<?php
        /**
         * Handle an optional member document upload (photo/passport or ID card).
         * Returns the stored filename (to persist in DB) on success, null if no
         * file was supplied (since these uploads are optional), or false on
         * upload failure.
         *
         * @param array  $file       The relevant $_FILES['...'] entry
         * @param string $subfolder  Destination folder under /uploads (e.g. 'member_photos')
         * @param string $prefix     Filename prefix for readability
         */
        function handleOptionalMemberUpload(array $file, string $subfolder, string $prefix){
                // No file chosen — this is fine, the field is optional
                if(!isset($file['error']) || $file['error'] === UPLOAD_ERR_NO_FILE){
                        return null;
                }
                if($file['error'] !== UPLOAD_ERR_OK){
                        return false;
                }

                $allowedExt = ['jpg', 'jpeg', 'png', 'pdf'];
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if(!in_array($extension, $allowedExt, true)){
                        return false;
                }

                $uploadDir = __DIR__ . '/../uploads/' . $subfolder . '/';
                if(!file_exists($uploadDir)){
                        mkdir($uploadDir, 0755, true);
                }

                $fileName = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
                $destination = $uploadDir . $fileName;

                if(move_uploaded_file($file['tmp_name'], $destination)){
                        return $fileName;
                }
                return false;
        }

        function registerMember(mysqli $conn, int $userId, string $phone, string $address,  string $regNo, string $birthdate,  int $wilayaId, int $branchId, string $gender, string $nida, string $checkNo, ?string $photoFile = null, ?string $idCardFile = null ){
                if($conn === false){
                        exit();
                }
                $sql = " INSERT INTO `members`(`user_id`, `phone`, `address`, `reg_no`, `birthdate`, `district_id`, `branch_id`, `gender`, `nida`, `check_no`, `photo_file`, `id_card_file`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?) ;";
                $stmt = $conn->prepare($sql);
                if($stmt === false){
                        exit();
                }
                $stmt->bind_param("issssiisssss",$userId,$phone,$address,$regNo,$birthdate,$wilayaId,$branchId,$gender,$nida,$checkNo,$photoFile,$idCardFile);
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
            // FIX: bind_param order was swapped — $userId and $status were in wrong positions.
            // SQL: phone, birthdate, nida, gender, status WHERE user_id
            // Types: s s s s s i  (five strings then one int)
            $sql = "UPDATE members SET phone = ?, birthdate = ?, nida = ?, gender = ?, status=?, updated_at = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            if($stmt === false){
                exit;
            }
            $stmt->bind_param("sssssi", $phone, $birthdate, $nida, $gender, $status, $userId);
            return ($stmt->execute()) ? true : $stmt->errno;
        }


?>