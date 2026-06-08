<?php
session_start();
if(!isset($_SESSION['userid'])){
    echo "<script>alert('Unauthorized access'); window.history.back();</script>"; 
    exit();
}
require_once "../configs.php";
require_once "../functions/subsidiary_functions.php";
require_once "../functions/notification_functions.php";
$conn = openConn();

if($_SERVER['REQUEST_METHOD'] === 'POST'){

    if(isset($_POST['edit_subsidiary'])){
        $updated_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string($_POST['name']);
        $ledgerId = (int) $_POST['ledger_id'];
        $subId = (int) $_POST['sub_id'];
        $type = $conn->real_escape_string($_POST['type']);
        $update = updateSubsidiary($conn, $subId, $name, $ledgerId, $type, $updated_by);
        if($update){
            setNotification('success', 'Subsidiary updated successfully');
            echo "<script>window.location.href='../?page=edit_subsidiary&id=$subId';</script>";
        } else {
            echo "Error: Failed to update subsidiary";
        }
    }

    if(isset($_POST['registerCustomer'])){
        $created_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string(trim($_POST['name']));
        $phone = $conn->real_escape_string(trim($_POST['phone']));
        $email = $conn->real_escape_string(trim($_POST['email']));
        $address = $conn->real_escape_string(trim($_POST['address']));
        $vrn = $conn->real_escape_string(trim($_POST['vrn']));
        $subcategory = $conn->real_escape_string(trim($_POST['subcategory']));
        $tin = $conn->real_escape_string(trim($_POST['tin']));
        $ledger = (int) $_POST['ledger'];
        $newCustomer = createSubsidiary($conn, $name, $ledger, $created_by, 'customer', $subcategory, $phone, $email, $tin, $vrn, $address, null);
        if(is_numeric($newCustomer) && $newCustomer > 0){
            setNotification('success', 'Customer registered successfully');
            echo "<script>window.location.href='../?page=register_customer';</script>";
        } else {
            echo "Error: " . $newCustomer;
        }
    }

    if(isset($_POST['registerSupplier'])){
        $created_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string(trim($_POST['name']));
        $phone = $conn->real_escape_string(trim($_POST['phone']));
        $email = $conn->real_escape_string(trim($_POST['email']));
        $address = $conn->real_escape_string(trim($_POST['address']));
        $vrn = $conn->real_escape_string(trim($_POST['vrn']));
        $subcategory = $conn->real_escape_string(trim($_POST['subcategory']));
        $tin = $conn->real_escape_string(trim($_POST['tin']));
        $ledger = (int) $_POST['ledger'];
        $newSupplier = createSubsidiary($conn, $name, $ledger, $created_by, 'supplier', $subcategory, $phone, $email, $tin, $vrn, $address, null);
        if(is_numeric($newSupplier) && $newSupplier > 0){
            setNotification('success', 'Supplier registered successfully');
            echo "<script>window.location.href='../?page=register_supplier';</script>";
        } else {
            echo "Error: " . $newSupplier;
        }
    }

    if(isset($_POST['addothers'])){
        $created_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string(trim($_POST['name']));
        $ledger = (int) $_POST['ledger'];
        $newOther = createSubsidiary($conn, $name, $ledger, $created_by, 'others', 'others', null, null, null, null, null, null);
        if(is_numeric($newOther) && $newOther > 0){
            setNotification('success', 'Other subsidiary added successfully');
            echo "<script>window.location.href='../?page=add_other_subsidiary';</script>";
        } else {
            echo "Error: " . $newOther;
        }
    }

    if(isset($_POST['addstock'])){
        $created_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string(trim($_POST['item']));
        $ledger = (int) $_POST['ledger'];
        $newStock = createSubsidiary($conn, $name, $ledger, $created_by, 'stock', 'others', null, null, null, null, null, null);
        if(is_numeric($newStock) && $newStock > 0){
            setNotification('success', 'Stock added successfully');
            echo "<script>window.location.href='../?page=register_stock';</script>";
        } else {
            echo "Error: " . $newStock;
        }
    }

    if(isset($_POST['addasset'])){
        $created_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string(trim($_POST['item']));
        $ledger = (int) $_POST['ledger'];
        $newAsset = createSubsidiary($conn, $name, $ledger, $created_by, 'asset', 'others', null, null, null, null, null, null);
        if(is_numeric($newAsset) && $newAsset > 0){
            setNotification('success', 'Asset added successfully');
            echo "<script>window.location.href='../?page=register_asset';</script>";
        } else {
            echo "Error: " . $newAsset;
        }
    }

    if(isset($_POST['registerStaff'])){
        $created_by = (int) $_SESSION['userid'];
        $name = $conn->real_escape_string(trim($_POST['name']));
        $phone = $conn->real_escape_string(trim($_POST['phone']));
        $email = $conn->real_escape_string(trim($_POST['email']));
        $address = $conn->real_escape_string(trim($_POST['address']));
        $subcategory = $conn->real_escape_string(trim($_POST['subcategory']));
        $tin = $conn->real_escape_string(trim($_POST['tin']));
        $ledger = (int) $_POST['ledger'];
        $newstaff = createSubsidiary($conn, $name, $ledger, $created_by, 'staff', $subcategory, $phone, $email, $tin, null, $address, null);
        if(is_numeric($newstaff) && $newstaff > 0){
            setNotification('success', 'Staff registered successfully');
            echo "<script>window.location.href='../?page=register_staff';</script>";
        } else {
            echo "Error: " . $newstaff;
        }
    }
}
?>