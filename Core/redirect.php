<?php

function redirectByRole($user) {
    if ($user['role'] === 'treasurer') {
        header("Location: ../Dashboards/treasurerDashboard.php");
    } else {
        header("Location: ../Dashboards/otherMemberDashboard.php");
    }
    exit;
}
