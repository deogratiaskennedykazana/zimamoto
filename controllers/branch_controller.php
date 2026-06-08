<?php
        session_start();
        if(!$_SESSION){
            // echo "<script>window.history.back();</script>";
        }
        require_once "../configs.php";
        require_once  "../functions/branch_functions.php";
         require_once "../functions/user_function.php";
        $conn = openConn();
        if($_SERVER['REQUEST_METHOD'] === 'POST'){
            if(isset($_POST['reg_branch'])){
                print_r($_POST);
                 $name = $conn->real_escape_string($_POST['name']);
                 $address = $conn->real_escape_string($_POST['address']);
                 $phone = $conn->real_escape_string($_POST['phone']);
                 $email = $conn->real_escape_string($_POST['email']);
                $region = $conn->real_escape_string($_POST['region']);
                $newBranch = registerBranch($conn, $name, $phone, $email, $address, $region);
                if(!$newBranch){
                    echo $newBranch;
                    return;
                }
                echo "<script>alert('SUCCESS'); window.history.back();</script>";
            }
              if(isset($_POST['addstaff'])){
                print_r($_POST);
                $userId = (int) $_POST['user_id'];
                $role = trim( $conn->real_escape_string($_POST['role']));
                $newROle = setUserRole($conn, $userId, $role);
                if(!$newROle){
                    echo $newROle;
                    return;
                }
                echo "<script>alert('SUCCESS'); window.history.back();</script>";
            }
        }
?>