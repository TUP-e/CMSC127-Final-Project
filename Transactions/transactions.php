<?php

// All transaction DB functions here 

require_once __DIR__ . "/../DBConnector.php";

/* ==================== READ Functions ==================== */

function getAllTransactions(int $orgId, array $filters = []): array {
    $db   = new DBConnector();
    $conn = $db->connect();

    $where  = ["t.org_id = ?"];
    $params = [$orgId];
    $types  = "i";

    if (!empty($filters['type'])) {
        $where[]  = "td.type = ?";
        $params[] = $filters['type'];
        $types   .= "s";
    }
    if (!empty($filters['date_from'])) {
        $where[]  = "td.transaction_date >= ?";
        $params[] = $filters['date_from'];
        $types   .= "s";
    }
    if (!empty($filters['date_to'])) {
        $where[]  = "td.transaction_date <= ?";
        $params[] = $filters['date_to'];
        $types   .= "s";
    }

    $whereSQL = implode(" AND ", $where);

    $stmt = $conn->prepare("
        SELECT  t.transaction_id,
                t.created_at,
                t.is_locked,
                u.name             AS entered_by_name,
                td.type,
                td.amount,
                td.description,
                td.transaction_date
        FROM transactions t
        JOIN transaction_details td ON td.transaction_id = t.transaction_id
        JOIN users u                ON u.user_id         = t.entered_by
        WHERE {$whereSQL}
        ORDER BY td.transaction_date DESC, t.created_at DESC
    ");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function getTransactionById(int $transactionId): array|false {
    $db   = new DBConnector();
    $conn = $db->connect();

    $stmt = $conn->prepare("
        SELECT  t.transaction_id,
                t.org_id,
                t.entered_by,
                t.created_at,
                t.is_locked,
                td.type,
                td.amount,
                td.description,
                td.transaction_date
        FROM transactions t
        JOIN transaction_details td ON td.transaction_id = t.transaction_id
        WHERE t.transaction_id = ?
    ");
    $stmt->bind_param("i", $transactionId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

function getOrgSummary(int $orgId): array {
    $db   = new DBConnector();
    $conn = $db->connect();

    $stmt = $conn->prepare("
        SELECT  o.name,
                o.description,
                o.starting_balance,
                o.current_balance,
                COALESCE(SUM(CASE WHEN td.type = 'income'  THEN td.amount ELSE 0 END), 0) AS total_income,
                COALESCE(SUM(CASE WHEN td.type = 'expense' THEN td.amount ELSE 0 END), 0) AS total_expenses
        FROM organizations o
        LEFT JOIN transactions t         ON t.org_id          = o.org_id
        LEFT JOIN transaction_details td ON td.transaction_id = t.transaction_id
        WHERE o.org_id = ?
        GROUP BY o.org_id
    ");
    $stmt->bind_param("i", $orgId);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?? [];
}

function getAuditLog(int $orgId): array {
    $db   = new DBConnector();
    $conn = $db->connect();

    $stmt = $conn->prepare("
        SELECT  al.log_id,
                al.action,
                al.changed_at,
                al.transaction_id,
                u.name              AS changed_by_name,
                ao.type             AS old_type,
                ao.amount           AS old_amount,
                ao.description      AS old_description,
                ao.tx_date          AS old_date,
                an.type             AS new_type,
                an.amount           AS new_amount,
                an.description      AS new_description,
                an.tx_date          AS new_date
        FROM transaction_audit_log al
        JOIN transactions t                ON t.transaction_id  = al.transaction_id
        JOIN users u                       ON u.user_id         = al.changed_by
        LEFT JOIN transaction_audit_old ao ON ao.log_id         = al.log_id
        LEFT JOIN transaction_audit_new an ON an.log_id         = al.log_id
        WHERE t.org_id = ?
        ORDER BY al.changed_at DESC
        LIMIT 100
    ");
    $stmt->bind_param("i", $orgId);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/* ==================== CREATE Functions ==================== */

function addTransaction(
    int    $orgId,
    int    $userId,
    string $type,
    float  $amount,
    string $description,
    string $date
): array {
    $errors = _validateInput($type, $amount, $date);
    if (!empty($errors)) {
        return ['success' => false, 'error' => implode(' ', $errors)];
    }

    $db   = new DBConnector();
    $conn = $db->connect();
    $conn->begin_transaction();

    try {
        // Insert header row
        $stmt = $conn->prepare("INSERT INTO transactions (org_id, entered_by) VALUES (?, ?)");
        $stmt->bind_param("ii", $orgId, $userId);
        $stmt->execute();
        $transactionId = $conn->insert_id;
        $stmt->close();

        // Insert detail row
        $stmt = $conn->prepare("
            INSERT INTO transaction_details (transaction_id, type, amount, description, transaction_date)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("isdss", $transactionId, $type, $amount, $description, $date);
        $stmt->execute();
        $stmt->close();

        // Audit — INSERT has no old snapshot
        $logId = _insertAuditLog($conn, $transactionId, $userId, 'INSERT');
        _insertAuditNew($conn, $logId, $type, $amount, $description, $date);

        _recalcBalance($conn, $orgId);

        $conn->commit();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        return ['success' => false, 'error' => 'Failed to add transaction.'];
    }
}

/* ==================== UPDATE Functions ==================== */

function updateTransaction(
    int    $transactionId,
    int    $userId,
    string $type,
    float  $amount,
    string $description,
    string $date
): array {
    $errors = _validateInput($type, $amount, $date);
    if (!empty($errors)) {
        return ['success' => false, 'error' => implode(' ', $errors)];
    }

    $existing = getTransactionById($transactionId);
    if (!$existing)             return ['success' => false, 'error' => 'Transaction not found.'];
    if ($existing['is_locked']) return ['success' => false, 'error' => 'Transaction is locked and cannot be edited.'];

    $db   = new DBConnector();
    $conn = $db->connect();
    $conn->begin_transaction();

    try {
        // Snapshot old values first
        $logId = _insertAuditLog($conn, $transactionId, $userId, 'UPDATE');
        _insertAuditOld($conn, $logId,
            $existing['type'],
            (float) $existing['amount'],
            $existing['description'],
            $existing['transaction_date']
        );

        // Update details
        $stmt = $conn->prepare("
            UPDATE transaction_details
            SET type = ?, amount = ?, description = ?, transaction_date = ?
            WHERE transaction_id = ?
        ");
        $stmt->bind_param("sdssi", $type, $amount, $description, $date, $transactionId);
        $stmt->execute();
        $stmt->close();

        // Snapshot new values
        _insertAuditNew($conn, $logId, $type, $amount, $description, $date);

        _recalcBalance($conn, $existing['org_id']);

        $conn->commit();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        return ['success' => false, 'error' => 'Failed to update transaction.'];
    }
}

/* ==================== DELETE Functions ==================== */

function deleteTransaction(int $transactionId, int $userId): array {
    $existing = getTransactionById($transactionId);
    if (!$existing)             return ['success' => false, 'error' => 'Transaction not found.'];
    if ($existing['is_locked']) return ['success' => false, 'error' => 'Transaction is locked and cannot be deleted.'];

    $db   = new DBConnector();
    $conn = $db->connect();
    $conn->begin_transaction();

    try {
        $orgId = $existing['org_id'];

        // Snapshot old values 
        $logId = _insertAuditLog($conn, $transactionId, $userId, 'DELETE');
        _insertAuditOld($conn, $logId,
            $existing['type'],
            (float) $existing['amount'],
            $existing['description'],
            $existing['transaction_date']
        );

        // Delete cascades to transaction_details automatically
        $stmt = $conn->prepare("DELETE FROM transactions WHERE transaction_id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        $stmt->close();

        _recalcBalance($conn, $orgId);

        $conn->commit();
        return ['success' => true, 'error' => null];

    } catch (Exception $e) {
        $conn->rollback();
        error_log($e->getMessage());
        return ['success' => false, 'error' => 'Failed to delete transaction.'];
    }
}

/* ==================== LOCK Functions ==================== */

function lockOldTransactions(): void {
    $db   = new DBConnector();
    $conn = $db->connect();

    $conn->query("
        UPDATE transactions
        SET is_locked = TRUE
        WHERE is_locked = FALSE
          AND created_at < NOW() - INTERVAL 24 HOUR
    ");
}

/* ==================== PRIVATE HELPERS ==================== */

function _validateInput(string $type, float $amount, string $date): array {
    $errors = [];
    if (!in_array($type, ['income', 'expense'], true)) $errors[] = 'Type must be income or expense.';
    if ($amount <= 0)                                   $errors[] = 'Amount must be greater than zero.';
    if (!DateTime::createFromFormat('Y-m-d', $date))    $errors[] = 'Invalid date.';
    return $errors;
}

function _insertAuditLog(mysqli $conn, int $txId, int $userId, string $action): int {
    $stmt = $conn->prepare("
        INSERT INTO transaction_audit_log (transaction_id, changed_by, action)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("iis", $txId, $userId, $action);
    $stmt->execute();
    $logId = $conn->insert_id;
    $stmt->close();
    return $logId;
}

function _insertAuditOld(mysqli $conn, int $logId, string $type, float $amount, ?string $desc, string $date): void {
    $stmt = $conn->prepare("
        INSERT INTO transaction_audit_old (log_id, type, amount, description, tx_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isdss", $logId, $type, $amount, $desc, $date);
    $stmt->execute();
    $stmt->close();
}


function _insertAuditNew(mysqli $conn, int $logId, string $type, float $amount, ?string $desc, string $date): void {
    $stmt = $conn->prepare("
        INSERT INTO transaction_audit_new (log_id, type, amount, description, tx_date)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("isdss", $logId, $type, $amount, $desc, $date);
    $stmt->execute();
    $stmt->close();
}

// function to recalculate current balance after any change
function _recalcBalance(mysqli $conn, int $orgId): void {
    $stmt = $conn->prepare("
        UPDATE organizations
        SET current_balance = starting_balance + COALESCE((
            SELECT SUM(CASE WHEN td.type = 'income'  THEN  td.amount ELSE 0 END) -
                   SUM(CASE WHEN td.type = 'expense' THEN  td.amount ELSE 0 END)
            FROM transactions t
            JOIN transaction_details td ON td.transaction_id = t.transaction_id
            WHERE t.org_id = ?
        ), 0)
        WHERE org_id = ?
    ");
    $stmt->bind_param("ii", $orgId, $orgId);
    $stmt->execute();
    $stmt->close();
}

