<?php
// src/Views/auth/register.php
$error = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | SYNCRO LAB</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-page-content">
        <a href="../../index.php" class="auth-brand">SYNCRO LAB</a>
        <section class="auth-form-panel" aria-labelledby="auth-page-title">
            <p class="auth-modal-eyebrow">SYNCRO LAB ACCOUNT</p>
            <h1 id="auth-page-title">CREATE YOUR ACCOUNT</h1>
            <p class="auth-page-copy">Create an account to book services and manage your cart.</p>

            <?php if ($error): ?>
                <div style="color: #d32f2f; background: #ffebee; padding: 12px; border-radius: 4px; margin: 16px 0;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="register-handler.php">
                <label for="full_name">FULL NAME</label>
                <input id="full_name" name="full_name" type="text" autocomplete="name" required>
                <label for="email">EMAIL</label>
                <input id="email" name="email" type="email" autocomplete="email" required>
                <label for="phone">PHONE NUMBER</label>
                <input id="phone" name="phone" type="tel" autocomplete="tel">
                <label for="password">PASSWORD (min 8 chars)</label>
                <input id="password" name="password" type="password" autocomplete="new-password" required>
                <label for="confirm_password">CONFIRM PASSWORD</label>
                <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" required>
                <button class="btn btn--green btn--full" type="submit" style="margin-top: 18px;">SIGN UP</button>
            </form>
            <p class="auth-switch">
                Already have an account? <a href="login.php">Log in</a>
            </p>
        </section>
    </main>
</body>
</html>