<?php
    session_start();

    $email = $_POST['email'];
    $password = $_POST['password'];

    $conn = new mysqli('127.0.0.1', 'root', '', 'student_org');

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    $sql = "SELECT u.user_id, u.password_hash, ur.role, ur.org_id
            FROM users u
            LEFT JOIN user_roles ur ON u.user_id = ur.user_id
            WHERE u.email = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "Email not found";
        exit;
    }

    $user = $result->fetch_assoc();

    if (!password_verify($password, $user['password_hash'])) {
        echo "Password incorrect";
        exit;
    }

    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['email'] = $email;
    $_SESSION['role'] = $user['role'];
    $_SESSION['org_id'] = $user['org_id'];

    if ($user['role'] === 'treasurer') {
        header('Location: treasurerDashboard.php');
    } else {
        header('Location: otherMemberDashboard.php');
    }

    $conn->close();
    exit;
?>
