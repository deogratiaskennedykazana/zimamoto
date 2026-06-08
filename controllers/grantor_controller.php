<?php
    session_start();
    if(!$_SESSION){
        echo "<script> window.location.href='./login.php';</script>";
        exit;
    }
    require_once "../functions/grantor_functions.php";
    require_once "../functions/user_function.php";
    require_once "../functions/notification_functions.php";
    require_once "../configs.php";
    $conn = openConn();

    if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['respond_grantor'])){
        $token = $conn->real_escape_string($_POST['token']);
        $status = $conn->real_escape_string($_POST['status']);
        $comment = $conn->real_escape_string($_POST['comment'] ?? '');

        $result = respondToGrantorRequest($conn, $token, $status, $comment);
        if($result === true){
            setNotification('success', 'Your response has been recorded.');
        } else {
            setNotification('error', $result);
        }
        echo "<script>window.location.href='../?page=my_grantor_requests';</script>";
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['respond'])){
        $token = $conn->real_escape_string($_GET['respond']);
        $status = $conn->real_escape_string($_GET['status']);
        $comment = $conn->real_escape_string($_GET['comment'] ?? '');
        $result = respondToGrantorRequest($conn, $token, $status, $comment);
        if($result === true){
            echo "<script>alert('Response recorded successfully.'); window.location.href='./';</script>";
        } else {
            echo "<script>alert('$result'); window.location.href='./';</script>";
        }
    }
    $conn->close();
?>
