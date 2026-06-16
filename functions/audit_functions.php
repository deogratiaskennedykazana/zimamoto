<?php
/**
 * Audit Trail Functions
 * Logs every significant action performed by users in the system.
 */

/**
 * Record an audit log entry
 *
 * @param mysqli  $conn
 * @param int     $userId     User performing the action
 * @param string  $action     e.g. 'create', 'update', 'delete', 'approve', 'reject', 'login', 'logout'
 * @param string  $module     e.g. 'loans', 'budgets', 'users', 'vouchers', 'members'
 * @param int|null $recordId  Primary key of the affected record (if applicable)
 * @param string|null $detail Human-readable description of what changed
 * @param array   $oldValues  Previous values (for updates)
 * @param array   $newValues  New values (for updates)
 */
function logAudit(
    mysqli $conn,
    int $userId,
    string $action,
    string $module,
    ?int $recordId = null,
    ?string $detail = null,
    array $oldValues = [],
    array $newValues = []
) {
    $ipAddress  = $_SERVER['REMOTE_ADDR'] ?? null;
    $userAgent  = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $sessionId  = session_id() ?: null;
    $oldJson    = !empty($oldValues) ? json_encode($oldValues) : null;
    $newJson    = !empty($newValues) ? json_encode($newValues) : null;

    $sql = "INSERT INTO audit_trail 
            (user_id, action, module, record_id, detail, old_values, new_values, ip_address, user_agent, session_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("issiisssss",
        $userId, $action, $module, $recordId, $detail,
        $oldJson, $newJson, $ipAddress, $userAgent, $sessionId
    );
    return $stmt->execute();
}

/**
 * Get audit trail records with optional filters
 */
function getAuditTrail(
    mysqli $conn,
    array $filters = [],
    int $limit = 100,
    int $offset = 0
) {
    $where = ["1=1"];
    $params = [];
    $types  = "";

    if (!empty($filters['user_id'])) {
        $where[] = "at.user_id = ?";
        $params[] = (int)$filters['user_id'];
        $types   .= "i";
    }
    if (!empty($filters['module'])) {
        $where[] = "at.module = ?";
        $params[] = $filters['module'];
        $types   .= "s";
    }
    if (!empty($filters['action'])) {
        $where[] = "at.action = ?";
        $params[] = $filters['action'];
        $types   .= "s";
    }
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(at.created_at) >= ?";
        $params[] = $filters['date_from'];
        $types   .= "s";
    }
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(at.created_at) <= ?";
        $params[] = $filters['date_to'];
        $types   .= "s";
    }

    $whereStr = implode(" AND ", $where);
    $sql = "SELECT at.*, u.name AS user_name, u.email AS user_email
            FROM audit_trail at
            LEFT JOIN users u ON at.user_id = u.id
            WHERE $whereStr
            ORDER BY at.created_at DESC
            LIMIT ? OFFSET ?";

    $params[] = $limit;
    $params[] = $offset;
    $types   .= "ii";

    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

/**
 * Count audit records for pagination
 */
function countAuditTrail(mysqli $conn, array $filters = []) {
    $where = ["1=1"];
    $params = [];
    $types  = "";

    if (!empty($filters['user_id'])) {
        $where[] = "user_id = ?";
        $params[] = (int)$filters['user_id'];
        $types   .= "i";
    }
    if (!empty($filters['module'])) {
        $where[] = "module = ?";
        $params[] = $filters['module'];
        $types   .= "s";
    }
    if (!empty($filters['action'])) {
        $where[] = "action = ?";
        $params[] = $filters['action'];
        $types   .= "s";
    }
    if (!empty($filters['date_from'])) {
        $where[] = "DATE(created_at) >= ?";
        $params[] = $filters['date_from'];
        $types   .= "s";
    }
    if (!empty($filters['date_to'])) {
        $where[] = "DATE(created_at) <= ?";
        $params[] = $filters['date_to'];
        $types   .= "s";
    }

    $whereStr = implode(" AND ", $where);
    $sql = "SELECT COUNT(*) AS cnt FROM audit_trail WHERE $whereStr";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return 0;
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    return $row['cnt'] ?? 0;
}

/**
 * Get audit trail for a specific record
 */
function getRecordAuditTrail(mysqli $conn, string $module, int $recordId) {
    $sql = "SELECT at.*, u.name AS user_name
            FROM audit_trail at
            LEFT JOIN users u ON at.user_id = u.id
            WHERE at.module = ? AND at.record_id = ?
            ORDER BY at.created_at DESC";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return [];
    $stmt->bind_param("si", $module, $recordId);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

/**
 * Get action badge color for display
 */
function auditActionBadge(string $action): string {
    $map = [
        'create'  => 'badge-success',
        'update'  => 'badge-info',
        'delete'  => 'badge-danger',
        'approve' => 'badge-primary',
        'reject'  => 'badge-warning',
        'login'   => 'badge-secondary',
        'logout'  => 'badge-secondary',
        'export'  => 'badge-dark',
        'view'    => 'badge-light',
    ];
    return $map[$action] ?? 'badge-secondary';
}
