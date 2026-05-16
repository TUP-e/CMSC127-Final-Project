<?php
// Forms/register_form.php
session_start();
require_once "../DBConnector.php";

/* -------------------- FLASH MESSAGES -------------------- */
$error   = $_SESSION['error']   ?? null; unset($_SESSION['error']);
$success = $_SESSION['success'] ?? null; unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <link rel="stylesheet" href="auth.css">
</head>
<body>

<div class="auth-page-wrapper">
    <div class="auth-form-container">

        <h1>Get Started!</h1>

        <?php if ($error): ?>
            <p style="color:red;"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if ($success): ?>
            <p style="color:green;"><?= htmlspecialchars($success) ?></p>
        <?php endif; ?>

        <form method="POST" action="../Actions/register.php">

            <input type="text"
                   name="full_name"
                   placeholder="Full Name"
                   required>

            <input type="text"
                   name="student_number"
                   placeholder="Student ID Number"
                   required>

            <input type="email"
                   name="email"
                   placeholder="Email Address"
                   required>

            <input type="password"
                   name="password"
                   placeholder="Password (min. 8 characters)"
                   required>

            <input type="password"
                   name="confirm_password"
                   placeholder="Confirm Password"
                   required>

            <!-- -------------------- ROLE -------------------- -->
            <p class="role-label">Register as:</p>
            <div class="role-selection-group">

                <label>
                    <input type="radio" name="role" value="treasurer" required>
                    Treasurer
                </label>

                <label>
                    <input type="radio" name="role" value="officer">
                    Officer
                </label>

                <label>
                    <input type="radio" name="role" value="member">
                    Member
                </label>

                <label>
                    <input type="radio" name="role" value="adviser">
                    Adviser
                </label>

            </div>

            <!-- -------------------- ORGANIZATION -------------------- -->
            <input type="text"
                name="org_id"
                placeholder="Enter Organization ID"
                required>

            <button type="submit">Create Account</button>

        </form>

        <p>Already have an account? <a href="login_form.php">Sign in</a></p>

    </div>
</div>

</body>
</html>