<?php
// views/account/forgot_password.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot password - Routina</title>
    <link rel="stylesheet" href="/css/login-matrix.css" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;600;700&family=Space+Grotesk:wght@400;500;600&display=swap" rel="stylesheet">
</head>
<body class="hud-login">

    <div id="stars"></div>
    <div id="stars2"></div>

    <div class="login-stage">
        <div class="float-wrap">
            <div class="hud-orbit" aria-hidden="true">
                <div class="hud-ring ring-1"></div>
                <div class="hud-ring ring-2"></div>
                <div class="hud-ring ring-3"></div>
                <div class="hud-ticks"></div>
                <div class="hud-scan"></div>
            </div>

            <div class="login-box hud-panel" tabindex="0">
                <div class="hud-header">
                    <div class="hud-title">RECOVERY</div>
                    <div class="hud-sub">Reset your access</div>
                </div>

                <p class="text-muted">Enter your email and we’ll send a reset link.</p>

                <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="success-message">
                        <?php echo htmlspecialchars($success); ?>
                    </div>
                <?php endif; ?>

                <form method="post" action="/forgot-password" class="mt-3">
                    <?= csrf_field() ?>
                    <div class="user-box">
                        <label class="sr-only" for="forgot-email">Email</label>
                        <span class="input-icon" aria-hidden="true">⬡</span>
                        <input id="forgot-email" name="email" type="email" required autocomplete="email" placeholder="Email" />
                    </div>

                    <div class="hud-actions">
                        <a href="/login" class="hud-btn hud-btn-ghost">Back to login</a>
                        <button type="submit" class="hud-btn">Send reset link</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
