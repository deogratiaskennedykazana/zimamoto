<?php
session_start();
if(!$_SESSION){
    echo "<script>window.history.back()</script>";
}
require_once '../configs.php';
require_once "../functions/submain_functions.php";
require_once "../functions/notification_functions.php";
$conn = openConn();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if(isset($_POST['create_submain'])){
        $name = $conn->real_escape_string(trim($_POST['name']));
        $master_id = (int)$_POST['master_id'];
        $created_by = (int)$_SESSION['userid'];
        $result = createSubmain($conn, $name, $master_id, $created_by);
        
        if(is_numeric($result)){
            setNotification('success', 'Submain created successfully');
            echo "<script>window.location.href='../?page=submaster';</script>";
        } else {
            echo "Error: " . $result;
        }
    }
    
    if(isset($_POST['update_submain'])){
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string(trim($_POST['name']));
        $master_id = (int)$_POST['master_id'];
        $updated_by = (int)$_SESSION['userid'];
        $result = updateSubmain($conn, $id, $name, $master_id, $updated_by);
        
        if(is_numeric($result)){
            setNotification('success', 'Submain updated successfully');
            echo "<script>window.location.href='../?page=submaster';</script>";
        } else {
            echo "Error: " . $result;
        }
    }
    
    if(isset($_POST['delete_submain'])){
        $id = (int)$_POST['id'];
        $deleted_by = (int)$_SESSION['userid'];
        $result = softDeleteSubmain($conn, $id, $deleted_by);
        
        if(is_numeric($result)){
            setNotification('success', 'Submain deleted successfully');
            echo "<script>window.location.href='../?page=submaster';</script>";
        } else {
            echo "Error: " . $result;
        }
    }
}
?>