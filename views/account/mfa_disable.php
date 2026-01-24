<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 450px; margin: 60px auto;">
    <div class="card">
        <div class="card-kicker">Security</div>
        <div class="routina-title">Disable Two-Factor Authentication</div>
        <p class="text-muted mt-2">Enter your password to confirm you want to disable two-factor authentication.</p>

        <div class="alert alert-warning mt-3">
            <strong>Warning:</strong> Disabling MFA will make your account less secure. Only proceed if you understand the risks.
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-4">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Current Password</label>
                <input type="password" name="password" class="form-control" required autofocus>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-danger flex-grow-1">Disable MFA</button>
                <a href="/profile" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
