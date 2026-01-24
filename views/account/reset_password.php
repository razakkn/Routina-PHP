<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 450px; margin: 60px auto;">
    <div class="card">
        <div class="card-kicker">Reset Password</div>
        
        <?php if (isset($token)): ?>
            <!-- Password Reset Form -->
            <div class="routina-title">Set New Password</div>
            <p class="text-muted mt-2">Enter your new password below.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="mt-4" id="reset-form">
                <?= csrf_field() ?>
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="mb-3">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" id="new-password" class="form-control" required minlength="8">
                </div>

                <div class="password-strength mb-3">
                    <div class="strength-bar">
                        <div class="strength-fill" id="strength-fill"></div>
                    </div>
                    <ul class="password-requirements mt-2">
                        <li id="req-length"><span class="req-icon">○</span> At least 8 characters</li>
                        <li id="req-upper"><span class="req-icon">○</span> One uppercase letter</li>
                        <li id="req-lower"><span class="req-icon">○</span> One lowercase letter</li>
                        <li id="req-number"><span class="req-icon">○</span> One number</li>
                        <li id="req-special"><span class="req-icon">○</span> One special character (!@#$%^&*)</li>
                    </ul>
                </div>

                <div class="mb-3">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirm" id="confirm-password" class="form-control" required>
                    <div id="match-status" class="small mt-1" style="display: none;"></div>
                </div>

                <button type="submit" class="btn btn-primary w-100" id="submit-btn" disabled>Reset Password</button>
            </form>

        <?php elseif (!empty($success)): ?>
            <!-- Success Message -->
            <div class="routina-title">Check Your Email</div>
            <div class="alert alert-success mt-3">
                <i class="bi bi-envelope-check"></i>
                If an account exists with that email, you will receive a password reset link shortly.
            </div>
            <p class="text-muted">Didn't receive the email? Check your spam folder or <a href="/reset-password">try again</a>.</p>
            <a href="/login" class="btn btn-outline-primary w-100 mt-3">Back to Login</a>

        <?php else: ?>
            <!-- Request Reset Form -->
            <div class="routina-title">Forgot Password?</div>
            <p class="text-muted mt-2">Enter your email and we'll send you a reset link.</p>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="post" class="mt-4">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label">Email Address</label>
                    <input type="email" name="email" class="form-control" required placeholder="you@example.com">
                </div>

                <button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
            </form>

            <div class="text-center mt-3">
                <a href="/login">Back to Login</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reset-form');
    if (!form) return;

    const password = document.getElementById('new-password');
    const confirm = document.getElementById('confirm-password');
    const submit = document.getElementById('submit-btn');
    const strengthFill = document.getElementById('strength-fill');
    const matchStatus = document.getElementById('match-status');

    const requirements = {
        length: { el: document.getElementById('req-length'), test: p => p.length >= 8 },
        upper: { el: document.getElementById('req-upper'), test: p => /[A-Z]/.test(p) },
        lower: { el: document.getElementById('req-lower'), test: p => /[a-z]/.test(p) },
        number: { el: document.getElementById('req-number'), test: p => /[0-9]/.test(p) },
        special: { el: document.getElementById('req-special'), test: p => /[!@#$%^&*(),.?":{}|<>]/.test(p) }
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

        // Update strength bar
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

        checkMatch();
        return score === 5;
    }

    function checkMatch() {
        const pwd = password.value;
        const conf = confirm.value;
        const allReqsMet = Object.values(requirements).every(r => r.test(pwd));

        if (conf.length > 0) {
            matchStatus.style.display = 'block';
            if (pwd === conf) {
                matchStatus.className = 'small mt-1 text-success';
                matchStatus.textContent = '✓ Passwords match';
            } else {
                matchStatus.className = 'small mt-1 text-danger';
                matchStatus.textContent = '✕ Passwords do not match';
            }
        } else {
            matchStatus.style.display = 'none';
        }

        submit.disabled = !(allReqsMet && pwd === conf && pwd.length > 0);
    }

    password.addEventListener('input', checkPassword);
    confirm.addEventListener('input', checkMatch);
});
</script>

<style>
.password-requirements {
    list-style: none;
    padding: 0;
    margin: 0;
    font-size: 0.85rem;
}
.password-requirements li {
    padding: 2px 0;
    color: #6c757d;
}
.password-requirements li.met {
    color: #198754;
}
.password-requirements .req-icon {
    display: inline-block;
    width: 20px;
}
.strength-bar {
    height: 4px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
}
.strength-fill {
    height: 100%;
    transition: width 0.3s, background-color 0.3s;
}
.strength-fill.weak { background: #dc3545; }
.strength-fill.fair { background: #fd7e14; }
.strength-fill.good { background: #ffc107; }
.strength-fill.strong { background: #198754; }
</style>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
