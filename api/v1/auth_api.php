<?php
$response = [];
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once "../../configs.php";
require_once "../../functions/user_function.php";
require_once "../../functions/member_functions.php";
require_once "../../functions/min_sub_functions.php";
$conn = openConn();

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(isset($_POST['app_token']) && $_POST['app_token']=== "zisa_system_app_token" ){
        
       if(isset($_POST['login_request'])){
            $email = $conn->real_escape_string($_POST['email']);
            $password = trim($_POST['password']);
            $user = selectUserByEmail($conn, $email);
            
            if($user && is_array($user)){
                if(password_verify($password, $user['password'])){
                    if($user['status'] === "approved"){
                        // Get member information using user ID
                        $member = selectMemberByUserId($conn, $user['id']);
                        
                        // Combine user and member data
                        $fullUserData = $user;
                        if($member && is_array($member)){
                            $fullUserData['member'] = $member;
                        } else {
                            $fullUserData['member'] = null;
                        }
                        
                        $response['success'] = true;
                        $response['user'] = $fullUserData;
                        $response['message'] = "Login successful";
                    } else {
                        $response['success'] = false;
                        $response['message'] = "User is inactive please contact your admin";
                    }
                } else {
                    $response['success'] = false;
                    $response['message'] = "Incorrect password";
                }
            } else {
                $response['success'] = false;
                $response['message'] = "User not found";
            }
        }
        
        if(isset($_POST['select_user_by_id'])){
              $userId = (int) $_POST['user_id'];
            $user = selectUserById($conn, $userId);
            
            if($user && is_array($user)){
                
                    if($user['status'] === "approved"){
                        // Get member information using user ID
                        $member = selectMemberByUserId($conn, $userId);
                        
                        // Combine user and member data
                        $fullUserData = $user;
                        if($member && is_array($member)){
                            $fullUserData['member'] = $member;
                        } else {
                            $fullUserData['member'] = null;
                        }
                        
                        $response['success'] = true;
                        $response['message'] = "User Data feched success";
                        $response['user'] = $fullUserData;
                      
                    } else {
                        $response['success'] = false;
                        $response['message'] = "User is inactive please contact your admin";
                    }
                 
            } else {
                $response['success'] = false;
                $response['message'] = "User not found";
            }
        }
        
        
        if(isset($_POST['user_register_request'])){
            $name = $conn->real_escape_string($_POST['name']);
            $phone = $conn->real_escape_string($_POST['phone']);
            $reg_no = $conn->real_escape_string($_POST['reg_no']);
            $branch_id = $conn->real_escape_string($_POST['branch_id']);
            $nida = $conn->real_escape_string($_POST['nida']);
            $email =$conn->real_escape_string($_POST['email']);
            $check_no = $conn->real_escape_string($_POST['check']);
            $address = $conn->real_escape_string($_POST['address']);
            $district_id = $conn->real_escape_string($_POST['ditrictId']);
            $gender = $conn->real_escape_string($_POST['gender']);
            $birthdate = $conn->real_escape_string($_POST['birthdate']);
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
           
            $newUser = registerUser($conn,$name,$email,"member","member",$password,0,$branch_id);
            if($newUser){
                $user_id = $newUser;
                $newMember = registerMember($conn,$user_id,$phone,$address,$reg_no,$birthdate,$district_id,$branch_id,$gender,$nida,$check_no);
                if(!$newMember){
                    $response['success'] = false;
                    $response['message'] = "Member registration failed";
                    echo json_encode($response);
                    return;
                }
                // register sub account associated to this person
                $newAmanaSub = createMinsub($conn,$name . " Amana Account",$user_id,9,$branch_id, 'person', 'amana');
                if(!$newAmanaSub){
                    $response['success'] = false;
                    $response['message'] = "Amana account creation failed";
                    echo json_encode($response);
                    return;
                }
                $newShareSub = createMinsub($conn,$name . " Share Account",$user_id,8,$branch_id, 'person', 'share');
                if(!$newShareSub){
                    $response['success'] = false;
                    $response['message'] = "Share account creation failed";
                    echo json_encode($response);
                    return;
                }
                $newSavingSub = createMinsub($conn,$name . " Saving Account",$user_id,7, $branch_id,'person', 'saving');
                if(!$newSavingSub){
                    $response['success'] = false;
                    $response['message'] = "Saving account creation failed";
                    echo json_encode($response);
                    return;
                }
                
                
                $response['success'] = true;
                $response['message'] = "User registered successfully";
            } else{
                $response['success'] = false;
                $response['message'] = "User registration failed";
            }
        }
        
        
        if(isset($_POST['update_user_profile_details'])){
            // Validate all required fields are present
            if(empty($_POST['user_id']) || empty($_POST['name']) || empty($_POST['phone']) || 
               empty($_POST['email']) || empty($_POST['birthdate']) || empty($_POST['nida']) || 
               empty($_POST['gender'])){
                $response['success'] = false;
                $response['message'] = "All fields are required";
                echo json_encode($response);
                return;
            }
            
            $user_id = $conn->real_escape_string($_POST['user_id']);
            $name = $conn->real_escape_string($_POST['name']);
            $phone = $conn->real_escape_string($_POST['phone']);
            $email = $conn->real_escape_string($_POST['email']);
            $birthdate = $conn->real_escape_string($_POST['birthdate']);
            $nida = $conn->real_escape_string($_POST['nida']);
            $gender = $conn->real_escape_string($_POST['gender']);
            
            $userUpdated = updateUser($conn, $user_id, $name, $email);
            if(!$userUpdated){
                $response['success'] = false;
                $response['message'] = "User update failed";
                echo json_encode($response);
                return;
            }
            
            $memberUpdated = updateMember($conn, $user_id, $phone, $birthdate, $nida, $gender);
            if(!$memberUpdated){
                $response['success'] = false;
                $response['message'] = "Member update failed";
                echo json_encode($response);
                return;
            }
            
            $minSubsUpdated = updateMinSubsAccounts($conn, $user_id, $name);
            if(!$minSubsUpdated){
                $response['success'] = false;
                $response['message'] = "Accounts update failed";
                echo json_encode($response);
                return;
            }
            
            $response['success'] = true;
            $response['message'] = "Profile updated successfully";
        }
        
       if(isset($_POST['reset_password_request'])){
            $userId = (int) $_POST['user_id'];
            $current_password = $_POST['current_password'];
            $new_password = $_POST['new_password'];
            $user = selectUserById($conn, $userId);
            if($user){
                if(password_verify($current_password, $user['password'])){
                    $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                    $updateSuccess = updateUserPassword($conn, $userId, $new_password_hash);
                    if($updateSuccess){
                        $response['success'] = true;
                        $response['message'] = "Password reset successfully";
                    }else{
                        $response['success'] = false;
                        $response['message'] = "Password reset failed";
                    }
                }else{
                    $response['success'] = false;
                    $response['message'] = "Wrong original password provided";
                }
            }else{
                $response['success'] = false;
                $response['message'] = "User not found";
            }
        }
        
        
    } else {
        $response['success'] = false;
        $response['message'] = "Uathorized";
    }
} else {
    $response['success'] = false;
    $response['message'] = "Invalid request method";
}

echo json_encode($response);
?>