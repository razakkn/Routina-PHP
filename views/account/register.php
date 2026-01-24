<?php
// views/account/register.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Create account - Routina</title>
    <link rel="stylesheet" href="/css/site.css" />
    <link rel="stylesheet" href="/css/auth.css" />
    <style>
        .password-strength { margin-top: 8px; }
        .strength-bar { height: 4px; background: #e9ecef; border-radius: 2px; overflow: hidden; }
        .strength-fill { height: 100%; width: 0; transition: width 0.3s, background-color 0.3s; }
        .strength-fill.weak { background: #dc3545; }
        .strength-fill.fair { background: #fd7e14; }
        .strength-fill.good { background: #ffc107; }
        .strength-fill.strong { background: #198754; }
        .password-requirements { list-style: none; padding: 0; margin: 10px 0 0; font-size: 0.8rem; }
        .password-requirements li { padding: 2px 0; color: #6c757d; }
        .password-requirements li.met { color: #198754; }
        .password-requirements .req-icon { display: inline-block; width: 18px; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }
        .divider-or { display: flex; align-items: center; margin: 20px 0; color: #6c757d; font-size: 0.85rem; }
        .divider-or::before, .divider-or::after { content: ''; flex: 1; height: 1px; background: #dee2e6; }
        .divider-or span { padding: 0 12px; }
        .btn-google { background: #fff; border: 1px solid #dadce0; color: #3c4043; display: flex; align-items: center; justify-content: center; gap: 10px; transition: background 0.2s, box-shadow 0.2s; }
        .btn-google:hover { background: #f8f9fa; box-shadow: 0 1px 3px rgba(0,0,0,0.12); }
        .btn-google svg { width: 18px; height: 18px; }
    </style>
</head>
<body class="auth-body">
    <div class="auth-wrap">
        <div class="auth-card">
            <div class="auth-brand">Routina</div>
            <div class="auth-sub">Personal Diary</div>

            <div class="auth-prompt">This is your space.</div>
            <div class="auth-context">Create an account to start your quiet timeline.</div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mt-3 mb-0" role="alert">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <!-- Google Sign Up -->
            <a href="/auth/google?action=register" class="btn btn-google btn-lg w-100 mt-4">
                <svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/></svg>
                Sign up with Google
            </a>

            <div class="divider-or"><span>or</span></div>

            <form method="post" action="/register" id="register-form">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Display name</label>
                    <input class="form-control form-control-lg auth-input" name="display_name" placeholder="Your name" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input class="form-control form-control-lg auth-input" name="email" type="email" required autocomplete="username" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone <small class="text-muted">(optional)</small></label>
                    <input class="form-control form-control-lg auth-input" name="phone" type="tel" placeholder="+1 555 123 4567" autocomplete="tel" />
                </div>

                <div class="mb-3">
                    <label class="form-label">Password</label>
                    <input class="form-control form-control-lg auth-input" name="password" id="password" type="password" required autocomplete="new-password" />
                    <div class="password-strength">
                        <div class="strength-bar"><div class="strength-fill" id="strength-fill"></div></div>
                        <ul class="password-requirements">
                            <li id="req-length"><span class="req-icon">○</span> At least 8 characters</li>
                            <li id="req-upper"><span class="req-icon">○</span> One uppercase letter</li>
                            <li id="req-lower"><span class="req-icon">○</span> One lowercase letter</li>
                            <li id="req-number"><span class="req-icon">○</span> One number</li>
                            <li id="req-special"><span class="req-icon">○</span> One special character</li>
                        </ul>
                    </div>
                </div>

                <button type="submit" id="submit-btn" class="btn btn-dark btn-lg w-100 btn-pill" disabled>
                    Create account →
                </button>

                <div class="auth-foot mt-3">
                    <span class="text-muted">Already have an account?</span>
                    <a class="auth-link ms-1" href="/login">Sign in</a>
                </div>
            </form>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const password = document.getElementById('password');
        const submit = document.getElementById('submit-btn');
        const strengthFill = document.getElementById('strength-fill');

        const requirements = {
            length: { el: document.getElementById('req-length'), test: p => p.length >= 8 },
            upper: { el: document.getElementById('req-upper'), test: p => /[A-Z]/.test(p) },
            lower: { el: document.getElementById('req-lower'), test: p => /[a-z]/.test(p) },
            number: { el: document.getElementById('req-number'), test: p => /[0-9]/.test(p) },
            special: { el: document.getElementById('req-special'), test: p => /[!@#$%^&*(),.?":{}|<>_\-+=\[\]\\\/`~]/.test(p) }
        };

        function checkPassword() {
            const val = password.value;
            let score = 0;

            for (const [key, req] of Object.entries(requirements)) {
                const met = req.test(val);
                if (met) score++;
                req.el.classList.toggle('met', met);
                req.el.querySelector('.req-icon').textContent = met ? '✓' : '○';
            }

            strengthFill.className = 'strength-fill';
            if (score === 0) {
                strengthFill.style.width = '0%';
            } else if (score <= 2) {
                strengthFill.style.width = '25%';
                strengthFill.classList.add('weak');
            } else if (score <= 3) {
                strengthFill.style.width = '50%';
                strengthFill.classList.add('fair');
            } else if (score <= 4) {
                strengthFill.style.width = '75%';
                strengthFill.classList.add('good');
            } else {
                strengthFill.style.width = '100%';
                strengthFill.classList.add('strong');
            }

            submit.disabled = score < 5;
        }

        password.addEventListener('input', checkPassword);
    });
    </script>
</body>
</html>
