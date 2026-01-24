<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 500px; margin: 60px auto;">
    <div class="card">
        <div class="card-kicker">Security</div>
        <div class="routina-title">Setup Two-Factor Authentication</div>
        <p class="text-muted mt-2">Protect your account with an authenticator app like Google Authenticator, Authy, or 1Password.</p>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="mt-4">
            <h5>Step 1: Save this secret key</h5>
            <p class="text-muted">Copy this key into your authenticator app, or scan the QR code if available.</p>
            
            <div class="bg-light p-3 rounded text-center mb-3">
                <code class="h5 user-select-all" style="letter-spacing: 2px; word-break: break-all;">
                    <?= htmlspecialchars(chunk_split($secret, 4, ' ')) ?>
                </code>
            </div>

            <?php
                // Generate otpauth URL for QR code
                $user = \Routina\Models\User::find((int)$_SESSION['user_id']);
                $appName = 'Routina';
                $email = urlencode($user->email ?? 'user');
                $otpAuthUrl = "otpauth://totp/{$appName}:{$email}?secret={$secret}&issuer={$appName}";
            ?>
            
            <div class="text-center mb-4">
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=<?= urlencode($otpAuthUrl) ?>" 
                     alt="QR Code" class="border rounded" style="max-width: 200px;">
                <div class="text-muted small mt-2">Scan with your authenticator app</div>
            </div>

            <h5>Step 2: Verify setup</h5>
            <p class="text-muted">Enter the 6-digit code from your authenticator app to confirm setup.</p>
            
            <form method="post" class="mt-3">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <input type="text" name="code" class="form-control form-control-lg text-center" 
                           maxlength="6" pattern="[0-9]{6}" inputmode="numeric" 
                           placeholder="000000" required autofocus
                           style="font-size: 1.5rem; letter-spacing: 0.5rem; max-width: 200px; margin: 0 auto;">
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary flex-grow-1">Enable MFA</button>
                    <a href="/profile" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>

        <div class="alert alert-warning mt-4">
            <strong>Important:</strong> Save your secret key in a safe place. You'll need it if you lose access to your authenticator app.
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
