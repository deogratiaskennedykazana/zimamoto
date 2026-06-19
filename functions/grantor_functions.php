<?php

require_once __DIR__ . '/email_functions.php';
// selectLoanById and selectUserById are loaded by the calling controller;
// but in case this file is ever included standalone, guard against missing functions.
if (!function_exists('selectLoanById')) {
    require_once __DIR__ . '/loan_functions.php';
}
if (!function_exists('selectUserById')) {
    require_once __DIR__ . '/user_function.php';
}

function sendGrantorRequest(mysqli $conn, int $loanId, int $grantorId, int $applicantId) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

    // Insert grantor_notifications record with token
    $sql = "INSERT INTO grantor_notifications (loan_id, grantor_id, applicant_id, token, expires_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $loanId, $grantorId, $applicantId, $token, $expires);
    if (!$stmt->execute()) return false;
    $notifId = $stmt->insert_id;
    $stmt->close();

    // Mark loan_grantors as notified
    $sql = "UPDATE loan_grantors SET notified_at = NOW() WHERE loan_id = ? AND grantor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $loanId, $grantorId);
    $stmt->execute();
    $stmt->close();

    // Fetch people & loan details for the notification message
    $applicant  = selectUserById($conn, $applicantId);
    $grantor    = selectUserById($conn, $grantorId);
    $loan       = selectLoanById($conn, $loanId);
    $applicantName = $applicant ? $applicant['name'] : 'A member';
    $grantorName   = $grantor  ? $grantor['name']   : 'Member';
    $loanAmount    = $loan     ? number_format((float)$loan['principle'], 2) : '0.00';
    $loanPeriod    = $loan     ? (int)$loan['period'] : 0;

    // Build accept / reject URLs using the token
    $baseUrl    = rtrim(APP_URL, '/');
    $acceptUrl  = $baseUrl . '/zimamoto/controllers/grantor_controller.php?respond=' . urlencode($token) . '&status=accepted';
    $rejectUrl  = $baseUrl . '/zimamoto/controllers/grantor_controller.php?respond=' . urlencode($token) . '&status=rejected';
    $dashUrl    = $baseUrl . '/zimamoto/?page=my_grantor_requests';

    // ── In-app notification ──────────────────────────────────────────
    createSystemNotification(
        $conn, $grantorId,
        'Loan Guarantor Request',
        "You have been selected as a guarantor for a loan of TZS {$loanAmount} ({$loanPeriod} months) by {$applicantName}. Please log in to review and respond.",
        'warning',
        './?page=my_grantor_requests'
    );

    // ── Email notification ───────────────────────────────────────────
    // Only send if grantor has an email address
    $grantorEmail = $grantor['email'] ?? '';
    if (!empty($grantorEmail)) {
        $subject = APP_NAME . ' — Loan Guarantor Request from ' . $applicantName;

        $emailBody = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'><title>Guarantor Request</title></head>
<body style='font-family:Arial,sans-serif;background:#f4f6f9;margin:0;padding:0;'>
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6f9;padding:30px 0;'>
  <tr><td align='center'>
    <table width='600' cellpadding='0' cellspacing='0' style='background:#ffffff;border-radius:8px;overflow:hidden;box-shadow:0 2px 8px rgba(0,0,0,0.1);'>
      <!-- Header -->
      <tr><td style='background:#007bff;padding:24px 32px;'>
        <h1 style='color:#ffffff;margin:0;font-size:22px;'>" . APP_NAME . "</h1>
        <p style='color:#cce5ff;margin:4px 0 0;font-size:13px;'>Guarantor Request Notification</p>
      </td></tr>
      <!-- Body -->
      <tr><td style='padding:32px;'>
        <p style='font-size:15px;color:#333;'>Dear <strong>{$grantorName}</strong>,</p>
        <p style='font-size:15px;color:#333;'>
          <strong>{$applicantName}</strong> has selected you as a <strong>guarantor</strong> for their loan application.
        </p>
        <!-- Loan details box -->
        <table width='100%' cellpadding='12' cellspacing='0' style='background:#f8f9fa;border-radius:6px;margin:20px 0;'>
          <tr>
            <td style='font-size:13px;color:#666;border-bottom:1px solid #dee2e6;'><strong>Loan Amount</strong></td>
            <td style='font-size:13px;color:#333;border-bottom:1px solid #dee2e6;text-align:right;'>TZS {$loanAmount}</td>
          </tr>
          <tr>
            <td style='font-size:13px;color:#666;border-bottom:1px solid #dee2e6;'><strong>Repayment Period</strong></td>
            <td style='font-size:13px;color:#333;border-bottom:1px solid #dee2e6;text-align:right;'>{$loanPeriod} months</td>
          </tr>
          <tr>
            <td style='font-size:13px;color:#666;'><strong>Applicant</strong></td>
            <td style='font-size:13px;color:#333;text-align:right;'>{$applicantName}</td>
          </tr>
        </table>
        <p style='font-size:14px;color:#555;'>As a guarantor, you agree to be responsible for this loan if the borrower defaults. Please review carefully before responding.</p>
        <p style='font-size:14px;color:#555;'>This request expires in <strong>30 days</strong>.</p>
        <!-- CTA Buttons -->
        <table cellpadding='0' cellspacing='0' style='margin:28px 0;'>
          <tr>
            <td style='padding-right:12px;'>
              <a href='{$acceptUrl}' style='background:#28a745;color:#fff;text-decoration:none;padding:12px 28px;border-radius:5px;font-size:15px;font-weight:bold;display:inline-block;'>✔ Accept</a>
            </td>
            <td>
              <a href='{$rejectUrl}' style='background:#dc3545;color:#fff;text-decoration:none;padding:12px 28px;border-radius:5px;font-size:15px;font-weight:bold;display:inline-block;'>✖ Reject</a>
            </td>
          </tr>
        </table>
        <p style='font-size:13px;color:#888;'>Or log in to your dashboard to respond:<br>
          <a href='{$dashUrl}' style='color:#007bff;'>{$dashUrl}</a>
        </p>
        <hr style='border:none;border-top:1px solid #dee2e6;margin:24px 0;'>
        <p style='font-size:12px;color:#aaa;'>If you did not expect this email, please ignore it. Do not share your response link with anyone.</p>
      </td></tr>
      <!-- Footer -->
      <tr><td style='background:#f8f9fa;padding:16px 32px;text-align:center;'>
        <p style='font-size:12px;color:#aaa;margin:0;'>" . APP_NAME . " &mdash; Savings &amp; Credit Cooperative</p>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>";

        // Send directly (not via queue) so the email goes out immediately
        sendEmail($grantorEmail, $subject, $emailBody);
    }

    return $notifId;
}

function selectGrantorPendingRequests(mysqli $conn, int $grantorId) {
    $sql = "SELECT gn.*, u.name AS applicant_name, l.principle, l.period, lt.name AS loan_type
            FROM grantor_notifications gn
            JOIN users u ON gn.applicant_id = u.id
            JOIN loans l ON gn.loan_id = l.id
            LEFT JOIN loan_types lt ON l.loan_type = lt.id
            WHERE gn.grantor_id = ? AND gn.status = 'pending' AND gn.expires_at > NOW()
            ORDER BY gn.sent_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $grantorId);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

function respondToGrantorRequest(mysqli $conn, string $token, string $status, string $comment = null) {
    $sql = "SELECT * FROM grantor_notifications WHERE token = ? AND status = 'pending' AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $notification = stmt_fetch_assoc($stmt);
    $stmt->close();

    if (!$notification) return 'Invalid or expired token';

    $sql = "UPDATE grantor_notifications SET status = ?, comment = ?, responded_at = NOW() WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $status, $comment, $token);
    $stmt->execute();
    $stmt->close();

    $sql = "UPDATE loan_grantors SET status = ?, response_comment = ?, responded_at = NOW() WHERE loan_id = ? AND grantor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $status, $comment, $notification['loan_id'], $notification['grantor_id']);
    $stmt->execute();
    $stmt->close();

    $statusText = $status === 'accepted' ? 'accepted' : 'rejected';
    $grantor = selectUserById($conn, $notification['grantor_id']);
    $grantorName = $grantor ? $grantor['name'] : 'A guarantor';

    createSystemNotification($conn, $notification['applicant_id'], 'Guarantor Response',
        "$grantorName has $statusText your loan guarantee request." . ($comment ? " Comment: $comment" : ""),
        $status === 'accepted' ? 'success' : 'danger', "./?page=my_loan");

    return true;
}

function selectGrantorNotificationsByLoanId(mysqli $conn, int $loanId) {
    $sql = "SELECT gn.*, u.name AS grantor_name FROM grantor_notifications gn 
            JOIN users u ON gn.grantor_id = u.id 
            WHERE gn.loan_id = ? ORDER BY gn.sent_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loanId);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

/**
 * All grantor_notification rows for a given grantor (all statuses) — used for history view.
 */
function selectAllGrantorNotificationsForGrantor(mysqli $conn, int $grantorId) {
    $sql = "SELECT gn.*, u.name AS applicant_name, l.principle, l.period, lt.name AS loan_type
            FROM grantor_notifications gn
            JOIN users u ON gn.applicant_id = u.id
            JOIN loans l ON gn.loan_id = l.id
            LEFT JOIN loan_types lt ON l.loan_type = lt.id
            WHERE gn.grantor_id = ?
            ORDER BY gn.sent_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $grantorId);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

// NOTE: countPendingGrantorRequests() is defined in notification_functions.php — do NOT redeclare here.
