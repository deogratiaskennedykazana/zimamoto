<?php
    session_start();
    if(!$_SESSION){
        echo "<script> window.history.back();</script>";
        exit;
    }
    require_once "../functions/budget_functions.php";
    require_once "../functions/notification_functions.php";
    require_once "../configs.php";
    $conn = openConn();

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['addbudget'])){
            $year = $conn->real_escape_string($_POST['year']);
            $description = $conn->real_escape_string($_POST['description']);
            $notes = $conn->real_escape_string($_POST['notes'] ?? '');
            $created_by = (int) $_SESSION['userid'];
            $sub_ids = $_POST['sub_id'] ?? [];
            $amounts = $_POST['amount'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            $total = array_sum($amounts);
            $ref_no = 'BDG/' . $year . '/' . time();

            $existing = selectBudgetByYear($conn, $year);
            if($existing) {
                setNotification('error', 'Budget for this year already exists');
                echo "<script>window.history.back();</script>";
                exit;
            }

            $newBudget = createBudget($conn, $ref_no, $year, $description, $total, 'pending', $notes, $created_by);
            if(is_numeric($newBudget) && $newBudget > 0){
                for($i = 0; $i < count($sub_ids); $i++){
                    $sub_id = (int) $sub_ids[$i];
                    $amount = (float) $amounts[$i];
                    $itemDesc = $conn->real_escape_string($descriptions[$i] ?? '');
                    if($amount > 0){
                        createBudgetItem($conn, $newBudget, $sub_id, $itemDesc, $amount, $created_by);
                    }
                }
                setNotification('success', 'Budget created successfully');
                echo "<script>window.location.href='../?page=all_budgets';</script>";
            } else {
                setNotification('error', 'Failed to create budget: ' . $newBudget);
                echo "<script>window.history.back();</script>";
            }
        }

        if(isset($_POST['updatebudget'])){
            $id = (int) $_POST['budget_id'];
            $year = $conn->real_escape_string($_POST['year']);
            $description = $conn->real_escape_string($_POST['description']);
            $notes = $conn->real_escape_string($_POST['notes'] ?? '');
            $updated_by = (int) $_SESSION['userid'];
            $sub_ids = $_POST['sub_id'] ?? [];
            $amounts = $_POST['amount'] ?? [];
            $descriptions = $_POST['item_description'] ?? [];
            $item_ids = $_POST['item_id'] ?? [];
            $total = array_sum($amounts);
            $budget = selectBudgetById($conn, $id);
            $ref_no = $budget['ref_no'];

            updateBudget($conn, $id, $ref_no, $year, $description, $total, $budget['status'], $notes, $updated_by);

            for($i = 0; $i < count($sub_ids); $i++){
                $sub_id = (int) $sub_ids[$i];
                $amount = (float) $amounts[$i];
                $itemDesc = $conn->real_escape_string($descriptions[$i] ?? '');
                $item_id = isset($item_ids[$i]) ? (int) $item_ids[$i] : 0;
                if($amount > 0){
                    if($item_id > 0){
                        updateBudgetItem($conn, $item_id, $id, $sub_id, $itemDesc, $amount, $updated_by);
                    } else {
                        createBudgetItem($conn, $id, $sub_id, $itemDesc, $amount, $updated_by);
                    }
                }
            }

            setNotification('success', 'Budget updated successfully');
            echo "<script>window.location.href='../?page=all_budgets';</script>";
        }

        if(isset($_POST['approve_budget'])){
            $id = (int) $_POST['budget_id'];
            $approved_by = (int) $_SESSION['userid'];
            $sql = "UPDATE budget SET status='approved', approved_by=?, approved_at=NOW() WHERE id=? AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $approved_by, $id);
            if($stmt->execute()){
                setNotification('success', 'Budget approved successfully');
            } else {
                setNotification('error', 'Failed to approve budget');
            }
            echo "<script>window.location.href='../?page=all_budgets';</script>";
        }

        if(isset($_POST['reject_budget'])){
            $id = (int) $_POST['budget_id'];
            $rejected_by = (int) $_SESSION['userid'];
            $reason = $conn->real_escape_string($_POST['rejection_reason'] ?? '');
            $sql = "UPDATE budget SET status='rejected', rejected_by=?, rejected_at=NOW(), rejection_reason=? WHERE id=? AND deleted_at IS NULL";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $rejected_by, $reason, $id);
            if($stmt->execute()){
                setNotification('success', 'Budget rejected');
            } else {
                setNotification('error', 'Failed to reject budget');
            }
            echo "<script>window.location.href='../?page=all_budgets';</script>";
        }
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        if(isset($_GET['delete_budget'])){
            $id = (int) $_GET['delete_budget'];
            $deleted_by = (int) $_SESSION['userid'];
            if(softDeleteBudget($conn, $id, $deleted_by)){
                setNotification('success', 'Budget deleted');
            } else {
                setNotification('error', 'Failed to delete budget');
            }
            echo "<script>window.location.href='../?page=all_budgets';</script>";
        }
        if(isset($_GET['delete_item'])){
            $id = (int) $_GET['delete_item'];
            $deleted_by = (int) $_SESSION['userid'];
            softDeleteBudgetItemById($conn, $id, $deleted_by);
            echo "<script>window.history.back();</script>";
        }
    }
    $conn->close();
?>
