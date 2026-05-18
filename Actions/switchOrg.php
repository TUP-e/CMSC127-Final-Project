<?php
// Actions/switch_org.php
session_start();
require_once "../Core/session.php";
require_once "../Core/redirect.php";

requireLogin();

$org_id = (int)($_POST['org_id'] ?? 0);

if ($org_id === 0) {
    header("Location: ../Dashboards/treasurerDashboard.php");
    exit;
}

$user = $_SESSION['user'];

// Find the org in the user's org list
$matched = null;
foreach ($user['orgs'] as $org) {
    if ((int)$org['org_id'] === $org_id) {
        $matched = $org;
        break;
    }
}

if (!$matched) {
    //  reject if Org not in user's list
    header("Location: ../Dashboards/treasurerDashboard.php");
    exit;
}

// Update session with new current org
$_SESSION['user']['current_org_id'] = $matched['org_id'];
$_SESSION['user']['org_id']         = $matched['org_id'];
$_SESSION['user']['org_name']       = $matched['org_name'];
$_SESSION['user']['role']           = $matched['role'];

redirectByRole($_SESSION['user']); // Redirect to appropriate dashboard based on new session info