<?php

require_once __DIR__ . '/email_functions.php';

/**
 * Set a flash notification message in session
 */
function setNotification($type, $message) {
    $_SESSION['notification'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Get and clear flash notification from session
 */
function getNotification() {
    if (isset($_SESSION['notification'])) {
        $notification = $_SESSION['notification'];
        unset($_SESSION['notification']);
        return $notification;
    }
    return null;
}

/**
 * Create a system notification (in-app) and optionally send email/SMS
 */
function createSystemNotification(mysqli $conn, int $userId, string $title, string $message, string $type = 'info', string $link = null) {
    $sql = "INSERT INTO system_notifications (user_id, type, title, message, link) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("issss", $userId, $type, $title, $message, $link);
    $result = $stmt->execute();
    $stmt->close(); // close immediately — prevents hitting max_prepared_stmt_count
    
    if ($result) {
        // Send email/SMS via user preferences (does NOT call createSystemNotification again)
        sendUserNotification($conn, $userId, $title, $message, $type, $link);
    }
    
    return $result;
}

/**
 * Get user notifications from database
 */
function getUserNotifications(mysqli $conn, int $userId, int $limit = 20) {
    $sql = "SELECT * FROM system_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("ii", $userId, $limit);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

/**
 * Count unread notifications for a user
 */
function countUnreadNotifications(mysqli $conn, int $userId) {
    $sql = "SELECT COUNT(*) AS cnt FROM system_notifications WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    $stmt->close();
    return $row['cnt'] ?? 0;
}

/**
 * Count pending grantor (guarantor) requests for a user
 */
function countPendingGrantorRequests(mysqli $conn, int $userId) {
    $sql = "SELECT COUNT(*) AS cnt FROM grantor_notifications WHERE grantor_id = ? AND status = 'pending' AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    $stmt->close();
    return $row['cnt'] ?? 0;
}

/**
 * Mark a single notification as read
 */
function markNotificationRead(mysqli $conn, int $notificationId, int $userId) {
    $sql = "UPDATE system_notifications SET is_read = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

/**
 * Mark all notifications as read for a user
 */
function markAllNotificationsRead(mysqli $conn, int $userId) {
    $sql = "UPDATE system_notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Send bulk notification to all users
 */
function sendBulkNotification(mysqli $conn, string $title, string $message, string $type = 'info', string $link = null, string $userRole = null) {
    $sql = "SELECT id FROM users WHERE status = 'approved'";
    if ($userRole) {
        $sql .= " AND role = ?";
    }
    $stmt = $conn->prepare($sql);
    if ($userRole) {
        $stmt->bind_param("s", $userRole);
    }
    $stmt->execute();
    $users = stmt_fetch_all($stmt);
    
    $success = true;
    foreach ($users as $user) {
        $result = createSystemNotification($conn, $user['id'], $title, $message, $type, $link);
        if (!$result) $success = false;
    }
    
    return $success;
}

/**
 * Send notification to all users in a branch
 */
function sendBranchNotification(mysqli $conn, int $branchId, string $title, string $message, string $type = 'info', string $link = null) {
    $sql = "SELECT id FROM users WHERE branch_id = ? AND status = 'approved'";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $branchId);
    $stmt->execute();
    $users = stmt_fetch_all($stmt);
    
    $success = true;
    foreach ($users as $user) {
        $result = createSystemNotification($conn, $user['id'], $title, $message, $type, $link);
        if (!$result) $success = false;
    }
    
    return $success;
}

/**
 * Delete old notifications (older than 90 days)
 */
function cleanOldNotifications(mysqli $conn) {
    $sql = "DELETE FROM system_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL 90 DAY)";
    return $conn->query($sql);
}
