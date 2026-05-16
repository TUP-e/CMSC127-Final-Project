<?php

session_start();
require_once "../Core/auth.php";
require_once "../Core/redirect.php";
require_once "../Core/session.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Forms/login_form.php");
    exit;
}

/* -------------------- INPUTS -------------------- */
$email    = trim($_POST["email"]    ?? '');
$password =      $_POST["password"] ?? '';

/* -------------------- VALIDATE -------------------- */
if (!$email || !$password) {
    $_SESSION['error'] = "Email and password are required.";
    header("Location: ../Forms/login_form.php");
    exit;
}

/* -------------------- VERIFY -------------------- */
$user = verifyUser($email, $password);

if (!$user) {
    $_SESSION['error'] = "Invalid email or password.";
    header("Location: ../Forms/login_form.php");
    exit;
}

/* -------------------- SET SESSION -------------------- */
session_regenerate_id(true);
$_SESSION['user'] = $user;

/* -------------------- REDIRECT BY ROLE -------------------- */
redirectByRole($user); 