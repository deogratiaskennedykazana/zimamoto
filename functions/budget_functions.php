<?php
// ============================================================
//  BUDGET FUNCTIONS — Zima-Moto
// ============================================================

function createBudget(mysqli $conn, string $ref_no, string $year, string $description, float $total_amount, string $status, string $notes, int $created_by) {
    $sql = "INSERT INTO `budget` (`ref_no`, `year`, `descreption`, `total_amount`, `status`, `notes`, `created_by`)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return "Prepare failed: " . $conn->error;
    $stmt->bind_param("sssdssi", $ref_no, $year, $description, $total_amount, $status, $notes, $created_by);
    if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); return $id; }
    $err = $stmt->error; $stmt->close(); return $err;
}

function createBudgetItem(mysqli $conn, int $budget_id, int $sub_id, string $description, float $amount, int $created_by) {
    $sql = "INSERT INTO `budget_items` (`budget_id`, `sub_id`, `description`, `amount`, `created_by`)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return "Prepare failed: " . $conn->error;
    $stmt->bind_param("iisdi", $budget_id, $sub_id, $description, $amount, $created_by);
    if ($stmt->execute()) { $id = $stmt->insert_id; $stmt->close(); return $id; }
    $err = $stmt->error; $stmt->close(); return $err;
}

function updateBudget(mysqli $conn, int $id, string $ref_no, string $year, string $description, float $total_amount, string $status, string $notes, int $updated_by) {
    $sql = "UPDATE `budget` SET `ref_no`=?, `year`=?, `descreption`=?, `total_amount`=?, `status`=?, `notes`=?, `updated_by`=?, `updated_at`=NOW() WHERE `id`=? AND `deleted_at` IS NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("sssdssii", $ref_no, $year, $description, $total_amount, $status, $notes, $updated_by, $id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function updateBudgetItem(mysqli $conn, int $id, int $budget_id, int $sub_id, string $description, float $amount, int $updated_by) {
    $sql = "UPDATE `budget_items` SET `budget_id`=?, `sub_id`=?, `description`=?, `amount`=?, `updated_by`=?, `updated_at`=NOW() WHERE `id`=? AND `deleted_at` IS NULL";
    $stmt = $conn->prepare($sql);
    if (!$stmt) return false;
    $stmt->bind_param("iisdii", $budget_id, $sub_id, $description, $amount, $updated_by, $id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function selectAllBudgets(mysqli $conn) {
    $sql = "SELECT b.*, u1.name AS created_by_name, u2.name AS approved_by_name, u3.name AS rejected_by_name
            FROM budget b
            LEFT JOIN users u1 ON b.created_by  = u1.id
            LEFT JOIN users u2 ON b.approved_by = u2.id
            LEFT JOIN users u3 ON b.rejected_by = u3.id
            WHERE b.deleted_at IS NULL ORDER BY b.created_at DESC";
    $result = $conn->query($sql);
    return $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

function selectBudgetByStatus(mysqli $conn, string $status) {
    $sql = "SELECT b.*, u1.name AS created_by_name FROM budget b
            LEFT JOIN users u1 ON b.created_by = u1.id
            WHERE b.status = ? AND b.deleted_at IS NULL ORDER BY b.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();
    return $data;
}

function selectBudgetById(mysqli $conn, int $id) {
    $sql = "SELECT b.*, u1.name AS created_by_name, u2.name AS approved_by_name, u3.name AS rejected_by_name
            FROM budget b
            LEFT JOIN users u1 ON b.created_by  = u1.id
            LEFT JOIN users u2 ON b.approved_by = u2.id
            LEFT JOIN users u3 ON b.rejected_by = u3.id
            WHERE b.id = ? AND b.deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $data = stmt_fetch_assoc($stmt);
    $stmt->close();
    return $data;
}

function selectBudgetItems(mysqli $conn, int $budget_id) {
    $sql = "SELECT bi.*, u1.name AS created_by_name FROM budget_items bi
            LEFT JOIN users u1 ON bi.created_by = u1.id
            WHERE bi.budget_id = ? AND bi.deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $budget_id);
    $stmt->execute();
    $data = stmt_fetch_all($stmt);
    $stmt->close();
    return $data;
}

function softDeleteBudget(mysqli $conn, int $id, int $deleted_by) {
    $sql = "UPDATE `budget` SET deleted_at=NOW(), deleted_by=? WHERE id=? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $deleted_by, $id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function softDeleteBudgetItemById(mysqli $conn, int $id, int $deleted_by) {
    $sql = "UPDATE `budget_items` SET deleted_at=NOW(), deleted_by=? WHERE id=? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $deleted_by, $id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function softDeleteBudgetItemsByBudgetId(mysqli $conn, int $budget_id, int $deleted_by) {
    $sql = "UPDATE `budget_items` SET deleted_at=NOW(), deleted_by=? WHERE budget_id=? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $deleted_by, $budget_id);
    $ok = $stmt->execute(); $stmt->close(); return $ok;
}

function selectBudgetByYear(mysqli $conn, string $year) {
    $sql = "SELECT * FROM budget WHERE year = ? AND deleted_at IS NULL LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $year);
    $stmt->execute();
    return stmt_fetch_assoc($stmt);
}

function countBudgetsByStatus(mysqli $conn, string $status) {
    $sql = "SELECT COUNT(*) AS cnt FROM budget WHERE status=? AND deleted_at IS NULL";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $row = stmt_fetch_assoc($stmt);
    $stmt->close();
    return $row['cnt'] ?? 0;
}
