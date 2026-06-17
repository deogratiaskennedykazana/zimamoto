<?php
    session_start();
    if(!$_SESSION){
        echo "<script> window.history.back();</script>";
        exit;
    }
    require_once "../functions/meeting_functions.php";
    require_once "../functions/notification_functions.php";
    require_once "../configs.php";
    $conn = openConn();

    if($_SERVER['REQUEST_METHOD'] === 'POST'){
        if(isset($_POST['create_meeting'])){
            $title = $conn->real_escape_string($_POST['title']);
            $meeting_date = $conn->real_escape_string($_POST['meeting_date']);
            $meeting_type = $conn->real_escape_string($_POST['meeting_type']);
            $venue = $conn->real_escape_string($_POST['venue']);
            $chairperson = $conn->real_escape_string($_POST['chairperson']);
            $content = $_POST['content'];
            $status = $conn->real_escape_string($_POST['status'] ?? 'draft');
            $created_by = (int) $_SESSION['userid'];

            $newMeeting = insertMeetingMinutes($conn, $title, $meeting_date, $meeting_type, $venue, $chairperson, $content, $status, $created_by);
            if(is_numeric($newMeeting) && $newMeeting > 0){
                $attendee_names = $_POST['attendee_name'] ?? [];
                $attendee_roles = $_POST['attendee_role'] ?? [];
                $attendee_present = $_POST['attendee_present'] ?? [];
                for($i = 0; $i < count($attendee_names); $i++){
                    $name = $conn->real_escape_string($attendee_names[$i]);
                    $role = $conn->real_escape_string($attendee_roles[$i] ?? '');
                    $present = isset($attendee_present[$i]) ? 1 : 0;
                    if(!empty($name)){
                        insertMeetingAttendee($conn, $newMeeting, null, $name, $role, $present);
                    }
                }
                setNotification('success', 'Meeting minutes created successfully');
                echo "<script>window.location.href='../?page=meeting_list';</script>";
            } else {
                setNotification('error', 'Failed to create meeting minutes: ' . $newMeeting);
                echo "<script>window.history.back();</script>";
            }
        }

        if(isset($_POST['update_meeting'])){
            $id = (int) $_POST['meeting_id'];
            $title = $conn->real_escape_string($_POST['title']);
            $meeting_date = $conn->real_escape_string($_POST['meeting_date']);
            $meeting_type = $conn->real_escape_string($_POST['meeting_type']);
            $venue = $conn->real_escape_string($_POST['venue']);
            $chairperson = $conn->real_escape_string($_POST['chairperson']);
            $content = $_POST['content'];
            $status = $conn->real_escape_string($_POST['status'] ?? 'draft');
            $updated_by = (int) $_SESSION['userid'];

            if(updateMeetingMinutes($conn, $id, $title, $meeting_date, $meeting_type, $venue, $chairperson, $content, $status, $updated_by)){
                deleteAttendeesByMinutesId($conn, $id);
                $attendee_names = $_POST['attendee_name'] ?? [];
                $attendee_roles = $_POST['attendee_role'] ?? [];
                $attendee_present = $_POST['attendee_present'] ?? [];
                for($i = 0; $i < count($attendee_names); $i++){
                    $name = $conn->real_escape_string($attendee_names[$i]);
                    $role = $conn->real_escape_string($attendee_roles[$i] ?? '');
                    $present = isset($attendee_present[$i]) ? 1 : 0;
                    if(!empty($name)){
                        insertMeetingAttendee($conn, $id, null, $name, $role, $present);
                    }
                }
                setNotification('success', 'Meeting minutes updated');
            } else {
                setNotification('error', 'Failed to update meeting minutes');
            }
            echo "<script>window.location.href='../?page=meeting_list';</script>";
        }

        if(isset($_POST['publish_meeting'])){
            $id = (int) $_POST['meeting_id'];
            $updated_by = (int) $_SESSION['userid'];
            $sql = "UPDATE meeting_minutes SET status='published', updated_by=?, updated_at=NOW() WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $updated_by, $id);
            $stmt->execute();
            setNotification('success', 'Meeting minutes published');
            echo "<script>window.location.href='../?page=meeting_list';</script>";
        }
    }

    if($_SERVER['REQUEST_METHOD'] === 'GET'){
        if(isset($_GET['delete_meeting'])){
            $id = (int) $_GET['delete_meeting'];
            $deleted_by = (int) $_SESSION['userid'];
            softDeleteMeetingMinutes($conn, $id, $deleted_by);
            setNotification('success', 'Meeting minutes deleted');
            echo "<script>window.location.href='../?page=meeting_list';</script>";
        }
    }
    $conn->close();
?>
