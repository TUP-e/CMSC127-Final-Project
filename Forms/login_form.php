<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>

    <link rel="stylesheet" href="auth.css">
</head>
<body>


    <div class="auth-page-wrapper">
        <div class="auth-form-container">

            <h1>Welcome Back!</h1>

            <!-- ERROR MESSAGE -->
            <?php if (isset($_SESSION['error'])): ?>
                <p class="error-msg">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </p>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- LOGIN FORM -->
            <form method="POST" action="../Actions/login.php">

                <!-- Email -->
                <input 
                    type="email" 
                    name="email"
                    placeholder="Email Address"
                    required
                >

                <!-- Password -->
                <input 
                    type="password" 
                    name="password"
                    placeholder="Password"
                    required
                >

                <!-- Submit -->
                <button type="submit">Login</button>

            </form>

            <!-- Redirect to register -->
            <p>
                Don't have an account? 
                <a href="register_form.php">Register here</a>
            </p>

        </div>
    </div>

</body>
</html>