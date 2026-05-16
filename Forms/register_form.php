<?php
require_once "../DBConnector.php";

$db = new DBConnector();
$conn = $db->connect();

$success = false;
$error = "";

/* -------------------- FETCH ORGANIZATIONS -------------------- */
$orgs = [];

$result = $conn->query("SELECT org_id, name FROM organizations");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $orgs[] = $row;
    }
} else {
    die("Failed to fetch organizations: " . $conn->error);
}

/* -------------------- HANDLE FORM -------------------- */
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $name           = trim($_POST["full_name"]      ?? '');
    $student_number = trim($_POST["student_number"] ?? '');
    $email          = trim($_POST["email"]          ?? '');
    $password       = $_POST["password"]            ?? '';
    $confirm        = $_POST["confirm_password"]    ?? '';
    $role           = $_POST["role"]                ?? '';
    $org_id         = (int) ($_POST["org_id"]       ?? 0);


    // -------------------- VALIDATE --------------------
    $allowed_roles = ['treasurer', 'officer', 'member', 'adviser'];

    if (!$name || !$student_number || !$email || !$password || !$role || !$org_id) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match.";
    } elseif (!in_array($role, $allowed_roles, true)) {
        $error = "Invalid role selected.";
    } else {

        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $conn->begin_transaction();

        try {

            // -------------------- CHECK DUPLICATE --------------------
            $check = $conn->prepare("
                SELECT user_id FROM users
                WHERE student_number = ? OR email = ?
                LIMIT 1
            ");
            $check->bind_param("ss", $student_number, $email);
            $check->execute();
            $check->store_result();

            if ($check->num_rows > 0) {
                $error = "A user with that student number or email already exists.";
                $conn->rollback();
            } else {
                $check->close();

                // -------------------- INSERT USER --------------------
                $stmt = $conn->prepare("
                    INSERT INTO users (student_number, name, email, password_hash)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->bind_param("ssss", $student_number, $name, $email, $password_hash);
                $stmt->execute();
                $user_id = $stmt->insert_id;
                $stmt->close();

                // -------------------- INSERT ROLE --------------------
                $stmt2 = $conn->prepare("
                    INSERT INTO user_roles (user_id, org_id, role)
                    VALUES (?, ?, ?)
                ");
                $stmt2->bind_param("iis", $user_id, $org_id, $role);
                $stmt2->execute();
                $stmt2->close();

                $conn->commit();
                $success = true;
            }

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Registration failed. Please try again.";
            error_log($e->getMessage());
        }
    }
}

// -------------------- keep values on error --------------------
function old(string $key, string $default = ''): string {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return $default;
    return htmlspecialchars(trim($_POST[$key] ?? $default));
}


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

        <h1>Register Student</h1>

        <?php if ($success): ?>
            <p class="success-msg">
                Account created successfully. <a href="login_form.php">Sign in here</a>.
            </p>
        <?php endif; ?>

        <?php if ($error): ?>
            <p class="error-msg"><?= htmlspecialchars($error) ?></p>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="POST" action="register.php">

            <input type="text"
                   name="full_name"
                   placeholder="Full Name"
                   value="<?= old('full_name') ?>"
                   required>

            <input type="text"
                   name="student_number"
                   placeholder="Student Number"
                   value="<?= old('student_number') ?>"
                   required>

            <input type="email"
                   name="email"
                   placeholder="Email Address"
                   value="<?= old('email') ?>"
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
            <div class="role-selection-group">
            <p>Register as:</p>
                

                <label>
                    <input type="radio" name="role" value="treasurer"
                        <?= old('role') === 'treasurer' ? 'checked' : '' ?> required>
                    Treasurer
                </label>

                <label>
                    <input type="radio" name="role" value="officer"
                        <?= old('role') === 'officer' ? 'checked' : '' ?>>
                    Officer
                </label>

                <label>
                    <input type="radio" name="role" value="member"
                        <?= old('role') === 'member' ? 'checked' : '' ?>>
                    Member
                </label>

                <label>
                    <input type="radio" name="role" value="adviser"
                        <?= old('role') === 'adviser' ? 'checked' : '' ?>>
                    Adviser
                </label>


            </div>

            <!-- -------------------- ORGANIZATION (Dropdown selection) -------------------- -->
             
            <select name="org_id" required>
                <option value=""> Select Organization </option>
                <?php foreach ($orgs as $org): ?>
                    <option value="<?= $org['org_id'] ?>"
                        <?= old('org_id') == $org['org_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($org['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit">Register</button>

        </form>
        <?php endif; ?>

        <p>Already have an account? <a href="login_form.php">Sign in</a></p>

    </div>
</div>

</body>
</html>