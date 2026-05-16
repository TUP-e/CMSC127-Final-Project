<?php
require_once __DIR__ . '/../DBConnector.php';

function findUserByEmail($email) {
    $db = new DBConnector();
    $conn = $db->connect();

    $stmt = $conn->prepare("
    SELECT u.user_id, u.name, u.email, u.password_hash, ur.role, ur.org_id
    FROM users u
    JOIN user_roles ur ON u.user_id = ur.user_id
    WHERE u.email = ?
    LIMIT 1
    ");
    
    $stmt->bind_param("s", $email);
    $stmt->execute();

    return $stmt->get_result()->fetch_assoc();
}

function verifyUser($email, $password) {
    $user = findUserByEmail($email);

    if ($user && password_verify($password, $user['password_hash'])) {
        return $user;
    }

    return false;
}