<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create Account - Routina</title>
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
                    <div class="hud-title">REGISTER</div>
                    <div class="hud-sub">Create your Routina ID</div>
                </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="/register" id="register-form">
            <?= csrf_field() ?>

            <div class="user-box">
                <label class="sr-only" for="register-routina-id">Routina ID</label>
                <span class="input-icon" aria-hidden="true">◈</span>
                <input type="text" name="routina_id" required placeholder="Routina ID" aria-label="Routina ID" value="<?= htmlspecialchars($routina_id ?? '') ?>">
            </div>

            <div class="user-box">
                <label class="sr-only" for="register-email">Email</label>
                <span class="input-icon" aria-hidden="true">⬡</span>
                <input type="email" name="email" required placeholder="Email" aria-label="Email" value="<?= htmlspecialchars($email ?? '') ?>">
            </div>

            <div class="user-box">
                <label class="sr-only" for="register-password">Password</label>
                <span class="input-icon" aria-hidden="true">◍</span>
                <input type="password" name="password" id="password" required placeholder="Password" aria-label="Password">
            </div>

            <?php if (!empty($suggestions)): ?>
                <div class="suggested-ids">
                    <div class="suggested-ids__label">Try one of these:</div>
                    <div class="suggested-ids__list">
                        <?php foreach ($suggestions as $s): ?>
                            <button type="button" class="suggestion-btn" data-id="<?= htmlspecialchars($s) ?>">
                                @<?= htmlspecialchars($s) ?>
                            </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="hud-actions">
                <a href="/login" class="hud-btn hud-btn-ghost">Back to login</a>
                <button type="submit" name="register_btn" id="register-btn" class="hud-btn">Create account</button>
            </div>
            <div id="register-hint" class="register-hint"></div>
        </form>

                <div class="hud-links">
                    <a href="/auth/google?action=register" class="google-btn">Sign up with Google</a>
                    <a href="/login" class="create-link">Already have an account?</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        const passwordInput = document.getElementById('password');
        const routinaInput = document.querySelector('input[name="routina_id"]');
        const emailInput = document.querySelector('input[name="email"]');
        const registerBtn = document.getElementById('register-btn');
        const hint = document.getElementById('register-hint');

        function isFormValid() {
            const password = passwordInput.value;
            const passOk = password.length >= 8 && /[A-Z]/.test(password) && /[a-z]/.test(password) && /\d/.test(password) && /[^A-Za-z0-9]/.test(password);
            const routinaOk = (routinaInput?.value || '').trim().length >= 3;
            const emailOk = /\S+@\S+\.\S+/.test(emailInput?.value || '');
            return passOk && routinaOk && emailOk;
        }

        function updateButton() {
            const valid = isFormValid();
            if (!valid) {
                hint.textContent = 'Password needs 8+ chars, upper/lowercase, number, and a special character.';
            } else {
                hint.textContent = '';
            }
        }

        passwordInput.addEventListener('input', updateButton);
        routinaInput?.addEventListener('input', updateButton);
        emailInput?.addEventListener('input', updateButton);

        document.querySelectorAll('.suggestion-btn').forEach((btn) => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id') || '';
                if (id) {
                    routinaInput.value = id;
                    updateButton();
                    routinaInput.focus();
                }
            });
        });
        updateButton();
    </script>
</body>
</html>
