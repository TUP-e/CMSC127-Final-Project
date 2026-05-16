<?php
// Core session management and auth helpers
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Login user by storing their info in session
function login($user) {
    $_SESSION['user'] = $user;
}

/*  maybe a seperate module for this

function logout() {
    $_SESSION = [];
    session_destroy();
}

*/

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user']);
}

// Helper to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: ../Forms/login_form.php");
        exit;
    }
}

// Check if user is treasurer
function isTreasurer() {
    return isLoggedIn() && $_SESSION['user']['role'] === 'treasurer';
}

// Helper to require treasurer role
function requireTreasurer() {
    requireLogin();

    if (!isTreasurer()) {
        die("Unauthorized");
    }
}

