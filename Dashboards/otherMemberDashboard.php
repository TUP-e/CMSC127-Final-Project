<?php
require_once "../Core/session.php";
require_once "../Transactions/transactions.php";

requireLogin();

// Treasurer ?? get outta here!
if (isTreasurer()) {
    header("Location: treasurerDashboard.php");
    exit;
}

$user   = $_SESSION['user'];
$orgId  = (int) $user['org_id'];

$summary = getOrgSummary($orgId);

/* -------------------- FILTERS -------------------- */
$filters = [
    'type'      => $_GET['type']      ?? '',
    'date_from' => $_GET['date_from'] ?? '',
    'date_to'   => $_GET['date_to']   ?? '',
];
$transactions = getAllTransactions($orgId, array_filter($filters));
$auditLog     = getAuditLog($orgId);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="../Forms/auth.css">
</head>
<body>

    <!-- -------------------- HEADER -------------------- -->
    <header>
    <h1>
        <strong><?= htmlspecialchars($summary['name'] ?? '') ?></strong>
        <br>Org ID: <strong><?= htmlspecialchars($orgId) ?></strong>
    </h1>
    
    <div class="user-info">
        Logged in as: <strong><?= htmlspecialchars($user['name']) ?></strong>
        | Role: <strong><?= htmlspecialchars($user['role']) ?></strong>
        | <a href="../Actions/logout.php">Logout</a>
    </div>


    <!-- -------------------- ORG SWITCH -------------------- -->
    <?php
    $user        = $_SESSION['user'];
    $currentOrg  = (int)$user['current_org_id'];
    $orgs        = $user['orgs'];
    ?>
    <?php if (count($orgs) > 1): ?>
    <form method="POST" action="../Actions/switchOrg.php">
        <label>Switch Org:
            <select name="org_id" onchange="this.form.submit()">
                <?php foreach ($orgs as $org): ?>
                    <option value="<?= $org['org_id'] ?>"
                        <?= (int)$org['org_id'] === $currentOrg ? 'selected' : '' ?>>
                        <?= htmlspecialchars($org['org_name']) ?>
                        (<?= htmlspecialchars($org['role']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </label>
    </form>
    <?php else: ?>
        <span><?= htmlspecialchars($orgs[0]['org_name'] ?? '') ?></span>
    <?php endif; ?>
    
    </header>


    <!-- -------------------- SUMMARY -------------------- -->
    <h2>Balance Summary</h2>
    <table border="1" cellpadding="6">
        <tr>
            <th>Starting Balance</th>
            <th>Total Income</th>
            <th>Total Expenses</th>
            <th>Current Balance</th>
        </tr>
        <tr>
            <td>Php <?= number_format($summary['starting_balance'] ?? 0, 2) ?></td>
            <td style="color:green;">+Php <?= number_format($summary['total_income'] ?? 0, 2) ?></td>
            <td style="color:red;">-Php <?= number_format($summary['total_expenses'] ?? 0, 2) ?></td>
            <td><strong>Php <?= number_format($summary['current_balance'] ?? 0, 2) ?></strong></td>
        </tr>
    </table>

    <hr>

    <!-- -------------------- FILTERS -------------------- -->
    <h2>Transaction Log</h2>
    <form method="GET" action="otherMemberDashboard.php">
        <label>Type:
            <select name="type">
                <option value="">All</option>
                <option value="income"  <?= ($filters['type']) === 'income'  ? 'selected' : '' ?>>Income</option>
                <option value="expense" <?= ($filters['type']) === 'expense' ? 'selected' : '' ?>>Expense</option>
            </select>
        </label>
        
        <label>From: <input type="date" name="date_from" value="<?= htmlspecialchars($filters['date_from']) ?>"></label>
      
        <label>To: <input type="date" name="date_to" value="<?= htmlspecialchars($filters['date_to']) ?>"></label>
        
        <button type="submit">Filter</button>
        <a href="otherMemberDashboard.php"><button type="button">Clear</button></a>
    </form>

    <!-- -------------------- TRANSACTIONS TABLE -------------------- -->
    <?php if (empty($transactions)): ?>
        <p>No transactions found.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Date</th>
                    <th>Type</th>
                    <th>Amount</th>
                    <th>Description</th>
                    <th>Entered By</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $tx): ?>
                <tr>
                    <td><?= $tx['transaction_id'] ?></td>
                    <td><?= htmlspecialchars($tx['transaction_date']) ?></td>
                    <td><?= htmlspecialchars($tx['type']) ?></td>
                    <td style="color:<?= $tx['type'] === 'income' ? 'green' : 'red' ?>">
                        <?= $tx['type'] === 'income' ? '+' : '-' ?>Php <?= number_format($tx['amount'], 2) ?>
                    </td>
                    <td><?= htmlspecialchars($tx['description'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($tx['entered_by_name']) ?></td>
                    <td><?= $tx['is_locked'] ? 'Locked' : 'Active' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <hr>

    <!-- -------------------- AUDIT LOG -------------------- -->
    <h2>Audit Log</h2>
    <?php if (empty($auditLog)): ?>
        <p>No audit history yet.</p>
    <?php else: ?>
        <table border="1" cellpadding="6">
            <thead>
                <tr>
                    <th>Log ID</th>
                    <th>Transaction ID</th>
                    <th>Action</th>
                    <th>Changed By</th>
                    <th>When</th>
                    <th>Before</th>
                    <th>After</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auditLog as $log): ?>
                <tr>
                    <td><?= $log['log_id'] ?></td>
                    <td><?= $log['transaction_id'] ?></td>
                    <td><?= htmlspecialchars($log['action']) ?></td>
                    <td><?= htmlspecialchars($log['changed_by_name']) ?></td>
                    <td><?= htmlspecialchars($log['changed_at']) ?></td>
                    <td>
                        <?php if ($log['old_type']): ?>
                            <?= htmlspecialchars($log['old_type']) ?>
                            Php <?= number_format($log['old_amount'], 2) ?>
                            (<?= htmlspecialchars($log['old_date']) ?>)
                            <?= $log['old_description'] ? '— ' . htmlspecialchars($log['old_description']) : '' ?>
                        <?php else: ?> — <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($log['new_type']): ?>
                            <?= htmlspecialchars($log['new_type']) ?>
                            Php <?= number_format($log['new_amount'], 2) ?>
                            (<?= htmlspecialchars($log['new_date']) ?>)
                            <?= $log['new_description'] ? '— ' . htmlspecialchars($log['new_description']) : '' ?>
                        <?php else: ?> — <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

</body>
</html>