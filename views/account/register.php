<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account - Routina</title>
    <link rel="stylesheet" href="/css/login-matrix.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
</head>
<body class="galactic-login">

    <div id="stars"></div>
    <div id="stars2"></div>

    <div id="intro-text">
        <h1>Routina</h1>
        <p>[ Create Your Account ]</p>
    </div>

    <div class="login-bg" aria-hidden="true"></div>

    <div class="login-box" tabindex="0">
        <h2>Create Account</h2>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/register" id="register-form">
            <?= csrf_field() ?>

            <div class="user-box">
                <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5z" fill="currentColor" opacity="0.9" />
                        <path d="M2 22c0-3.3 2.7-6 6-6h8c3.3 0 6 2.7 6 6v0H2z" fill="currentColor" opacity="0.9" />
                    </svg>
                </span>
                <input type="text" name="routina_id" required placeholder="Routina ID" aria-label="Routina ID">
            </div>

            <div class="user-box">
                <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M17 8v-2a5 5 0 0 0-10 0v2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                        <rect x="3" y="11" width="18" height="10" rx="2" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                </span>
                <input type="email" name="email" required placeholder="Email" aria-label="Email">
            </div>

            <div class="user-box">
                <span class="input-icon" aria-hidden="true">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M12 12c2.7 0 5-2.3 5-5s-2.3-5-5-5-5 2.3-5 5 2.3 5 5 5z" fill="currentColor" opacity="0.9" />
                        <path d="M2 22c0-3.3 2.7-6 6-6h8c3.3 0 6 2.7 6 6v0H2z" fill="currentColor" opacity="0.9" />
                    </svg>
                </span>
                <input type="password" name="password" id="password" required placeholder="Password" aria-label="Password">
            </div>

            <button type="submit" name="register_btn" id="register-btn" disabled>Create Account</button>
        </form>

        <div class="secondary" style="margin-top:12px; display:flex; justify-content:space-between; align-items:center;">
            <a href="/auth/google?action=register" class="google-btn">Sign up with Google</a>
            <a href="/login" class="create-link">Already have an account?</a>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const registerBtn = document.getElementById('register-btn');

        passwordInput.addEventListener('input', () => {
            const password = passwordInput.value;
            const isValid = password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /\d/.test(password) && /[^A-Za-z0-9]/.test(password);
            registerBtn.disabled = !isValid;
        });
    </script>
</body>
</html>
