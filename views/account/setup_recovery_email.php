<?php ob_start(); ?>

<div class="routina-wrap" style="max-width: 500px; margin: 60px auto;">
    <div class="card">
        <div class="card-kicker">Account Verification</div>
        <div class="routina-title">Add Recovery Email</div>
        <p class="text-muted mt-2">
            Welcome, <strong>@<?= htmlspecialchars($routina_id ?? '') ?></strong>! 
            To secure your account and enable password recovery, please add your email address.
        </p>

        <div class="alert alert-info mt-3">
            <strong>Why is this required?</strong><br>
            Your email is used only for account recovery and important notifications. 
            Without it, you won't be able to reset your password if you forget it.
        </div>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger mt-3"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" class="mt-4">
            <?= csrf_field() ?>
            <div class="mb-3">
                <label class="form-label">Recovery Email Address</label>
                <input type="email" name="email" class="form-control" required 
                       placeholder="your@email.com" autofocus>
                <small class="text-muted">We'll send a verification link to this address.</small>
            </div>

            <button type="submit" class="btn btn-primary w-100">Verify & Continue</button>
        </form>

        <div class="mt-4 text-center">
            <small class="text-muted">
                Your email is private and will never be shared or displayed publicly.
            </small>
        </div>
    </div>
</div>

<?php 
$content = ob_get_clean(); 
include __DIR__ . '/../layouts/main.php'; 
?>
