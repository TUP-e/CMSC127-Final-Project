<?php
require_once "../Core/session.php";
require_once "transactions.php";

requireLogin();
requireTreasurer();

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Dashboards/treasurerDashboard.php");
    exit;
}

/* -------------------- INPUTS -------------------- */
$transactionId = (int) ($_POST['transaction_id']    ?? 0);
$userId        = (int) ($_SESSION['user']['user_id'] ?? 0);

if ($transactionId === 0) {
    $_SESSION['error'] = "Invalid transaction.";
    header("Location: ../Dashboards/treasurerDashboard.php");
    exit;
}

/* -------------------- CALL -------------------- */
$result = deleteTransaction($transactionId, $userId);

if ($result['success']) {
    $_SESSION['success'] = "Transaction deleted.";
} else {
    $_SESSION['error'] = $result['error'];
}

header("Location: ../Dashboards/treasurerDashboard.php");
exit;