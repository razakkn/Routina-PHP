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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="galactic-login">

    <div id="stars"></div>
    <div id="stars2"></div>

    <div id="intro-text">
        <h1>Routina</h1>
        <p>[ Click to Launch ]</p>
    </div>

    <div class="login-bg" aria-hidden="true"></div>

    <div class="login-bg" aria-hidden="true"></div>

    <div class="login-stage">
        <div class="float-wrap">
            <div class="login-box" tabindex="0">
                <h2>Welcome to your Routina</h2>

                <?php if (isset($error)): ?>
                    <div class="error-message">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form action="/login" method="post">
                    <?= csrf_field() ?>

                    <div class="user-box">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5z" fill="currentColor" opacity="0.9" />
                                <path d="M2 22c0-3.3 2.7-6 6-6h8c3.3 0 6 2.7 6 6v0H2z" fill="currentColor" opacity="0.9" />
                            </svg>
                        </span>
                        <input type="text" name="routina_id" required="" autocomplete="username" placeholder="Username" aria-label="Username" value="<?php echo htmlspecialchars($routina_id ?? ''); ?>">
                    </div>
                    <div class="user-box">
                        <span class="input-icon" aria-hidden="true">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                                <path d="M17 8v-2a5 5 0 0 0-10 0v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                                <rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <input type="password" name="password" required="" autocomplete="current-password" placeholder="Password" aria-label="Password">
                    </div>

                    <button type="submit" name="login_btn">Login</button>
                </form>

                <div class="secondary" style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
                    <a href="/auth/google" class="google-btn">Sign in with Google</a>
                    <a href="/register" class="create-link">Create account</a>
                </div>
            </div>
        </div>
    </div>

    <script src="/js/login-parallax.js" defer></script>
    <!-- Minimal page: no Three.js or matrix scripts -->
</body>
</html>
