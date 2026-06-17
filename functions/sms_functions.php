<?php
/**
 * SMS Functions — Africa's Talking
 * Handles sending OTP codes and general SMS via AT API.
 */

/**
 * Send an SMS via Africa's Talking.
 * Returns true on success, error string on failure.
 */
function sendSMS(string $phone, string $message): bool|string {
    $phone = formatPhoneE164($phone);

    $postData = http_build_query([
        'username' => SMS_USERNAME,
        'to'       => $phone,
        'message'  => $message,
        'from'     => SMS_FROM,
    ]);

    $ch = curl_init('https://api.africastalking.com/version1/messaging');
    curl_setopt_array($ch, [
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'apiKey: ' . SMS_API_KEY,
            'Content-Type: application/x-www-form-urlencoded',
        ],
        CURLOPT_TIMEOUT => 15,
    ]);

    $response  = curl_exec($ch);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        error_log("SMS curl error: $curlError");
        return "SMS delivery error: $curlError";
    }

    $data   = json_decode($response, true);
    $status = $data['SMSMessageData']['Recipients'][0]['status'] ?? '';

    if ($status === 'Success') {
        return true;
    }

    $errMsg = $data['SMSMessageData']['Recipients'][0]['statusCode'] ?? $response;
    error_log("SMS send failed: $errMsg");
    return "SMS failed: $errMsg";
}

/**
 * Generate a 6-digit numeric OTP.
 */
function generateOTP(): string {
    return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

/**
 * Save a hashed OTP to the DB for a user, expiring in 10 minutes.
 * Replaces any existing pending OTPs for the same user.
 */
function saveSmsOTP(mysqli $conn, int $userId, string $otp): bool {
    $conn->query("DELETE FROM sms_otp WHERE user_id = $userId");
    $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    $hashed  = password_hash($otp, PASSWORD_DEFAULT);
    $stmt    = $conn->prepare("INSERT INTO sms_otp (user_id, otp_hash, expires_at) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $hashed, $expires);
    return $stmt->execute();
}

/**
 * Verify an OTP for a user.
 * Returns true if valid and not expired, deletes the record on success.
 */
function verifySmsOTP(mysqli $conn, int $userId, string $otp): bool {
    $stmt = $conn->prepare(
        "SELECT id, otp_hash FROM sms_otp
         WHERE user_id = ? AND expires_at > NOW()
         ORDER BY id DESC LIMIT 1"
    );
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);

    if (!$row) return false;

    if (password_verify($otp, $row['otp_hash'])) {
        $conn->query("DELETE FROM sms_otp WHERE id = {$row['id']}");
        return true;
    }
    return false;
}

/**
 * Generate, save, and send an MFA OTP to a user's phone.
 * Returns true on success or an error string on failure.
 */
function sendMfaOTP(mysqli $conn, int $userId, string $phone): bool|string {
    $otp    = generateOTP();
    $text   = APP_NAME . ": Your verification code is $otp. Valid for 10 minutes. Do not share it.";
    $result = sendSMS($phone, $text);
    if ($result !== true) return $result;
    saveSmsOTP($conn, $userId, $otp);
    return true;
}

/**
 * Enable SMS MFA for a user.
 */
function enableSmsMFA(mysqli $conn, int $userId): bool {
    $stmt = $conn->prepare("UPDATE users SET sms_mfa_enabled = 1 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Disable SMS MFA for a user.
 */
function disableSmsMFA(mysqli $conn, int $userId): bool {
    $stmt = $conn->prepare("UPDATE users SET sms_mfa_enabled = 0 WHERE id = ?");
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Normalise a phone number to E.164 (+255XXXXXXXXX for Tanzania).
 * Accepts: 0712345678 | 255712345678 | +255712345678
 */
function formatPhoneE164(string $phone): string {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    if (str_starts_with($phone, '+'))   return $phone;
    if (str_starts_with($phone, '255')) return '+' . $phone;
    if (str_starts_with($phone, '0'))   return '+255' . substr($phone, 1);
    return '+255' . $phone;
}
