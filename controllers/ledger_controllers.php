<?php
session_start();
if(!$_SESSION){
    echo "<script>window.history.back()</script>";
}
require_once '../configs.php';
require_once "../functions/ledger_functions.php";
require_once "../functions/notification_functions.php";

$conn = openConn();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    
    if(isset($_POST['create_ledger'])){
        $name = $conn->real_escape_string(trim($_POST['name']));
        $submain_id = (int)$_POST['submain_id'];
        $created_by = (int)$_SESSION['userid'];
        $result = createLedger($conn, $name, $submain_id, $created_by);
        
        if(is_numeric($result)){
            setNotification('success', 'Ledger created successfully');
            echo "<script>window.location.href='../?page=ledger';</script>";
        } else {
            echo "<script>alert('Error: ".$result."');window.history.back();</script>";
        }
    }
    
    if(isset($_POST['update_ledger'])){
        $id = (int)$_POST['id'];
        $name = $conn->real_escape_string(trim($_POST['name']));
        $submain_id = (int)$_POST['submain_id'];
        $updated_by = (int)$_SESSION['userid'];
        $result = updateLedger($conn, $id, $name, $submain_id, $updated_by);
        
        if(is_numeric($result)){
            setNotification('success', 'Ledger updated successfully');
            echo "<script>window.location.href='../?page=ledger';</script>";
        } else {
            echo "<script>alert('Error: ".$result."');window.history.back();</script>";
        }
    }
    
    if(isset($_POST['delete_ledger'])){
        $id = (int)$_POST['id'];
        $deleted_by = (int)$_SESSION['userid'];
        $result = softDeleteLedger($conn, $id, $deleted_by);
        
        if(is_numeric($result)){
            setNotification('success', 'Ledger deleted successfully');
            echo "<script>window.location.href='../?page=ledger';</script>";
        } else {
            echo "<script>alert('Error: ".$result."');window.history.back();</script>";
        }
    }
}
?>