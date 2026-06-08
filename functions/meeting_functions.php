<?php
// ============================================================
//  MEETING MINUTES FUNCTIONS — Zima-Moto
// ============================================================

function insertMeetingMinutes(mysqli $conn, string $title, string $meeting_date, string $meeting_type, string $venue, string $chairperson, string $content, string $status, int $created_by) {
    $sql = "INSERT INTO `meeting_minutes` (`title`,`meeting_date`,`meeting_type`,`venue`,`chairperson`,`content`,`status`,`created_by`)
            VALUES (?,?,?,?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return "Prepare error: " . $conn->error;
    $stmt->bind_param("sssssssi", $title, $meeting_date, $meeting_type, $venue, $chairperson, $content, $status, $created_by);
    if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); return $id; }
    $err = $stmt->error; $stmt->close(); return $err;
}

function updateMeetingMinutes(mysqli $conn, int $id, string $title, string $meeting_date, string $meeting_type, string $venue, string $chairperson, string $content, string $status, int $updated_by) {
    $sql = "UPDATE `meeting_minutes` SET `title`=?,`meeting_date`=?,`meeting_type`=?,`venue`=?,`chairperson`=?,`content`=?,`status`=?,`updated_by`=?,`updated_at`=NOW() WHERE id=? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("sssssssii", $title, $meeting_date, $meeting_type, $venue, $chairperson, $content, $status, $updated_by, $id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function selectAllMeetingMinutes(mysqli $conn) {
    $sql = "SELECT m.*, u.name AS created_by_name FROM meeting_minutes m
            LEFT JOIN users u ON m.created_by = u.id
            WHERE m.deleted_at IS NULL ORDER BY m.meeting_date DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function selectMeetingMinutesById(mysqli $conn, int $id) {
    $sql = "SELECT m.*, u.name AS created_by_name FROM meeting_minutes m
            LEFT JOIN users u ON m.created_by = u.id
            WHERE m.id = ? AND m.deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = stmt_fetch_assoc($stmt);
    $stmt->close();
    return $data;
}

function softDeleteMeetingMinutes(mysqli $conn, int $id, int $deleted_by) {
    $sql = "UPDATE `meeting_minutes` SET deleted_at=NOW(), deleted_by=? WHERE id=? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $deleted_by, $id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function insertMeetingAttendee(mysqli $conn, int $minutes_id, ?int $user_id, string $name, string $role, int $present) {
    $sql = "INSERT INTO `meeting_attendees` (`minutes_id`,`user_id`,`name`,`role`,`present`) VALUES (?,?,?,?,?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iissi", $minutes_id, $user_id, $name, $role, $present);
    if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); return $id; }
    $err = $stmt->error; $stmt->close(); return $err;
}

function selectAttendeesByMinutesId(mysqli $conn, int $minutes_id) {
    $sql = "SELECT * FROM meeting_attendees WHERE minutes_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $minutes_id);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();
    return $data;
}

function deleteAttendeesByMinutesId(mysqli $conn, int $minutes_id) {
    $sql = "DELETE FROM meeting_attendees WHERE minutes_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $minutes_id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}
