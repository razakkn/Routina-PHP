<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 400px; margin: 80px auto;">
    <div class="card text-center">
        <div class="card-kicker">Two-Factor Authentication</div>
        <div class="routina-title">Verify Your Identity</div>
        
        <div class="mfa-icon my-4">
            <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="color: var(--primary-color, #6366f1);">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                <circle cx="12" cy="16" r="1"></circle>
            </svg>
        </div>

        <p class="text-muted">Enter the 6-digit code from your authenticator app.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-4" id="mfa-form">
            <?= csrf_field() ?>
            
            <div class="mfa-code-input mb-4">
                <input type="text" name="code" id="mfa-code" class="form-control form-control-lg text-center" 
                       maxlength="6" pattern="[0-9]{6}" inputmode="numeric" autocomplete="one-time-code"
                       placeholder="000000" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary w-100" id="verify-btn">Verify</button>
        </form>

        <div class="mt-4">
            <small class="text-muted">
                <a href="/login" class="text-decoration-none">← Use a different account</a>
            </small>
        </div>

        <div class="mt-3">
            <small class="text-muted">
                Lost access to your authenticator? <a href="/reset-password">Reset password</a>
            </small>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('mfa-code');
    const form = document.getElementById('mfa-form');
    const btn = document.getElementById('verify-btn');

    // Only allow digits
    input.addEventListener('input', function(e) {
        this.value = this.value.replace(/[^0-9]/g, '');
        
        // Auto-submit when 6 digits entered
        if (this.value.length === 6) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Verifying...';
            form.submit();
        }
    });

    // Handle paste
    input.addEventListener('paste', function(e) {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text');
        const digits = paste.replace(/[^0-9]/g, '').substring(0, 6);
        this.value = digits;
        
        if (digits.length === 6) {
            setTimeout(() => form.submit(), 100);
        }
    });

    // Focus on load
    input.focus();
});
</script>

<style>
.mfa-code-input input {
    font-size: 2rem;
    letter-spacing: 0.5rem;
    font-family: monospace;
    max-width: 200px;
    margin: 0 auto;
}
.mfa-code-input input::placeholder {
    color: #dee2e6;
    letter-spacing: 0.5rem;
}
.mfa-icon {
    opacity: 0.9;
}
</style>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
