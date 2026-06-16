<?php

function createRole(mysqli $conn, string $name, string $description, int $created_by) {
    $sql = "INSERT INTO roles (name, description, created_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("ssi", $name, $description, $created_by);
    return $stmt->execute();
}

function selectAllRoles(mysqli $conn) {
    $sql = "SELECT r.*, u.name AS created_by_name FROM roles r LEFT JOIN users u ON r.created_by = u.id WHERE r.deleted_at IS NULL ORDER BY r.name";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function selectRoleById(mysqli $conn, int $id) {
    $sql = "SELECT * FROM roles WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return stmt_fetch_assoc($stmt);
}

function updateRole(mysqli $conn, int $id, string $name, string $description) {
    $sql = "UPDATE roles SET name = ?, description = ? WHERE id = ? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssi", $name, $description, $id);
    return $stmt->execute();
}

function softDeleteRole(mysqli $conn, int $id) {
    $sql = "UPDATE roles SET deleted_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    return $stmt->execute();
}

// function setRolePermissions(mysqli $conn, int $roleId, array $modules) {
//     $sql = "DELETE FROM role_permissions WHERE role_id = ?";
//     $stmt = $conn->prepare($sql);
//     $stmt->bind_param("i", $roleId);
//     $stmt->execute();
//     $stmt->close();

//     $sql = "INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_approve) VALUES (?, ?, ?, ?, ?, ?, ?)";
//     $stmt = $conn->prepare($sql);
//     foreach ($modules as $module => $perms) {
//         $view = $perms['can_view'] ?? 0;
//         $create = $perms['can_create'] ?? 0;
//         $edit = $perms['can_edit'] ?? 0;
//         $delete = $perms['can_delete'] ?? 0;
//         $approve = $perms['can_approve'] ?? 0;
//         $stmt->bind_param("isiiii", $roleId, $module, $view, $create, $edit, $delete, $approve);
//         $stmt->execute();
//     }
//     return true;
// }

function setRolePermissions(mysqli $conn, int $roleId, array $modules) {
    $sql = "DELETE FROM role_permissions WHERE role_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    $stmt->close();

    $sql = "INSERT INTO role_permissions (role_id, module, can_view, can_create, can_edit, can_delete, can_approve) VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    foreach ($modules as $module => $perms) {
        $view = $perms['can_view'] ?? 0;
        $create = $perms['can_create'] ?? 0;
        $edit = $perms['can_edit'] ?? 0;
        $delete = $perms['can_delete'] ?? 0;
        $approve = $perms['can_approve'] ?? 0;
        $stmt->bind_param("isiiiii", $roleId, $module, $view, $create, $edit, $delete, $approve);
        $stmt->execute();
    }
    return true;
}

function getRolePermissions(mysqli $conn, int $roleId) {
    $sql = "SELECT * FROM role_permissions WHERE role_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $roleId);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

function assignUserRole(mysqli $conn, int $userId, int $roleId, int $assigned_by) {
    $sql = "INSERT INTO user_role_assignments (user_id, role_id, assigned_by) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $userId, $roleId, $assigned_by);
    return $stmt->execute();
}

function revokeUserRole(mysqli $conn, int $userId, int $roleId) {
    $sql = "UPDATE user_role_assignments SET revoked_at = NOW() WHERE user_id = ? AND role_id = ? AND revoked_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $userId, $roleId);
    return $stmt->execute();
}

function getUserRoles(mysqli $conn, int $userId) {
    $sql = "SELECT r.*, ura.assigned_at, ura.revoked_at, u.name AS assigned_by_name 
            FROM user_role_assignments ura 
            JOIN roles r ON ura.role_id = r.id 
            LEFT JOIN users u ON ura.assigned_by = u.id 
            WHERE ura.user_id = ? AND ura.revoked_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    return stmt_fetch_all($stmt);
}

function selectAllUsersWithRoles(mysqli $conn) {
    $sql = "SELECT u.*, b.name AS branch_name,
            (SELECT GROUP_CONCAT(r.name SEPARATOR ', ') FROM user_role_assignments ura JOIN roles r ON ura.role_id = r.id WHERE ura.user_id = u.id AND ura.revoked_at IS NULL) AS assigned_roles
            FROM users u 
            LEFT JOIN branches b ON u.branch_id = b.id 
            WHERE u.deleted_at IS NULL 
            ORDER BY u.name";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function userHasPermission(mysqli $conn, int $userId, string $module, string $action = 'can_view') {
    $sql = "SELECT rp.$action FROM user_role_assignments ura 
            JOIN role_permissions rp ON ura.role_id = rp.role_id 
            WHERE ura.user_id = ? AND rp.module = ? AND ura.revoked_at IS NULL AND rp.$action = 1 
            LIMIT 1";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("is", $userId, $module);
    $stmt->execute();
    $stmt->store_result();
    return $stmt->num_rows > 0;
}

function getLoanAdvisorSuggestion(mysqli $conn, int $userId, float $amount, int $loanTypeId, int $period) {
    $loanType = null;
    $sql = "SELECT * FROM loan_types WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $loanTypeId);
    $stmt->execute();
    $loanType = stmt_fetch_assoc($stmt);

    $savings = 0;
    $sub = selectMinSubByUserIDAndCategory($conn, $userId, 'saving');
    if ($sub && is_array($sub)) {
        $transactions = getMinTransactionByMinSubId($conn, $sub['id']);
        if ($transactions && is_array($transactions)) {
            foreach ($transactions as $t) {
                if ($t['dr_account'] == $sub['id']) $savings += $t['amount'];
                elseif ($t['cr_account'] == $sub['id']) $savings -= $t['amount'];
            }
        }
    }

    $amanaBalance = 0;
    $subAmana = selectMinSubByUserIDAndCategory($conn, $userId, 'amana');
    if ($subAmana && is_array($subAmana)) {
        $transactions = getMinTransactionByMinSubId($conn, $subAmana['id']);
        if ($transactions && is_array($transactions)) {
            foreach ($transactions as $t) {
                if ($t['dr_account'] == $subAmana['id']) $amanaBalance += $t['amount'];
                elseif ($t['cr_account'] == $subAmana['id']) $amanaBalance -= $t['amount'];
            }
        }
    }

    $shareBalance = 0;
    $subShare = selectMinSubByUserIDAndCategory($conn, $userId, 'share');
    if ($subShare && is_array($subShare)) {
        $transactions = getMinTransactionByMinSubId($conn, $subShare['id']);
        if ($transactions && is_array($transactions)) {
            foreach ($transactions as $t) {
                if ($t['dr_account'] == $subShare['id']) $shareBalance += $t['amount'];
                elseif ($t['cr_account'] == $subShare['id']) $shareBalance -= $t['amount'];
            }
        }
    }

    $existingLoans = 0;
    $subLoan = selectMinSubByUserIDAndCategory($conn, $userId, 'loan');
    if ($subLoan && is_array($subLoan)) {
        $transactions = getMinTransactionByMinSubId($conn, $subLoan['id']);
        if ($transactions && is_array($transactions)) {
            foreach ($transactions as $t) {
                if ($t['dr_account'] == $subLoan['id']) $existingLoans += $t['amount'];
                elseif ($t['cr_account'] == $subLoan['id']) $existingLoans -= $t['amount'];
            }
        }
    }

    $totalSavings = $savings + $amanaBalance + $shareBalance;
    $maxLoanBySavings = $totalSavings * 3;
    $interestRate = 12.0;
    $monthlyRate = $interestRate / 12 / 100;
    $monthlyPayment = $amount * $monthlyRate * pow(1 + $monthlyRate, $period) / (pow(1 + $monthlyRate, $period) - 1);
    $totalInterest = ($monthlyPayment * $period) - $amount;
    $totalRepayment = $monthlyPayment * $period;

    return [
        'loan_type_name' => $loanType ? $loanType['name'] : 'Unknown',
        'requested_amount' => $amount,
        'period_months' => $period,
        'interest_rate' => $interestRate,
        'monthly_payment' => round($monthlyPayment, 2),
        'total_interest' => round($totalInterest, 2),
        'total_repayment' => round($totalRepayment, 2),
        'savings_balance' => $savings,
        'amana_balance' => $amanaBalance,
        'share_balance' => $shareBalance,
        'total_savings' => $totalSavings,
        'existing_loan_balance' => $existingLoans,
        'max_loan_based_on_savings' => round($maxLoanBySavings, 2),
        'is_affordable' => $amount <= $maxLoanBySavings,
        'message' => $amount <= $maxLoanBySavings 
            ? 'Based on your savings, you qualify for this loan amount.'
            : 'Your total savings (TZS ' . number_format($totalSavings, 2) . ') allow a maximum loan of TZS ' . number_format($maxLoanBySavings, 2) . '. Consider reducing the amount.',
    ];
}
