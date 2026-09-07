<?php
// src/Views/auth/login.php
$error = $_GET['error'] ?? '';
$message = $_GET['message'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | SYNCRO LAB</title>
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body class="auth-page">
    <main class="auth-page-content">
        <a href="../../index.php" class="auth-brand">SYNCRO LAB</a>
        <section class="auth-form-panel" aria-labelledby="auth-page-title">
            <p class="auth-modal-eyebrow">SYNCRO LAB ACCOUNT</p>
            <h1 id="auth-page-title">WELCOME BACK</h1>
            <p class="auth-page-copy">Log in to book services and manage your cart.</p>

            <?php if ($error): ?>
                <div style="color: #d32f2f; background: #ffebee; padding: 12px; border-radius: 4px; margin: 16px 0;">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            <?php if ($message): ?>
                <div style="color: #2e7d32; background: #e8f5e9; padding: 12px; border-radius: 4px; margin: 16px 0;">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <form class="auth-form" method="post" action="login-handler.php">
                <label for="email">EMAIL</label>
                <input id="email" name="email" type="email" autocomplete="email" required>
                <label for="password">PASSWORD</label>
                <input id="password" name="password" type="password" autocomplete="current-password" required>
                <button class="btn btn--green btn--full" type="submit" style="margin-top: 18px;">LOG IN</button>
            </form>
            <p class="auth-switch">
                Need an account? <a href="register.php">Sign up</a>
            </p>
        </section>
    </main>
</body>
</html>