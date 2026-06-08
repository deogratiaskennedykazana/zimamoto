<?php

function setNotification($type, $message) {
    $_SESSION['notification'] = [
        'type' => $type,
        'message' => $message
    ];
}

function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}

function createSystemNotification(mysqli $conn, int $userId, string $title, string $message, string $type = 'info', string $link = null) {
    $sql = "INSERT INTO system_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("issss", $userId, $type, $title, $message, $link);
    return $stmt->execute();
}

function getUserNotifications(mysqli $conn, int $userId, int $limit = 20) {
    $sql = "SELECT * FROM system_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

function countUnreadNotifications(mysqli $conn, int $userId) {
    $sql = "SELECT COUNT(*) AS cnt FROM system_notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    return $row['cnt'] ?? 0;
}

function markNotificationRead(mysqli $conn, int $notificationId, int $userId) {
    $sql = "UPDATE system_notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

function markAllNotificationsRead(mysqli $conn, int $userId) {
    $sql = "UPDATE system_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}
