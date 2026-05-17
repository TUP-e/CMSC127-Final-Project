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
$orgId       = (int)   ($_SESSION['user']['org_id'] ?? 0);
$userId      = (int)   ($_SESSION['user']['user_id'] ?? 0);
$type        = trim(    $_POST['type']               ?? '');
$amount      = (float) ($_POST['amount']             ?? 0);
$description = trim(    $_POST['description']        ?? '');
$date        = trim(    $_POST['transaction_date']   ?? '');

/* -------------------- CALL -------------------- */
$result = addTransaction($orgId, $userId, $type, $amount, $description, $date);

if ($result['success']) {
    $_SESSION['success'] = "Transaction added successfully.";
} else {
    $_SESSION['error'] = $result['error'];
}

header("Location: ../Dashboards/treasurerDashboard.php");
exit;