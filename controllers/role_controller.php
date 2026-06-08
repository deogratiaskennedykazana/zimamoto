<?php
    session_start();
    if(!$_SESSION){
        echo "<script> window.history.back();</script>";
        exit;
    }
    require_once "../functions/role_functions.php";
    require_once "../functions/user_function.php";
    require_once "../functions/notification_functions.php";
    require_once "../configs.php";
    $conn = openConn();

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['create_role'])){
            $name = $conn->real_escape_string($_POST['name']);
            $description = $conn->real_escape_string($_POST['description']);
            $created_by = (int) $_SESSION['userid'];
            if(createRole($conn, $name, $description, $created_by)){
                setNotification('success', 'Role created successfully');
            } else {
                setNotification('error', 'Failed to create role');
            }
            echo "<script>window.location.href='../?page=manage_roles';</script>";
        }

        if(isset($_POST['update_role'])){
            $id = (int) $_POST['role_id'];
            $name = $conn->real_escape_string($_POST['name']);
            $description = $conn->real_escape_string($_POST['description']);
            if(updateRole($conn, $id, $name, $description)){
                setNotification('success', 'Role updated');
            } else {
                setNotification('error', 'Failed to update role');
            }
            echo "<script>window.location.href='../?page=manage_roles';</script>";
        }

        if(isset($_POST['save_permissions'])){
            $role_id = (int) $_POST['role_id'];
            $modules = [];
            $module_names = $_POST['module'] ?? [];
            $can_view = $_POST['can_view'] ?? [];
            $can_create = $_POST['can_create'] ?? [];
            $can_edit = $_POST['can_edit'] ?? [];
            $can_delete = $_POST['can_delete'] ?? [];
            $can_approve = $_POST['can_approve'] ?? [];

            for($i = 0; $i < count($module_names); $i++){
                $modules[$module_names[$i]] = [
                    'can_view' => isset($can_view[$i]) ? 1 : 0,
                    'can_create' => isset($can_create[$i]) ? 1 : 0,
                    'can_edit' => isset($can_edit[$i]) ? 1 : 0,
                    'can_delete' => isset($can_delete[$i]) ? 1 : 0,
                    'can_approve' => isset($can_approve[$i]) ? 1 : 0,
                ];
            }

            if(setRolePermissions($conn, $role_id, $modules)){
                setNotification('success', 'Permissions saved');
            } else {
                setNotification('error', 'Failed to save permissions');
            }
            echo "<script>window.location.href='../?page=manage_roles';</script>";
        }

        if(isset($_POST['assign_role'])){
            $user_id = (int) $_POST['user_id'];
            $role_id = (int) $_POST['role_id'];
            $assigned_by = (int) $_SESSION['userid'];
            if(assignUserRole($conn, $user_id, $role_id, $assigned_by)){
                $user = selectUserById($conn, $user_id);
                $role = selectRoleById($conn, $role_id);
                createSystemNotification($conn, $user_id, 'Role Assigned',
                    "You have been assigned the role: {$role['name']}.", 'info', './?page=user_profile');
                setNotification('success', 'Role assigned successfully');
            } else {
                setNotification('error', 'Failed to assign role');
            }
            echo "<script>window.location.href='../?page=assign_user_roles';</script>";
        }

        if(isset($_POST['revoke_role'])){
            $user_id = (int) $_POST['user_id'];
            $role_id = (int) $_POST['role_id'];
            if(revokeUserRole($conn, $user_id, $role_id)){
                setNotification('success', 'Role revoked');
            } else {
                setNotification('error', 'Failed to revoke role');
            }
            echo "<script>window.location.href='../?page=assign_user_roles';</script>";
        }
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        if(isset($_GET['delete_role'])){
            $id = (int) $_GET['delete_role'];
            softDeleteRole($conn, $id);
            setNotification('success', 'Role deleted');
            echo "<script>window.location.href='../?page=manage_roles';</script>";
        }
    }
    $conn->close();
?>
