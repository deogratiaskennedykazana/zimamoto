<?php
@ini_set('max_input_vars', 50000);
@ini_set('post_max_size', '500M');
@ini_set('memory_limit', '512M');

    session_start();
    if(!$_SESSION){
      //  echo "<script>window.history.back();</script>";
    }
    require_once "../functions/user_function.php";
    require_once "../functions/member_functions.php";
    require_once "../functions/min_sub_functions.php";
    require_once "../functions/min_transaction_functions.php";
    require_once "../configs.php";
    $conn = openConn();
    if($_SERVER['REQUEST_METHOD'] === 'POST'){
      
        
        
      // print_r($_POST);
        if(isset($_POST['uploadmemberdetails'])){
         
          $names =$_POST['name'];
          $phones = $_POST['phone'];
          $reg_nos = $_POST['regno'];
          // $branch_id = $_POST['branch_id'];
          $nidas = $_POST['nida'];
          $emails =$_POST['email'];
          $check_nos = $_POST['checkno'];
          $addresses =$_POST['address'];
          $district_ids =$_POST['districtId'];
          $genders = $_POST['gender'];
          $birthdates = $_POST['birthdate'];
          $branch_id = $conn->real_escape_string($_POST['branch_id']);
          $password = password_hash("12345", PASSWORD_DEFAULT);
          for($i =0; $i<count($names); $i++){
            $name = htmlspecialchars($names[$i]);
            $phone = $conn->real_escape_string($phones[$i]);
            $reg_no = $conn->real_escape_string($reg_nos[$i]);
           
            $nida = $conn->real_escape_string($nidas[$i]);
            $email =$conn->real_escape_string($emails[$i]);
            $check_no = $conn->real_escape_string($check_nos[$i]);
            $address = $conn->real_escape_string($addresses[$i]);
            $district_id = $conn->real_escape_string($district_ids[$i]);
            $gender = $conn->real_escape_string($genders[$i]);
            $birthdate = $conn->real_escape_string($birthdates[$i]);
          $newUser = registerUser($conn,$name,$email,"member","member",$password,'branch',$branch_id);
          if($newUser){
            $user_id = $newUser;
            $newMember = registerMember($conn,$user_id,$phone,$address,$reg_no,$birthdate,$district_id,$branch_id,$gender,$nida,$check_no);
            if(!$newMember){
              echo $newMember;
              return;
            }
            // register sub account assciated to this person
            $newAmanaSub = createMinsub($conn,$name . " Amana Acount",$user_id,9,$branch_id, 'person', 'amana');
            if(!$newAmanaSub){
              echo $newAmanaSub;
              return;
            }
            $newShareSub = createMinsub($conn,$name . " Share Acount",$user_id,8,$branch_id, 'person', 'share');
            if(!$newShareSub){
              echo $newShareSub;
              return;
            }
            $newSavingSub = createMinsub($conn,$name . " Saving Acount",$user_id,7, $branch_id,'person', 'saving');
            if(!$newSavingSub){
              echo $newSavingSub;
              return;
            }
            $newLoanSub = createMinsub($conn,$name . " Loan Acount",$user_id,59, $branch_id,'person', 'loan');
            if(!$newLoanSub){
              echo $newLoanSub;
              return;
            }
          }
        }
          echo "<script>alert('SUCCESS'); window.location.href='../?page=all_member_list';</script>";
        }
        if(isset($_POST['register_member'])){
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
          $newUser = registerUser($conn,$name,$email,"member","member",$password,'branch',$branch_id);
          if($newUser){
            $user_id = $newUser;
            $newMember = registerMember($conn,$user_id,$phone,$address,$reg_no,$birthdate,$district_id,$branch_id,$gender,$nida,$check_no);
            if(!$newMember){
              echo $newMember;
              return;
            }
            // register sub account assciated to this person
            $newAmanaSub = createMinsub($conn,$name . " Amana Acount",$user_id,9,$branch_id, 'person', 'amana');
            if(!$newAmanaSub){
              echo $newAmanaSub;
              return;
            }
            $newShareSub = createMinsub($conn,$name . " Share Acount",$user_id,8,$branch_id, 'person', 'share');
            if(!$newShareSub){  
              echo $newShareSub;
              return;
            }
            $newSavingSub = createMinsub($conn,$name . " Saving Acount",$user_id,7, $branch_id,'person', 'saving');
            if(!$newSavingSub){
              echo $newSavingSub;
              return;
            }
            $newLoanSub = createMinsub($conn,$name . " Loan Account",$user_id,59, $branch_id,'person', 'loan');
            if(!$newLoanSub){
              echo $newLoanSub;
              return;
            }
          }
          echo "<script>alert('SUCCESS'); window.history.back();</script>";
        }
        if(isset($_POST['upload_contribution'])){
          print_r($_POST);
          $date = $conn->real_escape_string($_POST['date']);
          $branch_id = $conn->real_escape_string($_POST['branch_id']);
          $type = $conn->real_escape_string($_POST['type']);
         $subs = $_POST['sub_id'];
         $amounts = $_POST['amount'];
         $ref = "JVUP/" . $date . "/" . $branch_id;
         $dr_account = (int) $_POST['dr_account'];
         for($i=0; $i<count($subs); $i++){
           $sub_id = $conn->real_escape_string($subs[$i]);
           $amount = $conn->real_escape_string($amounts[$i]);
           $newTransaction = createMinTransaction($conn,$ref,$dr_account,"contribution Upload",$amount,$sub_id,$date, (int) $_SESSION['userid'],$branch_id,"active");
           if(!$newTransaction){
             echo $newTransaction;
             return;
           }
         }
         echo "<script>alert('SUCCESS'); window.location.href='../?page=upload_contribution';</script>";
        } 
        
        
          
      if(isset($_POST['upload_general_memeber_contribution'])){ 
        $date = $conn->real_escape_string($_POST['date']);
        $subs = $_POST['sub_id'];
        $amounts = $_POST['amount'];
        $member_branch_ids = $_POST['member_branch_id'];
        $dr_account = (int) $_POST['dr_account'];
        $count = min(count($subs), count($amounts), count($member_branch_ids));
        $conn->begin_transaction();
        try {
            for($i = 0; $i < $count; $i++){
                
                if(!isset($subs[$i]) || !isset($amounts[$i]) || !isset($member_branch_ids[$i])){
                    continue;
                }
                
                $sub_id = $conn->real_escape_string($subs[$i]);
                $amount = (float) $amounts[$i];
                $member_branch_id = $conn->real_escape_string($member_branch_ids[$i]);
                
                $ref = "JVUP/" . $date . "/" . $member_branch_id . "/" . ($i + 1);
                
                createMinTransaction(
                    $conn,
                    $ref,
                    $dr_account,
                    "contribution Upload",
                    $amount,
                    $sub_id,
                    $date, 
                    (int) $_SESSION['userid'],
                    $member_branch_id,
                    "active"
                );
            }
            
            $conn->commit();
            echo "<script>alert('SUCCESS: $count records inserted'); window.location.href='../?page=upload_contributions';</script>";
            
        } catch (Exception $e) {
            $conn->rollback();
            echo "<script>alert('ERROR: " . $e->getMessage() . "');</script>";
        }
    }
        
        // Handle form submission
        if(isset($_POST['update_member'])) {
            // Get form data
            print_r($_POST);
            $name = $conn->real_escape_string($_POST['name']);
            $phone =$conn->real_escape_string( $_POST['phone']);
            $address = $conn->real_escape_string( $_POST['address']);
            $reg_no = $conn->real_escape_string($_POST['reg_no']);
            $birthdate = $conn->real_escape_string($_POST['birthdate']);
            $branch_id = $_POST['branch_id'];
            $gender = $_POST['gender'];
            $nida = $_POST['nida'];
            $check_no = $_POST['check_no'];
            $status = $_POST['status'];
            $member_id = $_POST['member_id'];
            $user_id = $_POST['user_id'];
            $email = $_POST['email'];
            
            // Update members table
            $sql1 = "UPDATE members SET 
                     phone = '$phone', 
                     address = '$address', 
                     reg_no = '$reg_no', 
                     birthdate = '$birthdate', 
                       
                     gender = '$gender', 
                     nida = '$nida', 
                     check_no = '$check_no'
                   
                     WHERE id = $member_id";
            
            $conn->query($sql1);
            
            // Update users table
            $sql2 = "UPDATE users SET 
                     name = '$name', 
                     email = '$email', 
                       status = '$status'
                     WHERE id = $user_id";
            
            $conn->query($sql2);
            
            echo "<script>alert('SUCCESS'); window.location.href='../?page=edit_member&member_id=$member_id&branch_id=$branch_id'</script>"; 
        } 
        
        if(isset($_POST['change_branch'])) {
            $member_id = $_POST['member_id'];
            $new_branch_id = $_POST['new_branch_id'];
            
             
            $sql = "UPDATE members SET branch_id = '$new_branch_id' WHERE id = $member_id";
            $conn->query($sql);
            
            echo "<script>alert('SUCCESS'); window.location.href='../?page=change_branch_member&member_id=$member_id&branch_id= $new_branch_id'</script>"; 
        }
        
       
        
        
        
    }
      if($_SERVER['REQUEST_METHOD'] ==='GET'){
      if(isset($_GET['delete_member'])){
        print_r($_GET);
        $memberDeatails = selectMemberById($conn, (int) $_GET['member_id']);
        if($memberDeatails && is_array($memberDeatails)){
          $userId = $memberDeatails['user_id'];
          // for subs
          $minSubs = selectMinSubsByUserId($conn, $userId);
          if($minSubs && is_array($minSubs)){
            foreach($minSubs as $minSub){
              $minSubId = $minSub['id'];
              $deleteMinSUb = softDeleteMinSub($conn, $minSubId);
              if(!$deleteMinSUb){
                echo $deleteMinSUb;
                return;
              }
              $deleteTransaction = softDeleteMinTransactionByAccountId($conn, $minSubId);
              if(!$deleteTransaction){
                echo $deleteTransaction;
                return;
              }
            }
          }
          // delete user and then member
          $deleteUser = softDeleteUser($conn, $userId);
          if(!$deleteUser){
            echo $deleteUser;
            return;
          }
          $deleteMember = softDeleteMember($conn, (int) $_GET['member_id']);
          if(!$deleteMember){
            echo $deleteMember;
            return;
          }
         

        }
      }
       echo "<script>alert('SUCCESS'); window.location.href='../?page=update_member_list_per_branch'</script>";
    }
?>