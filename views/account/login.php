<?php
// views/account/login.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Your Routina</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
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
                    <div class="hud-title">LOGIN</div>
                    <div class="hud-sub">Routina Access Gate</div>
                </div>

                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="post">
                    <?= csrf_field() ?>

                    <div class="user-box">
                        <label class="sr-only" for="login-username">Username</label>
                        <span class="input-icon" aria-hidden="true">◈</span>
                        <input id="login-username" type="text" name="routina_id" required="" autocomplete="username" placeholder="Username" aria-label="Username" value="<?php echo htmlspecialchars($routina_id ?? ''); ?>">
                    </div>
                    <div class="user-box">
                        <label class="sr-only" for="login-password">Password</label>
                        <span class="input-icon" aria-hidden="true">⬡</span>
                        <input id="login-password" type="password" name="password" required="" autocomplete="current-password" placeholder="Password" aria-label="Password">
                    </div>

                    <div class="hud-actions">
                        <a href="/register" class="hud-btn hud-btn-ghost">Register</a>
                        <button type="submit" name="login_btn" class="hud-btn">Log in</button>
                    </div>
                </form>

                <div class="hud-links">
                    <a href="/auth/google" class="google-btn">Sign in with Google</a>
                    <a href="/forgot-password" class="create-link">Forgot password?</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/login-parallax.js" defer></script>
    <!-- Minimal page: no Three.js or matrix scripts -->
</body>
</html>
