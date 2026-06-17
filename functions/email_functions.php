<?php
/**
 * Email and SMS Notification Functions
 */

/**
 * Send email using PHPMailer
 */
function sendEmail($to, $subject, $message, $fromEmail = null, $fromName = null) {
    require_once __DIR__ . '/../vendor/autoload.php';
    
    $fromEmail = $fromEmail ?: SMTP_FROM_EMAIL;
    $fromName = $fromName ?: SMTP_FROM_NAME;
    
    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = SMTP_PORT;
        
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($to);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $mail->ErrorInfo];
    }
}

/**
 * Queue a notification for processing
 */
function queueNotification(mysqli $conn, $userId, $type, $subject, $message, $recipient) {
    $sql = "INSERT INTO notification_queue (user_id, type, subject, message, recipient) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("issss", $userId, $type, $subject, $message, $recipient);
    return $stmt->execute();
}

/**
 * Process pending notifications in queue
 */
function processNotificationQueue(mysqli $conn, $limit = 10) {
    $sql = "SELECT * FROM notification_queue WHERE status = 'pending' ORDER BY created_at ASC LIMIT ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $notifications = stmt_fetch_all($stmt);
    
    foreach ($notifications as $notification) {
        $result = ['success' => false, 'message' => 'Unknown error'];
        
        if ($notification['type'] == 'email') {
            $result = sendEmail($notification['recipient'], $notification['subject'], $notification['message']);
        } elseif ($notification['type'] == 'sms') {
            $result = sendSMS($notification['recipient'], $notification['message']);
        }
        
        $status = $result['success'] ? 'sent' : 'failed';
        $error = $result['success'] ? null : $result['message'];
        
        $update = $conn->prepare("UPDATE notification_queue SET status = ?, error_message = ?, sent_at = NOW() WHERE id = ?");
        if ($update) {
            $update->bind_param("ssi", $status, $error, $notification['id']);
            $update->execute();
        }
    }
    return true;
}

/**
 * Send notification via all enabled channels for a user
 */
function sendUserNotification(mysqli $conn, $userId, $title, $message, $type = 'info', $link = null) {
    // Always create in-app notification
    $notifResult = createSystemNotification($conn, $userId, $title, $message, $type, $link);
    
    // Check user notification preferences
    $prefs = getUserNotificationSettings($conn, $userId);
    
    if ($prefs) {
        // Send email if enabled
        if ($prefs['notify_email'] && !empty($prefs['email_address'])) {
            $subject = APP_NAME . ' - ' . $title;
            $htmlMessage = "<h3>{$title}</h3><p>{$message}</p>";
            if ($link) {
                $htmlMessage .= "<p><a href='{$link}'>View Details</a></p>";
            }
            queueNotification($conn, $userId, 'email', $subject, $htmlMessage, $prefs['email_address']);
        }
        
        // Send SMS if enabled
        if ($prefs['notify_sms'] && !empty($prefs['phone_number'])) {
            $smsText = APP_NAME . ": {$title} - {$message}";
            if ($link) {
                $smsText .= " - " . APP_URL;
            }
            queueNotification($conn, $userId, 'sms', null, $smsText, $prefs['phone_number']);
        }
    }
    
    return $notifResult;
}

/**
 * Get user notification settings
 */
function getUserNotificationSettings(mysqli $conn, $userId) {
    $sql = "SELECT * FROM notification_settings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return null;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = stmt_fetch_assoc($stmt);
    
    // If no settings exist, create default
    if (!$result) {
        // Fetch user info for defaults
        $userSql = "SELECT email, phone FROM users WHERE id = ?";
        $userStmt = $conn->prepare($userSql);
        if ($userStmt) {
            $userStmt->bind_param("i", $userId);
            $userStmt->execute();
            $user = stmt_fetch_assoc($userStmt);
            
            $insert = $conn->prepare("INSERT INTO notification_settings (user_id, email_address, phone_number) VALUES (?, ?, ?)");
            if ($insert) {
                $insert->bind_param("iss", $userId, $user['email'], $user['phone']);
                $insert->execute();
            }
        }
        
        // Refetch
        $stmt->execute();
        $result = stmt_fetch_assoc($stmt);
    }
    
    return $result;
}

/**
 * Update user notification settings
 */
function updateUserNotificationSettings(mysqli $conn, $userId, $settings) {
    $fields = [];
    $params = [];
    $types = '';
    
    $allowed = ['notify_email', 'notify_sms', 'notify_in_app', 'email_address', 'phone_number'];
    
    foreach ($allowed as $field) {
        if (isset($settings[$field])) {
            $fields[] = "$field = ?";
            $params[] = $settings[$field];
            $types .= is_int($settings[$field]) ? 'i' : 's';
        }
    }
    
    if (empty($fields)) return false;
    
    $params[] = $userId;
    $types .= 'i';
    
    $sql = "INSERT INTO notification_settings (user_id, " . implode(', ', array_keys($settings)) . ") 
            VALUES (?, " . implode(', ', array_fill(0, count($settings), '?')) . ")
            ON DUPLICATE KEY UPDATE " . implode(', ', $fields);
    
    // Simpler approach - check if exists
    $check = $conn->query("SELECT id FROM notification_settings WHERE user_id = $userId");
    if ($check && $check->num_rows > 0) {
        $sql = "UPDATE notification_settings SET " . implode(', ', $fields) . " WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $stmt->bind_param($types, ...$params);
    } else {
        $allFields = array_keys($settings);
        $allFields[] = 'user_id';
        $allValues = array_values($settings);
        $allValues[] = $userId;
        $placeholders = implode(', ', array_fill(0, count($allFields), '?'));
        $sql = "INSERT INTO notification_settings (" . implode(', ', $allFields) . ") VALUES ($placeholders)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) return false;
        $typesAll = '';
        foreach ($allValues as $v) {
            $typesAll .= is_int($v) ? 'i' : 's';
        }
        $stmt->bind_param($typesAll, ...$allValues);
    }
    
    return $stmt->execute();
}
