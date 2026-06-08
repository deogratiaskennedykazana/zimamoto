<?php
    session_start();
    if(!$_SESSION){
        //echo "<script> window.history.back();</script>";
    }
    require_once "../functions/min_sub_functions.php";
    require_once "../configs.php";
    $conn = openConn();
    if($_SERVER['REQUEST_METHOD'] == "POST"){
        // print_r($_POST);
        if(isset($_POST['registerminsub'])){
            $name = ""; // $conn->real_escape_string($_POST['name']);
            $sub_id = (int) $_POST['subsidiary'];
            $type = $conn->real_escape_string($_POST['type']);
            $category = $conn->real_escape_string($_POST['category']);
            if($category === 'amana'){
                $name = $conn->real_escape_string($_POST['name']) . " Amana Account";

            } elseif($category === 'saving'){
                $name = $conn->real_escape_string($_POST['name']) . " Saving Account";
            } else if($category === 'loan'){
                $name = $conn->real_escape_string($_POST['name']) . " Loan Account";
            } else if($category === 'share'){
                $name = $conn->real_escape_string($_POST['name']) . " Share Account";
            }else{
                $name = $conn->real_escape_string($_POST['name']);
            }
            $newMinSub = createMinsub($conn, $name,null,$sub_id,0,$type,$category);
            if(!$newMinSub){
                echo $newMinSub;
                return;
            }
            echo "<script>alert('SUCCESS'); window.history.back();</script>";
        }
        
    }
?>