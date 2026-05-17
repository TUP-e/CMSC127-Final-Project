<?php
session_start();
require_once "../DBConnector.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../Forms/register_form.php");
    exit;
}

/* -------------------- INPUTS -------------------- */
$name           = trim($_POST["full_name"]        ?? '');
$student_number = trim($_POST["student_number"]   ?? '');
$email          = trim($_POST["email"]            ?? '');
$password       =      $_POST["password"]         ?? '';
$confirm        =      $_POST["confirm_password"] ?? '';
$role           =      $_POST["role"]             ?? '';
$org_id         = (int)($_POST["org_id"]          ?? 0);

$allowed_roles  = ['treasurer', 'officer', 'member', 'adviser'];

/* -------------------- VALIDATE -------------------- */
if (!$name || !$student_number || !$email || !$password || !$role || !$org_id) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../Forms/register_form.php");
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email address.";
    header("Location: ../Forms/register_form.php");
    exit;
}
if (strlen($password) < 8) {
    $_SESSION['error'] = "Password must be at least 8 characters.";
    header("Location: ../Forms/register_form.php");
    exit;
}
if ($password !== $confirm) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: ../Forms/register_form.php");
    exit;
}
if (!in_array($role, $allowed_roles, true)) {
    $_SESSION['error'] = "Invalid role selected.";
    header("Location: ../Forms/register_form.php");
    exit;
}

/* -------------------- DB -------------------- */
$db   = new DBConnector();
$conn = $db->connect();
$conn->begin_transaction();

try {
    // -------------------- CHECK ORG EXISTS --------------------
    $orgCheck = $conn->prepare("SELECT org_id FROM organizations WHERE org_id = ?");
    $orgCheck->bind_param("i", $org_id);
    $orgCheck->execute();
    $orgCheck->store_result();

    if ($orgCheck->num_rows === 0) {
        $conn->rollback();
        $_SESSION['error'] = "Organization ID not found. Please check and try again.";
        header("Location: ../Forms/register_form.php");
        exit;
    }
    $orgCheck->close();

    // -------------------- CHECK IF USER ALREADY EXISTS --------------------
    $check = $conn->prepare("
        SELECT user_id FROM users
        WHERE email = ? OR student_number = ?
        LIMIT 1
    ");
    $check->bind_param("ss", $email, $student_number);
    $check->execute();
    $existingUser = $check->get_result()->fetch_assoc();
    $check->close();

    if ($existingUser) {
        // ── User exists ──
        $user_id = $existingUser['user_id'];

        // Check if already in this org
        $roleCheck = $conn->prepare("
            SELECT user_id FROM user_roles
            WHERE user_id = ? AND org_id = ?
            LIMIT 1
        ");
        $roleCheck->bind_param("ii", $user_id, $org_id);
        $roleCheck->execute();
        $roleCheck->store_result();

        if ($roleCheck->num_rows > 0) {
            $conn->rollback();
            $_SESSION['error'] = "You are already a member of this organization.";
            header("Location: ../Forms/register_form.php");
            exit;
        }
        $roleCheck->close();

    } else {
        // ── New user ──
        $password_hash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("
            INSERT INTO users (student_number, name, email, password_hash)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->bind_param("ssss", $student_number, $name, $email, $password_hash);
        $stmt->execute();
        $user_id = $conn->insert_id;
        $stmt->close();
    }

    // -------------------- INSERT ROLE --------------------
    $stmt2 = $conn->prepare("
        INSERT INTO user_roles (user_id, org_id, role)
        VALUES (?, ?, ?)
    ");
    $stmt2->bind_param("iis", $user_id, $org_id, $role);
    $stmt2->execute();
    $stmt2->close();

    $conn->commit();

    $_SESSION['success'] = "Registered successfully. You can now log in.";
    header("Location: ../Forms/login_form.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    error_log($e->getMessage());
    $_SESSION['error'] = "Registration failed. Please try again.";
    header("Location: ../Forms/register_form.php");
    exit;
}