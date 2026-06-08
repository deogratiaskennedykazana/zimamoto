<?php

function sendGrantorRequest(mysqli $conn, int $loanId, int $grantorId, int $applicantId) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));

    $sql = "INSERT INTO grantor_notifications (loan_id, grantor_id, applicant_id, token, expires_at) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiss", $loanId, $grantorId, $applicantId, $token, $expires);
    if (!$stmt->execute()) return false;
    $notifId = $stmt->insert_id;
    $stmt->close();

    $sql = "UPDATE loan_grantors SET notified_at = NOW() WHERE loan_id = ? AND grantor_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $loanId, $grantorId);
    $stmt->execute();
    $stmt->close();

    $applicant = selectUserById($conn, $applicantId);
    $loan = selectLoanById($conn, $loanId);
    $applicantName = $applicant ? $applicant['name'] : 'A member';

    createSystemNotification($conn, $grantorId, 'Loan Guarantor Request',
        "You have been selected as a guarantor for a loan of TZS " . number_format($loan['principle'], 2) . " by $applicantName. Please review and respond.",
        'warning', "./?page=my_grantor_requests");

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

function countPendingGrantorRequests(mysqli $conn, int $userId) {
    $sql = "SELECT COUNT(*) AS cnt FROM grantor_notifications WHERE grantor_id = ? AND status = 'pending' AND expires_at > NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    return $row['cnt'] ?? 0;
}
