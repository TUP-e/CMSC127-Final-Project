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
$transactionId = (int)   ($_POST['transaction_id']   ?? 0);
$userId        = (int)   ($_SESSION['user']['user_id'] ?? 0);
$type          = trim(    $_POST['type']               ?? '');
$amount        = (float) ($_POST['amount']             ?? 0);
$description   = trim(    $_POST['description']        ?? '');
$date          = trim(    $_POST['transaction_date']   ?? '');

if ($transactionId === 0) {
    $_SESSION['error'] = "Invalid transaction.";
    header("Location: ../Dashboards/treasurerDashboard.php");
    exit;
}

/* -------------------- CALL -------------------- */
$result = updateTransaction($transactionId, $userId, $type, $amount, $description, $date);

if ($result['success']) {
    $_SESSION['success'] = "Transaction updated successfully.";
} else {
    $_SESSION['error'] = $result['error'];
}

header("Location: ../Dashboards/treasurerDashboard.php");
exit;