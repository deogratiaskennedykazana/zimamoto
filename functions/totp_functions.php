<?php
/**
 * TOTP (Time-based One-Time Password) Functions for Multi-Factor Authentication
 */

/**
 * Generate a random Base32 secret key for TOTP
 */
function generateTOTPSecret($length = 32) {
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < $length; $i++) {
        $secret .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $secret;
}

/**
 * Get TOTP for a given secret and time
 */
function getTOTP($secret, $timeSlice = null) {
    if ($timeSlice === null) {
        $timeSlice = floor(time() / 30);
    }
    
    $secret = base32Decode($secret);
    
    // Pack time into binary string
    $time = chr(0) . chr(0) . chr(0) . chr(0) . pack('N*', $timeSlice);
    
    // Hash it with SHA1
    $hash = hash_hmac('SHA1', $time, $secret, true);
    
    // Use last nibble as offset
    $offset = ord(substr($hash, -1)) & 0x0F;
    
    // Grab 4 bytes from offset
    $truncatedHash = substr($hash, $offset, 4);
    
    // Convert to integer
    $code = unpack('N', $truncatedHash)[1];
    $code &= 0x7FFFFFFF;
    $code %= 1000000;
    
    return str_pad($code, 6, '0', STR_PAD_LEFT);
}

/**
 * Verify a TOTP code against a secret
 * Checks current, previous, and next time slices (30s each) to allow for clock drift
 */
function verifyTOTP($secret, $code) {
    $timeSlice = floor(time() / 30);
    
    // Check current, -1, and +1 time slices (90 second window)
    for ($i = -1; $i <= 1; $i++) {
        $expected = getTOTP($secret, $timeSlice + $i);
        if (hash_equals($expected, $code)) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate a TOTP provisioning URI for QR code
 * Format: otpauth://totp/ISSUER:ACCOUNT?secret=SECRET&issuer=ISSUER
 */
function generateTOTPURI($username, $secret, $issuer = null) {
    if ($issuer === null) {
        $issuer = APP_NAME;
    }
    $encodedIssuer = rawurlencode($issuer);
    $encodedUser = rawurlencode($username);
    return "otpauth://totp/{$encodedIssuer}:{$encodedUser}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
}

/**
 * Generate recovery codes for backup
 */
function generateRecoveryCodes($count = 8) {
    $codes = [];
    for ($i = 0; $i < $count; $i++) {
        $code = strtoupper(bin2hex(random_bytes(4)));
        // Format as XXXX-XXXX
        $codes[] = substr($code, 0, 4) . '-' . substr($code, 4, 4);
    }
    return $codes;
}

/**
 * Base32 decoding
 */
function base32Decode($input) {
    $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $input = strtoupper($input);
    $input = str_replace('=', '', $input);
    
    $output = '';
    $buffer = 0;
    $bitsLeft = 0;
    
    for ($i = 0; $i < strlen($input); $i++) {
        $value = strpos($alphabet, $input[$i]);
        if ($value === false) continue;
        
        $buffer = ($buffer << 5) | $value;
        $bitsLeft += 5;
        
        if ($bitsLeft >= 8) {
            $output .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
            $bitsLeft -= 8;
        }
    }
    
    return $output;
}

/**
 * Enable TOTP for a user
 */
function enableUserTOTP(mysqli $conn, $userId, $secret) {
    $sql = "UPDATE users SET totp_secret = ?, totp_enabled = 1 WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("si", $secret, $userId);
    return $stmt->execute();
}

/**
 * Disable TOTP for a user
 */
function disableUserTOTP(mysqli $conn, $userId) {
    $sql = "UPDATE users SET totp_secret = NULL, totp_enabled = 0, totp_recovery_codes = NULL WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $userId);
    return $stmt->execute();
}

/**
 * Save recovery codes for a user
 */
function saveRecoveryCodes(mysqli $conn, $userId, array $codes) {
    $hashedCodes = array_map(function($code) {
        return password_hash($code, PASSWORD_DEFAULT);
    }, $codes);
    
    $sql = "UPDATE users SET totp_recovery_codes = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $codesJson = json_encode($hashedCodes);
    $stmt->bind_param("si", $codesJson, $userId);
    return $stmt->execute();
}

/**
 * Verify a recovery code and remove it if used
 */
function verifyRecoveryCode(mysqli $conn, $userId, $code) {
    $sql = "SELECT totp_recovery_codes FROM users WHERE id = ? AND totp_enabled = 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    
    if (!$row || empty($row['totp_recovery_codes'])) return false;
    
    $codes = json_decode($row['totp_recovery_codes'], true);
    if (!is_array($codes)) return false;
    
    foreach ($codes as $index => $hashedCode) {
        if (password_verify($code, $hashedCode)) {
            // Remove used code
            unset($codes[$index]);
            $updatedCodes = json_encode(array_values($codes));
            $updateStmt = $conn->prepare("UPDATE users SET totp_recovery_codes = ? WHERE id = ?");
            if ($updateStmt) {
                $updateStmt->bind_param("si", $updatedCodes, $userId);
                $updateStmt->execute();
            }
            return true;
        }
    }
    
    return false;
}

/**
 * Check if user has TOTP enabled
 */
function isTOTPEnabled(mysqli $conn, $userId) {
    $sql = "SELECT totp_enabled FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    return $row && $row['totp_enabled'] == 1;
}

/**
 * Check if user has completed MFA for this session
 */
function isMFAVerified() {
    return isset($_SESSION['mfa_verified']) && $_SESSION['mfa_verified'] === true;
}

/**
 * Require MFA verification (call after password verification)
 */
function requireMFAVerification() {
    $_SESSION['mfa_required'] = true;
    $_SESSION['mfa_verified'] = false;
}

/**
 * Complete MFA verification
 */
function completeMFAVerification() {
    $_SESSION['mfa_required'] = false;
    $_SESSION['mfa_verified'] = true;
}
